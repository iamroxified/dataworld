<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$user_details = get_user_details($user_id);

if (is_array($user_details)) {
    extract($user_details);
} else {
    die("Error: Unable to retrieve user details.");
}

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

function userBindingDetailBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'acknowledged' => 'success',
        'processing' => 'info',
        'completed' => 'primary',
        'cancelled', 'rejected' => 'danger',
        default => 'warning',
    };
}

function userBindingDetailPaymentBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'completed', 'paid', 'success' => 'success',
        'failed', 'cancelled', 'rejected' => 'danger',
        'processing' => 'info',
        default => 'warning',
    };
}

function userBindingDetailOrderBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'processing' => 'info',
        'completed', 'delivered' => 'success',
        'cancelled', 'rejected', 'failed' => 'danger',
        default => 'warning',
    };
}

function userBindingDetailFileHref(?string $path): ?string
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

try {
    if ($requestId > 0) {
        $stmt = QueryDB(
            "SELECT br.*, o.order_number, o.status AS order_status, o.payment_status, o.payment_method, o.total_amount
             FROM binding_requests br
             LEFT JOIN orders o ON br.order_id = o.id
             WHERE br.id = ? AND br.user_id = ?",
            [$requestId, $user_id]
        );
    } elseif ($orderId > 0) {
        $stmt = QueryDB(
            "SELECT br.*, o.order_number, o.status AS order_status, o.payment_status, o.payment_method, o.total_amount
             FROM binding_requests br
             LEFT JOIN orders o ON br.order_id = o.id
             WHERE br.order_id = ? AND br.user_id = ?",
            [$orderId, $user_id]
        );
    } else {
        throw new Exception('Binding request not found.');
    }

    $binding_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$binding_request) {
        throw new Exception('Binding request not found or you are not authorized to view it.');
    }
} catch (Exception $e) {
    error_log("Binding request retrieval error: " . $e->getMessage());
    header('Location: binding_request.php');
    exit;
}

$coverPageHref = userBindingDetailFileHref($binding_request['cover_page_path'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Binding Request Details - SYi-Tech</title>
    <?php include('nav/links.php'); ?>
    <style>
        .order-details-container {
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('nav/sidebar.php'); ?>
        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class='container'>
                <div class='page-inner'>
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Binding Request Details</h3>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <a href="binding_request.php" class="btn btn-primary btn-round">My Binding Requests</a>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-12'>
                            <div class='card'>
                                <div class="card-body">
                                    <div class="order-details-container">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h2>Order #<?php echo htmlspecialchars($binding_request['order_number'] ?? 'N/A'); ?></h2>
                                            <span class="badge bg-<?php echo userBindingDetailBadge($binding_request['status'] ?? null); ?>">
                                                <?php echo htmlspecialchars(ucfirst((string) ($binding_request['status'] ?? 'pending'))); ?>
                                            </span>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5>Request Information</h5>
                                                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($binding_request['full_name']); ?></p>
                                                <p><strong>Department:</strong> <?php echo htmlspecialchars($binding_request['department']); ?></p>
                                                <p><strong>Program:</strong> <?php echo htmlspecialchars($binding_request['programe']); ?></p>
                                                <p><strong>Pages:</strong> <?php echo (int) $binding_request['pages']; ?></p>
                                                <p><strong>Copies:</strong> <?php echo (int) ($binding_request['copies'] ?? 1); ?></p>
                                                <p><strong>Color:</strong> <span style="display:inline-block;width:20px;height:20px;border-radius:50%;vertical-align:middle;border:1px solid #d1d5db;background:<?php echo htmlspecialchars($binding_request['color']); ?>;"></span> <?php echo htmlspecialchars($binding_request['color']); ?></p>
                                                <p><strong>Request Date:</strong>
                                                    <?php echo date('F j, Y g:i A', strtotime((string) $binding_request['created_at'])); ?>
                                                </p>
                                                <?php if (!empty($binding_request['acknowledged_at'])): ?>
                                                    <p><strong>Acknowledged On:</strong>
                                                        <?php echo date('F j, Y g:i A', strtotime((string) $binding_request['acknowledged_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h5>Payment Information</h5>
                                                <p><strong>Payment Method:</strong>
                                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($binding_request['payment_method'] ?? 'paystack')))); ?></p>
                                                <p><strong>Payment Status:</strong>
                                                    <span class="badge bg-<?php echo userBindingDetailPaymentBadge($binding_request['payment_status'] ?? null); ?>">
                                                        <?php echo htmlspecialchars(ucfirst((string) ($binding_request['payment_status'] ?? 'pending'))); ?>
                                                    </span>
                                                </p>
                                                <p><strong>Order Status:</strong>
                                                    <span class="badge bg-<?php echo userBindingDetailOrderBadge($binding_request['order_status'] ?? null); ?>">
                                                        <?php echo htmlspecialchars(ucfirst((string) ($binding_request['order_status'] ?? 'pending'))); ?>
                                                    </span>
                                                </p>
                                                <p><strong>Total Amount:</strong> N<?php echo number_format((float) ($binding_request['total_amount'] ?? 0), 2); ?></p>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <h5>Cover Page</h5>
                                                <?php if ($coverPageHref): ?>
                                                    <a href="<?php echo htmlspecialchars($coverPageHref); ?>" target="_blank">View Cover Page</a>
                                                <?php else: ?>
                                                    <p class="mb-0 text-muted">No cover page file is available for this request.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($binding_request['admin_note'])): ?>
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <h5>Admin Note</h5>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars((string) $binding_request['admin_note'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
</body>

</html>
