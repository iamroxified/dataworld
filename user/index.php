<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get current user details
$user_id = $_SESSION['user_id'];
$user_details = get_user_details($user_id);

// If user details are not found, redirect to login
if (!$user_details) {
    header('Location: ../login.php');
    exit;
}

// Check for appropriate role
$user_role = $user_details['role'];
if ($user_role !== 'user' && $user_role !== 'customer') {
    header('Location: ../login.php');
    exit;
}

// Set variables for the header
$last_name = $user_details['last_name'] ?? '';
$first_name = $user_details['first_name'] ?? '';
$username = $user_details['username'] ?? '';
$email = $user_details['email'] ?? '';

$user_ref_code = (string) ($user_details['code'] ?? $username);
$wallet_balance = get_user_wallet_balance($user_id);
$referrals_count = (int) get_ref_count($user_ref_code);

$analyticsStatsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN o.status IN ('pending','processing') THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN o.status IN ('completed','delivered') THEN 1 ELSE 0 END) AS completed_count
     FROM analytics_requests ar
     LEFT JOIN orders o ON ar.order_id = o.id
     WHERE ar.user_id = ?"
);
$analyticsStatsStmt->execute([$user_id]);
$analyticsStats = $analyticsStatsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active_count' => 0, 'completed_count' => 0];

$bindingStatsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN status IN ('pending','processing') THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) AS completed_count
     FROM binding_requests
     WHERE user_id = ?"
);
$bindingStatsStmt->execute([$user_id]);
$bindingStats = $bindingStatsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active_count' => 0, 'completed_count' => 0];

$analyticsRecentStmt = $pdo->prepare(
    "SELECT ar.*, o.order_number, o.status AS order_status, o.payment_status
     FROM analytics_requests ar
     LEFT JOIN orders o ON ar.order_id = o.id
     WHERE ar.user_id = ?
     ORDER BY ar.created_at DESC
     LIMIT 5"
);
$analyticsRecentStmt->execute([$user_id]);
$recentAnalytics = $analyticsRecentStmt->fetchAll(PDO::FETCH_ASSOC);

$bindingRecentStmt = $pdo->prepare(
    "SELECT br.*, o.status AS order_status
     FROM binding_requests br
     LEFT JOIN orders o ON br.order_id = o.id
     WHERE br.user_id = ?
     ORDER BY br.created_at DESC
     LIMIT 5"
);
$bindingRecentStmt->execute([$user_id]);
$recentBinding = $bindingRecentStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>User Dashboard - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">User Dashboard</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Dashboard</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-3">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h4 class="card-title mb-0">Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h4>
                                    <span class="text-muted"><?php echo htmlspecialchars($email); ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="make_request" class="btn btn-primary"><i class="fas fa-plus"></i> New Analytics Request</a>
                                        <a href="add_bind_request.php" class="btn btn-outline-primary"><i class="fas fa-book"></i> New Binding Request</a>
                                        <a href="wallet.php" class="btn btn-outline-success"><i class="fas fa-wallet"></i> My Wallet</a>
                                        <a href="referrals.php" class="btn btn-outline-info"><i class="fas fa-user-friends"></i> Referrals</a>
                                        <a href="my-orders.php" class="btn btn-outline-secondary"><i class="fas fa-shopping-cart"></i> My Orders</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Analytics Requests</p>
                                                <h4 class="card-title"><?php echo (int) ($analyticsStats['total'] ?? 0); ?></h4>
                                                <small class="text-muted"><?php echo (int) ($analyticsStats['active_count'] ?? 0); ?> active</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Binding Requests</p>
                                                <h4 class="card-title"><?php echo (int) ($bindingStats['total'] ?? 0); ?></h4>
                                                <small class="text-muted"><?php echo (int) ($bindingStats['active_count'] ?? 0); ?> pending</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Wallet Balance</p>
                                                <h4 class="card-title">₦<?php echo number_format($wallet_balance, 2); ?></h4>
                                                <small class="text-muted">Available balance</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Referrals</p>
                                                <h4 class="card-title"><?php echo $referrals_count; ?></h4>
                                                <small class="text-muted">Total referred users</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h4 class="card-title mb-0">Recent Analytics Requests</h4>
                                    <a href="analytics_request.php" class="btn btn-sm btn-outline-primary">View all</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recentAnalytics)): ?>
                                        <p class="text-muted mb-0">No analytics requests yet.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($recentAnalytics as $request): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['project_topic'] ?? 'Project'); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['order_number'] ?? 'No Order'); ?> · <?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                                    </div>
                                                    <span class="badge bg-<?php echo in_array(($request['order_status'] ?? 'pending'), ['completed','delivered'], true) ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($request['order_status'] ?? 'pending'); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h4 class="card-title mb-0">Recent Binding Requests</h4>
                                    <a href="binding_request.php" class="btn btn-sm btn-outline-primary">View all</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recentBinding)): ?>
                                        <p class="text-muted mb-0">No binding requests yet.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($recentBinding as $request): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['programe'] ?? 'Binding Request'); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars((string) ($request['pages'] ?? 0)); ?> pages · <?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                                    </div>
                                                    <span class="badge bg-<?php echo in_array(($request['status'] ?? 'pending'), ['completed','delivered'], true) ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($request['status'] ?? 'pending'); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
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
