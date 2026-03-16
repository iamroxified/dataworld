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
$uss = extract(get_user_details($user_id));

// Flash message logic
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch binding requests
$binding_query = "SELECT * FROM binding_requests WHERE user_id = ? ORDER BY created_at DESC";
$binding_stmt = $pdo->prepare($binding_query);
$binding_stmt->execute([$user_id]);
$binding_requests = $binding_stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Binding Requests - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
    <!-- Add datatables css -->
    <link rel="stylesheet" href="assets/css/datatables.min.css">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('nav/sidebar.php'); ?>
        <!-- End Sidebar -->

        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">My Binding Requests</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">My Binding Requests</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0">
                                            <i class="fas fa-book"></i> Binding Requests History
                                        </h4>
                                        <a href="add_bind_request.php" class="btn btn-primary btn-round ms-auto">
                                            <i class="fa fa-plus"></i>
                                            Make Binding Request
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Binding Requests History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="binding-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>SN</th>
                                                    <th>Full Name</th>
                                                    <th>Department</th>
                                                    <th>Color</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $sn = 1;
                                                foreach ($binding_requests as $request) : ?>
                                                    <tr>
                                                        <td><?php echo $sn++; ?></td>
                                                        <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($request['department']); ?></td>
                                                        <td style="background-color:<?php echo htmlspecialchars($request['color']); ?>"></td>
                                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                        <td>
                                                            <a href="view_bind_request.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">View</a>
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
            <!-- Add datatables js -->
            <script src="assets/js/datatables.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#binding-table').DataTable();
                });
            </script>
        </div>
    </div>
</body>

</html>
