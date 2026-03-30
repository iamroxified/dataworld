<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');
require('../includes/ai_automation.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php');
    exit;
}

$currentUser = getCurrentUser();
if (($currentUser['role'] ?? '') !== 'admin') {
    header('Location:../login.php?redirect=admin/view_request.php');
    exit;
}

$projectRoot = dirname(__DIR__);
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: analytics_request.php');
    exit;
}

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

try {
    syiAiLoadDependencies($projectRoot);
} catch (Throwable $throwable) {
    $message = $throwable->getMessage();
    $messageType = 'danger';
}

function adminAutomationBadge(string $status): string
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

function adminPaymentBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'completed', 'paid', 'success' => 'success',
        'failed', 'cancelled', 'rejected' => 'danger',
        'processing' => 'info',
        'pending' => 'warning',
        default => 'secondary',
    };
}

function adminAutomationJobUuid(int $requestId): string
{
    return 'analytics-request-' . $requestId;
}

function adminFetchRequest(PDO $pdo, int $requestId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT ar.*, o.id AS order_id, o.order_number, o.status AS order_status, o.payment_status,
                u.first_name, u.last_name, u.email, u.username
         FROM analytics_requests ar
         LEFT JOIN orders o ON ar.order_id = o.id
         LEFT JOIN users u ON ar.user_id = u.id
         WHERE ar.id = ?"
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adminFetchAutomationJob(PDO $pdo, int $requestId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM student_jobs WHERE job_uuid = ? LIMIT 1');
    $stmt->execute([adminAutomationJobUuid($requestId)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adminCreateManualPaymentRecord(PDO $pdo, array $request): void
{
    $existingPayment = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status IN ('paid', 'success', 'completed')");
    $existingPayment->execute([(int) ($request['order_id'] ?? 0)]);

    if ((int) $existingPayment->fetchColumn() > 0) {
        return;
    }

    $referenceBase = preg_replace('/[^A-Za-z0-9]+/', '', (string) ($request['order_number'] ?? ''));
    if ($referenceBase === '') {
        $referenceBase = 'ORDER' . (int) ($request['order_id'] ?? 0);
    }

    $reference = 'MANUAL-' . strtoupper($referenceBase) . '-' . date('YmdHis');
    $insert = $pdo->prepare(
        'INSERT INTO payments (user_id, order_id, reference, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        (int) ($request['user_id'] ?? 0),
        (int) ($request['order_id'] ?? 0),
        $reference,
        (float) ($request['payment_amount'] ?? 0),
        (string) ($request['currency'] ?? 'NGN'),
        'completed',
    ]);
}

function adminPrepareAutomationPayload(array $request, string $projectRoot): array
{
    $chaptersText = null;
    $methodologyText = null;
    $datasetSummaryJson = null;
    $datasetOriginalName = null;
    $datasetPath = null;
    $chapterOutlineMarkdown = null;
    $questionnaireText = null;

    $chaptersPath = $request['chapter3_file'] ?? null;
    $chaptersAbsolutePath = syiAiAbsolutePath($projectRoot, $chaptersPath);

    if ($chaptersAbsolutePath && is_file($chaptersAbsolutePath)) {
        $chaptersText = syiAiExtractDocumentText($chaptersAbsolutePath);
        $methodologyText = syiAiExtractMethodology($chaptersText);
    }

    $questionairePath = $request['questionaire'] ?? null;
    $questionaireAbsolutePath = syiAiAbsolutePath($projectRoot, $questionairePath);
    if ($questionaireAbsolutePath && is_file($questionaireAbsolutePath)) {
        if (syiAiIsDatasetFile($questionairePath)) {
            $datasetPath = $questionairePath;
            $datasetOriginalName = basename((string) $questionairePath);
            $datasetSummaryJson = json_encode(
                syiAiSummarizeDataset($questionaireAbsolutePath),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } elseif (in_array(strtolower((string) pathinfo((string) $questionairePath, PATHINFO_EXTENSION)), ['pdf', 'docx', 'doc', 'txt'], true)) {
            $questionnaireText = syiAiExtractDocumentText($questionaireAbsolutePath);
        }
    }

    if ($questionnaireText !== null && trim($questionnaireText) !== '') {
        $chaptersText = trim(implode("\n\n", array_filter([
            $chaptersText,
            "Questionnaire / Research Instrument\n" . $questionnaireText,
        ], static fn($value) => trim((string) $value) !== '')));
    }

    $hasChapterFile = !empty($request['chapter3_file']) && $chaptersAbsolutePath && is_file($chaptersAbsolutePath);
    $hasQuestionnaireFile = !empty($request['questionaire']) && $questionaireAbsolutePath && is_file($questionaireAbsolutePath);
    $coverage = syiAiAssessInputCoverage($chaptersText, $datasetSummaryJson, [
        'has_chapter_file' => $hasChapterFile,
        'has_questionnaire_file' => $hasQuestionnaireFile,
    ]);
    if (!empty($coverage['missing'])) {
        adminAuditMissingAutomationInputs((int) $request['id'], $coverage['missing']);
        throw new RuntimeException(
            'Automation paused. We are missing required inputs: ' . implode(', ', $coverage['missing']) . '. ' .
            'Please upload Chapter One (with objectives, research questions, hypotheses), Chapter Three (methodology), ' .
            'and the questionnaire or dataset responses, then retry.'
        );
    }

    $submissionMode = ($chaptersText !== null || $datasetSummaryJson !== null) ? 'full_upload' : 'topic_only';

    return [
        'job_uuid' => adminAutomationJobUuid((int) $request['id']),
        'student_name' => trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: ($request['username'] ?? 'Student'),
        'student_email' => (string) ($request['email'] ?? ''),
        'student_identifier' => (string) ($request['username'] ?? ('student-' . $request['user_id'])),
        'submission_mode' => $submissionMode,
        'project_topic' => (string) ($request['project_topic'] ?? ''),
        'chapters_original_name' => !empty($request['chapter3_file']) ? basename((string) $request['chapter3_file']) : null,
        'chapters_path' => $request['chapter3_file'] ?? null,
        'chapters_text' => $chaptersText,
        'methodology_text' => $methodologyText,
        'dataset_original_name' => $datasetOriginalName,
        'dataset_path' => $datasetPath,
        'dataset_summary_json' => $datasetSummaryJson,
        'chapter_outline_markdown' => $chapterOutlineMarkdown,
    ];
}

function adminAuditMissingAutomationInputs(int $requestId, array $missing): void
{
    if ($requestId <= 0 || $missing === []) {
        return;
    }

    try {
        global $pdo;
        if (!$pdo instanceof PDO) {
            return;
        }

        $jobUuid = adminAutomationJobUuid($requestId);
        $stmt = $pdo->prepare('SELECT job_uuid FROM student_jobs WHERE job_uuid = ? LIMIT 1');
        $stmt->execute([$jobUuid]);
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            return;
        }

        $message = 'Missing required inputs: ' . implode(', ', $missing) . '.';
        $update = $pdo->prepare('UPDATE student_jobs SET error_message = ?, updated_at = NOW() WHERE job_uuid = ?');
        $update->execute([$message, $jobUuid]);
    } catch (Throwable $throwable) {
        error_log('Unable to audit missing automation inputs: ' . $throwable->getMessage());
    }
}

function adminComputeAutomationCoverage(array $request, string $projectRoot): array
{
    $chaptersText = null;
    $datasetSummaryJson = null;
    $questionnaireText = null;

    $chaptersPath = $request['chapter3_file'] ?? null;
    $chaptersAbsolutePath = syiAiAbsolutePath($projectRoot, $chaptersPath);
    if ($chaptersAbsolutePath && is_file($chaptersAbsolutePath)) {
        try {
            $chaptersText = syiAiExtractDocumentText($chaptersAbsolutePath);
        } catch (Throwable $throwable) {
            $chaptersText = $chaptersText ?? null;
        }
    }

    $questionairePath = $request['questionaire'] ?? null;
    $questionaireAbsolutePath = syiAiAbsolutePath($projectRoot, $questionairePath);
    if ($questionaireAbsolutePath && is_file($questionaireAbsolutePath)) {
        try {
            if (syiAiIsDatasetFile($questionairePath)) {
                $datasetSummaryJson = json_encode(
                    syiAiSummarizeDataset($questionaireAbsolutePath),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            } elseif (in_array(strtolower((string) pathinfo((string) $questionairePath, PATHINFO_EXTENSION)), ['pdf', 'docx', 'doc', 'txt'], true)) {
                $questionnaireText = syiAiExtractDocumentText($questionaireAbsolutePath);
            }
        } catch (Throwable $throwable) {
            $datasetSummaryJson = $datasetSummaryJson ?? null;
            $questionnaireText = $questionnaireText ?? null;
        }
    }

    if ($questionnaireText !== null && trim($questionnaireText) !== '') {
        $chaptersText = trim(implode("\n\n", array_filter([
            $chaptersText,
            "Questionnaire / Research Instrument\n" . $questionnaireText,
        ], static fn($value) => trim((string) $value) !== '')));
    }

    $hasChapterFile = !empty($request['chapter3_file']) && $chaptersAbsolutePath && is_file($chaptersAbsolutePath);
    $hasQuestionnaireFile = !empty($request['questionaire']) && $questionaireAbsolutePath && is_file($questionaireAbsolutePath);

    return syiAiAssessInputCoverage($chaptersText, $datasetSummaryJson, [
        'has_chapter_file' => $hasChapterFile,
        'has_questionnaire_file' => $hasQuestionnaireFile,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $request = adminFetchRequest($pdo, $requestId);
        if (!$request) {
            throw new RuntimeException('Analytics request not found.');
        }

        if (isset($_POST['update_payment_status'])) {
            if (!syiAiValidateCsrf($_POST['csrf_token'] ?? null)) {
                throw new RuntimeException('Invalid payment status form token. Please refresh and try again.');
            }

            $newPaymentStatus = strtolower(trim((string) ($_POST['payment_status'] ?? '')));
            if (!in_array($newPaymentStatus, ['pending', 'completed', 'failed'], true)) {
                throw new RuntimeException('Invalid payment status selected.');
            }

            $orderId = (int) ($request['order_id'] ?? 0);
            if ($orderId <= 0) {
                throw new RuntimeException('This analytics request is not linked to a valid order.');
            }

            $orderStmt = $pdo->prepare('SELECT status, payment_status FROM orders WHERE id = ? LIMIT 1');
            $orderStmt->execute([$orderId]);
            $orderRow = $orderStmt->fetch();

            if (!$orderRow) {
                throw new RuntimeException('Order record not found for this analytics request.');
            }

            $currentOrderStatus = (string) ($orderRow['status'] ?? 'pending');
            $currentPaymentStatus = strtolower((string) ($orderRow['payment_status'] ?? 'pending'));
            $updatedOrderStatus = $currentOrderStatus;

            if ($newPaymentStatus === 'completed' && in_array($currentOrderStatus, ['pending', 'failed', 'cancelled', 'rejected'], true)) {
                $updatedOrderStatus = 'processing';
            }

            $pdo->beginTransaction();

            $updatePayment = $pdo->prepare('UPDATE orders SET payment_status = ?, status = ? WHERE id = ?');
            $updatePayment->execute([$newPaymentStatus, $updatedOrderStatus, $orderId]);

            if ($newPaymentStatus === 'completed' && $currentPaymentStatus !== 'completed') {
                adminCreateManualPaymentRecord($pdo, $request);
            }

            $pdo->commit();

            $_SESSION['message'] = $newPaymentStatus === 'completed'
                ? 'Payment status updated to completed. The request is now cleared for processing and automation.'
                : 'Payment status updated successfully.';
            $_SESSION['message_type'] = 'success';

            header("Location: view_request.php?id={$requestId}");
            exit;
        }

        if (isset($_POST['update_status'])) {
            $newStatus = $_POST['status'] ?? '';
            $orderId = (int) ($_POST['order_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $currentStatus = $stmt->fetchColumn();

            $allowedTransitions = [
                'pending' => ['processing', 'cancelled'],
                'processing' => ['completed'],
            ];

            if (isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                $_SESSION['message'] = 'Status updated successfully!';
                $_SESSION['message_type'] = 'success';
            }

            header("Location: view_request.php?id={$requestId}");
            exit;
        }

        if (isset($_POST['upload_completed_work'])) {
            if (isset($_FILES['completed_work']) && $_FILES['completed_work']['error'] == 0) {
                $uploadDir = '../uploads/completed_analytics/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES['completed_work']['name'], PATHINFO_EXTENSION);
                $completedWorkFile = 'uploads/completed_analytics/' . uniqid() . '.' . $fileExtension;

                if (move_uploaded_file($_FILES['completed_work']['tmp_name'], '../' . $completedWorkFile)) {
                    $stmt = $pdo->prepare("UPDATE analytics_requests SET completed_work = ? WHERE id = ?");
                    $stmt->execute([$completedWorkFile, $requestId]);
                    $_SESSION['message'] = 'Completed work uploaded successfully!';
                    $_SESSION['message_type'] = 'success';
                }
            }

            header("Location: view_request.php?id={$requestId}");
            exit;
        }

        if (isset($_POST['trigger_automation'])) {
            if (!syiAiValidateCsrf($_POST['csrf_token'] ?? null)) {
                throw new RuntimeException('Invalid automation form token. Please refresh and try again.');
            }

            if (($request['payment_status'] ?? '') !== 'completed') {
                throw new RuntimeException('Automation can only be triggered after payment has been confirmed.');
            }

            $payload = adminPrepareAutomationPayload($request, $projectRoot);

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

            $job = adminFetchAutomationJob($pdo, $requestId);

            if ($job) {
                $update = $pdo->prepare(
                    'UPDATE student_jobs SET
                        user_id = :user_id,
                        student_name = :student_name,
                        student_email = :student_email,
                        student_identifier = :student_identifier,
                        submission_mode = :submission_mode,
                        project_topic = :project_topic,
                        chapters_original_name = :chapters_original_name,
                        chapters_path = :chapters_path,
                        chapters_text = :chapters_text,
                        methodology_text = :methodology_text,
                        dataset_original_name = :dataset_original_name,
                        dataset_path = :dataset_path,
                        dataset_summary_json = :dataset_summary_json,
                        chapter_outline_markdown = :chapter_outline_markdown,
                        degree_level = :degree_level,
                        target_pages = :target_pages,
                        include_graphs = :include_graphs,
                        hypothesis_mode = :hypothesis_mode,
                        output_format = :output_format,
                        admin_notes = :admin_notes,
                        configured_by = :configured_by,
                        status = :status,
                        system_prompt = NULL,
                        user_prompt = NULL,
                        ai_markdown = NULL,
                        generated_file_name = NULL,
                        generated_file_path = NULL,
                        download_name = NULL,
                        download_expires_at = NULL,
                        generated_at = NULL,
                        email_sent_at = NULL,
                        reviewed_at = NULL,
                        reviewed_by = NULL,
                        error_message = NULL
                     WHERE job_uuid = :job_uuid'
                );
            } else {
                $update = $pdo->prepare(
                    'INSERT INTO student_jobs (
                        job_uuid, user_id, student_name, student_email, student_identifier, submission_mode, project_topic,
                        chapters_original_name, chapters_path, chapters_text, methodology_text,
                        dataset_original_name, dataset_path, dataset_summary_json, chapter_outline_markdown,
                        degree_level, target_pages, include_graphs, hypothesis_mode, output_format,
                        admin_notes, configured_by, status
                     ) VALUES (
                        :job_uuid, :user_id, :student_name, :student_email, :student_identifier, :submission_mode, :project_topic,
                        :chapters_original_name, :chapters_path, :chapters_text, :methodology_text,
                        :dataset_original_name, :dataset_path, :dataset_summary_json, :chapter_outline_markdown,
                        :degree_level, :target_pages, :include_graphs, :hypothesis_mode, :output_format,
                        :admin_notes, :configured_by, :status
                     )'
                );
            }

            $update->execute([
                ':job_uuid' => $payload['job_uuid'],
                ':user_id' => (int) $request['user_id'],
                ':student_name' => $payload['student_name'],
                ':student_email' => $payload['student_email'],
                ':student_identifier' => $payload['student_identifier'],
                ':submission_mode' => $payload['submission_mode'],
                ':project_topic' => $payload['project_topic'],
                ':chapters_original_name' => $payload['chapters_original_name'],
                ':chapters_path' => $payload['chapters_path'],
                ':chapters_text' => $payload['chapters_text'],
                ':methodology_text' => $payload['methodology_text'],
                ':dataset_original_name' => $payload['dataset_original_name'],
                ':dataset_path' => $payload['dataset_path'],
                ':dataset_summary_json' => $payload['dataset_summary_json'],
                ':chapter_outline_markdown' => $payload['chapter_outline_markdown'],
                ':degree_level' => $degreeLevel,
                ':target_pages' => $targetPages,
                ':include_graphs' => $includeGraphs,
                ':hypothesis_mode' => $hypothesisMode,
                ':output_format' => $outputFormat,
                ':admin_notes' => $adminNotes,
                ':configured_by' => (int) $currentUser['id'],
                ':status' => 'configured',
            ]);

            header('Location: generate.php?job=' . urlencode($payload['job_uuid']));
            exit;
        }
    } catch (Throwable $throwable) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $throwable->getMessage();
        $messageType = 'danger';
    }
}

$request = adminFetchRequest($pdo, $requestId);
if (!$request) {
    header('Location: analytics_request.php');
    exit;
}

$automationJob = adminFetchAutomationJob($pdo, $requestId);
$inputCoverage = adminComputeAutomationCoverage($request, $projectRoot);
$automationBlocked = !empty($inputCoverage['missing']);
$selectedDegreeLevel = $automationJob && isset($automationJob['degree_level'])
    ? (string) $automationJob['degree_level']
    : 'BSc/HND';
$selectedTargetPages = $automationJob && isset($automationJob['target_pages'])
    ? (int) $automationJob['target_pages']
    : syiAiRecommendedPagesForDegree($selectedDegreeLevel);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>View Request - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
  <style>
    .automation-card .form-label { font-weight: 600; }
    .automation-summary { background: #f8fafc; border-radius: 10px; padding: 15px; }
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
            <h3 class="fw-bold mb-3">View Analytics Request</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">All Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">View Request</a></li>
            </ul>
          </div>

          <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType ?: 'info'); ?>">
              <?php echo htmlspecialchars($message); ?>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-8">
              <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                  <div class="d-flex align-items-center">
                    <h4 class="mb-0"><i class="fas fa-wallet"></i> View Analytics Request Details</h4>
                    <a href="analytics_request.php" class="btn btn-primary btn-round ms-auto">
                      <i class="fa fa-caret-left"></i> All Analytics Requests
                    </a>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Request Details (Order #<?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?>)</h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>User:</strong> <?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></p>
                      <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email'] ?? ''); ?></p>
                      <hr>
                      <p><strong>Project Topic:</strong> <?php echo htmlspecialchars($request['project_topic']); ?></p>
                      <p><strong>Program Type:</strong> <?php echo htmlspecialchars($request['program_type']); ?></p>
                      <p><strong>Software:</strong> <?php echo htmlspecialchars($request['software']); ?></p>
                      <p><strong>Institution:</strong> <?php echo htmlspecialchars($request['institution']); ?></p>
                      <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                      <p><strong>State:</strong> <?php echo htmlspecialchars($request['state']); ?></p>
                      <p><strong>Country:</strong> <?php echo htmlspecialchars($request['country']); ?></p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Amount:</strong> <?php echo $request['currency']; ?><?php echo number_format((float) $request['payment_amount'], 2); ?></p>
                      <p><strong>Payment Status:</strong> <span class="badge bg-<?php echo adminPaymentBadge($request['payment_status'] ?? null); ?>"><?php echo ucfirst((string) ($request['payment_status'] ?? 'pending')); ?></span></p>
                      <p><strong>Delivery Status:</strong> <span class="badge bg-<?php echo ($request['status'] ?? '') === 'completed' ? 'success' : (($request['status'] ?? '') === 'processing' ? 'info' : 'warning'); ?>"><?php echo ucfirst($request['status'] ?? 'pending'); ?></span></p>
                      <p><strong>Project Completion Status:</strong> <span class="badge bg-<?php echo $request['order_status'] === 'completed' ? 'success' : ($request['order_status'] === 'processing' ? 'info' : ($request['order_status'] === 'cancelled' ? 'danger' : 'warning')); ?>"><?php echo ucfirst($request['order_status']); ?></span></p>
                      <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></p>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <?php if (!empty($request['chapter3_file'])): ?>
                        <p><strong>Chapter 1-3 File:</strong> <a href="../<?php echo htmlspecialchars($request['chapter3_file']); ?>" target="_blank">Download File</a></p>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <?php if (!empty($request['questionaire'])): ?>
                        <p><strong>Dataset / Questionnaire:</strong> <a href="../<?php echo htmlspecialchars($request['questionaire']); ?>" target="_blank">Download File</a></p>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <?php if (!empty($request['completed_work'])): ?>
                        <p><strong>Completed Analytics:</strong> <a href="../<?php echo htmlspecialchars($request['completed_work']); ?>" target="_blank">Download Completed Work</a></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card mb-4">
                <div class="card-header">
                  <h4 class="card-title">Payment Confirmation</h4>
                </div>
                <div class="card-body">
                  <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(syiAiCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                      <label for="payment_status">Update Payment Status</label>
                      <select name="payment_status" id="payment_status" class="form-control">
                        <option value="pending" <?php echo (($request['payment_status'] ?? 'pending') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo (($request['payment_status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo (($request['payment_status'] ?? '') === 'failed') ? 'selected' : ''; ?>>Failed</option>
                      </select>
                    </div>
                    <small class="text-muted d-block mb-3">
                      Setting payment to completed will mirror a successful payment verification and move the order to processing when it is still awaiting payment confirmation.
                    </small>
                    <button type="submit" name="update_payment_status" class="btn btn-success">Save Payment Status</button>
                  </form>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header">
                  <h4 class="card-title">Update Status</h4>
                </div>
                <div class="card-body">
                  <form method="post" action="">
                    <input type="hidden" name="order_id" value="<?php echo (int) $request['order_id']; ?>">
                    <div class="form-group">
                      <label for="status">Update Project Status</label>
                      <select name="status" id="status" class="form-control">
                        <?php if ($request['order_status'] === 'pending'): ?>
                          <option value="processing">Processing</option>
                          <option value="cancelled">Cancel</option>
                        <?php elseif ($request['order_status'] === 'processing'): ?>
                          <option value="completed">Completed</option>
                        <?php endif; ?>
                      </select>
                    </div>
                    <?php if (in_array($request['order_status'], ['pending', 'processing'], true)): ?>
                      <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                    <?php else: ?>
                      <p class="mb-0">No status updates available.</p>
                    <?php endif; ?>
                  </form>
                </div>
              </div>

              <?php if ($request['order_status'] === 'completed'): ?>
              <div class="card mb-4">
                <div class="card-header">
                  <h4 class="card-title">Upload Completed Work</h4>
                </div>
                <div class="card-body">
                  <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                      <label for="completed_work">Upload File</label>
                      <input type="file" name="completed_work" class="form-control" required>
                    </div>
                    <button type="submit" name="upload_completed_work" class="btn btn-success">Upload</button>
                  </form>
                </div>
              </div>
              <?php endif; ?>

              <div class="card automation-card">
                <div class="card-header">
                  <h4 class="card-title">AI Automation Trigger</h4>
                </div>
                <div class="card-body">
                  <div class="automation-summary mb-3">
                    <p class="mb-2"><strong>Access:</strong> Admin only</p>
                    <p class="mb-2"><strong>Source:</strong> This existing analytics request</p>
                    <p class="mb-0"><strong>Payment:</strong> <?php echo $request['payment_status'] === 'completed' ? 'Cleared for automation' : 'Automation locked until payment is completed'; ?></p>
                  </div>

                  <?php if ($automationBlocked): ?>
                    <div class="alert alert-warning">
                      <strong>Automation paused:</strong> Missing required inputs: <?php echo htmlspecialchars(implode(', ', $inputCoverage['missing']), ENT_QUOTES, 'UTF-8'); ?>.
                      Upload the items below to proceed.
                    </div>
                  <?php endif; ?>

                  <div class="mb-3">
                    <div class="list-group">
                      <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          Chapter One (objectives, research questions, hypotheses)
                          <?php if (empty($inputCoverage['has_chapter_one']) || empty($inputCoverage['has_objectives']) || empty($inputCoverage['has_research_questions']) || empty($inputCoverage['has_hypotheses'])): ?>
                            <small class="d-block">
                              <a href="upload_chapter3.php?id=<?php echo (int) $requestId; ?>">Upload Chapter 1-3 file</a>
                            </small>
                          <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo !empty($inputCoverage['has_chapter_one']) && !empty($inputCoverage['has_objectives']) && !empty($inputCoverage['has_research_questions']) && !empty($inputCoverage['has_hypotheses']) ? 'success' : 'danger'; ?>">
                          <?php echo !empty($inputCoverage['has_chapter_one']) && !empty($inputCoverage['has_objectives']) && !empty($inputCoverage['has_research_questions']) && !empty($inputCoverage['has_hypotheses']) ? 'Present' : 'Missing'; ?>
                        </span>
                      </div>
                      <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          Chapter Three (methodology)
                          <?php if (empty($inputCoverage['has_chapter_three'])): ?>
                            <small class="d-block">
                              <a href="upload_chapter3.php?id=<?php echo (int) $requestId; ?>">Upload Chapter 1-3 file</a>
                            </small>
                          <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo !empty($inputCoverage['has_chapter_three']) ? 'success' : 'danger'; ?>">
                          <?php echo !empty($inputCoverage['has_chapter_three']) ? 'Present' : 'Missing'; ?>
                        </span>
                      </div>
                      <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          Questionnaire or dataset responses
                          <?php if (empty($inputCoverage['has_questionnaire_text']) && empty($inputCoverage['has_dataset'])): ?>
                            <small class="d-block">
                              <a href="upload_questionnaire.php?id=<?php echo (int) $requestId; ?>">Upload questionnaire / dataset</a>
                            </small>
                          <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo (!empty($inputCoverage['has_questionnaire_text']) || !empty($inputCoverage['has_dataset'])) ? 'success' : 'danger'; ?>">
                          <?php echo (!empty($inputCoverage['has_questionnaire_text']) || !empty($inputCoverage['has_dataset'])) ? 'Present' : 'Missing'; ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <?php if ($automationJob): ?>
                    <div class="mb-3">
                      <span class="badge bg-<?php echo adminAutomationBadge((string) $automationJob['status']); ?>">
                        <?php echo htmlspecialchars(ucfirst((string) $automationJob['status'])); ?>
                      </span>
                      <?php if (!empty($automationJob['generated_at'])): ?>
                        <small class="text-muted d-block mt-2">Generated: <?php echo htmlspecialchars((string) $automationJob['generated_at']); ?></small>
                      <?php endif; ?>
                      <?php if (!empty($automationJob['download_name'])): ?>
                        <small class="d-block mt-2"><a href="../downloads/<?php echo rawurlencode((string) $automationJob['download_name']); ?>">Download generated file</a></small>
                      <?php endif; ?>
                      <?php if (!empty($automationJob['error_message'])): ?>
                        <small class="text-danger d-block mt-2"><?php echo htmlspecialchars((string) $automationJob['error_message']); ?></small>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(syiAiCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group mb-3">
                      <label class="form-label">Degree Level</label>
                      <select class="form-control" name="degree_level" required>
                        <?php foreach (['NCE/ND', 'BSc/HND', 'PGD', 'MSc/MPhil', 'PhD'] as $option): ?>
                          <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selectedDegreeLevel === $option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Pages</label>
                      <select class="form-control" name="target_pages" required>
                        <?php foreach ([30, 50, 70, 100] as $pageCount): ?>
                          <option value="<?php echo $pageCount; ?>" <?php echo ($selectedTargetPages === $pageCount) ? 'selected' : ''; ?>>
                            <?php echo $pageCount; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="text-muted d-block mt-2">Recommended pages by degree: BSc/HND 30, MSc/MPhil 70, PhD 100.</small>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Graphs</label>
                      <select class="form-control" name="include_graphs">
                        <option value="1" <?php echo ((int) ($automationJob['include_graphs'] ?? 1) === 1) ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo ((int) ($automationJob['include_graphs'] ?? 1) === 0) ? 'selected' : ''; ?>>No</option>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Hypothesis Testing</label>
                      <select class="form-control" name="hypothesis_mode">
                        <option value="yes" <?php echo (($automationJob['hypothesis_mode'] ?? 'auto-detect') === 'yes') ? 'selected' : ''; ?>>Yes</option>
                        <option value="auto-detect" <?php echo (($automationJob['hypothesis_mode'] ?? 'auto-detect') === 'auto-detect') ? 'selected' : ''; ?>>Auto-detect</option>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Output Format</label>
                      <select class="form-control" name="output_format">
                        <option value="word" <?php echo (($automationJob['output_format'] ?? 'word') === 'word') ? 'selected' : ''; ?>>Word</option>
                        <option value="pdf" <?php echo (($automationJob['output_format'] ?? 'word') === 'pdf') ? 'selected' : ''; ?>>PDF</option>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Admin Notes</label>
                      <textarea class="form-control" name="admin_notes" rows="3"><?php echo htmlspecialchars((string) ($automationJob['admin_notes'] ?? '')); ?></textarea>
                    </div>
                    <button type="submit" name="trigger_automation" class="btn btn-success w-100" <?php echo ($request['payment_status'] !== 'completed' || $automationBlocked) ? 'disabled' : ''; ?>>
                      Trigger Automation
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <?php include('nav/footer.php'); ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
