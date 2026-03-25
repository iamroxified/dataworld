<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Mpdf\Mpdf;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException as OpenAIErrorException;
use OpenAI\Exceptions\RateLimitException as OpenAIRateLimitException;
use OpenAI\Exceptions\TransporterException as OpenAITransporterException;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Smalot\PdfParser\Parser as PdfParser;

const SYI_AI_SYSTEM_PROMPT = <<<'PROMPT'
You are a Nigerian academic data analyst.
Use Chapter 3 methodology to analyze the dataset.

Generate:
- Chapter 4: Data Presentation & Analysis
- Chapter 5: Summary, Conclusion & Recommendations
- Abstract

Rules:
1. Every table must have: Title, Frequency & Percentage, Source: Fieldwork, 2025, Detailed interpretation in paragraph form.
2. Where applicable, generate graphs and output the exact data points in JSON format like {"chart_type":"bar","data":{"labels":[...],"values":[...]}} so PHP can create real charts.
3. Use formal academic tone (Nigerian universities standard).
4. Detect and test hypotheses automatically using the stated method.
5. Total length: [pages from admin setting] pages.

Output in clean Markdown with clear headings, tables, and JSON graph blocks.
PROMPT;

const SYI_TOPIC_ONLY_PROMPT = <<<'PROMPT'
You are a Nigerian academic research consultant.

Create a professional placeholder outline for Chapter 1, Chapter 2, and Chapter 3 for the project topic below.

Rules:
1. Use formal academic tone used in Nigerian tertiary institutions.
2. Include a realistic methodology section in Chapter 3 with research design, population, sample size, sampling technique, instrument, validity, reliability, and method of data analysis.
3. Keep the structure practical for a data analysis project.
4. Return only clean Markdown with headings.
PROMPT;

const SYI_PRIVATE_HTACCESS = <<<'HTACCESS'
Options -Indexes
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTACCESS;

function syiAiLoadDependencies(string $projectRoot): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $autoloadPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    if (!file_exists($autoloadPath)) {
        throw new RuntimeException(
            'Composer dependencies are missing. Run composer require openai-php/client smalot/pdfparser phpoffice/phpword phpoffice/phpspreadsheet mpdf/mpdf vlucas/phpdotenv phpmailer/phpmailer first.'
        );
    }

    require_once $autoloadPath;

    $dotenvPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
    if (file_exists($dotenvPath) && class_exists(Dotenv::class)) {
        Dotenv::createImmutable($projectRoot)->safeLoad();
    }

    $loaded = true;
}

function syiAiEnv(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function syiAiSetFlash(string $key, string $message): void
{
    $_SESSION['syi_ai_flash'][$key] = $message;
}

function syiAiGetFlash(string $key): ?string
{
    $message = $_SESSION['syi_ai_flash'][$key] ?? null;
    unset($_SESSION['syi_ai_flash'][$key]);
    return $message;
}

function syiAiCsrfToken(): string
{
    if (empty($_SESSION['syi_ai_csrf'])) {
        $_SESSION['syi_ai_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['syi_ai_csrf'];
}

function syiAiValidateCsrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['syi_ai_csrf']) && hash_equals($_SESSION['syi_ai_csrf'], $token);
}

function syiAiSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'student-job';
}

function syiAiUuid(): string
{
    return bin2hex(random_bytes(16));
}

function syiAiEnsureStorage(string $projectRoot): array
{
    $baseDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai_jobs';
    $paths = [
        'base' => $baseDir,
        'source' => $baseDir . DIRECTORY_SEPARATOR . 'source',
        'generated' => $baseDir . DIRECTORY_SEPARATOR . 'generated',
        'charts' => $baseDir . DIRECTORY_SEPARATOR . 'charts',
    ];

    foreach ($paths as $path) {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create storage directory: ' . $path);
        }
        syiAiProtectDirectory($path);
    }

    return $paths;
}

function syiAiProtectDirectory(string $directory): void
{
    $htaccess = $directory . DIRECTORY_SEPARATOR . '.htaccess';

    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, SYI_PRIVATE_HTACCESS);
    }
}

function syiAiRelativePath(string $projectRoot, string $absolutePath): string
{
    $normalizedRoot = str_replace('\\', '/', realpath($projectRoot) ?: $projectRoot);
    $normalizedPath = str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath);

    if (str_starts_with($normalizedPath, $normalizedRoot)) {
        return ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
    }

    return $normalizedPath;
}

function syiAiAbsolutePath(string $projectRoot, ?string $relativePath): ?string
{
    $relativePath = trim((string) $relativePath);
    if ($relativePath === '') {
        return null;
    }

    $cleanRelative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
    return $projectRoot . DIRECTORY_SEPARATOR . $cleanRelative;
}

function syiAiEnsureZipExtension(string $context): void
{
    if (class_exists('ZipArchive')) {
        return;
    }

    throw new RuntimeException(
        'The PHP zip extension is required for ' . $context . '. Enable extension=zip in the PHP configuration used by your web server, then restart the server. If you need an immediate workaround, switch the output format to PDF and use CSV instead of XLSX/DOCX uploads.'
    );
}

function syiAiIsDatasetFile(?string $path): bool
{
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
    return in_array($extension, ['csv', 'xls', 'xlsx'], true);
}

function syiAiStoreUpload(
    array $file,
    string $destinationDir,
    array $allowedExtensions,
    int $maxBytes,
    string $prefix
): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . ($file['name'] ?? 'file') . '.');
    }

    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Uploaded file exceeds the allowed size limit.');
    }

    $originalName = (string) ($file['name'] ?? 'upload');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported file format: ' . $extension);
    }

    $safeName = syiAiSlug(pathinfo($originalName, PATHINFO_FILENAME));
    $storedName = $prefix . '-' . $safeName . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = $destinationDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to move uploaded file.');
    }

    return [
        'original_name' => $originalName,
        'stored_name' => $storedName,
        'absolute_path' => $destination,
        'extension' => $extension,
        'size' => (int) $file['size'],
    ];
}

function syiAiNormalizeText(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

function syiAiExtractDocumentText(string $filePath): string
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return match ($extension) {
        'pdf' => syiAiExtractPdfText($filePath),
        'docx' => syiAiExtractDocxText($filePath),
        'doc' => syiAiExtractLegacyDocText($filePath),
        'txt' => syiAiNormalizeText((string) file_get_contents($filePath)),
        default => throw new RuntimeException('Unsupported document format for text extraction.'),
    };
}

function syiAiExtractPdfText(string $filePath): string
{
    $parser = new PdfParser();
    $pdf = $parser->parseFile($filePath);
    return syiAiNormalizeText($pdf->getText());
}

function syiAiExtractDocxText(string $filePath): string
{
    syiAiEnsureZipExtension('reading DOCX files');

    $zip = new ZipArchive();

    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Unable to read DOCX file.');
    }

    $documentXml = $zip->getFromName('word/document.xml') ?: '';
    $headerXml = $zip->getFromName('word/header1.xml') ?: '';
    $footerXml = $zip->getFromName('word/footer1.xml') ?: '';
    $zip->close();

    $xml = $headerXml . "\n" . $documentXml . "\n" . $footerXml;
    $xml = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml);
    $xml = strip_tags($xml);

    return syiAiNormalizeText($xml);
}

function syiAiExtractLegacyDocText(string $filePath): string
{
    $contents = (string) file_get_contents($filePath);
    $text = preg_replace('/[^[:print:]\s]/u', ' ', $contents) ?? $contents;
    return syiAiNormalizeText($text);
}

function syiAiExtractMethodology(string $chaptersText): string
{
    $lower = strtolower($chaptersText);
    $startPatterns = ['chapter three', 'chapter 3', 'methodology', 'research methodology'];
    $endPatterns = ['chapter four', 'chapter 4', 'results', 'data presentation'];

    $start = null;
    foreach ($startPatterns as $pattern) {
        $position = strpos($lower, $pattern);
        if ($position !== false) {
            $start = $position;
            break;
        }
    }

    if ($start === null) {
        $length = max(0, mb_strlen($chaptersText, 'UTF-8') - 8000);
        return trim(mb_substr($chaptersText, $length, 8000, 'UTF-8'));
    }

    $end = null;
    foreach ($endPatterns as $pattern) {
        $position = strpos($lower, $pattern, $start + 50);
        if ($position !== false) {
            $end = $position;
            break;
        }
    }

    $methodology = $end !== null
        ? mb_substr($chaptersText, $start, $end - $start, 'UTF-8')
        : mb_substr($chaptersText, $start, 12000, 'UTF-8');

    return trim($methodology);
}

function syiAiSummarizeDataset(string $filePath): array
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        return syiAiSummarizeCsv($filePath);
    }

    if (in_array($extension, ['xlsx', 'xls'], true)) {
        return syiAiSummarizeSpreadsheet($filePath);
    }

    throw new RuntimeException('Unsupported dataset format.');
}

function syiAiSummarizeCsv(string $filePath): array
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open CSV dataset.');
    }

    $headers = [];
    $rows = [];
    $totalRows = 0;
    $sampleLimit = 1000;

    while (($data = fgetcsv($handle)) !== false) {
        if ($headers === []) {
            $headers = syiAiNormalizeHeaders($data);
            continue;
        }

        if (count(array_filter($data, static fn($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }

        $totalRows++;

        if (count($rows) < $sampleLimit) {
            $rows[] = syiAiCombineRow($headers, $data);
        }
    }

    fclose($handle);

    return syiAiBuildDatasetProfile($headers, $rows, $totalRows, ['sheet_names' => ['CSV Sheet']]);
}

function syiAiSummarizeSpreadsheet(string $filePath): array
{
    syiAiEnsureZipExtension('reading XLSX/XLS datasets');

    $spreadsheet = SpreadsheetIOFactory::load($filePath);
    $sheet = $spreadsheet->getSheet(0);
    $sheetNames = $spreadsheet->getSheetNames();
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = $sheet->getHighestDataColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    $rawHeaders = [];
    for ($column = 1; $column <= $highestColumnIndex; $column++) {
        $rawHeaders[] = (string) $sheet->getCellByColumnAndRow($column, 1)->getFormattedValue();
    }

    $headers = syiAiNormalizeHeaders($rawHeaders);
    $rows = [];
    $totalRows = 0;
    $sampleLimit = 1000;

    for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
        $rowData = [];
        $hasValue = false;

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $value = $sheet->getCellByColumnAndRow($column, $rowIndex)->getFormattedValue();
            if (trim((string) $value) !== '') {
                $hasValue = true;
            }
            $rowData[] = $value;
        }

        if (!$hasValue) {
            continue;
        }

        $totalRows++;
        if (count($rows) < $sampleLimit) {
            $rows[] = syiAiCombineRow($headers, $rowData);
        }
    }

    return syiAiBuildDatasetProfile($headers, $rows, $totalRows, ['sheet_names' => $sheetNames]);
}

function syiAiNormalizeHeaders(array $headers): array
{
    $normalized = [];

    foreach ($headers as $index => $header) {
        $value = trim((string) $header);
        $normalized[] = $value !== '' ? $value : 'Column ' . ($index + 1);
    }

    return $normalized;
}

function syiAiCombineRow(array $headers, array $row): array
{
    $combined = [];
    foreach ($headers as $index => $header) {
        $combined[$header] = trim((string) ($row[$index] ?? ''));
    }
    return $combined;
}

function syiAiBuildDatasetProfile(array $headers, array $rows, int $totalRows, array $meta = []): array
{
    $columns = [];
    $numericColumns = [];
    $categoricalColumns = [];

    foreach ($headers as $header) {
        $values = array_column($rows, $header);
        $nonEmptyValues = array_values(array_filter($values, static fn($value) => trim((string) $value) !== ''));
        $missing = count($values) - count($nonEmptyValues);
        $numericValues = [];

        foreach ($nonEmptyValues as $value) {
            $numeric = syiAiParseNumeric($value);
            if ($numeric !== null) {
                $numericValues[] = $numeric;
            }
        }

        $isNumeric = count($nonEmptyValues) > 0 && (count($numericValues) / count($nonEmptyValues)) >= 0.8;

        if ($isNumeric) {
            $summary = [
                'type' => 'numeric',
                'non_empty' => count($nonEmptyValues),
                'missing' => $missing,
                'mean' => syiAiRound(syiAiMean($numericValues)),
                'median' => syiAiRound(syiAiMedian($numericValues)),
                'std_dev' => syiAiRound(syiAiStdDev($numericValues)),
                'min' => syiAiRound(min($numericValues)),
                'max' => syiAiRound(max($numericValues)),
            ];
            $numericColumns[] = $header;
        } else {
            $frequencies = [];
            foreach ($nonEmptyValues as $value) {
                $key = $value;
                $frequencies[$key] = ($frequencies[$key] ?? 0) + 1;
            }
            arsort($frequencies);

            $topValues = [];
            foreach (array_slice($frequencies, 0, 10, true) as $label => $count) {
                $topValues[] = [
                    'label' => $label,
                    'count' => $count,
                    'percentage' => count($nonEmptyValues) > 0 ? syiAiRound(($count / count($nonEmptyValues)) * 100) : 0,
                ];
            }

            $summary = [
                'type' => 'categorical',
                'non_empty' => count($nonEmptyValues),
                'missing' => $missing,
                'unique_values' => count($frequencies),
                'top_values' => $topValues,
            ];
            $categoricalColumns[] = $header;
        }

        $columns[$header] = $summary;
    }

    $profile = [
        'row_count' => $totalRows,
        'sampled_rows' => count($rows),
        'headers' => $headers,
        'sheet_names' => $meta['sheet_names'] ?? [],
        'sample_rows' => array_slice($rows, 0, 5),
        'columns' => $columns,
        'numeric_columns' => $numericColumns,
        'categorical_columns' => $categoricalColumns,
    ];

    $profile['hypothesis_preview'] = syiAiBuildHypothesisPreview($rows, $profile);

    return $profile;
}

function syiAiParseNumeric(mixed $value): ?float
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = str_replace([',', '%'], ['', ''], $value);

    if (!is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

function syiAiMean(array $values): float
{
    return $values === [] ? 0.0 : array_sum($values) / count($values);
}

function syiAiMedian(array $values): float
{
    if ($values === []) {
        return 0.0;
    }

    sort($values);
    $count = count($values);
    $middle = intdiv($count, 2);

    if ($count % 2 === 0) {
        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    return $values[$middle];
}

function syiAiStdDev(array $values): float
{
    if (count($values) < 2) {
        return 0.0;
    }

    $mean = syiAiMean($values);
    $sum = 0.0;

    foreach ($values as $value) {
        $sum += ($value - $mean) ** 2;
    }

    return sqrt($sum / (count($values) - 1));
}

function syiAiRound(float $value): float
{
    return round($value, 3);
}

function syiAiBuildHypothesisPreview(array $rows, array $profile): array
{
    $previews = [];

    if (count($profile['categorical_columns']) >= 2) {
        $first = $profile['categorical_columns'][0];
        $second = $profile['categorical_columns'][1];
        $previews[] = syiAiChiSquarePreview($rows, $first, $second);
    }

    if (count($profile['numeric_columns']) >= 2) {
        $first = $profile['numeric_columns'][0];
        $second = $profile['numeric_columns'][1];
        $previews[] = syiAiCorrelationPreview($rows, $first, $second);
        $previews[] = syiAiRegressionPreview($rows, $first, $second);
    }

    return array_values(array_filter($previews));
}

function syiAiChiSquarePreview(array $rows, string $columnA, string $columnB): ?array
{
    $table = [];
    $labelsA = [];
    $labelsB = [];

    foreach ($rows as $row) {
        $a = trim((string) ($row[$columnA] ?? ''));
        $b = trim((string) ($row[$columnB] ?? ''));

        if ($a === '' || $b === '') {
            continue;
        }

        $labelsA[$a] = true;
        $labelsB[$b] = true;
        $table[$a][$b] = ($table[$a][$b] ?? 0) + 1;
    }

    $rowLabels = array_keys($labelsA);
    $columnLabels = array_keys($labelsB);

    if (count($rowLabels) < 2 || count($columnLabels) < 2) {
        return null;
    }

    $grandTotal = 0;
    $rowTotals = [];
    $columnTotals = [];

    foreach ($rowLabels as $rowLabel) {
        foreach ($columnLabels as $columnLabel) {
            $value = $table[$rowLabel][$columnLabel] ?? 0;
            $grandTotal += $value;
            $rowTotals[$rowLabel] = ($rowTotals[$rowLabel] ?? 0) + $value;
            $columnTotals[$columnLabel] = ($columnTotals[$columnLabel] ?? 0) + $value;
        }
    }

    if ($grandTotal === 0) {
        return null;
    }

    $chiSquare = 0.0;
    foreach ($rowLabels as $rowLabel) {
        foreach ($columnLabels as $columnLabel) {
            $observed = $table[$rowLabel][$columnLabel] ?? 0;
            $expected = ($rowTotals[$rowLabel] * $columnTotals[$columnLabel]) / $grandTotal;
            if ($expected > 0) {
                $chiSquare += (($observed - $expected) ** 2) / $expected;
            }
        }
    }

    $df = (count($rowLabels) - 1) * (count($columnLabels) - 1);
    $critical = syiAiChiSquareCritical($df);

    return [
        'method' => 'chi-square',
        'variables' => [$columnA, $columnB],
        'chi_square' => syiAiRound($chiSquare),
        'degrees_of_freedom' => $df,
        'critical_value_0_05' => $critical,
        'decision' => $critical !== null && $chiSquare > $critical
            ? 'Reject the null hypothesis at 0.05 level.'
            : 'Fail to reject the null hypothesis at 0.05 level.',
    ];
}

function syiAiChiSquareCritical(int $df): ?float
{
    $table = [
        1 => 3.841,
        2 => 5.991,
        3 => 7.815,
        4 => 9.488,
        5 => 11.070,
        6 => 12.592,
        7 => 14.067,
        8 => 15.507,
        9 => 16.919,
        10 => 18.307,
    ];

    return $table[$df] ?? null;
}

function syiAiCorrelationPreview(array $rows, string $columnA, string $columnB): ?array
{
    $x = [];
    $y = [];

    foreach ($rows as $row) {
        $xValue = syiAiParseNumeric($row[$columnA] ?? null);
        $yValue = syiAiParseNumeric($row[$columnB] ?? null);

        if ($xValue === null || $yValue === null) {
            continue;
        }

        $x[] = $xValue;
        $y[] = $yValue;
    }

    if (count($x) < 3) {
        return null;
    }

    $meanX = syiAiMean($x);
    $meanY = syiAiMean($y);
    $numerator = 0.0;
    $sumX = 0.0;
    $sumY = 0.0;

    foreach ($x as $index => $value) {
        $dx = $value - $meanX;
        $dy = $y[$index] - $meanY;
        $numerator += $dx * $dy;
        $sumX += $dx ** 2;
        $sumY += $dy ** 2;
    }

    if ($sumX <= 0 || $sumY <= 0) {
        return null;
    }

    $r = $numerator / sqrt($sumX * $sumY);
    $n = count($x);
    $tStatistic = abs($r) < 1
        ? ($r * sqrt($n - 2)) / sqrt(1 - ($r ** 2))
        : INF;

    return [
        'method' => 'correlation',
        'variables' => [$columnA, $columnB],
        'pearson_r' => syiAiRound($r),
        'sample_size' => $n,
        't_statistic' => syiAiRound((float) $tStatistic),
        'decision' => abs($tStatistic) >= 2
            ? 'Reject the null hypothesis at approximately 0.05 level.'
            : 'Fail to reject the null hypothesis at approximately 0.05 level.',
    ];
}

function syiAiRegressionPreview(array $rows, string $dependent, string $independent): ?array
{
    $x = [];
    $y = [];

    foreach ($rows as $row) {
        $xValue = syiAiParseNumeric($row[$independent] ?? null);
        $yValue = syiAiParseNumeric($row[$dependent] ?? null);

        if ($xValue === null || $yValue === null) {
            continue;
        }

        $x[] = $xValue;
        $y[] = $yValue;
    }

    if (count($x) < 3) {
        return null;
    }

    $meanX = syiAiMean($x);
    $meanY = syiAiMean($y);
    $numerator = 0.0;
    $denominator = 0.0;

    foreach ($x as $index => $xValue) {
        $numerator += ($xValue - $meanX) * ($y[$index] - $meanY);
        $denominator += ($xValue - $meanX) ** 2;
    }

    if ($denominator === 0.0) {
        return null;
    }

    $slope = $numerator / $denominator;
    $intercept = $meanY - ($slope * $meanX);
    $predicted = [];
    foreach ($x as $xValue) {
        $predicted[] = $intercept + ($slope * $xValue);
    }

    $ssRes = 0.0;
    $ssTot = 0.0;
    foreach ($y as $index => $actual) {
        $ssRes += ($actual - $predicted[$index]) ** 2;
        $ssTot += ($actual - $meanY) ** 2;
    }

    $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0.0;

    return [
        'method' => 'regression',
        'dependent_variable' => $dependent,
        'independent_variable' => $independent,
        'intercept' => syiAiRound($intercept),
        'slope' => syiAiRound($slope),
        'r_squared' => syiAiRound($rSquared),
    ];
}

function syiAiDetectMethod(string $methodology, array $datasetSummary): string
{
    $methodology = strtolower($methodology);

    $map = [
        'chi-square' => 'chi-square',
        'chisquare' => 'chi-square',
        'pearson' => 'correlation',
        'correlation' => 'correlation',
        'regression' => 'regression',
        'anova' => 'anova',
        't-test' => 't-test',
        't test' => 't-test',
    ];

    foreach ($map as $needle => $method) {
        if (str_contains($methodology, $needle)) {
            return $method;
        }
    }

    if (count($datasetSummary['categorical_columns'] ?? []) >= 2) {
        return 'chi-square';
    }

    if (count($datasetSummary['numeric_columns'] ?? []) >= 2) {
        return 'correlation';
    }

    return 'descriptive';
}

function syiAiCreateClient(): Client
{
    $apiKey = syiAiEnv('OPENAI_API_KEY');
    if (!$apiKey) {
        throw new RuntimeException('OPENAI_API_KEY is missing from .env');
    }

    return OpenAI::client($apiKey);
}

function syiAiModelCandidates(): array
{
    $primaryModel = trim((string) syiAiEnv('OPENAI_MODEL', 'gpt-4o'));
    $fallbackModel = trim((string) syiAiEnv('OPENAI_FALLBACK_MODEL', ''));

    $models = [$primaryModel !== '' ? $primaryModel : 'gpt-4o'];

    if ($fallbackModel !== '') {
        $models[] = $fallbackModel;
    } elseif ($primaryModel === 'gpt-4o') {
        $models[] = 'gpt-4o-mini';
    }

    return array_values(array_unique(array_filter($models)));
}

function syiAiRetryAttempts(): int
{
    return max(1, (int) syiAiEnv('OPENAI_MAX_RETRIES', '3'));
}

function syiAiOpenAiChatRequest(Client $client, array $messages, float $temperature = 0.2): array
{
    $models = syiAiModelCandidates();
    $maxAttempts = syiAiRetryAttempts();
    $lastError = null;

    foreach ($models as $modelIndex => $model) {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->chat()->create([
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                ]);

                $content = trim((string) ($response->choices[0]->message->content ?? ''));
                if ($content === '') {
                    throw new RuntimeException('OpenAI returned an empty response.');
                }

                return [
                    'model' => $model,
                    'content' => $content,
                ];
            } catch (Throwable $throwable) {
                if (!syiAiIsRetriableOpenAiError($throwable)) {
                    throw $throwable;
                }

                $lastError = $throwable;

                $hasAnotherAttempt = $attempt < $maxAttempts;
                $hasFallbackModel = $modelIndex < count($models) - 1;

                if ($hasAnotherAttempt) {
                    syiAiSleepForRetry($throwable, $attempt);
                    continue;
                }

                if ($hasFallbackModel) {
                    break;
                }
            }
        }
    }

    throw syiAiNormalizeOpenAiException($lastError);
}

function syiAiIsRetriableOpenAiError(Throwable $throwable): bool
{
    if ($throwable instanceof OpenAIRateLimitException || $throwable instanceof OpenAITransporterException) {
        return true;
    }

    if ($throwable instanceof OpenAIErrorException) {
        return $throwable->getStatusCode() === 429 || $throwable->getStatusCode() >= 500;
    }

    return false;
}

function syiAiNormalizeOpenAiException(?Throwable $throwable): RuntimeException
{
    if ($throwable instanceof OpenAIRateLimitException) {
        $models = implode(', ', syiAiModelCandidates());
        return new RuntimeException(
            'OpenAI rate limit reached for the current generation request. The system retried automatically, but your account/project is still being throttled. Wait about 1-2 minutes and try again, or switch to a lighter model such as gpt-4o-mini. Models checked: ' . $models . '.'
        );
    }

    if ($throwable instanceof OpenAIErrorException && $throwable->getStatusCode() === 429) {
        return new RuntimeException(
            'OpenAI returned HTTP 429 during generation. This usually means your project quota or token-per-minute limit has been exceeded. Try again shortly or use a lower-throughput model setting such as gpt-4o-mini.'
        );
    }

    if ($throwable !== null) {
        return new RuntimeException($throwable->getMessage(), 0, $throwable);
    }

    return new RuntimeException('The OpenAI request failed after multiple retry attempts.');
}

function syiAiSleepForRetry(Throwable $throwable, int $attempt): void
{
    $delaySeconds = syiAiRetryDelaySeconds($throwable, $attempt);
    usleep((int) round($delaySeconds * 1000000));
}

function syiAiRetryDelaySeconds(Throwable $throwable, int $attempt): float
{
    $response = null;

    if ($throwable instanceof OpenAIRateLimitException) {
        $response = $throwable->response;
    } elseif ($throwable instanceof OpenAIErrorException) {
        $response = $throwable->response;
    }

    if ($response !== null) {
        $retryAfter = syiAiParseRetryHeader($response->getHeaderLine('retry-after'));
        if ($retryAfter !== null) {
            return min(60.0, max(1.0, $retryAfter));
        }

        $requestReset = syiAiParseRetryHeader($response->getHeaderLine('x-ratelimit-reset-requests'));
        if ($requestReset !== null) {
            return min(60.0, max(1.0, $requestReset));
        }
    }

    return min(30.0, (float) (2 ** max(0, $attempt - 1)) + (random_int(100, 800) / 1000));
}

function syiAiParseRetryHeader(?string $value): ?float
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        return (float) $value;
    }

    preg_match_all('/(\d+(?:\.\d+)?)(ms|s|m|h)?/i', $value, $matches, PREG_SET_ORDER);
    if ($matches === []) {
        return null;
    }

    $seconds = 0.0;
    foreach ($matches as $match) {
        $amount = (float) $match[1];
        $unit = strtolower($match[2] ?? 's');

        $seconds += match ($unit) {
            'ms' => $amount / 1000,
            'm' => $amount * 60,
            'h' => $amount * 3600,
            default => $amount,
        };
    }

    return $seconds > 0 ? $seconds : null;
}

function syiAiGenerateTopicOutline(Client $client, array $job): string
{
    $messages = [
        ['role' => 'system', 'content' => SYI_TOPIC_ONLY_PROMPT],
        ['role' => 'user', 'content' => implode("\n\n", [
            'Project topic: ' . ($job['project_topic'] ?? ''),
            'Degree level: ' . ($job['degree_level'] ?? 'BSc/HND'),
            'Target pages for the final work: ' . ($job['target_pages'] ?? 50),
        ])],
    ];

    $response = syiAiOpenAiChatRequest($client, $messages, 0.2);

    return $response['content'];
}

function syiAiBuildSystemPrompt(int $pages): string
{
    return str_replace('[pages from admin setting]', (string) $pages, SYI_AI_SYSTEM_PROMPT);
}

function syiAiTruncate(string $text, int $maxLength): string
{
    if (mb_strlen($text, 'UTF-8') <= $maxLength) {
        return $text;
    }

    return mb_substr($text, 0, $maxLength, 'UTF-8') . '...';
}

function syiAiCompactDatasetSummary(array $datasetSummary): array
{
    if ($datasetSummary === []) {
        return [];
    }

    $compact = [
        'row_count' => $datasetSummary['row_count'] ?? null,
        'sheet_names' => array_slice($datasetSummary['sheet_names'] ?? [], 0, 5),
        'headers' => array_slice($datasetSummary['headers'] ?? [], 0, 20),
        'numeric_columns' => array_slice($datasetSummary['numeric_columns'] ?? [], 0, 8),
        'categorical_columns' => array_slice($datasetSummary['categorical_columns'] ?? [], 0, 8),
        'hypothesis_preview' => array_slice($datasetSummary['hypothesis_preview'] ?? [], 0, 3),
        'sample_rows' => [],
        'column_profiles' => [],
    ];

    foreach (array_slice($datasetSummary['sample_rows'] ?? [], 0, 3) as $row) {
        $trimmedRow = [];
        foreach (array_slice($row, 0, 8, true) as $column => $value) {
            $trimmedRow[$column] = syiAiTruncate((string) $value, 60);
        }
        $compact['sample_rows'][] = $trimmedRow;
    }

    foreach (array_slice($datasetSummary['columns'] ?? [], 0, 12, true) as $column => $profile) {
        $profileSummary = [
            'type' => $profile['type'] ?? 'unknown',
            'non_empty' => $profile['non_empty'] ?? null,
            'missing' => $profile['missing'] ?? null,
        ];

        if (($profile['type'] ?? '') === 'numeric') {
            $profileSummary['mean'] = $profile['mean'] ?? null;
            $profileSummary['median'] = $profile['median'] ?? null;
            $profileSummary['std_dev'] = $profile['std_dev'] ?? null;
            $profileSummary['min'] = $profile['min'] ?? null;
            $profileSummary['max'] = $profile['max'] ?? null;
        } else {
            $profileSummary['unique_values'] = $profile['unique_values'] ?? null;
            $profileSummary['top_values'] = array_slice($profile['top_values'] ?? [], 0, 5);
        }

        $compact['column_profiles'][$column] = $profileSummary;
    }

    return $compact;
}

function syiAiBuildGenerationUserPrompt(array $job): string
{
    $datasetSummary = json_decode((string) ($job['dataset_summary_json'] ?? ''), true) ?: [];
    $compactDatasetSummary = syiAiCompactDatasetSummary($datasetSummary);
    $analysisPackage = [
        'project_topic' => $job['project_topic'] ?? '',
        'degree_level' => $job['degree_level'] ?? 'BSc/HND',
        'graphs_required' => !empty($job['include_graphs']) ? 'Yes' : 'No',
        'hypothesis_setting' => $job['hypothesis_mode'] ?? 'auto-detect',
        'output_format' => strtoupper((string) ($job['output_format'] ?? 'word')),
        'submission_mode' => $job['submission_mode'] ?? 'full_upload',
        'detected_method' => syiAiDetectMethod((string) ($job['methodology_text'] ?? ''), $datasetSummary),
        'methodology_excerpt' => syiAiTruncate((string) ($job['methodology_text'] ?? ''), 3500),
        'chapters_1_to_3_excerpt' => syiAiTruncate((string) ($job['chapters_text'] ?? ''), 5000),
        'topic_outline' => syiAiTruncate((string) ($job['chapter_outline_markdown'] ?? ''), 2500),
        'dataset_summary' => $compactDatasetSummary,
        'admin_notes' => (string) ($job['admin_notes'] ?? ''),
        'workflow_order' => 'Tables -> Graphs -> Interpretation -> Source',
    ];

    if (($job['submission_mode'] ?? '') === 'topic_only' && $datasetSummary === []) {
        $analysisPackage['topic_only_instruction'] = 'No dataset was uploaded. Build a professional placeholder analysis structure that aligns with the generated Chapter 1-3 outline and a realistic 100 respondent study design.';
    }

    if ($compactDatasetSummary === []) {
        $analysisPackage['dataset_unavailable_instruction'] = 'No machine-readable dataset summary was available. Use the methodology and project variables to create academically plausible placeholder tables, hypothesis tests, graphs, and interpretations in Nigerian university style without mentioning AI.';
    }

    return implode("\n\n", [
        'Use the following project pack to produce the final answer.',
        json_encode($analysisPackage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'Graph rule: when graphs are required, output fenced json blocks with chart_type, labels and values exactly as instructed.',
        'Document order rule: keep the result in this order whenever relevant: tables, then graphs, then interpretation, then source note.',
    ]);
}

function syiAiGenerateAcademicMarkdown(Client $client, array $job): array
{
    $systemPrompt = syiAiBuildSystemPrompt((int) ($job['target_pages'] ?? 50));
    $userPrompt = syiAiBuildGenerationUserPrompt($job);

    $response = syiAiOpenAiChatRequest($client, [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt],
    ], 0.2);

    return [
        'system_prompt' => $systemPrompt,
        'user_prompt' => $userPrompt,
        'model' => $response['model'],
        'markdown' => $response['content'],
    ];
}

function syiAiMarkdownBlocks(string $markdown): array
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown) ?: [];
    $blocks = [];
    $count = count($lines);
    $index = 0;

    while ($index < $count) {
        $line = trim($lines[$index]);

        if ($line === '') {
            $index++;
            continue;
        }

        if (preg_match('/^```([a-z0-9_-]+)?$/i', $line, $matches)) {
            $language = strtolower($matches[1] ?? '');
            $index++;
            $buffer = [];

            while ($index < $count && trim($lines[$index]) !== '```') {
                $buffer[] = $lines[$index];
                $index++;
            }

            $blocks[] = [
                'type' => 'code',
                'language' => $language,
                'content' => trim(implode("\n", $buffer)),
            ];

            $index++;
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
            $blocks[] = [
                'type' => 'heading',
                'level' => strlen($matches[1]),
                'text' => trim($matches[2]),
            ];
            $index++;
            continue;
        }

        if (
            str_contains($line, '|')
            && isset($lines[$index + 1])
            && preg_match('/^\s*\|?[:\- ]+\|[:\-| ]+\s*$/', trim($lines[$index + 1]))
        ) {
            $tableLines = [$lines[$index], $lines[$index + 1]];
            $index += 2;

            while ($index < $count) {
                $candidate = trim($lines[$index]);
                if ($candidate === '' || !str_contains($candidate, '|')) {
                    break;
                }
                $tableLines[] = $lines[$index];
                $index++;
            }

            $blocks[] = [
                'type' => 'table',
                'table' => syiAiParseMarkdownTable($tableLines),
            ];
            continue;
        }

        if (preg_match('/^([-*]|\d+\.)\s+/', $line)) {
            $items = [];
            $ordered = preg_match('/^\d+\.\s+/', $line) === 1;

            while ($index < $count && preg_match('/^([-*]|\d+\.)\s+/', trim($lines[$index]))) {
                $items[] = preg_replace('/^([-*]|\d+\.)\s+/', '', trim($lines[$index])) ?? trim($lines[$index]);
                $index++;
            }

            $blocks[] = [
                'type' => 'list',
                'ordered' => $ordered,
                'items' => $items,
            ];
            continue;
        }

        $paragraphLines = [];
        while ($index < $count) {
            $candidate = trim($lines[$index]);
            if (
                $candidate === ''
                || preg_match('/^(#{1,6})\s+/', $candidate)
                || preg_match('/^```([a-z0-9_-]+)?$/i', $candidate)
                || preg_match('/^([-*]|\d+\.)\s+/', $candidate)
                || (
                    str_contains($candidate, '|')
                    && isset($lines[$index + 1])
                    && preg_match('/^\s*\|?[:\- ]+\|[:\-| ]+\s*$/', trim($lines[$index + 1]))
                )
            ) {
                break;
            }

            $paragraphLines[] = trim($lines[$index]);
            $index++;
        }

        if ($paragraphLines !== []) {
            $blocks[] = [
                'type' => 'paragraph',
                'text' => implode(' ', $paragraphLines),
            ];
        }
    }

    return $blocks;
}

function syiAiParseMarkdownTable(array $tableLines): array
{
    $rows = [];

    foreach ($tableLines as $lineIndex => $line) {
        if ($lineIndex === 1) {
            continue;
        }

        $trimmed = trim($line);
        $trimmed = trim($trimmed, '|');
        $parts = array_map('trim', explode('|', $trimmed));
        $rows[] = $parts;
    }

    $headers = $rows[0] ?? [];
    $body = array_slice($rows, 1);

    return ['headers' => $headers, 'rows' => $body];
}

function syiAiRenderDocument(
    string $projectRoot,
    string $markdown,
    string $outputPath,
    string $documentTitle
): array {
    $blocks = syiAiMarkdownBlocks($markdown);
    $charts = [];
    $storage = syiAiEnsureStorage($projectRoot);
    $chartsDir = $storage['charts'];

    foreach ($blocks as $index => $block) {
        if (($block['type'] ?? '') !== 'code' || ($block['language'] ?? '') !== 'json') {
            continue;
        }

        $decoded = json_decode((string) ($block['content'] ?? ''), true);
        if (!is_array($decoded) || !isset($decoded['chart_type'], $decoded['data'])) {
            continue;
        }

        $chartPath = syiAiRenderChartImage($chartsDir, $decoded, syiAiSlug($documentTitle) . '-' . $index);
        if ($chartPath !== null) {
            $charts[$index] = [
                'spec' => $decoded,
                'path' => $chartPath,
            ];
        }
    }

    $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

    if ($extension === 'docx') {
        syiAiRenderDocx($blocks, $charts, $outputPath, $documentTitle);
    } elseif ($extension === 'pdf') {
        syiAiRenderPdf($blocks, $charts, $outputPath, $documentTitle);
    } else {
        throw new RuntimeException('Unsupported output format requested.');
    }

    return $charts;
}

function syiAiDocxSafeText(string $text): string
{
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text) ?? $text;
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function syiAiRenderDocx(array $blocks, array $charts, string $outputPath, string $documentTitle): void
{
    syiAiEnsureZipExtension('creating Word output');

    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);
    $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 16, 'bold' => true], ['spaceAfter' => 240]);
    $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['spaceAfter' => 200]);
    $phpWord->addTitleStyle(3, ['name' => 'Times New Roman', 'size' => 13, 'bold' => true], ['spaceAfter' => 180]);
    $phpWord->addTableStyle(
        'SyiAcademicTable',
        ['borderSize' => 6, 'borderColor' => '333333', 'cellMargin' => 70],
        ['bgColor' => 'E9ECEF']
    );

    $section = $phpWord->addSection([
        'marginTop' => 900,
        'marginBottom' => 900,
        'marginLeft' => 1100,
        'marginRight' => 1100,
    ]);

    $section->addTitle(syiAiDocxSafeText($documentTitle), 1);

    foreach ($blocks as $index => $block) {
        switch ($block['type']) {
            case 'heading':
                $level = min(3, (int) $block['level']);
                $text = (string) $block['text'];
                if (preg_match('/^(chapter 5|abstract)\b/i', $text)) {
                    $section->addPageBreak();
                }
                $section->addTitle(syiAiDocxSafeText($text), $level);
                break;

            case 'paragraph':
                $section->addText(
                    (string) $block['text'],
                    ['name' => 'Times New Roman', 'size' => 12],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'lineHeight' => 1.5, 'spaceAfter' => 180]
                );
                break;

            case 'list':
                foreach ($block['items'] as $item) {
                    $section->addListItem(
                        (string) $item,
                        0,
                        ['name' => 'Times New Roman', 'size' => 12],
                        $block['ordered'] ? ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER] : []
                    );
                }
                break;

            case 'table':
                $table = $section->addTable('SyiAcademicTable');
                $headers = $block['table']['headers'] ?? [];
                if ($headers !== []) {
                    $table->addRow();
                    foreach ($headers as $header) {
                        $table->addCell(2200)->addText((string) $header, ['bold' => true, 'name' => 'Times New Roman']);
                    }
                }
                foreach (($block['table']['rows'] ?? []) as $row) {
                    $table->addRow();
                    foreach ($row as $cell) {
                        $table->addCell(2200)->addText((string) $cell, ['name' => 'Times New Roman']);
                    }
                }
                $section->addTextBreak(1);
                break;

            case 'code':
                if (isset($charts[$index])) {
                    $section->addImage($charts[$index]['path'], ['width' => 520, 'height' => 300]);
                    $section->addTextBreak(1);
                }
                break;
        }
    }

    $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($outputPath);
}

function syiAiRenderPdf(array $blocks, array $charts, string $outputPath, string $documentTitle): void
{
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 18,
        'margin_bottom' => 18,
        'margin_left' => 18,
        'margin_right' => 18,
    ]);

    $html = '<style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.6; }
        h1, h2, h3 { font-family: "Times New Roman", serif; }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
        th, td { border: 1px solid #333; padding: 8px; vertical-align: top; }
        th { background: #e9ecef; }
        p { text-align: justify; }
        .chart { margin: 18px 0; text-align: center; }
        .chart img { max-width: 100%; height: auto; }
    </style>';
    $html .= '<h1>' . htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') . '</h1>';

    foreach ($blocks as $index => $block) {
        switch ($block['type']) {
            case 'heading':
                $level = min(3, (int) $block['level']);
                $html .= '<h' . $level . '>' . htmlspecialchars((string) $block['text'], ENT_QUOTES, 'UTF-8') . '</h' . $level . '>';
                break;

            case 'paragraph':
                $html .= '<p>' . nl2br(htmlspecialchars((string) $block['text'], ENT_QUOTES, 'UTF-8')) . '</p>';
                break;

            case 'list':
                $tag = !empty($block['ordered']) ? 'ol' : 'ul';
                $html .= '<' . $tag . '>';
                foreach ($block['items'] as $item) {
                    $html .= '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $html .= '</' . $tag . '>';
                break;

            case 'table':
                $html .= '<table><thead><tr>';
                foreach (($block['table']['headers'] ?? []) as $header) {
                    $html .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach (($block['table']['rows'] ?? []) as $row) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                break;

            case 'code':
                if (isset($charts[$index])) {
                    $html .= '<div class="chart"><img src="' . htmlspecialchars($charts[$index]['path'], ENT_QUOTES, 'UTF-8') . '" alt="Chart"></div>';
                }
                break;
        }
    }

    $mpdf->WriteHTML($html);
    $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
}

function syiAiRenderChartImage(string $chartDirectory, array $chartSpec, string $basename): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $type = strtolower((string) ($chartSpec['chart_type'] ?? 'bar'));
    $data = $chartSpec['data'] ?? [];
    $labels = $data['labels'] ?? [];
    $values = $data['values'] ?? ($data['series'] ?? []);

    if (!is_array($labels) || !is_array($values) || $labels === [] || $values === []) {
        return null;
    }

    $width = 1200;
    $height = 700;
    $image = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 34, 34, 34);
    $blue = imagecolorallocate($image, 31, 119, 180);
    $green = imagecolorallocate($image, 44, 160, 44);
    $red = imagecolorallocate($image, 214, 39, 40);
    $orange = imagecolorallocate($image, 255, 127, 14);
    $gray = imagecolorallocate($image, 210, 210, 210);

    imagefill($image, 0, 0, $white);
    imagestring($image, 5, 40, 30, 'SYiTech Generated Chart', $black);

    if ($type === 'pie') {
        syiAiDrawPieChart($image, $labels, $values, [$blue, $green, $red, $orange], $black);
    } elseif ($type === 'line') {
        syiAiDrawLineChart($image, $labels, $values, $blue, $black, $gray);
    } else {
        syiAiDrawBarChart($image, $labels, $values, $blue, $black, $gray);
    }

    $path = $chartDirectory . DIRECTORY_SEPARATOR . $basename . '.png';
    imagepng($image, $path);
    imagedestroy($image);

    return $path;
}

function syiAiDrawBarChart($image, array $labels, array $values, int $barColor, int $textColor, int $gridColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $left = 100;
    $right = $width - 80;
    $top = 100;
    $bottom = $height - 120;
    $count = max(1, count($values));
    $maxValue = max(array_map(static fn($value) => (float) $value, $values));
    $maxValue = $maxValue > 0 ? $maxValue : 1;

    imageline($image, $left, $top, $left, $bottom, $textColor);
    imageline($image, $left, $bottom, $right, $bottom, $textColor);

    for ($step = 0; $step <= 5; $step++) {
        $y = $bottom - (int) (($bottom - $top) * ($step / 5));
        imageline($image, $left, $y, $right, $y, $gridColor);
        imagestring($image, 3, 30, $y - 7, (string) round(($maxValue / 5) * $step, 2), $textColor);
    }

    $chartWidth = $right - $left;
    $slot = (int) ($chartWidth / $count);
    $barWidth = max(20, (int) ($slot * 0.55));

    foreach ($values as $index => $value) {
        $numericValue = (float) $value;
        $x1 = $left + ($slot * $index) + (int) (($slot - $barWidth) / 2);
        $x2 = $x1 + $barWidth;
        $barHeight = (int) (($numericValue / $maxValue) * ($bottom - $top - 10));
        $y1 = $bottom - $barHeight;
        imagefilledrectangle($image, $x1, $y1, $x2, $bottom - 1, $barColor);
        imagestring($image, 3, $x1, $y1 - 18, (string) round($numericValue, 2), $textColor);
        imagestring($image, 2, $x1, $bottom + 10, substr((string) ($labels[$index] ?? 'Item'), 0, 12), $textColor);
    }
}

function syiAiDrawLineChart($image, array $labels, array $values, int $lineColor, int $textColor, int $gridColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $left = 100;
    $right = $width - 80;
    $top = 100;
    $bottom = $height - 120;
    $count = max(2, count($values));
    $maxValue = max(array_map(static fn($value) => (float) $value, $values));
    $maxValue = $maxValue > 0 ? $maxValue : 1;

    imageline($image, $left, $top, $left, $bottom, $textColor);
    imageline($image, $left, $bottom, $right, $bottom, $textColor);

    for ($step = 0; $step <= 5; $step++) {
        $y = $bottom - (int) (($bottom - $top) * ($step / 5));
        imageline($image, $left, $y, $right, $y, $gridColor);
        imagestring($image, 3, 30, $y - 7, (string) round(($maxValue / 5) * $step, 2), $textColor);
    }

    $chartWidth = $right - $left;
    $previousPoint = null;

    foreach ($values as $index => $value) {
        $numericValue = (float) $value;
        $x = $left + (int) (($chartWidth / max(1, $count - 1)) * $index);
        $y = $bottom - (int) (($numericValue / $maxValue) * ($bottom - $top - 10));

        if ($previousPoint !== null) {
            imageline($image, $previousPoint['x'], $previousPoint['y'], $x, $y, $lineColor);
        }

        imagefilledellipse($image, $x, $y, 12, 12, $lineColor);
        imagestring($image, 2, $x - 20, $bottom + 10, substr((string) ($labels[$index] ?? 'Item'), 0, 12), $textColor);
        imagestring($image, 3, $x - 10, $y - 20, (string) round($numericValue, 2), $textColor);
        $previousPoint = ['x' => $x, 'y' => $y];
    }
}

function syiAiDrawPieChart($image, array $labels, array $values, array $palette, int $textColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $centerX = (int) ($width * 0.35);
    $centerY = (int) ($height * 0.5);
    $diameter = 360;
    $total = array_sum(array_map(static fn($value) => (float) $value, $values));
    $total = $total > 0 ? $total : 1;
    $startAngle = 0.0;

    foreach ($values as $index => $value) {
        $slice = ((float) $value / $total) * 360;
        $color = $palette[$index % count($palette)];
        imagefilledarc(
            $image,
            $centerX,
            $centerY,
            $diameter,
            $diameter,
            (int) $startAngle,
            (int) ($startAngle + $slice),
            $color,
            IMG_ARC_PIE
        );
        $startAngle += $slice;
    }

    $legendX = (int) ($width * 0.68);
    $legendY = 140;

    foreach ($labels as $index => $label) {
        $color = $palette[$index % count($palette)];
        imagefilledrectangle($image, $legendX, $legendY, $legendX + 25, $legendY + 18, $color);
        $value = (float) ($values[$index] ?? 0);
        $percentage = round(($value / $total) * 100, 2);
        imagestring($image, 3, $legendX + 35, $legendY + 3, substr((string) $label, 0, 20) . ' (' . $percentage . '%)', $textColor);
        $legendY += 30;
    }
}

function syiAiIssueDownloadName(array $job): string
{
    $base = syiAiSlug((string) ($job['student_identifier'] ?? 'student')) . '-analysis';
    $suffix = substr(bin2hex(random_bytes(8)), 0, 12);
    $extension = strtolower((string) ($job['output_format'] ?? 'word')) === 'pdf' ? 'pdf' : 'docx';
    return $base . '-' . $suffix . '.' . $extension;
}

function syiAiDownloadUrl(string $baseUrl, string $downloadName): string
{
    return rtrim($baseUrl, '/') . '/downloads/' . rawurlencode($downloadName);
}

function syiAiSendReadyEmail(array $job, string $downloadUrl): bool
{
    $toAddress = trim((string) ($job['student_email'] ?? ''));
    if ($toAddress === '') {
        return false;
    }

    $fromAddress = syiAiEnv('MAIL_FROM_ADDRESS', 'no-reply@syitech.com.ng');
    $fromName = syiAiEnv('MAIL_FROM_NAME', 'SYiTech');

    try {
        $mailer = new PHPMailer(true);
        $transport = strtolower((string) syiAiEnv('MAIL_MAILER', 'smtp'));

        if ($transport === 'smtp') {
            $mailer->isSMTP();
            $mailer->Host = (string) syiAiEnv('MAIL_HOST', '127.0.0.1');
            $mailer->Username = (string) syiAiEnv('MAIL_USERNAME', '');
            $mailer->Password = (string) syiAiEnv('MAIL_PASSWORD', '');
            $mailer->SMTPAuth = $mailer->Username !== '';
            $mailer->Port = (int) syiAiEnv('MAIL_PORT', '587');
            $encryption = strtolower((string) syiAiEnv('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            $mailer->isMail();
        }

        $mailer->setFrom($fromAddress, $fromName);
        $mailer->addAddress($toAddress, (string) ($job['student_name'] ?? 'Student'));
        $mailer->isHTML(true);
        $mailer->Subject = 'Your SYiTech analysis is ready';
        $mailer->Body = '
            <p>Dear ' . htmlspecialchars((string) ($job['student_name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your project analysis request for <strong>' . htmlspecialchars((string) ($job['project_topic'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong> is ready for download.</p>
            <p><a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '">Download your completed file</a></p>
            <p>Regards,<br>SYiTech</p>
        ';
        $mailer->AltBody = "Your project analysis is ready.\nDownload here: " . $downloadUrl;
        $mailer->send();

        return true;
    } catch (MailException $exception) {
        error_log('SYi AI mail error: ' . $exception->getMessage());
        return false;
    }
}
