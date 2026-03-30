<?php
declare(strict_types=1);

ob_start();
session_start();

require('../db/config.php');
require('../db/functions.php');
require('../includes/ai_automation.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php?redirect=admin/job_details.php');
    exit;
}

$currentUser = getCurrentUser();
if ((string) ($currentUser['role'] ?? '') !== 'admin') {
    header('Location:../login.php?redirect=admin/job_details.php');
    exit;
}

$projectRoot = dirname(__DIR__);
$errorMessage = '';
$successMessage = syiAiGetFlash('success') ?? '';
$jobUuid = trim((string) ($_GET['job'] ?? ''));
$job = null;

try {
    syiAiLoadDependencies($projectRoot);
} catch (Throwable $throwable) {
    $errorMessage = $throwable->getMessage();
}

function syiAiFormatDateTime(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'N/A';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('M d, Y H:i', $timestamp);
}

function syiAiRequestIdFromJobUuid(string $jobUuid): ?int
{
    if (preg_match('/^analytics-request-(\d+)$/', $jobUuid, $matches) !== 1) {
        return null;
    }

    return (int) $matches[1];
}

function syiAiAdminBadge(string $status): string
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage === '') {
    try {
        if (!syiAiValidateCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Invalid admin form token. Please retry.');
        }

        $jobUuid = trim((string) ($_POST['job_uuid'] ?? ''));
        if ($jobUuid === '') {
            throw new RuntimeException('Job reference is missing.');
        }

        if (isset($_POST['save_settings'])) {
            $degreeLevel = (string) ($_POST['degree_level'] ?? 'BSc/HND');
            $recommendedPages = syiAiRecommendedPagesForDegree($degreeLevel);
            $targetPages = (int) ($_POST['target_pages'] ?? $recommendedPages);
            if (!in_array($targetPages, [30, 50, 70, 100], true)) {
                $targetPages = $recommendedPages;
            }
            $includeGraphs = ($_POST['include_graphs'] ?? '1') === '1' ? 1 : 0;
            $hypothesisMode = ($_POST['hypothesis_mode'] ?? 'auto-detect') === 'yes' ? 'yes' : 'auto-detect';
            $outputFormat = ($_POST['output_format'] ?? 'word') === 'pdf' ? 'pdf' : 'word';
            $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));

            $update = $pdo->prepare(
                'UPDATE student_jobs
                 SET degree_level = ?, target_pages = ?, include_graphs = ?, hypothesis_mode = ?, output_format = ?, admin_notes = ?, status = ?, configured_by = ?
                 WHERE job_uuid = ?'
            );
            $update->execute([
                $degreeLevel,
                $targetPages,
                $includeGraphs,
                $hypothesisMode,
                $outputFormat,
                $adminNotes,
                'configured',
                (int) $currentUser['id'],
                $jobUuid,
            ]);

            syiAiSetFlash('success', 'AI settings saved successfully.');
        }

        if (isset($_POST['mark_reviewed'])) {
            $review = $pdo->prepare(
                'UPDATE student_jobs SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE job_uuid = ? AND status = ?'
            );
            $review->execute(['reviewed', (int) $currentUser['id'], $jobUuid, 'ready']);
            syiAiSetFlash('success', 'Job marked as reviewed.');
        }

        header('Location: job_details.php?job=' . urlencode($jobUuid));
        exit;
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

try {
    if ($jobUuid === '') {
        throw new RuntimeException('Job reference is required.');
    }

    $stmt = $pdo->prepare('SELECT * FROM student_jobs WHERE job_uuid = ? LIMIT 1');
    $stmt->execute([$jobUuid]);
    $job = $stmt->fetch() ?: null;
} catch (Throwable $throwable) {
    if ($errorMessage === '') {
        $errorMessage = $throwable->getMessage();
    }
}

$datasetSummary = $job && !empty($job['dataset_summary_json'])
    ? json_decode((string) $job['dataset_summary_json'], true)
    : null;
$selectedDegreeLevel = $job && isset($job['degree_level'])
    ? (string) $job['degree_level']
    : 'BSc/HND';
$selectedTargetPages = $job && isset($job['target_pages'])
    ? (int) $job['target_pages']
    : syiAiRecommendedPagesForDegree($selectedDegreeLevel);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Job Details - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
  <style>
    .summary-box {
      background: #0f172a;
      color: #e2e8f0;
      border-radius: 12px;
      padding: 16px;
      max-height: 360px;
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
            <h3 class="fw-bold mb-3">Job Details</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="admin_settings.php">AI Review Queue</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Job Details</a></li>
            </ul>
          </div>

          <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
          <?php endif; ?>

          <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
          <?php endif; ?>

          <?php if (!$job): ?>
            <div class="alert alert-warning">Job not found.</div>
          <?php else: ?>
            <?php $selectedRequestId = syiAiRequestIdFromJobUuid((string) $job['job_uuid']); ?>
            <div class="row">
              <div class="col-md-5">
                <div class="card">
                  <div class="card-header">
                    <h4 class="card-title">Job Overview</h4>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <strong><?php echo htmlspecialchars((string) $job['student_name']); ?></strong>
                      <div class="text-muted"><?php echo htmlspecialchars((string) $job['student_email']); ?></div>
                    </div>
                    <div class="mb-3">
                      <div class="text-muted">Project Topic</div>
                      <div><?php echo htmlspecialchars((string) $job['project_topic']); ?></div>
                    </div>
                    <ul class="list-group mb-3">
                      <li class="list-group-item"><strong>Status:</strong>
                        <span class="badge bg-<?php echo syiAiAdminBadge((string) $job['status']); ?>">
                          <?php echo htmlspecialchars(ucfirst((string) $job['status'])); ?>
                        </span>
                      </li>
                      <li class="list-group-item"><strong>Submission Mode:</strong> <?php echo htmlspecialchars((string) ($job['submission_mode'] ?? 'N/A')); ?></li>
                      <li class="list-group-item"><strong>Created:</strong> <?php echo htmlspecialchars(syiAiFormatDateTime((string) ($job['created_at'] ?? ''))); ?></li>
                      <li class="list-group-item"><strong>Updated:</strong> <?php echo htmlspecialchars(syiAiFormatDateTime((string) ($job['updated_at'] ?? ''))); ?></li>
                      <li class="list-group-item"><strong>Generated:</strong> <?php echo htmlspecialchars(syiAiFormatDateTime((string) ($job['generated_at'] ?? ''))); ?></li>
                      <li class="list-group-item"><strong>Reviewed:</strong> <?php echo htmlspecialchars(syiAiFormatDateTime((string) ($job['reviewed_at'] ?? ''))); ?></li>
                      <li class="list-group-item"><strong>Download:</strong>
                        <?php if (!empty($job['download_name'])): ?>
                          <a href="../downloads/<?php echo rawurlencode((string) $job['download_name']); ?>">
                            <?php echo htmlspecialchars((string) $job['download_name']); ?>
                          </a>
                        <?php else: ?>
                          <span class="text-muted">Not available</span>
                        <?php endif; ?>
                      </li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2">
                      <a class="btn btn-primary" href="generate.php?job=<?php echo urlencode((string) $job['job_uuid']); ?>">Open Generation</a>
                      <?php if ($selectedRequestId): ?>
                        <a class="btn btn-success" href="view_request.php?id=<?php echo $selectedRequestId; ?>">Open Request</a>
                      <?php endif; ?>
                      <a class="btn btn-outline-secondary" href="admin_settings.php">Back to Queue</a>
                    </div>

                    <?php if (!empty($job['error_message'])): ?>
                      <div class="alert alert-danger mt-3"><?php echo nl2br(htmlspecialchars((string) $job['error_message'])); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="col-md-7">
                <div class="card">
                  <div class="card-header">
                    <h4 class="card-title">AI Settings & Actions</h4>
                  </div>
                  <div class="card-body">
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(syiAiCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="job_uuid" value="<?php echo htmlspecialchars((string) $job['job_uuid'], ENT_QUOTES, 'UTF-8'); ?>">

                      <div class="form-group mb-3">
                        <label for="degree_level" class="form-label">Degree Level</label>
                        <select class="form-control" id="degree_level" name="degree_level" required>
                          <?php foreach (['NCE/ND', 'BSc/HND', 'PGD', 'MSc/MPhil', 'PhD'] as $option): ?>
                            <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedDegreeLevel === $option ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($option); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="target_pages" class="form-label">Pages</label>
                        <select class="form-control" id="target_pages" name="target_pages" required>
                          <?php foreach ([30, 50, 70, 100] as $pageCount): ?>
                            <option value="<?php echo $pageCount; ?>" <?php echo $selectedTargetPages === $pageCount ? 'selected' : ''; ?>>
                              <?php echo $pageCount; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-2">Recommended pages by degree: BSc/HND 30, MSc/MPhil 70, PhD 100.</small>
                      </div>

                      <div class="form-group mb-3">
                        <label class="form-label">Graphs</label>
                        <select class="form-control" name="include_graphs">
                          <option value="1" <?php echo ((int) ($job['include_graphs'] ?? 1) === 1) ? 'selected' : ''; ?>>Yes</option>
                          <option value="0" <?php echo ((int) ($job['include_graphs'] ?? 1) === 0) ? 'selected' : ''; ?>>No</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label class="form-label">Hypothesis Testing</label>
                        <select class="form-control" name="hypothesis_mode">
                          <option value="yes" <?php echo (($job['hypothesis_mode'] ?? 'auto-detect') === 'yes') ? 'selected' : ''; ?>>Yes</option>
                          <option value="auto-detect" <?php echo (($job['hypothesis_mode'] ?? 'auto-detect') === 'auto-detect') ? 'selected' : ''; ?>>Auto-detect</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label class="form-label">Output Format</label>
                        <select class="form-control" name="output_format">
                          <option value="word" <?php echo (($job['output_format'] ?? 'word') === 'word') ? 'selected' : ''; ?>>Word</option>
                          <option value="pdf" <?php echo (($job['output_format'] ?? 'word') === 'pdf') ? 'selected' : ''; ?>>PDF</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="4"><?php echo htmlspecialchars((string) ($job['admin_notes'] ?? '')); ?></textarea>
                      </div>

                      <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" name="save_settings" value="1">Save Settings</button>
                        <?php if ((string) $job['status'] === 'ready'): ?>
                          <button class="btn btn-dark" type="submit" name="mark_reviewed" value="1">Mark Reviewed</button>
                        <?php endif; ?>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h4 class="card-title">Extracted Methodology</h4>
                  </div>
                  <div class="card-body">
                    <div class="summary-box"><?php echo nl2br(htmlspecialchars(syiAiTruncate((string) ($job['methodology_text'] ?? 'No methodology extracted yet.'), 7000))); ?></div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h4 class="card-title">Dataset Summary</h4>
                  </div>
                  <div class="card-body">
                    <div class="summary-box"><?php echo htmlspecialchars(json_encode($datasetSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'No dataset summary available.'); ?></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php include('nav/footer.php'); ?>
    </div>
  </div>
</body>
</html>
