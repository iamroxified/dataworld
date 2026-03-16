<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{

// Get current user
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));


// Get referral details
if(isset($_GET['id'])){
    $referral_id = $_GET['id'];
    $referral_details = get_user_details($referral_id);
} else {
    header('Location: referral.php');
}

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>View Referral - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">My Referrals</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="referrals">My Referrals</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Referral Details</a></li>
            </ul>
          </div>
          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Referral Details</h3>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title"><?php echo htmlspecialchars($referral_details['first_name'] . ' ' . $referral_details['last_name']); ?></h4>
                </div>
                <div class="card-body">
                  <p><strong>Username:</strong> <?php echo htmlspecialchars($referral_details['username']); ?></p>
                  <p><strong>Email:</strong> <?php echo htmlspecialchars($referral_details['email']); ?></p>
                  <p><strong>Phone:</strong> <?php echo htmlspecialchars($referral_details['phone']); ?></p>
                  <p><strong>Registration Date:</strong> <?php echo date('M d, Y', strtotime($referral_details['created_at'])); ?></p>
                  <a href="referrals.php" class="btn btn-primary">Back to Referrals</a>
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