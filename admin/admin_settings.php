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
$selectedDegreeLevel = $selectedJob && isset($selectedJob['degree_level'])
    ? (string) $selectedJob['degree_level']
    : 'BSc/HND';
$selectedTargetPages = $selectedJob && isset($selectedJob['target_pages'])
    ? (int) $selectedJob['target_pages']
    : syiAiRecommendedPagesForDegree($selectedDegreeLevel);

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
                    <table id="jobs-table" class="display table table-striped table-hover queue-table">
                      <thead>
                        <tr>
                          <th>Student</th>
                          <th>Topic</th>
                          <th>Status</th>
                          <th>Created</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if ($jobs === []): ?>
                          <tr>
                            <td colspan="5" class="text-center text-muted">No jobs found.</td>
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
                            <td data-order="<?php echo htmlspecialchars((string) $jobItem['created_at']); ?>">
                              <?php echo htmlspecialchars(syiAiFormatDateTime((string) ($jobItem['created_at'] ?? ''))); ?>
                            </td>
                            <td>
                              <a class="btn btn-primary btn-sm" href="job_details.php?job=<?php echo urlencode((string) $jobItem['job_uuid']); ?>">
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
                  <p class="text-muted mb-0">Open a job from the queue to view details.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include('nav/footer.php'); ?>
      <script>
        (function() {
          if (typeof $ === 'undefined' || !$.fn.DataTable) {
            return;
          }
          $('#jobs-table').DataTable({
            order: [[3, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            responsive: true
          });
        })();
      </script>
    </div>
  </div>
</body>
</html>
