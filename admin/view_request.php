<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit;
}

  $user_id = $_SESSION['user_id'];
  $uss = extract(get_user_details($user_id));
// Check if request ID is provided
if (!isset($_GET['id'])) {
    header('Location: analytics_request.php');
    exit;
}

$request_id = $_GET['id'];

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $order_id = $_POST['order_id']; // Getting order_id from a hidden input

    // Fetch the current order status to validate transition
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

    $allowed_transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['completed']
    ];

    if (isset($allowed_transitions[$current_status]) && in_array($new_status, $allowed_transitions[$current_status])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $order_id])) {
            header("Location: view_request.php?id=$request_id&status_updated=true");
            exit;
        }
    }
}

// Handle completed work upload
if (isset($_POST['upload_completed_work'])) {
    if (isset($_FILES['completed_work']) && $_FILES['completed_work']['error'] == 0) {
        $upload_dir = '../uploads/completed_analytics/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['completed_work']['name'], PATHINFO_EXTENSION);
        $completed_work_file = 'uploads/completed_analytics/' . uniqid() . '.' . $file_extension;

        if (move_uploaded_file($_FILES['completed_work']['tmp_name'], '../' . $completed_work_file)) {
            $stmt = $pdo->prepare("UPDATE analytics_requests SET completed_work = ? WHERE id = ?");
            if ($stmt->execute([$completed_work_file, $request_id])) {
                header("Location: view_request.php?id=$request_id&upload_success=true");
                exit;
            }
        }
    }
}


// Fetch analytics request details
$stmt = $pdo->prepare("SELECT ar.*, o.id as order_id, o.order_number, o.status as order_status, o.payment_status, u.first_name, u.last_name, u.email FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id LEFT JOIN users u ON ar.user_id = u.id WHERE ar.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    // Request not found
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
            <h3 class="fw-bold mb-3">View Analytics Request</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">All Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">View Request</a></li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                  <div class="d-flex align-items-center">
                    <h4 class="mb-0">
                      <i class="fas fa-wallet"></i> View Analytics Request Details
                    </h4>
                    <a href="analytics_request.php" class="btn btn-primary btn-round ms-auto">
                      <i class="fa fa-caret-left"></i>
                      All Analytics Requests
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
                  <?php if (isset($_GET['status_updated'])): ?>
                    <div class="alert alert-success">Status updated successfully!</div>
                  <?php endif; ?>
                  <?php if (isset($_GET['upload_success'])): ?>
                    <div class="alert alert-success">Completed work uploaded successfully!</div>
                  <?php endif; ?>
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>User:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                      <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
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
                      <?php if ($request['completed_work']): ?>
                      <p><strong>Completed Analytics:</strong> <a
                          href="../<?php echo htmlspecialchars($request['completed_work']); ?>" target="_blank">Download
                          Completed Work</a></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Update Status</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $request['order_id']; ?>">
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
                            <?php if ($request['order_status'] === 'pending' || $request['order_status'] === 'processing'): ?>
                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                            <?php else: ?>
                            <p>No status updates available.</p>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <?php if ($request['order_status'] === 'completed'): ?>
                <div class="card">
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
            </div>
          </div>
          <?php include('nav/footer.php'); ?>
        </div>
      </div>
</body>

</html>