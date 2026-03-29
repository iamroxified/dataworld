<?php
declare(strict_types=1);

ob_start();
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../includes/ai_automation.php';

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

$projectRoot = dirname(__DIR__);
$jobUuid = trim((string) ($_GET['job'] ?? $_POST['job_uuid'] ?? ($argv[2] ?? '')));
$isPollRequest = isset($_GET['poll']) && $_GET['poll'] === '1';
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$isWorkerRequest = $requestMethod === 'POST'
    && (
        (string) ($_GET['worker'] ?? '') === '1'
        || (string) ($_POST['worker_start'] ?? '') === '1'
        || $isAjaxRequest
    );
$isCliWorker = PHP_SAPI === 'cli' && (string) ($argv[1] ?? '') === 'worker';

if (!$isCliWorker && !isset($_SESSION['user_id'])) {
    if ($isPollRequest || $isWorkerRequest) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => false,
            'message' => 'Your admin session has expired. Please log in again and retry.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location:../login.php?redirect=admin/generate.php');
    exit;
}

$currentUser = $isCliWorker ? null : getCurrentUser();
if (!$isCliWorker && (string) ($currentUser['role'] ?? '') !== 'admin') {
    if ($isPollRequest || $isWorkerRequest) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => false,
            'message' => 'Admin access is required for this request.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location:../login.php?redirect=admin/generate.php');
    exit;
}

$errorMessage = '';
$job = null;
$downloadUrl = null;
$backUrl = 'admin_settings.php';
$backLabel = 'Back to AI Review Queue';

function adminAiIsConnectionGone(Throwable $throwable): bool
{
    if (!$throwable instanceof PDOException) {
        return false;
    }

    $message = strtolower($throwable->getMessage());
    $driverCode = (string) ($throwable->errorInfo[1] ?? $throwable->getCode() ?? '');

    return in_array($driverCode, ['2006', '2013'], true)
        || str_contains($message, 'server has gone away')
        || str_contains($message, 'lost connection')
        || str_contains($message, 'error while sending query');
}

function adminAiReconnectPdo(): PDO
{
    global $host, $db, $user, $pass, $charset, $options, $pdo;

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
}

function adminAiRunWithReconnect(callable $callback, int $maxAttempts = 2)
{
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $attempt++;

        try {
            global $pdo;
            return $callback($pdo);
        } catch (Throwable $throwable) {
            if ($attempt >= $maxAttempts || !adminAiIsConnectionGone($throwable)) {
                throw $throwable;
            }

            adminAiReconnectPdo();
        }
    }

    throw new RuntimeException('Unable to complete the database operation.');
}

function adminAiFetchJob(string $jobUuid): ?array
{
    return adminAiRunWithReconnect(function (PDO $pdo) use ($jobUuid): ?array {
        $statement = $pdo->prepare('SELECT * FROM student_jobs WHERE job_uuid = ? LIMIT 1');
        $statement->execute([$jobUuid]);
        $row = $statement->fetch();
        return $row ?: null;
    });
}

function adminAiExecute(string $sql, array $params = []): void
{
    adminAiRunWithReconnect(function (PDO $pdo) use ($sql, $params): void {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
    });
}

function adminAiStorageExcerpt(string $text, int $maxBytes, string $notice): string
{
    $text = trim($text);
    if ($text === '' || strlen($text) <= $maxBytes) {
        return $text;
    }

    $suffix = "\n\n" . trim($notice);
    $sliceLength = max(0, $maxBytes - strlen($suffix));
    if (function_exists('mb_strcut')) {
        $trimmed = mb_strcut($text, 0, $sliceLength, 'UTF-8');
    } else {
        $trimmed = substr($text, 0, $sliceLength);
    }

    return rtrim((string) $trimmed) . $suffix;
}

function adminAiBuildDownloadUrl(?array $job): ?string
{
    if (!$job || empty($job['download_name'])) {
        return null;
    }

    return syiAiDownloadUrl(getBaseURL(), (string) $job['download_name']);
}

function adminAiJsonResponse(array $payload, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function adminAiFinishResponseAndContinue(array $payload, int $statusCode = 202): void
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        $body = '{"ok":false,"message":"Unable to encode response."}';
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    ignore_user_abort(true);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Connection: close');
    header('Content-Length: ' . strlen($body));
    echo $body;

    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
        return;
    }

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }

    flush();
}

function adminAiPersistFailure(string $jobUuid, string $message): void
{
    try {
        adminAiExecute(
            'UPDATE student_jobs SET status = ?, error_message = ? WHERE job_uuid = ?',
            [
                'failed',
                adminAiStorageExcerpt($message, 60000, '[Error message truncated for database storage.]'),
                $jobUuid,
            ]
        );
    } catch (Throwable $persistThrowable) {
        error_log('Unable to persist AI generation failure for job ' . $jobUuid . ': ' . $persistThrowable->getMessage());
    }
}

function adminAiFunctionEnabled(string $functionName): bool
{
    if (!function_exists($functionName)) {
        return false;
    }

    $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
    return !in_array($functionName, $disabled, true);
}

function adminAiCanUseExec(): bool
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return adminAiFunctionEnabled('popen') || adminAiFunctionEnabled('exec');
    }

    return adminAiFunctionEnabled('exec') || adminAiFunctionEnabled('popen');
}

function adminAiResolvePhpBinary(): ?string
{
    $candidates = [];

    $envBinary = trim((string) getenv('PHP_CLI_BINARY'));
    if ($envBinary !== '') {
        $candidates[] = $envBinary;
    }

    if (defined('PHP_BINARY') && PHP_BINARY !== '') {
        $candidates[] = PHP_BINARY;
    }

    if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
        $candidates[] = rtrim(PHP_BINDIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
    }

    if (defined('PHP_BINARY') && PHP_BINARY !== '') {
        $candidates[] = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
    }

    foreach (array_unique(array_filter($candidates)) as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate === '' || !is_file($candidate)) {
            continue;
        }

        $basename = strtolower((string) pathinfo($candidate, PATHINFO_BASENAME));
        if (str_contains($basename, 'httpd')) {
            continue;
        }

        if (!str_contains($basename, 'php')) {
            continue;
        }

        return $candidate;
    }

    return null;
}

function adminAiStartDetachedCliWorker(string $jobUuid): bool
{
    if (!adminAiCanUseExec()) {
        return false;
    }

    $phpBinary = adminAiResolvePhpBinary();
    if ($phpBinary === null) {
        error_log('AI generation worker could not resolve a PHP CLI binary.');
        return false;
    }

    $scriptPath = __FILE__;

    if (DIRECTORY_SEPARATOR === '\\') {
        $quote = static function (string $value): string {
            return '"' . str_replace('"', '""', $value) . '"';
        };

        $command = 'cmd /c start "" /B '
            . $quote($phpBinary)
            . ' '
            . $quote($scriptPath)
            . ' worker '
            . $quote($jobUuid);

        if (adminAiFunctionEnabled('popen')) {
            $handle = @popen($command, 'r');
            if ($handle === false) {
                return false;
            }

            @pclose($handle);
            return true;
        }

        if (adminAiFunctionEnabled('exec')) {
            @exec($command, $output, $resultCode);
            return !isset($resultCode) || (int) $resultCode === 0;
        }

        return false;
    }

    $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' worker ' . escapeshellarg($jobUuid) . ' > /dev/null 2>&1 &';
    if (adminAiFunctionEnabled('exec')) {
        @exec($command, $output, $resultCode);
        return !isset($resultCode) || (int) $resultCode === 0;
    }

    if (adminAiFunctionEnabled('popen')) {
        $handle = @popen($command, 'r');
        if ($handle === false) {
            return false;
        }

        @pclose($handle);
        return true;
    }

    return false;
}

function adminAiProcessJob(string $jobUuid, string $projectRoot): void
{
    try {
        $job = adminAiFetchJob($jobUuid);
        if (!$job) {
            throw new RuntimeException('The selected job could not be found.');
        }

        adminAiExecute(
            'UPDATE student_jobs SET status = ?, error_message = NULL WHERE job_uuid = ?',
            ['generating', $jobUuid]
        );

        $client = syiAiCreateClient();

        if ((string) $job['submission_mode'] === 'topic_only' && trim((string) ($job['chapter_outline_markdown'] ?? '')) === '') {
            $outline = syiAiGenerateTopicOutline($client, $job);
            $methodology = syiAiExtractMethodology($outline);

            adminAiExecute(
                'UPDATE student_jobs SET chapter_outline_markdown = ?, chapters_text = ?, methodology_text = ? WHERE job_uuid = ?',
                [$outline, $outline, $methodology, $jobUuid]
            );

            $job = adminAiFetchJob($jobUuid);
            if (!$job) {
                throw new RuntimeException('The selected job could not be reloaded after preparing the outline.');
            }
        }

        $generation = syiAiGenerateAcademicMarkdown($client, $job);
        $storage = syiAiEnsureStorage($projectRoot);

        $downloadName = syiAiIssueDownloadName($job);
        $outputPath = $storage['generated'] . DIRECTORY_SEPARATOR . $downloadName;
        $documentTitle = 'Project Analysis - ' . syiAiTruncate((string) $job['project_topic'], 90);
        syiAiRenderDocument($projectRoot, $generation['markdown'], $outputPath, $documentTitle);

        $relativeOutputPath = syiAiRelativePath($projectRoot, $outputPath);
        $expiryDays = max(1, (int) syiAiEnv('AI_DOWNLOAD_EXPIRY_DAYS', '14'));
        $expiryDate = (new DateTimeImmutable('now'))->modify('+' . $expiryDays . ' days')->format('Y-m-d H:i:s');
        $downloadUrl = syiAiDownloadUrl(getBaseURL(), $downloadName);

        $emailSent = syiAiSendReadyEmail(
            [
                'student_name' => $job['student_name'],
                'student_email' => $job['student_email'],
                'project_topic' => $job['project_topic'],
            ],
            $downloadUrl
        );

        $storedSystemPrompt = adminAiStorageExcerpt(
            (string) $generation['system_prompt'],
            32000,
            '[System prompt truncated for database storage.]'
        );
        $storedUserPrompt = adminAiStorageExcerpt(
            (string) $generation['user_prompt'],
            120000,
            '[Prompt log truncated for database storage.]'
        );
        $storedMarkdown = adminAiStorageExcerpt(
            (string) $generation['markdown'],
            450000,
            '[Markdown preview truncated for database storage. Full content remains in the generated file.]'
        );

        adminAiExecute(
            'UPDATE student_jobs
             SET system_prompt = ?, user_prompt = ?, ai_markdown = ?, generated_file_name = ?, generated_file_path = ?,
                 download_name = ?, download_expires_at = ?, status = ?, generated_at = NOW(), email_sent_at = ?, error_message = NULL
             WHERE job_uuid = ?',
            [
                $storedSystemPrompt,
                $storedUserPrompt,
                $storedMarkdown,
                $downloadName,
                $relativeOutputPath,
                $downloadName,
                $expiryDate,
                'ready',
                $emailSent ? date('Y-m-d H:i:s') : null,
                $jobUuid,
            ]
        );
    } catch (Throwable $throwable) {
        error_log('AI generation failed for job ' . $jobUuid . ': ' . $throwable->getMessage());
        adminAiPersistFailure($jobUuid, $throwable->getMessage());
    }
}

function adminAiStatusBadge(string $status): string
{
    return match ($status) {
        'uploaded' => 'warning',
        'configured' => 'info',
        'generating' => 'primary',
        'ready' => 'success',
        'reviewed' => 'dark',
        'failed' => 'danger',
        default => 'secondary',
    };
}

try {
    syiAiLoadDependencies($projectRoot);

    if ($jobUuid === '') {
        throw new RuntimeException('A job reference is required before generation can start.');
    }

    if ($isCliWorker) {
        adminAiProcessJob($jobUuid, $projectRoot);
        exit(0);
    }

    if ($isPollRequest) {
        $job = adminAiFetchJob($jobUuid);
        if (!$job) {
            adminAiJsonResponse([
                'ok' => false,
                'message' => 'The selected job could not be found.',
            ], 404);
        }

        adminAiJsonResponse([
            'ok' => true,
            'status' => (string) $job['status'],
            'generated_at' => $job['generated_at'] ?? null,
            'download_name' => $job['download_name'] ?? null,
            'download_url' => adminAiBuildDownloadUrl($job),
            'error_message' => $job['error_message'] ?? null,
        ]);
    }

    if ($isWorkerRequest) {
        if (!syiAiValidateCsrf($_POST['csrf_token'] ?? null)) {
            adminAiJsonResponse([
                'ok' => false,
                'message' => 'Invalid generation token. Please refresh the page and try again.',
            ], 422);
        }

        $job = adminAiFetchJob($jobUuid);
        if (!$job) {
            adminAiJsonResponse([
                'ok' => false,
                'message' => 'The selected job could not be found.',
            ], 404);
        }

        $jobStatus = (string) ($job['status'] ?? 'uploaded');
        $forceRestart = (string) ($_POST['force_restart'] ?? '') === '1';
        if (in_array($jobStatus, ['ready', 'reviewed'], true)) {
            adminAiJsonResponse([
                'ok' => true,
                'started' => false,
                'status' => $jobStatus,
                'message' => 'This job has already finished generating.',
            ]);
        }

        if ($jobStatus === 'generating' && !$forceRestart) {
            adminAiJsonResponse([
                'ok' => true,
                'started' => false,
                'status' => 'generating',
                'message' => 'Generation is already in progress for this job.',
            ]);
        }

        adminAiExecute(
            'UPDATE student_jobs SET status = ?, error_message = NULL WHERE job_uuid = ?',
            ['generating', $jobUuid]
        );

        if (adminAiStartDetachedCliWorker($jobUuid)) {
            adminAiJsonResponse([
                'ok' => true,
                'started' => true,
                'status' => 'generating',
                'message' => 'Generation started successfully in the background.',
                'runner' => 'cli',
            ], 202);
        }

        adminAiFinishResponseAndContinue([
            'ok' => true,
            'started' => true,
            'status' => 'generating',
            'message' => 'Generation started successfully. This page will keep checking for the result.',
            'runner' => 'http',
        ], 202);

        adminAiProcessJob($jobUuid, $projectRoot);
        exit;
    }

    $job = adminAiFetchJob($jobUuid);
    if (!$job) {
        throw new RuntimeException('The selected job could not be found.');
    }

    if (preg_match('/^analytics-request-(\d+)$/', (string) $job['job_uuid'], $matches) === 1) {
        $backUrl = 'view_request.php?id=' . (int) $matches[1];
        $backLabel = 'Back to Request';
    } else {
        $backUrl = 'admin_settings.php?job=' . urlencode((string) $job['job_uuid']);
    }

    $downloadUrl = adminAiBuildDownloadUrl($job);
} catch (Throwable $throwable) {
    if ($isPollRequest || $isWorkerRequest) {
        adminAiJsonResponse([
            'ok' => false,
            'message' => $throwable->getMessage(),
        ], 500);
    }

    $errorMessage = $throwable->getMessage();
}

$jobStatus = $job ? (string) ($job['status'] ?? 'uploaded') : '';
$shouldAutoStart = $job && in_array($jobStatus, ['configured', 'uploaded'], true);
$shouldPoll = $job && in_array($jobStatus, ['configured', 'uploaded', 'generating'], true);
$previewText = $job ? (string) ($job['ai_markdown'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Generate AI Output - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
  <style>
    .preview-box {
      background: #0f172a;
      color: #e2e8f0;
      border-radius: 12px;
      padding: 18px;
      max-height: 440px;
      overflow: auto;
      white-space: pre-wrap;
      word-break: break-word;
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <?php include('nav/sidebar.php'); ?>

    <div class="main-panel">
      <?php include('nav/header.php'); ?>
      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">AI Generation Result</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="admin_settings.php">AI Review Queue</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Generation Result</a></li>
            </ul>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <div>
                  <h4 class="card-title mb-1">AI Generation Result</h4>
                  <p class="mb-0 text-muted">The generator now starts quickly and keeps this page updated while the longer AI and document work runs in the background.</p>
                </div>
                <a class="btn btn-primary btn-round ms-auto" href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">
                  <i class="fa fa-caret-left"></i>
                  <?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </div>
            </div>
            <div class="card-body">
              <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
              <?php elseif ($job): ?>
                <div id="generation-feedback" class="alert alert-<?php echo in_array($jobStatus, ['ready', 'reviewed'], true) ? 'success' : ($jobStatus === 'failed' ? 'danger' : 'info'); ?>">
                  <?php if (in_array($jobStatus, ['ready', 'reviewed'], true)): ?>
                    Generation completed successfully for <strong><?php echo htmlspecialchars((string) $job['project_topic']); ?></strong>.
                  <?php elseif ($jobStatus === 'failed'): ?>
                    Generation failed. You can retry this job from this page.
                  <?php else: ?>
                    Generation has been queued for <strong><?php echo htmlspecialchars((string) $job['project_topic']); ?></strong>. This page will refresh automatically when the output is ready.
                  <?php endif; ?>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-4">
                    <ul class="list-group">
                      <li class="list-group-item">
                        <strong>Status:</strong>
                        <span class="badge bg-<?php echo adminAiStatusBadge($jobStatus); ?>">
                          <?php echo htmlspecialchars(ucfirst($jobStatus)); ?>
                        </span>
                      </li>
                      <li class="list-group-item"><strong>Output:</strong> <?php echo strtoupper(htmlspecialchars((string) $job['output_format'])); ?></li>
                      <li class="list-group-item"><strong>Model Target:</strong> <?php echo htmlspecialchars((string) ($job['degree_level'] ?? 'N/A')); ?></li>
                      <li class="list-group-item"><strong>Pages:</strong> <?php echo htmlspecialchars((string) ($job['target_pages'] ?? 'N/A')); ?></li>
                      <li class="list-group-item"><strong>Email Notice:</strong> <?php echo !empty($job['email_sent_at']) ? 'Sent' : 'Pending'; ?></li>
                      <li class="list-group-item"><strong>Generated:</strong> <?php echo htmlspecialchars((string) ($job['generated_at'] ?? 'Not yet generated')); ?></li>
                      <li class="list-group-item"><strong>Expiry:</strong> <?php echo htmlspecialchars((string) ($job['download_expires_at'] ?? 'Pending')); ?></li>
                      <li class="list-group-item">
                        <strong>Download:</strong>
                        <?php if (!empty($job['download_name'])): ?>
                          <a href="../downloads/<?php echo rawurlencode((string) $job['download_name']); ?>">
                            <?php echo htmlspecialchars((string) $job['download_name']); ?>
                          </a>
                        <?php else: ?>
                          <span class="text-muted">Waiting for generation</span>
                        <?php endif; ?>
                      </li>
                    </ul>

                    <?php if ($jobStatus === 'failed'): ?>
                      <div class="mt-3">
                        <button type="button" id="retry-generation" class="btn btn-warning" data-force="0">
                          Retry Generation
                        </button>
                      </div>
                    <?php endif; ?>

                    <?php if ($jobStatus === 'generating'): ?>
                      <div class="mt-3">
                        <button type="button" id="retry-generation" class="btn btn-outline-warning" data-force="1">
                          Restart Generation
                        </button>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($job['error_message'])): ?>
                      <div class="alert alert-danger mt-3 mb-0">
                        <?php echo nl2br(htmlspecialchars((string) $job['error_message'])); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6 mb-4">
                    <div class="preview-box"><?php echo htmlspecialchars($previewText !== '' ? syiAiTruncate($previewText, 9000) : 'The preview will appear here once the job is generated.'); ?></div>
                  </div>
                </div>

                <?php if ($downloadUrl): ?>
                  <div class="alert alert-info mb-0">
                    <strong>Public Secure Link:</strong>
                    <a href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>">
                      <?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php include('nav/footer.php'); ?>
    </div>
  </div>

  <?php if ($job && $errorMessage === ''): ?>
    <script>
      (function() {
        const jobUuid = <?php echo json_encode((string) $job['job_uuid']); ?>;
        const csrfToken = <?php echo json_encode(syiAiCsrfToken()); ?>;
        const shouldAutoStart = <?php echo $shouldAutoStart ? 'true' : 'false'; ?>;
        const endpointUrl = new URL(window.location.href);
        let pollTimer = null;

        function refreshPage() {
          window.location.reload();
        }

        function extractServerMessage(text) {
          const plain = (text || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

          if (plain !== '') {
            return plain.slice(0, 240);
          }

          return 'The server returned HTML instead of JSON. Check the latest generate.php deployment and your PHP error log.';
        }

        async function parseJsonResponse(response) {
          const text = await response.text();
          try {
            return {
              payload: JSON.parse(text),
              text
            };
          } catch (error) {
            throw new Error(extractServerMessage(text));
          }
        }

        function beginPolling() {
          if (pollTimer !== null) {
            return;
          }

          pollTimer = window.setInterval(async function() {
            try {
              const pollUrl = new URL(endpointUrl.href);
              pollUrl.searchParams.set('job', jobUuid);
              pollUrl.searchParams.set('poll', '1');

              const response = await fetch(pollUrl.toString(), {
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
              });

              if (!response.ok) {
                const text = await response.text();
                console.error('Polling error', extractServerMessage(text));
                return;
              }

              const { payload } = await parseJsonResponse(response);
              if (!payload || payload.ok !== true) {
                return;
              }

              if (payload.status === 'ready' || payload.status === 'reviewed' || payload.status === 'failed') {
                window.clearInterval(pollTimer);
                pollTimer = null;
                refreshPage();
              }
            } catch (error) {
              console.error('Polling error', error);
            }
          }, 5000);
        }

        async function startWorker(forceRestart = false) {
          try {
            const workerUrl = new URL(endpointUrl.href);
            workerUrl.searchParams.set('job', jobUuid);
            workerUrl.searchParams.set('worker', '1');

            const response = await fetch(workerUrl.toString(), {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: new URLSearchParams({
                job_uuid: jobUuid,
                worker_start: '1',
                force_restart: forceRestart ? '1' : '0',
                csrf_token: csrfToken
              }).toString()
            });

            const { payload } = await parseJsonResponse(response);
            if (!response.ok || !payload || payload.ok !== true) {
              throw new Error(payload && payload.message ? payload.message : 'Unable to start generation.');
            }

            beginPolling();
          } catch (error) {
            alert(error.message || 'Unable to start generation.');
          }
        }

        if (shouldAutoStart) {
          startWorker();
        } else if (<?php echo $shouldPoll ? 'true' : 'false'; ?>) {
          beginPolling();
        }

        const retryButton = document.getElementById('retry-generation');
        if (retryButton) {
          retryButton.addEventListener('click', function() {
            retryButton.disabled = true;
            const forceRestart = retryButton.dataset.force === '1';
            startWorker(forceRestart).finally(function() {
              retryButton.disabled = false;
            });
          });
        }
      })();
    </script>
  <?php endif; ?>
</body>
</html>
