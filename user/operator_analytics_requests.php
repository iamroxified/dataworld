<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Ensure user is logged in and has 'operator' or 'admin' role
if (!isset($_SESSION['user_id']) || !in_array(getCurrentUser()['role'], ['operator', 'admin'])) {
    header('Location:../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();

$analytics_requests = getAllAnalyticsRequestsForOperator();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Operator Analytics Requests - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">Analytics Requests (Completed Payments)</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Operator Panel</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Analytics Requests</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Requests Awaiting Processing</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="analytics-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>SN</th>
                                                    <th>Order #</th>
                                                    <th>User</th>
                                                    <th>Project Topic</th>
                                                    <th>Program Type</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $sn = 1;
                                                foreach ($analytics_requests as $request) : ?>
                                                    <tr>
                                                        <td><?php echo $sn++; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($request['project_topic'], 0, 50)); ?>...</td>
                                                        <td><?php echo htmlspecialchars($request['program_type']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo get_badge_class($request['status']); ?>">
                                                                <?php echo ucfirst($request['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                        <td>
                                                            <a href="view_analytics_request.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">View & Process</a>
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
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
    <script src="assets/js/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#analytics-table').DataTable();
        });
    </script>
</body>

</html>