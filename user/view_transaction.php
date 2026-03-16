<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: wallet.php');
    exit();
}

$transaction_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch transaction details
$stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$transaction_id, $user_id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    // Transaction not found or doesn't belong to the user
    header('Location: wallet.php');
    exit();
}

// Get user details
$user_details = get_user_details($user_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Transaction Receipt - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .receipt-header h2 {
            margin: 0;
        }
        .receipt-details p {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .receipt-details p span:first-child {
            font-weight: bold;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt-container, .receipt-container * {
                visibility: visible;
            }
            .receipt-container {
                    /* margin: 2rem auto;
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: .25rem; */
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
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
                        <h3 class="fw-bold mb-3">Transaction Receipt</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="wallet.php">My Wallet</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Transaction Receipt</a></li>
                        </ul>
                    </div>

                    <div class="receipt-container">
                        <div class="receipt-header">
                            <h2>Transaction Receipt</h2>
                            <p>SYi - Tech Global Services</p>
                        </div>
                        <div class="receipt-details">
                            <p><span>Transaction ID:</span> <span><?php echo htmlspecialchars($transaction['reference']); ?></span></p>
                            <p><span>Date:</span> <span><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></span></p>
                            <p><span>Customer Name:</span> <span><?php echo htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']); ?></span></p>
                            <p><span>Transaction Type:</span> <span><?php echo ucfirst($transaction['transaction_type']); ?></span></p>
                            <p><span>Description:</span> <span><?php echo htmlspecialchars($transaction['description']); ?></span></p>
                            <p><span>Amount:</span> <span>₦<?php echo number_format($transaction['amount'], 2); ?></span></p>
                            <p><span>Status:</span> <span><?php echo ucfirst($transaction['status']); ?></span></p>
                        </div>
                        <div class="text-center mt-4 no-print">
                            <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                            <a href="wallet.php" class="btn btn-secondary">Back to Wallet</a>
                        </div>
                    </div>

                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
</body>
</html>
