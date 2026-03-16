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
$current_user_details = getCurrentUser();

// Check if request ID is provided
if (!isset($_GET['id'])) {
    header('Location: operator_analytics_requests.php');
    exit;
}

$request_id = $_GET['id'];
$request = getAnalyticsRequestDetailsForOperator($request_id);

if (!$request) {
    $_SESSION['message'] = 'Analytics request not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: operator_analytics_requests.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    if (updateAnalyticsRequestStatus($request_id, $new_status)) {
        $_SESSION['message'] = 'Request status updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update request status.';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: view_analytics_request.php?id=' . $request_id);
    exit;
}

// Handle completed work upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['completed_work'])) {
    $upload_dir = '../uploads/completed_work/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['completed_work']['name'], PATHINFO_EXTENSION);
    $file_name = 'completed_work_' . $request_id . '_' . uniqid() . '.' . $file_extension;
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['completed_work']['tmp_name'], $target_file)) {
        if (updateAnalyticsRequestCompletedWork($request_id, 'uploads/completed_work/' . $file_name)) {
            $_SESSION['message'] = 'Completed work uploaded successfully!';
            $_SESSION['message_type'] = 'success';
            // Optionally update status to 'completed' if work is uploaded
            if ($request['status'] !== 'completed') {
                updateAnalyticsRequestStatus($request_id, 'completed');
            }
        } else {
            $_SESSION['message'] = 'Failed to save completed work path to database.';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Failed to upload completed work file.';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: view_analytics_request.php?id=' . $request_id);
    exit;
}

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Analytics Request - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">Analytics Request Details</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="operator_analytics_requests.php">Analytics Requests</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">View Request</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php if ($message) : ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Request #<?php echo htmlspecialchars($request['order_number']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>User:</strong> <?php echo htmlspecialchars($request['username']); ?> (<?php echo htmlspecialchars($request['email']); ?>)</p>
                                    <p><strong>Project Topic:</strong> <?php echo htmlspecialchars($request['project_topic']); ?></p>
                                    <p><strong>Program Type:</strong> <?php echo htmlspecialchars($request['program_type']); ?></p>
                                    <p><strong>Software:</strong> <?php echo htmlspecialchars($request['software']); ?></p>
                                    <p><strong>Institution:</strong> <?php echo htmlspecialchars($request['institution']); ?></p>
                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-<?php echo get_badge_class($request['status']); ?>"><?php echo ucfirst($request['status']); ?></span></p>
                                    <p><strong>Payment Status:</strong> <span class="badge bg-success"><?php echo ucfirst($request['payment_status']); ?></span></p>
                                    <p><strong>Requested On:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>

                                    <hr>

                                    <h5>Files:</h5>
                                    <?php if ($request['chapter3_file']) : ?>
                                        <p><strong>Chapter 3 File:</strong> <a href="../<?php echo htmlspecialchars($request['chapter3_file']); ?>" target="_blank" class="btn btn-info btn-sm">Download Chapter 3</a></p>
                                    <?php else : ?>
                                        <p>No Chapter 3 File uploaded by user.</p>
                                    <?php endif; ?>

                                    <?php if ($request['questionaire']) : ?>
                                        <p><strong>Questionnaire:</strong> <a href="../<?php echo htmlspecialchars($request['questionaire']); ?>" target="_blank" class="btn btn-info btn-sm">Download Questionnaire</a></p>
                                    <?php else : ?>
                                        <p>No Questionnaire uploaded by user.</p>
                                    <?php endif; ?>

                                    <?php if ($request['completed_work']) : ?>
                                        <p><strong>Completed Work:</strong> <a href="../<?php echo htmlspecialchars($request['completed_work']); ?>" target="_blank" class="btn btn-success btn-sm">Download Completed Work</a></p>
                                    <?php else : ?>
                                        <p>No Completed Work uploaded yet.</p>
                                    <?php endif; ?>

                                    <hr>

                                    <h5>Update Status:</h5>
                                    <form action="" method="POST" class="mb-3">
                                        <div class="input-group">
                                            <select name="new_status" class="form-control">
                                                <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $request['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                                        </div>
                                    </form>

                                    <h5>Upload Completed Work:</h5>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="input-group">
                                            <input type="file" name="completed_work" class="form-control" required>
                                            <button type="submit" class="btn btn-success">Upload Work</button>
                                        </div>
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