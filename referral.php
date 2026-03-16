<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db/config.php';
requireLogin();

// Get current user
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();

// Generate referral code (you might want to store this in the database)
$referral_code = generateReferralCode($user_id);

// Function to generate referral code
function generateReferralCode($user_id) {
    // Basic example: Encode user ID, you can add more complexity
    return base64_encode("USER" . $user_id);
}

// Get referral link
$referral_link = getBaseURL() . "/register.php?referral=" . $referral_code;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Program - DataWorld</title>
    <?php include('nav/links.php'); ?>
</head>

<body class="blog-page">
    <?php include('nav/header.php'); ?>

    <main class="main">
        <!-- Page Title -->
        <div class="page-title">
            <div class="heading">
                <div class="container">
                    <div class="row d-flex justify-content-center text-center">
                        <div class="col-lg-8">
                            <h1>Affiliate Program</h1>
                            <p class="mb-0">Share your referral code and earn commission!</p>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="breadcrumbs">
                <div class="container">
                    <ol>
                        <li><a href="index">Home</a></li>
                        <li class="current">Affiliate Program</li>
                    </ol>
                </div>
            </nav>
        </div>

        <div class="container section">
            <!-- User Welcome -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4><i class="bi bi-person-circle"></i> Welcome,
                            <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>!</h4>
                        <p class="mb-0">Start sharing your referral code to earn commissions on every successful signup.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Referral Code and Link -->
            <div class="row mb-5">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-code-square"></i> Your Referral Code</h5>
                            <p class="card-text"><strong><?php echo htmlspecialchars($referral_code); ?></strong></p>
                            <button class="btn btn-primary copy-code-btn" data-clipboard-text="<?php echo htmlspecialchars($referral_code); ?>">
                                <i class="bi bi-clipboard"></i> Copy Code
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-link-45deg"></i> Your Referral Link</h5>
                            <p class="card-text">
                                <a href="<?php echo htmlspecialchars($referral_link); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($referral_link); ?>
                                </a>
                            </p>
                            <button class="btn btn-success copy-link-btn" data-clipboard-text="<?php echo htmlspecialchars($referral_link); ?>">
                                <i class="bi bi-clipboard"></i> Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How it Works Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-question-circle"></i> How It Works</h5>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>Share your unique referral code or link with friends, family, and colleagues.</li>
                                <li>When someone signs up using your referral code, they become your referral.</li>
                                <li>You earn a commission on their first purchase.</li>
                            </ol>
                            <p><strong>Commission Rate:</strong> 10% on the first purchase made by your referrals.</p>
                            <p><strong>Payout:</strong> Commissions are paid out monthly via PayPal.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-info-circle"></i> Terms and Conditions</h5>
                        </div>
                        <div class="card-body">
                            <p>Referrals must be new users who have not previously registered on DataWorld.</p>
                            <p>Commissions are only paid on the first purchase made by a referral.</p>
                            <p>DataWorld reserves the right to modify the affiliate program terms at any time.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include('nav/footer.php'); ?>
    <!-- Clipboard.js -->
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var clipboardCode = new ClipboardJS('.copy-code-btn');

            clipboardCode.on('success', function(e) {
                console.log(e);
            });

            clipboardCode.on('error', function(e) {
                console.log(e);
            });

            var clipboardLink = new ClipboardJS('.copy-link-btn');

            clipboardLink.on('success', function(e) {
                console.log(e);
            });

            clipboardLink.on('error', function(e) {
                console.log(e);
            });
        });
    </script>
</body>

</html>