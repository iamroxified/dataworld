<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operator'){
  header('Location:login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$uss = extract(get_user_details($user_id));

// Fetch all analytics requests with order and user information
$analytics_query = "SELECT ar.*, o.order_number, o.status as order_status, o.payment_status, u.first_name, u.last_name FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id LEFT JOIN users u ON ar.user_id = u.id WHERE o.payment_status = 'completed' ORDER BY ar.created_at DESC";
$analytics_stmt = $pdo->prepare($analytics_query);
$analytics_stmt->execute();
$analytics_requests = $analytics_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Analytics Requests - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">Analytics Requests</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Analytics Requests</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Analytics Requests History</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="analytics-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>SN</th>
                          <th>User</th>
                          <th>Order #</th>
                          <th>Project Topic</th>
                          <th>Amount</th>
                          <th>Order Status</th>
                          <th>Payment Status</th>
                          <th>Date</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sn=1; foreach ($analytics_requests as $request): ?>
                        <tr>
                          <td><?php echo $sn++;?></td>
                          <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                          <td><strong><?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></strong></td>
                          <td>
                            <strong><?php echo htmlspecialchars(substr($request['project_topic'], 0, 50)); ?>...</strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($request['software']); ?>
                              Analysis with <?php echo htmlspecialchars($request['program_type']); ?></small>
                          </td>
                          <td>
                            <?php echo $request['currency']; ?><?php echo number_format($request['payment_amount'], 2); ?>
                          </td>
                          <td>
                            <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'delivered' => 'primary',
                                    'rejected' => 'danger'
                                ];
                                $class = $status_class[$request['order_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>">
                              <?php echo ucfirst($request['order_status']); ?>
                            </span>
                          </td>
                          <td>
                            <?php
                                $payment_status_class = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'failed' => 'danger'
                                ];
                                $p_class = $payment_status_class[$request['payment_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $p_class; ?>">
                              <?php echo ucfirst($request['payment_status']); ?>
                            </span>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                          <td>
                            <a href="view_request.php?id=<?php echo $request['id']; ?>"
                              class="btn btn-primary btn-sm">View</a>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#uploadModal" data-request-id="<?php echo $request['id']; ?>">
                                Upload Work
                            </button>
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

  <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Completed Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="upload_completed_work.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="request_id" id="request_id">
                        <div class="form-group">
                            <label for="completed_work">Completed Work File</label>
                            <input type="file" class="form-control-file" id="completed_work" name="completed_work" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

  <!-- Add datatables js -->
  <script src="assets/js/datatables.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#analytics-table').DataTable();

        var uploadModal = document.getElementById('uploadModal')
        uploadModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget
            var requestId = button.getAttribute('data-request-id')
            var modalBodyInput = uploadModal.querySelector('.modal-body #request_id')
            modalBodyInput.value = requestId
        })
    });
  </script>
</body>

</html>