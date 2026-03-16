<?php
require_once 'db/config.php';
require_once 'db/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Check if the token is valid
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Invalid or expired password reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($password_confirm)) {
        $error = 'Please enter and confirm your new password.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the password and clear the reset token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = 'Your password has been reset successfully. You can now <a href="login.php">login</a> with your new password.';
        } else {
            $error = 'Failed to reset your password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Reset Password - Data Analysis Global Services</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="reset-password-page">
  <?php include('nav/header.php'); ?>
  <main class="main">
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Reset Your Password</h1>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset Password Section -->
    <section id="reset-password" class="contact section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row justify-content-center">
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body p-4">
                <?php if ($error): ?>
                  <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                  </div>
                <?php endif; ?>
                <?php if ($success): ?>
                  <div class="alert alert-success" role="alert">
                    <?php echo $success; // Not escaping HTML here to allow the link to be clickable ?>
                  </div>
                <?php endif; ?>

                <?php if ($user && !$success): ?>
                  <form method="post" action="">
                    <div class="row gy-4">
                      <div class="col-12">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                      </div>
                      <div class="col-12">
                        <label for="password_confirm" class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                      </div>
                      <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                      </div>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Reset Password Section -->

  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>
