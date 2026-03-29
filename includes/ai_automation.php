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
You are a Nigerian academic data analyst and research methodologist.
Use the student's existing methodology, questionnaire/instrument, sample size, equations, and stated method of data analysis to generate the result chapters.

Generate the result in this exact order:
1. Abstract
2. Chapter Four: Data Presentation and Analysis
3. Degree-based closing chapters as specified below

Degree-based closing chapter rule:
[chapter plan from degree setting]

Mandatory rules:
1. Abstract must come first.
2. Use full chapter headings with the word "and", never "&".
3. Chapter Four must be titled exactly "Chapter Four: Data Presentation and Analysis".
4. Use SPSS-style table presentation in Chapter Four wherever quantitative analysis is being presented. Where applicable, use clear SPSS-like columns such as Frequency, Percent, Valid Percent, and Cumulative Percent without merging any cells.
5. No table should be merged.
6. Every questionnaire item and every research question must be treated accordingly in tabular form where applicable.
7. Every table must include a title, Source: Fieldwork, 2025, and an interpretation immediately below the table.
8. Every interpretation below a table must be at least 150 words.
9. Hypothesis testing must follow the stated inferential method in the work and include the proper test table with a well-detailed interpretation of at least 150 words.
10. Consider the sample size, research instrument, and stated method of data analysis before generating any analysis.
11. If equations or mathematical formulae are stated in the work, apply them appropriately.
12. Include Discussion of Findings in the proper chapter structure for the degree level.
13. Include qualitative analysis whenever the methodology or instrument indicates qualitative or mixed-method analysis.
14. Every graph must include a minimum 300-word interpretation explaining the trend, comparison, and implication to the study.
15. Every graph must have a figure title positioned below the graph. Include the figure title in the JSON block using a title field and do not output a separate standalone caption paragraph.
16. All sections and subsections must be properly numbered in academic style, for example 4.1, 4.2, 4.3, 5.1, 5.2.
17. Chapter headings must be written in a centralised academic style.
18. Where graphs are selected, use bar charts by default unless the data structure clearly requires a different chart type.
19. Graphs must be readable, correspond to the tables, and be accompanied by exact JSON chart blocks like {"chart_type":"bar","title":"Figure 4.1: Example Title","data":{"labels":[...],"values":[...]}} for PHP rendering.
20. Remove all asterisks. Do not use "*" anywhere in the final output.
21. Use formal academic tone in line with Nigerian university standards.
22. Total target length: [pages from admin setting] pages.

Output only clean Markdown with headings, tables, detailed interpretations, and JSON chart blocks.
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

function syiAiRecommendedPagesForDegree(string $degreeLevel): int
{
    return match (trim($degreeLevel)) {
        'NCE/ND', 'BSc/HND' => 30,
        'MSc/MPhil' => 70,
        'PhD' => 100,
        default => 50,
    };
}

function syiAiChapterPlanForDegree(string $degreeLevel): string
{
    return in_array(trim($degreeLevel), ['MSc/MPhil', 'PhD'], true)
        ? 'For MSc/MPhil and PhD, Chapter Four must contain Data Presentation and Analysis only, Chapter Five must be Discussion of Findings, and Chapter Six must contain Summary of the Study, Conclusion, Recommendations, Limitations of the Study, and Suggestions for Further Studies.'
        : 'For NCE/ND, BSc/HND, and PGD, Chapter Four must end with Discussion of Findings as its final subsection, and Chapter Five must contain Summary of the Study, Conclusion, and Recommendations.';
}

function syiAiDegreeUsesChapterSix(string $degreeLevel): bool
{
    return in_array(trim($degreeLevel), ['MSc/MPhil', 'PhD'], true);
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
    $degreeLevel = (string) ($job['degree_level'] ?? 'BSc/HND');
    $targetPages = (int) ($job['target_pages'] ?? syiAiRecommendedPagesForDegree($degreeLevel));

    $messages = [
        ['role' => 'system', 'content' => SYI_TOPIC_ONLY_PROMPT],
        ['role' => 'user', 'content' => implode("\n\n", [
            'Project topic: ' . ($job['project_topic'] ?? ''),
            'Degree level: ' . $degreeLevel,
            'Target pages for the final work: ' . $targetPages,
        ])],
    ];

    $response = syiAiOpenAiChatRequest($client, $messages, 0.2);

    return $response['content'];
}

function syiAiBuildSystemPrompt(array $job): string
{
    $pages = (int) ($job['target_pages'] ?? syiAiRecommendedPagesForDegree((string) ($job['degree_level'] ?? 'BSc/HND')));
    $degreeLevel = (string) ($job['degree_level'] ?? 'BSc/HND');

    return str_replace(
        ['[pages from admin setting]', '[chapter plan from degree setting]'],
        [(string) $pages, syiAiChapterPlanForDegree($degreeLevel)],
        SYI_AI_SYSTEM_PROMPT
    );
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

function syiAiMoveAbstractFirst(string $markdown): string
{
    $markdown = preg_replace("/\r\n|\r/", "\n", $markdown) ?? $markdown;

    if (!preg_match('/(^|\n)(#{1,6}\s*)?abstract\b.*?(?=\n(#{1,6}\s*(chapter|abstract)\b|chapter\s+(four|five|six)\b)|\z)/is', $markdown, $matches, PREG_OFFSET_CAPTURE)) {
        return trim($markdown);
    }

    $abstractBlock = trim((string) $matches[0][0]);
    $abstractOffset = (int) $matches[0][1];
    $before = trim(substr($markdown, 0, $abstractOffset));
    $after = trim(substr($markdown, $abstractOffset + strlen((string) $matches[0][0])));

    $remaining = trim(implode("\n\n", array_filter([$before, $after], static fn($value) => trim((string) $value) !== '')));

    return trim($abstractBlock . ($remaining !== '' ? "\n\n" . $remaining : ''));
}

function syiAiNormalizeAcademicHeading(array $job, string $heading): string
{
    $heading = trim(str_replace('&', 'and', $heading));
    $usesChapterSix = syiAiDegreeUsesChapterSix((string) ($job['degree_level'] ?? ''));

    if (preg_match('/^abstract\b/i', $heading) === 1) {
        return 'Abstract';
    }

    if (preg_match('/^chapter\s*(4|four)\b/i', $heading) === 1) {
        return 'Chapter Four: Data Presentation and Analysis';
    }

    if ($usesChapterSix) {
        if (preg_match('/^chapter\s*(5|five)\b/i', $heading) === 1) {
            return 'Chapter Five: Discussion of Findings';
        }

        if (preg_match('/^chapter\s*(6|six)\b/i', $heading) === 1) {
            return 'Chapter Six: Summary of the Study, Conclusion and Recommendations';
        }
    }

    if (preg_match('/^chapter\s*(5|five)\b/i', $heading) === 1) {
        return 'Chapter Five: Summary of the Study, Conclusion and Recommendations';
    }

    return $heading;
}

function syiAiNormalizeAcademicHeadings(array $job, string $markdown): string
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown) ?: [];

    foreach ($lines as $index => $line) {
        if (preg_match('/^(#{1,6}\s*)(.+)$/', $line, $matches) === 1) {
            $lines[$index] = $matches[1] . syiAiNormalizeAcademicHeading($job, $matches[2]);
            continue;
        }

        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }

        if (preg_match('/^(abstract|chapter\s*(4|four|5|five|6|six)\b.*)$/i', $trimmed) === 1) {
            $lines[$index] = syiAiNormalizeAcademicHeading($job, $trimmed);
        }
    }

    return trim(implode("\n", $lines));
}

function syiAiExtractChapterNumberFromHeading(string $heading): ?int
{
    $heading = trim($heading);

    if (preg_match('/\bchapter\s*(4|four)\b/i', $heading) === 1) {
        return 4;
    }

    if (preg_match('/\bchapter\s*(5|five)\b/i', $heading) === 1) {
        return 5;
    }

    if (preg_match('/\bchapter\s*(6|six)\b/i', $heading) === 1) {
        return 6;
    }

    return null;
}

function syiAiNormalizedHeadingKey(string $heading): string
{
    $heading = trim(strtolower($heading));
    $heading = preg_replace('/\s*\(continued\)\s*$/i', '', $heading) ?? $heading;
    $heading = preg_replace('/^chapter\s+(four|five|six|4|5|6)\s*:\s*/i', '', $heading) ?? $heading;
    $heading = preg_replace('/^\d+\.\d+\s+/u', '', $heading) ?? $heading;
    $heading = preg_replace('/^(table|figure)\s+\d+(?:\.\d+)?\s*:\s*/iu', '', $heading) ?? $heading;
    $heading = preg_replace('/\s+/u', ' ', $heading) ?? $heading;
    $heading = preg_replace('/[^a-z0-9 ]+/u', ' ', $heading) ?? $heading;
    return trim($heading);
}

function syiAiDeduplicateHeadingBlocks(string $markdown): string
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown) ?: [];
    $leadingLines = [];
    $blocks = [];
    $currentBlock = null;

    foreach ($lines as $line) {
        if (preg_match('/^(#{1,6})\s+(.+)$/', trim($line), $matches) === 1) {
            if ($currentBlock !== null) {
                $blocks[] = $currentBlock;
            }

            $currentBlock = [
                'heading' => trim($matches[2]),
                'key' => strlen($matches[1]) . '|' . syiAiNormalizedHeadingKey($matches[2]),
                'lines' => [$line],
            ];
            continue;
        }

        if ($currentBlock === null) {
            $leadingLines[] = $line;
            continue;
        }

        $currentBlock['lines'][] = $line;
    }

    if ($currentBlock !== null) {
        $blocks[] = $currentBlock;
    }

    $keptBlocks = [];
    $blockOrder = [];

    foreach ($blocks as $block) {
        $key = $block['key'];
        $candidateMarkdown = trim(implode("\n", $block['lines']));
        $candidateWords = syiAiCountWords($candidateMarkdown);

        if (!isset($keptBlocks[$key])) {
            $keptBlocks[$key] = $block;
            $blockOrder[] = $key;
            continue;
        }

        $existingMarkdown = trim(implode("\n", $keptBlocks[$key]['lines']));
        $existingWords = syiAiCountWords($existingMarkdown);

        if ($candidateWords > $existingWords) {
            $keptBlocks[$key] = $block;
        }
    }

    $merged = [];
    $leading = trim(implode("\n", $leadingLines));
    if ($leading !== '') {
        $merged[] = $leading;
    }

    foreach ($blockOrder as $key) {
        $blockMarkdown = trim(implode("\n", $keptBlocks[$key]['lines']));
        if ($blockMarkdown !== '') {
            $merged[] = $blockMarkdown;
        }
    }

    return trim(implode("\n\n", $merged));
}

function syiAiApplySectionNumbering(array $job, string $markdown): string
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown) ?: [];
    $currentChapter = null;
    $sectionCounters = [];

    foreach ($lines as $index => $line) {
        if (preg_match('/^(#{1,6}\s*)(.+)$/', $line, $matches) !== 1) {
            continue;
        }

        $prefix = $matches[1];
        $heading = trim($matches[2]);

        if (preg_match('/^abstract\b/i', $heading) === 1) {
            $lines[$index] = $prefix . 'Abstract';
            $currentChapter = null;
            continue;
        }

        $chapterNumber = syiAiExtractChapterNumberFromHeading($heading);
        if ($chapterNumber !== null) {
            $currentChapter = $chapterNumber;
            $sectionCounters[$chapterNumber] = 0;
            $lines[$index] = $prefix . syiAiNormalizeAcademicHeading($job, $heading);
            continue;
        }

        if ($currentChapter === null) {
            continue;
        }

        if (
            preg_match('/^\d+\.\d+\b/', $heading) === 1
            || preg_match('/^(table|figure)\s+\d+(?:\.\d+)?\b/i', $heading) === 1
        ) {
            continue;
        }

        $sectionCounters[$currentChapter] = ($sectionCounters[$currentChapter] ?? 0) + 1;
        $lines[$index] = $prefix . $currentChapter . '.' . $sectionCounters[$currentChapter] . ' ' . $heading;
    }

    return trim(implode("\n", $lines));
}

function syiAiFinalizeAcademicMarkdown(array $job, string $markdown): string
{
    $markdown = preg_replace("/\r\n|\r/", "\n", $markdown) ?? $markdown;
    $markdown = str_replace('*', '', $markdown);
    $markdown = str_replace(' & ', ' and ', $markdown);
    $markdown = str_replace('& Analysis', 'and Analysis', $markdown);
    $markdown = str_replace('& Recommendations', 'and Recommendations', $markdown);
    $markdown = syiAiNormalizeAcademicHeadings($job, $markdown);
    $markdown = syiAiMoveAbstractFirst($markdown);
    $markdown = syiAiDeduplicateHeadingBlocks($markdown);
    $markdown = syiAiApplySectionNumbering($job, $markdown);

    return trim($markdown);
}

function syiAiBuildGenerationUserPrompt(array $job): string
{
    $datasetSummary = json_decode((string) ($job['dataset_summary_json'] ?? ''), true) ?: [];
    $compactDatasetSummary = syiAiCompactDatasetSummary($datasetSummary);
    $degreeLevel = (string) ($job['degree_level'] ?? 'BSc/HND');
    $targetPages = (int) ($job['target_pages'] ?? syiAiRecommendedPagesForDegree($degreeLevel));
    $analysisPackage = [
        'project_topic' => $job['project_topic'] ?? '',
        'degree_level' => $degreeLevel,
        'recommended_pages_for_degree' => syiAiRecommendedPagesForDegree($degreeLevel),
        'target_pages' => $targetPages,
        'estimated_sample_size' => $datasetSummary['row_count'] ?? null,
        'chapter_structure_rule' => syiAiChapterPlanForDegree($degreeLevel),
        'graphs_required' => !empty($job['include_graphs']) ? 'Yes' : 'No',
        'graph_default_type' => !empty($job['include_graphs']) ? 'bar' : 'none',
        'graph_interpretation_minimum_words' => 300,
        'graph_caption_rule' => 'Each graph must have a figure title positioned below the graph and included in the JSON title field. Do not output a separate caption paragraph outside the JSON block.',
        'hypothesis_setting' => $job['hypothesis_mode'] ?? 'auto-detect',
        'output_format' => strtoupper((string) ($job['output_format'] ?? 'word')),
        'submission_mode' => $job['submission_mode'] ?? 'full_upload',
        'detected_method' => syiAiDetectMethod((string) ($job['methodology_text'] ?? ''), $datasetSummary),
        'methodology_excerpt' => syiAiTruncate((string) ($job['methodology_text'] ?? ''), 3500),
        'chapters_1_to_3_and_instrument_excerpt' => syiAiTruncate((string) ($job['chapters_text'] ?? ''), 6500),
        'topic_outline' => syiAiTruncate((string) ($job['chapter_outline_markdown'] ?? ''), 2500),
        'dataset_summary' => $compactDatasetSummary,
        'admin_notes' => (string) ($job['admin_notes'] ?? ''),
        'workflow_order' => 'Tables -> Graphs -> Interpretation -> Source',
        'section_numbering_rule' => 'All sections and subsections must be properly numbered in academic format such as 4.1, 4.2, 5.1, 6.1.',
        'special_rules' => [
            'Abstract first',
            'Use Chapter Four: Data Presentation and Analysis',
            'Use SPSS-format tables in Chapter Four',
            'Use Frequency, Percent, Valid Percent, and Cumulative Percent columns where applicable',
            'Do not merge any table cell',
            'Treat all research questions',
            'Treat all questionnaire items in tabular form where applicable',
            'Each table interpretation must be 150 words or more',
            'Each graph interpretation must be 300 words or more',
            'Each graph interpretation must explain trend, comparison, and implication to the study',
            'Each graph must have a figure title placed below the graph',
            'Put each graph title in the JSON title field and do not output a separate caption paragraph',
            'Where graphs are selected, use bar charts by default',
            'Chapter headings should be centralised in academic style',
            'Hypothesis tables must align with the stated inferential method',
            'Include discussion of findings',
            'Include qualitative analysis where applicable',
            'Remove all asterisks',
        ],
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
        'Graph rule: when graphs are required, output fenced json blocks with chart_type, title, labels and values exactly as instructed.',
        'Document order rule: keep the result in this order whenever relevant: tables, then graphs, then interpretation, then source note.',
    ]);
}

function syiAiApproxWordsPerPage(array $job): int
{
    return !empty($job['include_graphs']) ? 330 : 350;
}

function syiAiTargetWordCount(array $job): int
{
    $degreeLevel = (string) ($job['degree_level'] ?? 'BSc/HND');
    $targetPages = (int) ($job['target_pages'] ?? syiAiRecommendedPagesForDegree($degreeLevel));
    return max(2500, $targetPages * syiAiApproxWordsPerPage($job));
}

function syiAiCountWords(string $markdown): int
{
    $text = preg_replace('/```.*?```/s', ' ', $markdown) ?? $markdown;
    $text = preg_replace('/[|#`>\-\*\[\]\(\)_]/u', ' ', $text) ?? $text;
    preg_match_all('/[\p{L}\p{N}]+(?:[\'-][\p{L}\p{N}]+)*/u', $text, $matches);
    return count($matches[0] ?? []);
}

function syiAiPromptExcerpt(string $text, int $maxLength = 9000): string
{
    $text = trim($text);
    if ($text === '' || mb_strlen($text, 'UTF-8') <= $maxLength) {
        return $text;
    }

    $headLength = (int) floor($maxLength * 0.55);
    $tailLength = max(1000, $maxLength - $headLength - 40);

    $head = mb_substr($text, 0, $headLength, 'UTF-8');
    $tail = mb_substr($text, -$tailLength, null, 'UTF-8');

    return trim($head . "\n\n[...truncated for prompt context...]\n\n" . $tail);
}

function syiAiSectionGenerationPlan(array $job): array
{
    $usesChapterSix = syiAiDegreeUsesChapterSix((string) ($job['degree_level'] ?? ''));
    $targetWords = syiAiTargetWordCount($job);
    $abstractWords = min(450, max(250, (int) round($targetWords * 0.05)));
    $bodyWords = max(1800, $targetWords - $abstractWords);

    if ($usesChapterSix) {
        $chapterFourWords = max(4200, (int) round($bodyWords * 0.66));
        $chapterFiveWords = max(1800, (int) round($bodyWords * 0.16));
        $chapterSixWords = max(1600, $bodyWords - $chapterFourWords - $chapterFiveWords);

        return [
            'chapter_four' => [
                'key' => 'chapter_four',
                'heading' => 'Chapter Four: Data Presentation and Analysis',
                'min_words' => $chapterFourWords,
                'max_passes' => 6,
                'instructions' => 'Generate only Chapter Four. Treat all research questions and questionnaire items in SPSS-style tables where applicable. Include all quantitative analysis, hypothesis testing, qualitative analysis where applicable, readable bar-chart references where graphs are enabled, source notes, and detailed interpretations of at least 150 words per table. Every graph must include its figure title in the JSON title field and be followed by an interpretation of at least 300 words covering trend, comparison, and implication to the study. Do not place Discussion of Findings in Chapter Four. Number the subsections properly such as 4.1, 4.2, 4.3. Do not generate Chapter Five, Chapter Six, or the Abstract in this response.',
            ],
            'chapter_five' => [
                'key' => 'chapter_five',
                'heading' => 'Chapter Five: Discussion of Findings',
                'min_words' => $chapterFiveWords,
                'max_passes' => 4,
                'instructions' => 'Generate only Chapter Five: Discussion of Findings. Discuss the major findings in relation to the generated results, research questions, hypotheses, objectives, and qualitative evidence where applicable. Compare the findings with relevant literature and explain the implications of the findings. Number the subsections properly such as 5.1, 5.2, 5.3. Do not generate the Abstract, Chapter Four, or Chapter Six in this response.',
            ],
            'chapter_six' => [
                'key' => 'chapter_six',
                'heading' => 'Chapter Six: Summary of the Study, Conclusion and Recommendations',
                'min_words' => $chapterSixWords,
                'max_passes' => 3,
                'instructions' => 'Generate only Chapter Six. Include the numbered subheadings 6.1 Summary of Findings, 6.2 Conclusion, 6.3 Recommendations, 6.4 Limitations of the Study, and 6.5 Suggestions for Further Studies. Write them in a clear academic sequence and keep the chapter logically connected. Do not generate the Abstract or earlier chapters in this response.',
            ],
            'abstract' => [
                'key' => 'abstract',
                'heading' => 'Abstract',
                'min_words' => $abstractWords,
                'max_passes' => 2,
                'instructions' => 'Generate only the Abstract based on the full already-generated study body. Make it concise, formal, and academically polished. Do not generate any chapter in this response.',
            ],
        ];
    }

    $chapterFourWords = max(3200, (int) round($bodyWords * 0.74));
    $chapterFiveWords = max(1400, $bodyWords - $chapterFourWords);

    return [
        'chapter_four' => [
            'key' => 'chapter_four',
            'heading' => 'Chapter Four: Data Presentation and Analysis',
            'min_words' => $chapterFourWords,
            'max_passes' => 6,
                'instructions' => 'Generate only Chapter Four. Treat all research questions and questionnaire items in SPSS-style tables where applicable. Include all quantitative analysis, hypothesis testing, qualitative analysis where applicable, readable bar-chart references where graphs are enabled, source notes, and detailed interpretations of at least 150 words per table. Every graph must include its figure title in the JSON title field and be followed by an interpretation of at least 300 words covering trend, comparison, and implication to the study. The final subsection in this chapter must be a detailed Discussion of Findings. Number the subsections properly such as 4.1, 4.2, 4.3. Do not generate Chapter Five or the Abstract in this response.',
        ],
        'chapter_five' => [
            'key' => 'chapter_five',
            'heading' => 'Chapter Five: Summary of the Study, Conclusion and Recommendations',
            'min_words' => $chapterFiveWords,
            'max_passes' => 4,
            'instructions' => 'Generate only Chapter Five. Include the numbered subheadings 5.1 Summary of the Study, 5.2 Conclusion, and 5.3 Recommendations, and add any other relevant closing points where useful. Keep the chapter well structured, clear, and academically written. Do not generate the Abstract or earlier chapters in this response.',
        ],
        'abstract' => [
            'key' => 'abstract',
            'heading' => 'Abstract',
            'min_words' => $abstractWords,
            'max_passes' => 2,
            'instructions' => 'Generate only the Abstract based on the full already-generated study body. Make it concise, formal, and academically polished. Do not generate any chapter in this response.',
        ],
    ];
}

function syiAiEnsureSectionHeading(string $markdown, string $heading): string
{
    $markdown = trim($markdown);
    if ($markdown === '') {
        return '## ' . $heading;
    }

    if (preg_match('/^\s*(#{1,6}\s*)?' . preg_quote($heading, '/') . '\b/im', $markdown) === 1) {
        return $markdown;
    }

    return '## ' . $heading . "\n\n" . $markdown;
}

function syiAiStripSectionHeading(string $markdown, string $heading): string
{
    $markdown = trim($markdown);

    return trim((string) preg_replace(
        '/^\s*(#{1,6}\s*)?' . preg_quote($heading, '/') . '\s*\n+/i',
        '',
        $markdown,
        1
    ));
}

function syiAiStripMarkdownHeadingLines(string $markdown): string
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown) ?: [];
    $filtered = [];

    foreach ($lines as $line) {
        if (preg_match('/^\s*#{1,6}\s+/', trim($line)) === 1) {
            continue;
        }
        $filtered[] = $line;
    }

    return trim(implode("\n", $filtered));
}

function syiAiBuildSectionUserPrompt(
    array $job,
    array $sectionSpec,
    string $contextMarkdown = '',
    string $currentMarkdown = '',
    int $remainingWords = 0,
    bool $continuation = false
): string {
    $basePack = syiAiBuildGenerationUserPrompt($job);
    $segments = [
        'Base project pack for this generation:',
        $basePack,
    ];

    if (trim($contextMarkdown) !== '') {
        $segments[] = 'Previously generated study context for consistency:';
        $segments[] = syiAiPromptExcerpt($contextMarkdown, 8000);
    }

    if ($continuation) {
        $segments[] = 'Current section draft to continue from:';
        $segments[] = syiAiPromptExcerpt($currentMarkdown, 8000);
        $segments[] = 'Continue and expand only ' . $sectionSpec['heading'] . '. Do not restart the section. Do not repeat material already written. Do not create any new chapter headings or numbered subsection headings in the continuation. Continue from the existing final subsection using only additional paragraphs, tables, lists, graph JSON blocks where missing, and academically detailed interpretations until at least ' . $remainingWords . ' additional words have been added. Preserve the existing structure, numbering, graph-caption rules, and interpretation minimums. Return only Markdown for the continuation.';
    } else {
        $segments[] = 'Generate only this section now: ' . $sectionSpec['heading'] . '.';
        $segments[] = $sectionSpec['instructions'];
        $segments[] = 'Minimum target length for this section: ' . $sectionSpec['min_words'] . ' words.';
        $segments[] = 'Start with the heading "' . $sectionSpec['heading'] . '". Return only clean Markdown for this section.';
    }

    return implode("\n\n", $segments);
}

function syiAiGenerateTargetedSection(Client $client, array $job, string $systemPrompt, array $sectionSpec, string $contextMarkdown = ''): array
{
    $sectionMarkdown = '';
    $promptLog = [];
    $modelsUsed = [];
    $maxPasses = max(1, (int) ($sectionSpec['max_passes'] ?? 3));
    $minimumWords = max(250, (int) ($sectionSpec['min_words'] ?? 1200));
    $completionThreshold = max(220, (int) round($minimumWords * 0.08));

    for ($pass = 1; $pass <= $maxPasses; $pass++) {
        $currentWords = syiAiCountWords($sectionMarkdown);
        $remainingWords = max(0, $minimumWords - $currentWords);

        if ($pass > 1 && $remainingWords <= $completionThreshold) {
            break;
        }

        $continuation = $pass > 1;
        $userPrompt = syiAiBuildSectionUserPrompt(
            $job,
            $sectionSpec,
            $contextMarkdown,
            $sectionMarkdown,
            $remainingWords,
            $continuation
        );

        $response = syiAiOpenAiChatRequest($client, [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ], 0.2);

        $chunk = syiAiFinalizeAcademicMarkdown($job, (string) $response['content']);
        if ($continuation) {
            $chunk = syiAiStripSectionHeading($chunk, (string) $sectionSpec['heading']);
            $chunk = syiAiStripMarkdownHeadingLines($chunk);
        } else {
            $chunk = syiAiEnsureSectionHeading($chunk, (string) $sectionSpec['heading']);
        }

        $chunk = trim($chunk);
        if ($chunk === '') {
            break;
        }

        $sectionMarkdown = trim($sectionMarkdown . ($sectionMarkdown !== '' ? "\n\n" : '') . $chunk);
        $promptLog[] = '[' . strtoupper((string) $sectionSpec['key']) . ' | pass ' . $pass . "]\n" . $userPrompt;
        $modelsUsed[] = (string) $response['model'];

        if (syiAiCountWords($chunk) < 180 && $pass > 1) {
            break;
        }
    }

    $sectionMarkdown = syiAiEnsureSectionHeading(syiAiFinalizeAcademicMarkdown($job, $sectionMarkdown), (string) $sectionSpec['heading']);

    return [
        'markdown' => $sectionMarkdown,
        'prompt_log' => implode("\n\n-----\n\n", $promptLog),
        'models' => array_values(array_unique(array_filter($modelsUsed))),
    ];
}

function syiAiGenerateAcademicMarkdown(Client $client, array $job): array
{
    $systemPrompt = syiAiBuildSystemPrompt($job);
    $sectionPlan = syiAiSectionGenerationPlan($job);
    $sectionResults = [];
    $promptLog = [];
    $modelsUsed = [];
    $bodyContext = '';

    foreach (['chapter_four', 'chapter_five', 'chapter_six'] as $sectionKey) {
        if (!isset($sectionPlan[$sectionKey])) {
            continue;
        }

        $result = syiAiGenerateTargetedSection($client, $job, $systemPrompt, $sectionPlan[$sectionKey], $bodyContext);
        $sectionResults[$sectionKey] = $result['markdown'];
        if ($result['prompt_log'] !== '') {
            $promptLog[] = $result['prompt_log'];
        }
        $modelsUsed = array_merge($modelsUsed, $result['models']);
        $bodyContext = trim($bodyContext . ($bodyContext !== '' ? "\n\n" : '') . $result['markdown']);
    }

    if (isset($sectionPlan['abstract'])) {
        $result = syiAiGenerateTargetedSection($client, $job, $systemPrompt, $sectionPlan['abstract'], $bodyContext);
        $sectionResults['abstract'] = $result['markdown'];
        if ($result['prompt_log'] !== '') {
            $promptLog[] = $result['prompt_log'];
        }
        $modelsUsed = array_merge($modelsUsed, $result['models']);
    }

    $targetWords = syiAiTargetWordCount($job);
    $completionThreshold = max(700, (int) round($targetWords * 0.07));
    $expansionOrder = array_values(array_filter(['chapter_four', 'chapter_five', 'chapter_six'], static fn($key) => isset($sectionResults[$key])));

    for ($expansionPass = 1; $expansionPass <= 3; $expansionPass++) {
        $orderedForWordCheck = array_filter([
            $sectionResults['abstract'] ?? '',
            $sectionResults['chapter_four'] ?? '',
            $sectionResults['chapter_five'] ?? '',
            $sectionResults['chapter_six'] ?? '',
        ], static fn($value) => trim((string) $value) !== '');
        $combinedWords = syiAiCountWords(implode("\n\n", $orderedForWordCheck));
        $remainingWords = max(0, $targetWords - $combinedWords);

        if ($remainingWords <= $completionThreshold) {
            break;
        }

        foreach ($expansionOrder as $sectionKey) {
            $currentSection = trim((string) ($sectionResults[$sectionKey] ?? ''));
            if ($currentSection === '') {
                continue;
            }

            $orderedForWordCheck = array_filter([
                $sectionResults['abstract'] ?? '',
                $sectionResults['chapter_four'] ?? '',
                $sectionResults['chapter_five'] ?? '',
                $sectionResults['chapter_six'] ?? '',
            ], static fn($value) => trim((string) $value) !== '');
            $combinedWords = syiAiCountWords(implode("\n\n", $orderedForWordCheck));
            $remainingWords = max(0, $targetWords - $combinedWords);

            if ($remainingWords <= $completionThreshold) {
                break;
            }

            $sectionSpec = $sectionPlan[$sectionKey];
            $contextParts = [];
            foreach (['chapter_four', 'chapter_five', 'chapter_six', 'abstract'] as $contextKey) {
                if ($contextKey === $sectionKey) {
                    continue;
                }
                if (!empty($sectionResults[$contextKey])) {
                    $contextParts[] = $sectionResults[$contextKey];
                }
            }

            $userPrompt = syiAiBuildSectionUserPrompt(
                $job,
                $sectionSpec,
                implode("\n\n", $contextParts),
                $currentSection,
                min($remainingWords, max(1200, (int) round(($sectionSpec['min_words'] ?? 1200) * 0.22))),
                true
            );

            $response = syiAiOpenAiChatRequest($client, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], 0.2);

            $chunk = syiAiFinalizeAcademicMarkdown($job, (string) $response['content']);
            $chunk = syiAiStripSectionHeading($chunk, (string) $sectionSpec['heading']);
            $chunk = syiAiStripMarkdownHeadingLines($chunk);
            $chunk = trim($chunk);

            if ($chunk === '') {
                continue;
            }

            $sectionResults[$sectionKey] = syiAiEnsureSectionHeading(
                syiAiFinalizeAcademicMarkdown($job, trim($currentSection . "\n\n" . $chunk)),
                (string) $sectionSpec['heading']
            );
            $promptLog[] = '[' . strtoupper((string) $sectionSpec['key']) . ' | expansion ' . $expansionPass . "]\n" . $userPrompt;
            $modelsUsed[] = (string) $response['model'];
        }
    }

    $orderedSections = array_filter([
        $sectionResults['abstract'] ?? '',
        $sectionResults['chapter_four'] ?? '',
        $sectionResults['chapter_five'] ?? '',
        $sectionResults['chapter_six'] ?? '',
    ], static fn($value) => trim((string) $value) !== '');

    $combinedMarkdown = syiAiFinalizeAcademicMarkdown($job, implode("\n\n", $orderedSections));
    $combinedMarkdown = syiAiEnsureGraphBlocks($job, $combinedMarkdown);
    $combinedMarkdown = syiAiFinalizeAcademicMarkdown($job, $combinedMarkdown);

    return [
        'system_prompt' => $systemPrompt,
        'user_prompt' => implode("\n\n=====\n\n", $promptLog),
        'model' => implode(', ', array_values(array_unique(array_filter($modelsUsed)))),
        'markdown' => $combinedMarkdown,
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

function syiAiMarkdownBlocksToString(array $blocks): string
{
    $segments = [];

    foreach ($blocks as $block) {
        switch ($block['type'] ?? '') {
            case 'heading':
                $segments[] = str_repeat('#', max(1, (int) ($block['level'] ?? 2))) . ' ' . trim((string) ($block['text'] ?? ''));
                break;

            case 'paragraph':
                $segments[] = trim((string) ($block['text'] ?? ''));
                break;

            case 'list':
                $lines = [];
                foreach (($block['items'] ?? []) as $index => $item) {
                    $prefix = !empty($block['ordered']) ? ($index + 1) . '. ' : '- ';
                    $lines[] = $prefix . trim((string) $item);
                }
                if ($lines !== []) {
                    $segments[] = implode("\n", $lines);
                }
                break;

            case 'table':
                $headers = $block['table']['headers'] ?? [];
                $rows = $block['table']['rows'] ?? [];
                $tableLines = [];
                if ($headers !== []) {
                    $tableLines[] = '| ' . implode(' | ', array_map('trim', $headers)) . ' |';
                    $tableLines[] = '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |';
                }
                foreach ($rows as $row) {
                    $tableLines[] = '| ' . implode(' | ', array_map(static fn($value) => trim((string) $value), $row)) . ' |';
                }
                if ($tableLines !== []) {
                    $segments[] = implode("\n", $tableLines);
                }
                break;

            case 'code':
                $language = trim((string) ($block['language'] ?? ''));
                $segments[] = '```' . $language . "\n" . trim((string) ($block['content'] ?? '')) . "\n```";
                break;
        }
    }

    return trim(implode("\n\n", array_filter($segments, static fn($value) => trim((string) $value) !== '')));
}

function syiAiParseNumericValue(mixed $value): ?float
{
    $text = trim((string) $value);
    if ($text === '') {
        return null;
    }

    $text = str_replace([',', '%'], '', $text);
    if (!preg_match('/-?\d+(?:\.\d+)?/', $text, $matches)) {
        return null;
    }

    return (float) $matches[0];
}

function syiAiIsTotalLikeLabel(string $label): bool
{
    $normalized = strtolower(trim($label));
    return in_array($normalized, ['total', 'grand total', 'overall total'], true);
}

function syiAiNearestHeadingText(array $blocks, int $index): ?string
{
    for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
        if (($blocks[$cursor]['type'] ?? '') !== 'heading') {
            continue;
        }

        $headingText = trim((string) ($blocks[$cursor]['text'] ?? ''));
        if ($headingText !== '') {
            return $headingText;
        }
    }

    return null;
}

function syiAiChartTitleFromHeading(?string $heading, ?int $chapterNumber, int $figureIndex): string
{
    $heading = trim((string) $heading);
    if ($heading === '') {
        $heading = 'Data Presentation';
    }

    $heading = preg_replace('/^\d+\.\d+\s+/u', '', $heading) ?? $heading;
    $heading = preg_replace('/^(table|figure)\s+\d+(?:\.\d+)?\s*:\s*/iu', '', $heading) ?? $heading;
    $heading = trim($heading);

    $prefix = $chapterNumber !== null ? $chapterNumber . '.' . $figureIndex : (string) $figureIndex;
    return 'Figure ' . $prefix . ': ' . ($heading !== '' ? $heading : 'Data Presentation');
}

function syiAiChartSpecFromTableBlock(array $tableBlock, ?string $headingText, ?int $chapterNumber, int $figureIndex): ?array
{
    $headers = array_values($tableBlock['table']['headers'] ?? []);
    $rows = array_values($tableBlock['table']['rows'] ?? []);
    if (count($headers) < 2 || count($rows) < 2) {
        return null;
    }

    $preferredColumns = ['frequency', 'count', 'mean', 'average', 'score', 'percent', 'valid percent'];
    $valueColumn = null;

    foreach ($preferredColumns as $preferredHeader) {
        foreach ($headers as $index => $header) {
            if (strtolower(trim((string) $header)) === $preferredHeader) {
                $valueColumn = $index;
                break 2;
            }
        }
    }

    if ($valueColumn === null) {
        foreach (range(1, count($headers) - 1) as $index) {
            $numericHits = 0;
            foreach ($rows as $row) {
                if (syiAiParseNumericValue($row[$index] ?? null) !== null) {
                    $numericHits++;
                }
            }
            if ($numericHits >= 2) {
                $valueColumn = $index;
                break;
            }
        }
    }

    if ($valueColumn === null) {
        return null;
    }

    $labels = [];
    $values = [];
    foreach ($rows as $row) {
        $label = trim((string) ($row[0] ?? ''));
        $value = syiAiParseNumericValue($row[$valueColumn] ?? null);
        if ($label === '' || syiAiIsTotalLikeLabel($label) || $value === null) {
            continue;
        }

        $labels[] = $label;
        $values[] = $value;
    }

    if (count($labels) < 2) {
        return null;
    }

    $labels = array_slice($labels, 0, 8);
    $values = array_slice($values, 0, 8);

    return [
        'chart_type' => 'bar',
        'title' => syiAiChartTitleFromHeading($headingText, $chapterNumber, $figureIndex),
        'data' => [
            'labels' => $labels,
            'values' => $values,
        ],
    ];
}

function syiAiGenerateFallbackChartInterpretation(array $chartSpec, array $job): string
{
    $labels = array_values($chartSpec['data']['labels'] ?? []);
    $values = array_values($chartSpec['data']['values'] ?? []);
    if ($labels === [] || $values === [] || count($labels) !== count($values)) {
        return '';
    }

    $maxIndex = array_keys($values, max($values))[0];
    $minIndex = array_keys($values, min($values))[0];
    $maxLabel = (string) $labels[$maxIndex];
    $minLabel = (string) $labels[$minIndex];
    $maxValue = (float) $values[$maxIndex];
    $minValue = (float) $values[$minIndex];
    $range = $maxValue - $minValue;
    $average = array_sum($values) / max(1, count($values));
    $topic = trim((string) ($job['project_topic'] ?? 'the study'));
    $sampleSize = max(0, (int) ((json_decode((string) ($job['dataset_summary_json'] ?? ''), true)['row_count'] ?? 100)));
    $measurementLabel = array_sum($values) > 105 ? 'frequency' : 'percentage';

    $sentences = [
        $chartSpec['title'] . ' presents a clear visual summary of the distribution observed in the study and complements the corresponding SPSS-style table by showing the relative strength of each response category in a form that is easier to compare at a glance. The chart makes it evident that the pattern is not evenly distributed across the categories, which means the respondents did not react to the issue under investigation in a uniform manner.',
        'The most prominent category in the chart is ' . $maxLabel . ', with a recorded ' . $measurementLabel . ' value of ' . rtrim(rtrim(number_format($maxValue, 2, '.', ''), '0'), '.') . ', while the least represented category is ' . $minLabel . ' with a value of ' . rtrim(rtrim(number_format($minValue, 2, '.', ''), '0'), '.') . '. The difference between these two positions is ' . rtrim(rtrim(number_format($range, 2, '.', ''), '0'), '.') . ', and this spread shows that the tendency of the respondents leans more strongly toward some options than others.',
        'When the bars are considered together, the overall average across the displayed categories is approximately ' . rtrim(rtrim(number_format($average, 2, '.', ''), '0'), '.') . '. This mean position suggests that the responses cluster around a moderate central tendency, but the leading categories still stand out enough to indicate meaningful variation in how the respondents perceive the issue. In practical terms, the chart confirms that the distribution is patterned rather than random.',
        'Within the context of ' . $topic . ', this visual pattern is academically important because it points to the dimensions of the problem that are most visible to the respondents. Where the highest bar reflects stronger agreement, participation, prevalence, or occurrence, it indicates the aspect of the study that is likely exerting the strongest influence on the observed outcome. Where the lowest bar appears, it reflects the aspect that is less dominant, less frequent, or less accepted among the respondents.',
        'From an interpretive standpoint, the chart also helps to validate the narrative evidence already presented in the chapter because the visual ordering of the bars supports the conclusion that the respondents are not divided evenly on the issue. The shape of the distribution suggests a clear tendency in opinion or experience, and this strengthens the reliability of the descriptive analysis for the study. It also provides a useful bridge between the tabular results and the inferential conclusions drawn later in the chapter.',
        'For a sample size of about ' . ($sampleSize > 0 ? $sampleSize : 100) . ' respondents, the pattern shown in the chart is sufficiently strong to justify meaningful academic discussion. The visual result suggests that any intervention, recommendation, or policy response arising from this study should pay more attention to the dominant categories represented by the taller bars, while also addressing the weaker categories so that the overall situation can improve in a balanced way. Consequently, the chart reinforces the conclusion that the variable represented here makes a noticeable contribution to the broader findings of the study.',
    ];

    $interpretation = 'Graph Interpretation: ' . implode(' ', $sentences);
    while (syiAiCountWords($interpretation) < 300) {
        $interpretation .= ' This extended interpretation further shows that the visual evidence aligns with the empirical direction of the study and supports a careful academic conclusion based on the observed distribution of responses.';
    }

    return trim($interpretation);
}

function syiAiEnsureGraphBlocks(array $job, string $markdown): string
{
    if (empty($job['include_graphs'])) {
        return $markdown;
    }

    $blocks = syiAiMarkdownBlocks($markdown);
    if ($blocks === []) {
        return $markdown;
    }

    $chartCount = 0;
    foreach ($blocks as $block) {
        if (($block['type'] ?? '') !== 'code' || strtolower((string) ($block['language'] ?? '')) !== 'json') {
            continue;
        }

        $decoded = json_decode((string) ($block['content'] ?? ''), true);
        if (is_array($decoded) && isset($decoded['chart_type'], $decoded['data'])) {
            $chartCount++;
        }
    }

    $maxFallbackCharts = 4;
    $injectedCharts = 0;
    $currentChapterNumber = null;
    $figureCounters = [];
    $augmentedBlocks = [];
    $count = count($blocks);

    for ($index = 0; $index < $count; $index++) {
        $block = $blocks[$index];
        $augmentedBlocks[] = $block;

        if (($block['type'] ?? '') === 'heading') {
            $detectedChapter = syiAiExtractChapterNumberFromHeading((string) ($block['text'] ?? ''));
            if ($detectedChapter !== null) {
                $currentChapterNumber = $detectedChapter;
            }
            continue;
        }

        if (($block['type'] ?? '') !== 'table' || $chartCount + $injectedCharts >= $maxFallbackCharts) {
            continue;
        }

        $nextBlock = $blocks[$index + 1] ?? null;
        if (($nextBlock['type'] ?? '') === 'code' && strtolower((string) ($nextBlock['language'] ?? '')) === 'json') {
            $decoded = json_decode((string) ($nextBlock['content'] ?? ''), true);
            if (is_array($decoded) && isset($decoded['chart_type'], $decoded['data'])) {
                continue;
            }
        }

        $figureCounters[$currentChapterNumber ?? 0] = ($figureCounters[$currentChapterNumber ?? 0] ?? 0) + 1;
        $chartSpec = syiAiChartSpecFromTableBlock(
            $block,
            syiAiNearestHeadingText($blocks, $index),
            $currentChapterNumber,
            $figureCounters[$currentChapterNumber ?? 0]
        );

        if ($chartSpec === null) {
            continue;
        }

        $augmentedBlocks[] = [
            'type' => 'code',
            'language' => 'json',
            'content' => json_encode($chartSpec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];
        $augmentedBlocks[] = [
            'type' => 'paragraph',
            'text' => syiAiGenerateFallbackChartInterpretation($chartSpec, $job),
        ];
        $injectedCharts++;
    }

    if ($injectedCharts === 0) {
        return $markdown;
    }

    return syiAiMarkdownBlocksToString($augmentedBlocks);
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

function syiAiIsMajorAcademicHeading(string $text): bool
{
    return preg_match('/^(abstract|chapter\s+(four|4|five|5|six|6)\b)/i', trim($text)) === 1;
}

function syiAiChartFigureCaption(array $chartSpec, ?int $chapterNumber, int $figureIndex): string
{
    $title = trim((string) ($chartSpec['title'] ?? ''));
    $chartType = ucfirst(strtolower((string) ($chartSpec['chart_type'] ?? 'Chart')));
    $chapterPrefix = $chapterNumber !== null ? $chapterNumber . '.' . $figureIndex : (string) $figureIndex;

    if ($title !== '' && preg_match('/^figure\s+\d+(?:\.\d+)?:/i', $title) === 1) {
        return $title;
    }

    if ($title === '') {
        $title = $chartType . ' Presentation';
    }

    return 'Figure ' . $chapterPrefix . ': ' . $title;
}

function syiAiRenderDocx(array $blocks, array $charts, string $outputPath, string $documentTitle): void
{
    syiAiEnsureZipExtension('creating Word output');

    $phpWord = new PhpWord();
    $phpWord->getDocInfo()->setTitle($documentTitle);
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);
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
    $hasVisibleContent = false;
    $currentChapterNumber = null;
    $figureCounters = [];

    foreach ($blocks as $index => $block) {
        switch ($block['type']) {
            case 'heading':
                $level = min(3, (int) $block['level']);
                $text = (string) $block['text'];
                if ($hasVisibleContent && preg_match('/^chapter\s+(four|4|five|5|six|6)\b/i', $text) === 1) {
                    $section->addPageBreak();
                }
                $detectedChapter = syiAiExtractChapterNumberFromHeading($text);
                if ($detectedChapter !== null) {
                    $currentChapterNumber = $detectedChapter;
                }

                $fontSize = match ($level) {
                    1 => 16,
                    2 => 14,
                    default => 13,
                };

                $section->addText(
                    syiAiDocxSafeText($text),
                    ['name' => 'Times New Roman', 'size' => $fontSize, 'bold' => true],
                    [
                        'spaceAfter' => $level === 1 ? 240 : ($level === 2 ? 200 : 180),
                        'alignment' => syiAiIsMajorAcademicHeading($text)
                            ? \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                            : 'left',
                    ]
                );
                $hasVisibleContent = true;
                break;

            case 'paragraph':
                $section->addText(
                    syiAiDocxSafeText((string) $block['text']),
                    ['name' => 'Times New Roman', 'size' => 12],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'lineHeight' => 1.5, 'spaceAfter' => 180]
                );
                $hasVisibleContent = true;
                break;

            case 'list':
                foreach ($block['items'] as $item) {
                    $section->addListItem(
                        syiAiDocxSafeText((string) $item),
                        0,
                        ['name' => 'Times New Roman', 'size' => 12],
                        $block['ordered'] ? ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER] : []
                    );
                }
                $hasVisibleContent = true;
                break;

            case 'table':
                $table = $section->addTable('SyiAcademicTable');
                $headers = $block['table']['headers'] ?? [];
                if ($headers !== []) {
                    $table->addRow();
                    foreach ($headers as $header) {
                        $table->addCell(2200)->addText(syiAiDocxSafeText((string) $header), ['bold' => true, 'name' => 'Times New Roman']);
                    }
                }
                foreach (($block['table']['rows'] ?? []) as $row) {
                    $table->addRow();
                    foreach ($row as $cell) {
                        $table->addCell(2200)->addText(syiAiDocxSafeText((string) $cell), ['name' => 'Times New Roman']);
                    }
                }
                $section->addTextBreak(1);
                $hasVisibleContent = true;
                break;

            case 'code':
                if (isset($charts[$index])) {
                    $section->addImage($charts[$index]['path'], ['width' => 600, 'height' => 400]);
                    $chapterForFigure = $currentChapterNumber;
                    $figureCounters[$chapterForFigure ?? 0] = ($figureCounters[$chapterForFigure ?? 0] ?? 0) + 1;
                    $section->addText(
                        syiAiDocxSafeText(syiAiChartFigureCaption($charts[$index]['spec'], $chapterForFigure, $figureCounters[$chapterForFigure ?? 0])),
                        ['name' => 'Times New Roman', 'size' => 11, 'italic' => true],
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 100, 'spaceAfter' => 140]
                    );
                    $section->addTextBreak(1);
                    $hasVisibleContent = true;
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
    $mpdf->SetTitle($documentTitle);

    $html = '<style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.6; }
        h1, h2, h3 { font-family: "Times New Roman", serif; }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
        th, td { border: 1px solid #333; padding: 8px; vertical-align: top; }
        th { background: #e9ecef; }
        p { text-align: justify; }
        .chart { margin: 18px 0; text-align: center; }
        .chart img { max-width: 100%; height: auto; }
        .major-heading { text-align: center; }
        .figure-caption { text-align: center; font-style: italic; margin-top: 6px; margin-bottom: 14px; }
    </style>';
    $hasVisibleContent = false;
    $currentChapterNumber = null;
    $figureCounters = [];

    foreach ($blocks as $index => $block) {
        switch ($block['type']) {
            case 'heading':
                $level = min(3, (int) $block['level']);
                $text = (string) $block['text'];
                if ($hasVisibleContent && preg_match('/^chapter\s+(four|4|five|5|six|6)\b/i', $text) === 1) {
                    $html .= '<pagebreak />';
                }
                $detectedChapter = syiAiExtractChapterNumberFromHeading($text);
                if ($detectedChapter !== null) {
                    $currentChapterNumber = $detectedChapter;
                }

                $class = syiAiIsMajorAcademicHeading($text) ? ' class="major-heading"' : '';
                $html .= '<h' . $level . $class . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</h' . $level . '>';
                $hasVisibleContent = true;
                break;

            case 'paragraph':
                $html .= '<p>' . nl2br(htmlspecialchars((string) $block['text'], ENT_QUOTES, 'UTF-8')) . '</p>';
                $hasVisibleContent = true;
                break;

            case 'list':
                $tag = !empty($block['ordered']) ? 'ol' : 'ul';
                $html .= '<' . $tag . '>';
                foreach ($block['items'] as $item) {
                    $html .= '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $html .= '</' . $tag . '>';
                $hasVisibleContent = true;
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
                $hasVisibleContent = true;
                break;

            case 'code':
                if (isset($charts[$index])) {
                    $chapterForFigure = $currentChapterNumber;
                    $figureCounters[$chapterForFigure ?? 0] = ($figureCounters[$chapterForFigure ?? 0] ?? 0) + 1;
                    $caption = syiAiChartFigureCaption($charts[$index]['spec'], $chapterForFigure, $figureCounters[$chapterForFigure ?? 0]);
                    $html .= '<div class="chart"><img src="' . htmlspecialchars($charts[$index]['path'], ENT_QUOTES, 'UTF-8') . '" alt="Chart"></div>';
                    $html .= '<div class="figure-caption">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</div>';
                    $hasVisibleContent = true;
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

    $width = 1500;
    $height = 1000;
    $image = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 34, 34, 34);
    $blue = imagecolorallocate($image, 31, 119, 180);
    $green = imagecolorallocate($image, 44, 160, 44);
    $red = imagecolorallocate($image, 214, 39, 40);
    $orange = imagecolorallocate($image, 255, 127, 14);
    $gray = imagecolorallocate($image, 210, 210, 210);

    imagefill($image, 0, 0, $white);
    if (function_exists('imageantialias')) {
        imageantialias($image, true);
    }

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

function syiAiChartFontPath(): ?string
{
    static $resolved = false;
    static $fontPath = null;

    if ($resolved) {
        return $fontPath;
    }

    $resolved = true;
    $candidates = array_filter([
        syiAiEnv('AI_CHART_FONT_PATH'),
        'C:\\Windows\\Fonts\\arial.ttf',
        'C:\\Windows\\Fonts\\calibri.ttf',
        'C:\\Windows\\Fonts\\tahoma.ttf',
        'C:\\Windows\\Fonts\\times.ttf',
    ]);

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
            $fontPath = $candidate;
            break;
        }
    }

    return $fontPath;
}

function syiAiChartWriteText($image, string $text, int $x, int $y, int $color, int $fontSize = 14, string $align = 'left', float $angle = 0.0): void
{
    $text = trim($text);
    if ($text === '') {
        return;
    }

    $fontPath = syiAiChartFontPath();
    if ($fontPath !== null && function_exists('imagettfbbox') && function_exists('imagettftext')) {
        $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);
        if (is_array($bbox)) {
            $minX = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
            $maxX = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
            $textWidth = (int) abs($maxX - $minX);

            if ($align === 'center') {
                $x -= (int) round($textWidth / 2);
            } elseif ($align === 'right') {
                $x -= $textWidth;
            }
        }

        imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $text);
        return;
    }

    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);

    if ($align === 'center') {
        $x -= (int) round($textWidth / 2);
    } elseif ($align === 'right') {
        $x -= $textWidth;
    }

    imagestring($image, $font, $x, max(0, $y - 15), $text, $color);
}

function syiAiChartWrapLines(string $text, int $maxChars = 18, int $maxLines = 2): array
{
    $text = trim((string) (preg_replace('/\s+/', ' ', $text) ?? $text));
    if ($text === '') {
        return [];
    }

    $lines = explode("\n", wordwrap($text, $maxChars, "\n", true));
    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, 0, $maxLines);
        $lastIndex = $maxLines - 1;
        $lines[$lastIndex] = rtrim(substr($lines[$lastIndex], 0, max(1, $maxChars - 3)), '.') . '...';
    }

    return $lines;
}

function syiAiChartWriteWrappedText($image, string $text, int $centerX, int $topY, int $color, int $fontSize = 14, int $maxChars = 18, int $maxLines = 2): void
{
    $lineHeight = $fontSize + 6;

    foreach (syiAiChartWrapLines($text, $maxChars, $maxLines) as $index => $line) {
        syiAiChartWriteText(
            $image,
            $line,
            $centerX,
            $topY + $fontSize + ($index * $lineHeight),
            $color,
            $fontSize,
            'center'
        );
    }
}

function syiAiDrawBarChart($image, array $labels, array $values, int $barColor, int $textColor, int $gridColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $left = 150;
    $right = $width - 110;
    $top = 140;
    $bottom = $height - 240;
    $count = max(1, count($values));
    $maxValue = max(array_map(static fn($value) => (float) $value, $values));
    $maxValue = $maxValue > 0 ? $maxValue : 1;

    imagesetthickness($image, 2);
    imageline($image, $left, $top, $left, $bottom, $textColor);
    imageline($image, $left, $bottom, $right, $bottom, $textColor);
    imagesetthickness($image, 1);

    for ($step = 0; $step <= 5; $step++) {
        $y = $bottom - (int) (($bottom - $top) * ($step / 5));
        imageline($image, $left, $y, $right, $y, $gridColor);
        syiAiChartWriteText($image, (string) round(($maxValue / 5) * $step, 2), $left - 20, $y + 6, $textColor, 16, 'right');
    }

    $chartWidth = $right - $left;
    $slot = (int) ($chartWidth / $count);
    $barWidth = max(28, (int) ($slot * 0.58));

    foreach ($values as $index => $value) {
        $numericValue = (float) $value;
        $x1 = $left + ($slot * $index) + (int) (($slot - $barWidth) / 2);
        $x2 = $x1 + $barWidth;
        $barHeight = (int) (($numericValue / $maxValue) * ($bottom - $top - 10));
        $y1 = $bottom - $barHeight;
        imagefilledrectangle($image, $x1, $y1, $x2, $bottom - 1, $barColor);
        syiAiChartWriteText($image, (string) round($numericValue, 2), (int) (($x1 + $x2) / 2), max($top + 18, $y1 - 12), $textColor, 15, 'center');
        syiAiChartWriteWrappedText($image, (string) ($labels[$index] ?? 'Item'), (int) (($x1 + $x2) / 2), $bottom + 20, $textColor, 14, 16, 3);
    }
}

function syiAiDrawLineChart($image, array $labels, array $values, int $lineColor, int $textColor, int $gridColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $left = 150;
    $right = $width - 110;
    $top = 140;
    $bottom = $height - 240;
    $count = max(2, count($values));
    $maxValue = max(array_map(static fn($value) => (float) $value, $values));
    $maxValue = $maxValue > 0 ? $maxValue : 1;

    imagesetthickness($image, 2);
    imageline($image, $left, $top, $left, $bottom, $textColor);
    imageline($image, $left, $bottom, $right, $bottom, $textColor);
    imagesetthickness($image, 1);

    for ($step = 0; $step <= 5; $step++) {
        $y = $bottom - (int) (($bottom - $top) * ($step / 5));
        imageline($image, $left, $y, $right, $y, $gridColor);
        syiAiChartWriteText($image, (string) round(($maxValue / 5) * $step, 2), $left - 20, $y + 6, $textColor, 16, 'right');
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

        imagefilledellipse($image, $x, $y, 18, 18, $lineColor);
        syiAiChartWriteWrappedText($image, (string) ($labels[$index] ?? 'Item'), $x, $bottom + 20, $textColor, 14, 16, 3);
        syiAiChartWriteText($image, (string) round($numericValue, 2), $x, max($top + 18, $y - 18), $textColor, 15, 'center');
        $previousPoint = ['x' => $x, 'y' => $y];
    }
}

function syiAiDrawPieChart($image, array $labels, array $values, array $palette, int $textColor): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $centerX = (int) ($width * 0.34);
    $centerY = (int) ($height * 0.53);
    $diameter = 460;
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
    $legendY = 180;

    foreach ($labels as $index => $label) {
        $color = $palette[$index % count($palette)];
        imagefilledrectangle($image, $legendX, $legendY, $legendX + 30, $legendY + 22, $color);
        $value = (float) ($values[$index] ?? 0);
        $percentage = round(($value / $total) * 100, 2);
        syiAiChartWriteWrappedText(
            $image,
            (string) $label . ' (' . $percentage . '%)',
            $legendX + 170,
            $legendY - 6,
            $textColor,
            15,
            24,
            2
        );
        $legendY += 62;
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
            $mailer->Timeout = max(5, (int) syiAiEnv('MAIL_TIMEOUT', '30'));
            $mailer->CharSet = 'UTF-8';
            $encryption = strtolower((string) syiAiEnv('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mailer->SMTPAutoTLS = false;
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
