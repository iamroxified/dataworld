<?php
declare(strict_types=1);

ob_start();
session_start();

require('../db/config.php');
require('../db/functions.php');
require('../includes/ai_automation.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php?redirect=admin/generate.php');
    exit;
}

$currentUser = getCurrentUser();
if ((string) ($currentUser['role'] ?? '') !== 'admin') {
    header('Location:../login.php?redirect=admin/generate.php');
    exit;
}

$projectRoot = dirname(__DIR__);
$errorMessage = '';
$job = null;
$downloadUrl = null;
$backUrl = 'admin_settings.php';
$backLabel = 'Back to AI Review Queue';
$jobUuid = trim((string) ($_GET['job'] ?? $_POST['job_uuid'] ?? ''));

try {
    syiAiLoadDependencies($projectRoot);

    if ($jobUuid === '') {
        throw new RuntimeException('A job reference is required before generation can start.');
    }

    $statement = $pdo->prepare('SELECT * FROM student_jobs WHERE job_uuid = ? LIMIT 1');
    $statement->execute([$jobUuid]);
    $job = $statement->fetch();

    if (!$job) {
        throw new RuntimeException('The selected job could not be found.');
    }

    if (preg_match('/^analytics-request-(\d+)$/', (string) $job['job_uuid'], $matches) === 1) {
        $backUrl = 'view_request.php?id=' . (int) $matches[1];
        $backLabel = 'Back to Request';
    } else {
        $backUrl = 'admin_settings.php?job=' . urlencode((string) $job['job_uuid']);
    }

    $pdo->prepare('UPDATE student_jobs SET status = ?, error_message = NULL WHERE job_uuid = ?')
        ->execute(['generating', $jobUuid]);

    $client = syiAiCreateClient();

    if ((string) $job['submission_mode'] === 'topic_only' && trim((string) ($job['chapter_outline_markdown'] ?? '')) === '') {
        $outline = syiAiGenerateTopicOutline($client, $job);
        $methodology = syiAiExtractMethodology($outline);

        $pdo->prepare('UPDATE student_jobs SET chapter_outline_markdown = ?, chapters_text = ?, methodology_text = ? WHERE job_uuid = ?')
            ->execute([$outline, $outline, $methodology, $jobUuid]);

        $statement->execute([$jobUuid]);
        $job = $statement->fetch();
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

    $update = $pdo->prepare(
        'UPDATE student_jobs
         SET system_prompt = ?, user_prompt = ?, ai_markdown = ?, generated_file_name = ?, generated_file_path = ?,
             download_name = ?, download_expires_at = ?, status = ?, generated_at = NOW(), email_sent_at = ?, error_message = NULL
         WHERE job_uuid = ?'
    );

    $update->execute([
        $generation['system_prompt'],
        $generation['user_prompt'],
        $generation['markdown'],
        $downloadName,
        $relativeOutputPath,
        $downloadName,
        $expiryDate,
        'ready',
        $emailSent ? date('Y-m-d H:i:s') : null,
        $jobUuid,
    ]);

    $statement->execute([$jobUuid]);
    $job = $statement->fetch();
} catch (Throwable $throwable) {
    $errorMessage = $throwable->getMessage();

    if ($jobUuid !== '') {
        $fail = $pdo->prepare('UPDATE student_jobs SET status = ?, error_message = ? WHERE job_uuid = ?');
        $fail->execute(['failed', $errorMessage, $jobUuid]);
    }
}
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
                  <p class="mb-0 text-muted">This page runs the full automation flow from prompt construction to output generation and student notification.</p>
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
                <div class="alert alert-success">
                  Generation completed successfully for <strong><?php echo htmlspecialchars((string) $job['project_topic']); ?></strong>.
                </div>

                <div class="row">
                  <div class="col-md-6 mb-4">
                    <ul class="list-group">
                      <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars((string) $job['status']); ?></li>
                      <li class="list-group-item"><strong>Output:</strong> <?php echo strtoupper(htmlspecialchars((string) $job['output_format'])); ?></li>
                      <li class="list-group-item">
                        <strong>Download:</strong>
                        <a href="../downloads/<?php echo rawurlencode((string) $job['download_name']); ?>">
                          <?php echo htmlspecialchars((string) $job['download_name']); ?>
                        </a>
                      </li>
                      <li class="list-group-item"><strong>Email Notice:</strong> <?php echo $job['email_sent_at'] ? 'Sent' : 'Not sent'; ?></li>
                      <li class="list-group-item"><strong>Expiry:</strong> <?php echo htmlspecialchars((string) $job['download_expires_at']); ?></li>
                    </ul>
                  </div>
                  <div class="col-md-6 mb-4">
                    <div class="preview-box"><?php echo htmlspecialchars(syiAiTruncate((string) ($job['ai_markdown'] ?? ''), 9000)); ?></div>
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
</body>
</html>
