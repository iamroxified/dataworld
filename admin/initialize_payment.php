<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));

if (!isset($_GET['order_id'])) {
    header('Location: analytics_request.php');
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: analytics_request.php');
    exit;
}

$current_user = getCurrentUser();

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Initialize Payment - SYi - Tech Global Services</title>
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Complete Your Payment</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                </div>
                                <div class="card-body text-center">
                                    <p>You are about to pay <strong><?php echo htmlspecialchars($order['total_amount']); ?></strong> for your analytics request.</p>
                                    <button type="button" onclick="payWithPaystack()" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
            <script src="https://js.paystack.co/v1/inline.js"></script>
            <script>
                function payWithPaystack() {
                    var handler = PaystackPop.setup({
                        key: 'pk_test_4c2ce07c1da17f64ed2bd277b62efdf800fed7ad', // Replace with your public key
                        email: '<?php echo htmlspecialchars($current_user['email']); ?>',
                        amount: <?php echo $order['total_amount'] * 100; ?>,
                        currency: 'NGN', // Assuming NGN, you can make this dynamic
                        ref: '' + Math.floor((Math.random() * 1000000000) + 1),
                        metadata: {
                            order_id: <?php echo $order_id; ?>,
                            user_id: <?php echo $user_id; ?>
                        },
                        callback: function(response) {
                            // Verify payment
                            var reference = response.reference;
                            window.location.href = 'verify_payment.php?reference=' + reference;
                        },
                        onClose: function() {
                            alert('window closed');
                        }
                    });
                    handler.openIframe();
                }
            </script>
        </div>
    </div>
</body>

</html>