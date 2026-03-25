<?php
declare(strict_types=1);

ob_start();
session_start();

require('../db/config.php');
require('../db/functions.php');
require('../includes/ai_automation.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php?redirect=admin/admin_settings.php');
    exit;
}

$currentUser = getCurrentUser();
if ((string) ($currentUser['role'] ?? '') !== 'admin') {
    header('Location:../login.php?redirect=admin/admin_settings.php');
    exit;
}

$projectRoot = dirname(__DIR__);
$errorMessage = '';
$successMessage = syiAiGetFlash('success') ?? '';
$jobs = [];
$selectedJob = null;
$selectedJobUuid = trim((string) ($_GET['job'] ?? ''));

try {
    syiAiLoadDependencies($projectRoot);
} catch (Throwable $throwable) {
    $errorMessage = $throwable->getMessage();
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
            $targetPages = (int) ($_POST['target_pages'] ?? 50);
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

        header('Location: admin_settings.php?job=' . urlencode($jobUuid));
        exit;
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

try {
    $jobsStatement = $pdo->query('SELECT * FROM student_jobs ORDER BY created_at DESC LIMIT 50');
    $jobs = $jobsStatement->fetchAll();

    if ($selectedJobUuid !== '') {
        $selectedStatement = $pdo->prepare('SELECT * FROM student_jobs WHERE job_uuid = ? LIMIT 1');
        $selectedStatement->execute([$selectedJobUuid]);
        $selectedJob = $selectedStatement->fetch() ?: null;
    } elseif ($jobs !== []) {
        $selectedJob = $jobs[0];
        $selectedJobUuid = (string) $selectedJob['job_uuid'];
    }
} catch (Throwable $throwable) {
    if ($errorMessage === '') {
        $errorMessage = $throwable->getMessage();
    }
}

$datasetSummary = $selectedJob && !empty($selectedJob['dataset_summary_json'])
    ? json_decode((string) $selectedJob['dataset_summary_json'], true)
    : null;

$jobCounts = [
    'uploaded' => 0,
    'configured' => 0,
    'generating' => 0,
    'ready' => 0,
];

foreach ($jobs as $jobItem) {
    $status = (string) ($jobItem['status'] ?? '');
    if (array_key_exists($status, $jobCounts)) {
        $jobCounts[$status]++;
    }
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

function syiAiRequestIdFromJobUuid(string $jobUuid): ?int
{
    if (preg_match('/^analytics-request-(\d+)$/', $jobUuid, $matches) !== 1) {
        return null;
    }

    return (int) $matches[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>AI Review Queue - SYi - Tech Global Services</title>
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

    .queue-table td,
    .queue-table th {
      vertical-align: middle;
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
            <h3 class="fw-bold mb-3">AI Review Queue</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">AI Review Queue</a></li>
            </ul>
          </div>

          <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
          <?php endif; ?>

          <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
          <?php endif; ?>

          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-warning bubble-shadow-small">
                        <i class="fas fa-upload"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Uploaded</p>
                        <h4 class="card-title"><?php echo $jobCounts['uploaded']; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-sliders-h"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Configured</p>
                        <h4 class="card-title"><?php echo $jobCounts['configured']; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-cogs"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Generating</p>
                        <h4 class="card-title"><?php echo $jobCounts['generating']; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-check-circle"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Ready</p>
                        <h4 class="card-title"><?php echo $jobCounts['ready']; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-7">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex align-items-center">
                    <h4 class="card-title">Generated Job Queue</h4>
                    <a href="analytics_request.php" class="btn btn-primary btn-round ms-auto">
                      <i class="fa fa-list"></i>
                      Request Queue
                    </a>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="display table table-striped table-hover queue-table">
                      <thead>
                        <tr>
                          <th>Student</th>
                          <th>Topic</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if ($jobs === []): ?>
                          <tr>
                            <td colspan="4" class="text-center text-muted">No jobs found.</td>
                          </tr>
                        <?php endif; ?>

                        <?php foreach ($jobs as $jobItem): ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlspecialchars((string) $jobItem['student_name']); ?></strong><br>
                              <small class="text-muted"><?php echo htmlspecialchars((string) $jobItem['student_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(syiAiTruncate((string) $jobItem['project_topic'], 70)); ?></td>
                            <td>
                              <span class="badge bg-<?php echo syiAiAdminBadge((string) $jobItem['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst((string) $jobItem['status'])); ?>
                              </span>
                            </td>
                            <td>
                              <a class="btn btn-primary btn-sm" href="admin_settings.php?job=<?php echo urlencode((string) $jobItem['job_uuid']); ?>">
                                Open
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-5">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Selected Job</h4>
                </div>
                <div class="card-body">
                  <?php if (!$selectedJob): ?>
                    <p class="text-muted mb-0">Pick a job from the queue to continue.</p>
                  <?php else: ?>
                    <?php $selectedRequestId = syiAiRequestIdFromJobUuid((string) $selectedJob['job_uuid']); ?>
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(syiAiCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="job_uuid" value="<?php echo htmlspecialchars((string) $selectedJob['job_uuid'], ENT_QUOTES, 'UTF-8'); ?>">

                      <div class="mb-3">
                        <strong><?php echo htmlspecialchars((string) $selectedJob['student_name']); ?></strong>
                        <div class="text-muted"><?php echo htmlspecialchars((string) $selectedJob['project_topic']); ?></div>
                      </div>

                      <div class="form-group mb-3">
                        <label for="degree_level" class="form-label">Degree Level</label>
                        <select class="form-control" id="degree_level" name="degree_level" required>
                          <?php foreach (['NCE/ND', 'BSc/HND', 'PGD', 'MSc/MPhil', 'PhD'] as $option): ?>
                            <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedJob['degree_level'] === $option ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($option); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="target_pages" class="form-label">Pages</label>
                        <select class="form-control" id="target_pages" name="target_pages" required>
                          <?php foreach ([30, 50, 100] as $pageCount): ?>
                            <option value="<?php echo $pageCount; ?>" <?php echo (int) $selectedJob['target_pages'] === $pageCount ? 'selected' : ''; ?>>
                              <?php echo $pageCount; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="include_graphs" class="form-label">Graphs</label>
                        <select class="form-control" id="include_graphs" name="include_graphs" required>
                          <option value="1" <?php echo (int) $selectedJob['include_graphs'] === 1 ? 'selected' : ''; ?>>Yes</option>
                          <option value="0" <?php echo (int) $selectedJob['include_graphs'] === 0 ? 'selected' : ''; ?>>No</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="hypothesis_mode" class="form-label">Hypothesis Testing</label>
                        <select class="form-control" id="hypothesis_mode" name="hypothesis_mode" required>
                          <option value="yes" <?php echo $selectedJob['hypothesis_mode'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                          <option value="auto-detect" <?php echo $selectedJob['hypothesis_mode'] === 'auto-detect' ? 'selected' : ''; ?>>Auto-detect</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="output_format" class="form-label">Output Format</label>
                        <select class="form-control" id="output_format" name="output_format" required>
                          <option value="word" <?php echo $selectedJob['output_format'] === 'word' ? 'selected' : ''; ?>>Word</option>
                          <option value="pdf" <?php echo $selectedJob['output_format'] === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        </select>
                      </div>

                      <div class="form-group mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Optional notes for the generation run"><?php echo htmlspecialchars((string) ($selectedJob['admin_notes'] ?? '')); ?></textarea>
                      </div>

                      <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary" type="submit" name="save_settings" value="1">Save Settings</button>
                        <?php if ($selectedRequestId): ?>
                          <a class="btn btn-success" href="view_request.php?id=<?php echo $selectedRequestId; ?>">Open Request</a>
                        <?php else: ?>
                          <a class="btn btn-success" href="generate.php?job=<?php echo urlencode((string) $selectedJob['job_uuid']); ?>">Generate Now</a>
                        <?php endif; ?>
                        <?php if ((string) $selectedJob['status'] === 'ready'): ?>
                          <button class="btn btn-dark" type="submit" name="mark_reviewed" value="1">Mark Reviewed</button>
                        <?php endif; ?>
                      </div>
                    </form>

                    <?php if (!empty($selectedJob['download_name']) && in_array((string) $selectedJob['status'], ['ready', 'reviewed'], true)): ?>
                      <hr>
                      <p class="mb-0">
                        <strong>Secure Link:</strong>
                        <a href="../downloads/<?php echo rawurlencode((string) $selectedJob['download_name']); ?>">
                          ../downloads/<?php echo htmlspecialchars((string) $selectedJob['download_name']); ?>
                        </a>
                      </p>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <?php if ($selectedJob): ?>
            <div class="row">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h4 class="card-title">Extracted Methodology</h4>
                  </div>
                  <div class="card-body">
                    <div class="summary-box"><?php echo nl2br(htmlspecialchars(syiAiTruncate((string) ($selectedJob['methodology_text'] ?? 'No methodology extracted yet.'), 7000))); ?></div>
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
