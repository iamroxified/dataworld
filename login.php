<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();

require_once 'db/config.php';


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'].' '.$user['last_name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Redirect to intended page or analytics
                    if($user['role'] == 'customer'  ){
                        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user/index';
                    } else {
                        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'admin/index';
                    }
                    // $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user/index';
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
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
                      <p class="mt-1"><a href="forgot_password.php">Forgot Password?</a></p>
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