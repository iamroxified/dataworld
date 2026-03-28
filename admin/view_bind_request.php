<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php');
    exit;
}

$currentUser = getCurrentUser();
if (($currentUser['role'] ?? '') !== 'admin') {
    header('Location:../login.php?redirect=admin/view_bind_request.php');
    exit;
}

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($requestId <= 0) {
    header('Location: binding_request.php');
    exit;
}

function bindingAdminDetailCsrfToken(): string
{
    if (empty($_SESSION['binding_admin_csrf_token'])) {
        $_SESSION['binding_admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['binding_admin_csrf_token'];
}

function bindingAdminDetailValidateCsrf(?string $token): bool
{
    $sessionToken = $_SESSION['binding_admin_csrf_token'] ?? '';
    return is_string($token) && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function bindingAdminDetailBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'acknowledged' => 'success',
        'processing' => 'info',
        'completed' => 'primary',
        'cancelled', 'rejected' => 'danger',
        default => 'warning',
    };
}

function bindingAdminDetailPaymentBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'completed', 'paid', 'success' => 'success',
        'failed', 'cancelled', 'rejected' => 'danger',
        'processing' => 'info',
        default => 'warning',
    };
}

function bindingAdminDetailOrderBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'processing' => 'info',
        'completed', 'delivered' => 'success',
        'cancelled', 'rejected', 'failed' => 'danger',
        default => 'warning',
    };
}

function bindingAdminFileHref(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '') {
        return null;
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '../')) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

function bindingAdminFetchRequest(PDO $pdo, int $requestId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT br.*, o.order_number, o.status AS order_status, o.payment_status, o.payment_method, o.total_amount,
                u.first_name, u.last_name, u.email, u.phone,
                admin_user.first_name AS admin_first_name, admin_user.last_name AS admin_last_name
         FROM binding_requests br
         LEFT JOIN orders o ON br.order_id = o.id
         LEFT JOIN users u ON br.user_id = u.id
         LEFT JOIN users admin_user ON br.acknowledged_by = admin_user.id
         WHERE br.id = ?
         LIMIT 1"
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_request'])) {
    try {
        if (!bindingAdminDetailValidateCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Invalid request token. Please refresh the page and try again.');
        }

        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

        $update = $pdo->prepare(
            "UPDATE binding_requests
             SET status = 'acknowledged',
                 acknowledged_at = NOW(),
                 acknowledged_by = ?,
                 admin_note = ?
             WHERE id = ?"
        );
        $update->execute([(int) $currentUser['id'], $adminNote !== '' ? $adminNote : null, $requestId]);

        $_SESSION['message'] = 'Binding request acknowledged successfully.';
        $_SESSION['message_type'] = 'success';
        header('Location: view_bind_request.php?id=' . $requestId);
        exit;
    } catch (Throwable $throwable) {
        $message = $throwable->getMessage();
        $messageType = 'danger';
    }
}

$request = bindingAdminFetchRequest($pdo, $requestId);
if (!$request) {
    header('Location: binding_request.php');
    exit;
}

$coverPageHref = bindingAdminFileHref($request['cover_page_path'] ?? null);
$acknowledgedBy = trim((string) (($request['admin_first_name'] ?? '') . ' ' . ($request['admin_last_name'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Binding Request Details - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
</head>

<body>
    <div class="wrapper">
        <?php include('nav/sidebar.php'); ?>

        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Binding Request Details</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="binding_request.php">Binding Requests</a></li>
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
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0"><i class="fas fa-book-open"></i> Binding Request Overview</h4>
                                        <a href="binding_request.php" class="btn btn-light btn-round ms-auto">
                                            <i class="fa fa-caret-left"></i> Back to Requests
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Request Details</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Student:</strong> <?php echo htmlspecialchars(trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: ($request['full_name'] ?? '')); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email'] ?? ''); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['phone'] ?? ''); ?></p>
                                            <hr>
                                            <p><strong>Full Name on Cover:</strong> <?php echo htmlspecialchars($request['full_name']); ?></p>
                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                                            <p><strong>Programme:</strong> <?php echo htmlspecialchars($request['programe']); ?></p>
                                            <p><strong>Pages:</strong> <?php echo (int) $request['pages']; ?></p>
                                            <p><strong>Copies:</strong> <?php echo (int) ($request['copies'] ?? 1); ?></p>
                                            <p><strong>Selected Color:</strong> <span style="display:inline-block;width:20px;height:20px;border-radius:50%;vertical-align:middle;border:1px solid #d1d5db;background:<?php echo htmlspecialchars($request['color']); ?>;"></span> <?php echo htmlspecialchars($request['color']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Order #:</strong> <?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></p>
                                            <p><strong>Total Amount:</strong> N<?php echo number_format((float) ($request['total_amount'] ?? 0), 2); ?></p>
                                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($request['payment_method'] ?? 'paystack')))); ?></p>
                                            <p><strong>Payment Status:</strong> <span class="badge bg-<?php echo bindingAdminDetailPaymentBadge($request['payment_status'] ?? null); ?>"><?php echo htmlspecialchars(ucfirst((string) ($request['payment_status'] ?? 'pending'))); ?></span></p>
                                            <p><strong>Order Status:</strong> <span class="badge bg-<?php echo bindingAdminDetailOrderBadge($request['order_status'] ?? null); ?>"><?php echo htmlspecialchars(ucfirst((string) ($request['order_status'] ?? 'pending'))); ?></span></p>
                                            <p><strong>Binding Status:</strong> <span class="badge bg-<?php echo bindingAdminDetailBadge($request['status'] ?? null); ?>"><?php echo htmlspecialchars(ucfirst((string) ($request['status'] ?? 'pending'))); ?></span></p>
                                            <p><strong>Requested On:</strong> <?php echo date('F j, Y g:i A', strtotime((string) $request['created_at'])); ?></p>
                                            <?php if (!empty($request['acknowledged_at'])): ?>
                                                <p><strong>Acknowledged On:</strong> <?php echo date('F j, Y g:i A', strtotime((string) $request['acknowledged_at'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>Uploaded Cover Page</h5>
                                            <?php if ($coverPageHref): ?>
                                                <a href="<?php echo htmlspecialchars($coverPageHref); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    View Uploaded Cover Page
                                                </a>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted">No cover page file was found for this request.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($request['admin_note'])): ?>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5>Admin Note</h5>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars((string) $request['admin_note'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Acknowledge Request</h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>Current Status:</strong> <span class="badge bg-<?php echo bindingAdminDetailBadge($request['status'] ?? null); ?>"><?php echo htmlspecialchars(ucfirst((string) ($request['status'] ?? 'pending'))); ?></span></p>
                                    <?php if ($acknowledgedBy !== ''): ?>
                                        <p><strong>Acknowledged By:</strong> <?php echo htmlspecialchars($acknowledgedBy); ?></p>
                                    <?php endif; ?>
                                    <p class="text-muted">
                                        Use this action after confirming the request is visible on the admin side and ready for binding follow-up.
                                    </p>

                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(bindingAdminDetailCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="form-group">
                                            <label for="admin_note">Admin Note</label>
                                            <textarea name="admin_note" id="admin_note" rows="4" class="form-control" placeholder="Optional note for this binding request"><?php echo htmlspecialchars((string) ($request['admin_note'] ?? '')); ?></textarea>
                                        </div>
                                        <button type="submit" name="acknowledge_request" class="btn btn-success w-100">
                                            <?php echo strtolower((string) ($request['status'] ?? 'pending')) === 'acknowledged' ? 'Update Acknowledgement' : 'Acknowledge Request'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
</body>

</html>
