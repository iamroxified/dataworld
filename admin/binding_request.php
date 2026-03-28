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
    header('Location:../login.php?redirect=admin/binding_request.php');
    exit;
}

function bindingAdminRequestBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'acknowledged' => 'success',
        'processing' => 'info',
        'completed' => 'primary',
        'cancelled', 'rejected' => 'danger',
        default => 'warning',
    };
}

function bindingAdminPaymentBadge(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'completed', 'paid', 'success' => 'success',
        'failed', 'cancelled', 'rejected' => 'danger',
        'processing' => 'info',
        default => 'warning',
    };
}

$bindingQuery = $pdo->prepare(
    "SELECT br.*, o.order_number, o.status AS order_status, o.payment_status, o.total_amount,
            u.first_name, u.last_name, u.email,
            CONCAT(COALESCE(admin_user.first_name, ''), ' ', COALESCE(admin_user.last_name, '')) AS acknowledged_admin_name
     FROM binding_requests br
     LEFT JOIN orders o ON br.order_id = o.id
     LEFT JOIN users u ON br.user_id = u.id
     LEFT JOIN users admin_user ON br.acknowledged_by = admin_user.id
     ORDER BY br.created_at DESC"
);
$bindingQuery->execute();
$bindingRequests = $bindingQuery->fetchAll();

$totalRequests = count($bindingRequests);
$pendingRequests = count(array_filter($bindingRequests, static fn(array $row): bool => strtolower((string) ($row['status'] ?? 'pending')) === 'pending'));
$acknowledgedRequests = count(array_filter($bindingRequests, static fn(array $row): bool => strtolower((string) ($row['status'] ?? 'pending')) === 'acknowledged'));
$paidRequests = count(array_filter($bindingRequests, static fn(array $row): bool => in_array(strtolower((string) ($row['payment_status'] ?? 'pending')), ['paid', 'completed', 'success'], true)));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Binding Requests - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
    <link rel="stylesheet" href="assets/css/datatables.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include('nav/sidebar.php'); ?>

        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Binding Requests</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Binding Requests</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Requests</p>
                                                <h4 class="card-title"><?php echo number_format($totalRequests); ?></h4>
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
                                                <i class="fas fa-hourglass-half"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Pending</p>
                                                <h4 class="card-title"><?php echo number_format($pendingRequests); ?></h4>
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
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Acknowledged</p>
                                                <h4 class="card-title"><?php echo number_format($acknowledgedRequests); ?></h4>
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
                                                <p class="card-category">Paid Orders</p>
                                                <h4 class="card-title"><?php echo number_format($paidRequests); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">All Binding Requests</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="binding-requests-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Student</th>
                                            <th>Order #</th>
                                            <th>Programme</th>
                                            <th>Copies</th>
                                            <th>Payment</th>
                                            <th>Acknowledgement</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sn = 1; ?>
                                        <?php foreach ($bindingRequests as $request): ?>
                                            <tr>
                                                <td><?php echo $sn++; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: ($request['full_name'] ?? 'Unknown Student')); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['email'] ?? ''); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['programe'] ?? 'N/A'); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo (int) ($request['pages'] ?? 0); ?> pages</small>
                                                </td>
                                                <td><?php echo (int) ($request['copies'] ?? 1); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo bindingAdminPaymentBadge($request['payment_status'] ?? null); ?>">
                                                        <?php echo htmlspecialchars(ucfirst((string) ($request['payment_status'] ?? 'pending'))); ?>
                                                    </span>
                                                    <?php if (isset($request['total_amount'])): ?>
                                                        <br>
                                                        <small class="text-muted">N<?php echo number_format((float) $request['total_amount'], 2); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo bindingAdminRequestBadge($request['status'] ?? null); ?>">
                                                        <?php echo htmlspecialchars(ucfirst((string) ($request['status'] ?? 'pending'))); ?>
                                                    </span>
                                                    <?php if (!empty(trim((string) ($request['acknowledged_admin_name'] ?? '')))): ?>
                                                        <br>
                                                        <small class="text-muted">By <?php echo htmlspecialchars(trim((string) $request['acknowledged_admin_name'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime((string) $request['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_bind_request.php?id=<?php echo (int) $request['id']; ?>" class="btn btn-primary btn-sm">
                                                        View
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
            </div>
            <?php include('nav/footer.php'); ?>
            <script src="assets/js/datatables.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#binding-requests-table').DataTable({
                        order: [
                            [7, 'desc']
                        ]
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
