<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:login.php');
  exit;
}

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id === 0) {
    header('Location: analytics_request.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    $order_id = $_POST['order_id'];

    $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($update_stmt->execute([$new_status, $order_id])) {
        // Redirect to the same page to see the change
        header("Location: order-details.php?id=$request_id&update=success");
        exit;
    } else {
        $error = "Failed to update status.";
    }
}


// Fetch the analytics request details
$stmt = $pdo->prepare("SELECT ar.*, o.id as order_id, o.order_number, o.status as order_status, o.payment_status, u.first_name, u.last_name, u.email, u.phone FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id LEFT JOIN users u ON ar.user_id = u.id WHERE ar.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: analytics_request.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Order Details - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">Order Details</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">All Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Order #<?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-8">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Request Details</h4>
                </div>
                <div class="card-body">
                   <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                        <div class="alert alert-success">Order status updated successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                  <h5>Project Topic</h5>
                  <p><?php echo htmlspecialchars($request['project_topic']); ?></p>

                  <h5>Software for Analysis</h5>
                  <p><?php echo htmlspecialchars($request['software']); ?></p>

                  <h5>Program Type</h5>
                  <p><?php echo htmlspecialchars($request['program_type']); ?></p>

                  <h5>Project Description</h5>
                  <p><?php echo nl2br(htmlspecialchars($request['project_description'])); ?></p>
                  
                  <hr>

                  <h5>Uploaded Files</h5>
                  <p><a href="../user/uploads/questionaire/<?php echo htmlspecialchars($request['questionnaire_file']); ?>" target="_blank">View Questionnaire</a></p>
                  <p><a href="../user/uploads/chapter3/<?php echo htmlspecialchars($request['chapter3_file']); ?>" target="_blank">View Chapter 3</a></p>

                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Order & User Info</h4>
                </div>
                <div class="card-body">
                  <p><strong>Order #:</strong> <?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></p>
                  <p><strong>User:</strong> <?php echo htmlspecialchars($request['first_name']." ".$request['last_name']); ?></p>
                  <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                  <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                  <p><strong>Amount:</strong> <?php echo htmlspecialchars($request['currency']); ?><?php echo number_format($request['payment_amount'], 2); ?></p>
                  <p><strong>Payment Status:</strong> <span class="badge bg-<?php echo in_array(($request['payment_status'] ?? ''), ['paid', 'completed', 'success'], true) ? 'success' : (($request['payment_status'] ?? '') === 'failed' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($request['payment_status']); ?></span></p>
                  <p><strong>Order Status:</strong> <span class="badge bg-info"><?php echo ucfirst($request['order_status']); ?></span></p>
                  <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></p>
                  
                  <hr>
                  <h5>Update Order Status</h5>
                  <form method="POST">
                      <input type="hidden" name="order_id" value="<?php echo $request['order_id']; ?>">
                      <div class="form-group">
                          <select name="order_status" class="form-control">
                              <option value="pending" <?php echo $request['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                              <option value="processing" <?php echo $request['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                              <option value="completed" <?php echo $request['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                              <option value="delivered" <?php echo $request['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                              <option value="rejected" <?php echo $request['order_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                          </select>
                      </div>
                      <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
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
