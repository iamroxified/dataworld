<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db/config.php';


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='alert alert-info'>Form submitted - processing login...</div>";
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<div class='alert alert-info'>Email: " . htmlspecialchars($email) . "</div>";
    echo "<div class='alert alert-info'>Password length: " . strlen($password) . "</div>";
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
        echo "<div class='alert alert-warning'>Empty fields detected</div>";
    } else {
        try {
            echo "<div class='alert alert-info'>Attempting database query...</div>";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<div class='alert alert-info'>User found: " . htmlspecialchars($user['name']) . "</div>";
                echo "<div class='alert alert-info'>Verifying password...</div>";
                
                if (password_verify($password, $user['password'])) {
                    echo "<div class='alert alert-success'>Password verified! Setting session...</div>";
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'].' '.$user['last_name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    echo "<div class='alert alert-success'>Session set. Redirecting...</div>";
                    
                    // Add a small delay to see the debug messages
                    echo "<script>setTimeout(function() { window.location = ''; }, 2000);</script>";
                    
                    // Redirect to intended page or analytics
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard';
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Password verification failed</div>";
                    $error = 'Invalid email or password.';
                }
            } else {
                echo "<div class='alert alert-danger'>No user found with email: " . htmlspecialchars($email) . "</div>";
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Login - Data Analysis Global Services</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="login-page">
  <?php include('nav/header.php'); ?>
  <main class="main">
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Login to Your Account</h1>
              <p class="mb-0"></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Login Section -->
    <section id="login" class="contact section">
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
                    <?php echo htmlspecialchars($success); ?>
                  </div>
                <?php endif; ?>
                <form method="post" action="" class="">
                  <div class="row gy-4">
                    <div class="col-12">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="col-12">
                      <label for="password" class="form-label">Password</label>
                      <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="col-12 text-center">
                      <button type="submit"  class="btn btn-primary w-100">Login</button>
                    </div>
                    <div class="col-12 text-center">
                      <p class="mt-3"> Don't have an account? <a href="register.php">Register here</a></p>
                      <p>
                        <strong>Demo Accounts:</strong><br>
                        <small>
                          <strong>Admin:</strong> admin@test.com / admin123<br>
                          <strong>Customer:</strong> john@test.com / password123
                        </small>
                      </p>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Login Section -->

  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>
