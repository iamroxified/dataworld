<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{

// Get current user
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));
$user_referral_code = $code;

// Fetch referred users
$stmt = $pdo->prepare("SELECT * FROM users WHERE referral = ?");
$stmt->execute([$user_referral_code]);
$referrals = $stmt->fetchAll();

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>My Referrals - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">My Referrals</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <!-- <li class="nav-item"><a href="analytics_request.php">My Analytics Requests</a></li> -->
              <!-- <li class="separator"><i class="icon-arrow-right"></i></li> -->
              <li class="nav-item"><a href="#">My Referrals</a></li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card-body">
                <div class="row">
                  <div class="col-sm-12 col-lg-12">
                    <a href="#" onclick="copyToClipboard()">
                      <div class="card card-stats card-success card-round">
                        <div class="card-body">
                          <div class="row align-items-center">
                            <div class="col-11">
                              <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                  <h4 class="card-title"> <i class="fas fa-link"></i> Join SYi-Tech</h4>
                                  <p class="card-category">Share Referral Link </p>
                                </div>
                              </div>
                              <input type="text" id="copyText"
                                value="https://www.syitech.com.ng/register?referral=<?php echo $username; ?>"
                                class="form-control" readonly>
                            </div>
                            <div class="col-1 ">
                              <div class="icon-big text-center ">
                                <i class="fa fa-solid fa-arrow-right"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </a>
                  </div>

                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Users you have referred</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="referrals-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>SN</th>
                          <th>Username</th>
                          <th>Full Name</th>
                          <th>Email</th>
                          <th>Registration Date</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sn=1; foreach ($referrals as $referral): ?>
                        <tr>
                          <td><?php echo $sn++; ?></td>
                          <td><?php echo htmlspecialchars($referral['username']); ?></td>
                          <td><?php echo htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']); ?>
                          </td>
                          <td><?php echo htmlspecialchars($referral['email']); ?></td>
                          <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                          <td><a href="view_referral.php?id=<?php echo $referral['id']; ?>"
                              class="btn btn-primary btn-sm">View</a></td>
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
        $(document).ready(function () {
          $('#referrals-table').DataTable();
        });
      </script>
    </div>
  </div>
</body>

</html>