<?php
require_once 'db/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $country = trim($_POST['country']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (role,username, email, password, first_name, last_name, phone, address, city, country) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(['customer',$username, $email, $hashed_password, $first_name, $last_name, $phone, $address, $city, $country]);
                
                $success = 'Registration successful! You can now login.';
                
                // Clear form data
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Register - Data Analysis Global Services</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="index-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
      <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <p class="mb-20"></p>
            <h1>Create Your Account</h1>
            </div>
          </div>
        </div>
      </div>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Register</li>
          </ol>
        </nav>
        
    
    </div><!-- End Page Title -->

    <!-- Register Section -->
    <section id="register" class="contact section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">

          <div class="col-lg-8">
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
                    <br><a href="login.php" class="btn btn-primary mt-2">Login Now</a>
                  </div>
                <?php endif; ?>

                <form method="post" >
                  <div class="row gy-4">

                    <div class="col-md-6">
                      <label for="first_name" class="form-label">First Name *</label>
                      <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="last_name" class="form-label">Last Name *</label>
                      <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="username" class="form-label">Username *</label>
                      <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="email" class="form-label">Email *</label>
                      <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="password" class="form-label">Password *</label>
                      <input type="password" name="password" id="password" class="form-control" required minlength="6">
                      <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label">Confirm Password *</label>
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <div class="col-md-6">
                      <label for="phone" class="form-label">Phone</label>
                      <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="col-md-6">
                      <label for="city" class="form-label">City</label>
                      <input type="text" name="city" id="city" class="form-control" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>

                    <div class="col-md-6">
                      <label for="country" class="form-label">Country</label>
                      <input type="text" name="country" id="country" class="form-control" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
                    </div>

                    <div class="col-12">
                      <label for="address" class="form-label">Address</label>
                      <textarea name="address" id="address" class="form-control" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-primary w-100">Create Account</button>
                    </div>

                    <div class="col-12 text-center">
                      <p class="mt-3">
                        Already have an account? <a href="login.php">Login here</a>
                      </p>
                    </div>

                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Register Section -->

  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>
