<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit;
}

// Get current user
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));
// Check if request ID is provided
if (!isset($_GET['id'])) {
    header('Location: analytics_request.php');
    exit;
}

$request_id = $_GET['id'];

// Fetch analytics request details
$stmt = $pdo->prepare("SELECT ar.*, o.order_number, o.status as order_status, o.payment_status FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id WHERE ar.id = ? AND ar.user_id = ?");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    // Request not found or doesn't belong to the user
    header('Location: analytics_request.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>View Request - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
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
            <h3 class="fw-bold mb-3">My Analytics Requests</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">My Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">View Request</a></li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                  <div class="d-flex align-items-center">
                    <h4 class="mb-0">
                      <i class="fas fa-wallet"></i> View Analytics Request Details
                    </h4>
                    <a href="analytics_request.php" class="btn btn-primary btn-round ms-auto">
                      <i class="fa fa-caret-left"></i>
                      Analytics Request
                    </a>
                  </div>
                </div>

              </div>
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Request Details (Order
                    #<?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?>)</h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>Project Topic:</strong> <?php echo htmlspecialchars($request['project_topic']); ?></p>
                      <p><strong>Program Type:</strong> <?php echo htmlspecialchars($request['program_type']); ?></p>
                      <p><strong>Software:</strong> <?php echo htmlspecialchars($request['software']); ?></p>
                      <p><strong>Institution:</strong> <?php echo htmlspecialchars($request['institution']); ?></p>
                      <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                      <p><strong>State:</strong> <?php echo htmlspecialchars($request['state']); ?></p>
                      <p><strong>Country:</strong> <?php echo htmlspecialchars($request['country']); ?></p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Amount:</strong>
                        <?php echo $request['currency']; ?><?php echo number_format($request['payment_amount'], 2); ?>
                      </p>
                      <p><strong>Payment Status:</strong> <span
                          class="badge bg-<?php echo $request['payment_status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($request['payment_status']); ?></span>
                      </p>
                      <p><strong>Delivery Status:</strong> <span
                          class="badge bg-<?php echo $request['status'] === 'completed' ? 'success' : ($request['status'] === 'processing' ? 'info' : 'warning'); ?>"><?php echo ucfirst($request['status']); ?></span>
                      </p>
                      <p><strong>Project Completion Status:</strong> <span
                          class="badge bg-<?php echo $request['order_status'] === 'completed' ? 'success' : ($request['order_status'] === 'processing' ? 'info' : ($request['order_status'] === 'cancelled' ? 'danger' : 'warning')); ?>"><?php echo ucfirst($request['order_status']); ?></span>
                      </p>
                      <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></p>
                      <p><strong>Progress:</strong></p>
                      <?php if ($request['order_status'] === 'completed'): ?>
                      <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100"
                          aria-valuemin="0" aria-valuemax="100">100%</div>
                      </div>
                      <small class="text-success">Delivered to email</small>
                      <?php elseif ($request['order_status'] === 'processing'): ?>
                      <div class="progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60"
                          aria-valuemin="0" aria-valuemax="100">60%</div>
                      </div>
                      <small class="text-info">In progress (3-5 days)</small>
                      <?php elseif ($request['order_status'] === 'cancelled'): ?>
                      <div class="progress">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" aria-valuenow="1000"
                          aria-valuemin="0" aria-valuemax="100">0%</div>
                      </div>
                      <small class="text-danger ">Request Cancelled</small>
                      <?php else: ?>
                      <div class="progress">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 25%" aria-valuenow="25"
                          aria-valuemin="0" aria-valuemax="100">25%</div>
                      </div>
                      <small class="text-warning">Payment verification</small>

                      <?php endif; ?>
                      <?php if ($request['payment_status'] === 'pending' || $request['payment_status'] === 'failed'): ?>
                      <p><a href="initialize_payment.php?order_id=<?php echo $request['order_id']; ?>"
                          class="btn btn-primary mt-2">Retry Payment</a></p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <?php if ($request['chapter3_file']): ?>
                      <p><strong>Chapter 3 File:</strong> <a
                          href="../<?php echo htmlspecialchars($request['chapter3_file']); ?>" target="_blank">Download
                          File</a></p>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <?php if ($request['questionaire']): ?>
                      <p><strong>Questionaire:</strong> <a
                          href="../<?php echo htmlspecialchars($request['questionaire']); ?>" target="_blank">Download
                          Questionaire</a></p>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <?php if (isset($request['completed_work']) && $request['completed_work']): ?>
                      <p><strong>Completed Analytics:</strong> <a
                          href="../<?php echo htmlspecialchars($request['completed_work']); ?>" target="_blank">Download
                          Completed Work</a></p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>Re-upload Chapter 3 File:</strong>
                        <form method="post" enctype="multipart/form-data">
                          <input type="file" name="chapter3_file" required>
                          <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                        </form>
                        <?php
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["chapter3_file"])) {
                    $upload_dir = '../uploads/chapter3/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['chapter3_file']['name'], PATHINFO_EXTENSION);
        $chapter3_file = 'uploads/chapter3/' . uniqid() . '.' . $file_extension;
    
                                if (move_uploaded_file($_FILES["chapter3_file"]["tmp_name"], '../'.$chapter3_file)) {
                                    // Update the analytics_requests table with the new file path
                                    $stmt = $pdo->prepare("UPDATE analytics_requests SET chapter3_file = ? WHERE id = ? AND user_id = ?");
                                    $stmt->execute([$chapter3_file, $request_id, $user_id]);
        
                                    header('Location: view_request.php?id=' . $request_id . '&upload=success');
                                    exit;
                                } else {
                                    header('Location: view_request.php?id=' . $request_id . '&upload=failed');
                                    exit;
                                }
                            }
                          ?>

                      </p>
                      <p><strong>Re-upload Questionnaire:</strong>
                        <form method="post" enctype="multipart/form-data">
                          <input type="file" name="questionnaire" required>
                          <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                        </form>
                        <?php
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["questionnaire"])) {
                                $upload_dir = '../uploads/questionaire/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                $file_extension = pathinfo($_FILES['questionnaire']['name'], PATHINFO_EXTENSION);
                                $questionnaire_file = 'uploads/questionaire/' . uniqid() . '.' . $file_extension;

                                if (move_uploaded_file($_FILES["questionnaire"]["tmp_name"], '../' . $questionnaire_file)) {
                                    // Update the analytics_requests table with the new file path
                                    $stmt = $pdo->prepare("UPDATE analytics_requests SET questionaire = ? WHERE id = ? AND user_id = ?");
                                    $stmt->execute([$questionnaire_file, $request_id, $user_id]);

                                    header('Location: view_request.php?id=' . $request_id . '&upload=success');
                                    exit;
                                } else {
                                    header('Location: view_request.php?id=' . $request_id . '&upload=failed');
                                    exit;
                                }
                            }
                        ?>
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