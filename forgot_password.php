<?php
require_once 'db/config.php';
require_once 'db/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $user = get_user_by_email($email);

        if ($user) {
            // Generate a unique, secure token
            $token = bin2hex(random_bytes(50));
            $expires = new DateTime('+1 hour');
            $expires_str = $expires->format('Y-m-d H:i:s');

            // Store the token in the database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            if ($stmt->execute([$token, $expires_str, $user['id']])) {
                // Send the password reset link to the user's email
                $url = "http://{$_SERVER['HTTP_HOST']}/";
                $reset_link = $url."reset_password.php?token=$token";
                
                // For now, we will just display the link on the screen
                $success = "A password reset link has been sent to your email address. Please check your inbox. <br><strong>For testing purposes, here is the link:</strong> <a href='$reset_link'>$reset_link</a>";

                // In a real application, you would email the link:
                // $subject = "Password Reset Request";
                // $message = "Please click on the following link to reset your password: $reset_link";
                // $headers = "From: no-reply@yourdomain.com";
                // mail($email, $subject, $message, $headers);

            } else {
                $error = 'Failed to generate a reset token. Please try again.';
            }
        } else {
            // To prevent user enumeration, we don't reveal if the email exists or not
            $success = "If an account with that email address exists, a password reset link has been sent.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Forgot Password - Data Analysis Global Services</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="forgot-password-page">
  <?php include('nav/header.php'); ?>
  <main class="main">
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Forgot Your Password?</h1>
              <p class="mb-0">Enter your email address and we will send you a link to reset your password.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Forgot Password Section -->
    <section id="forgot-password" class="contact section">
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
                <form method="post" action="">
                  <div class="row gy-4">
                    <div class="col-12">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Forgot Password Section -->

  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>
