<?php
require_once 'db/config.php';

$error = '';
$success = '';

// Set referral code for the input field value
$referral_code_for_input = isset($_GET['referral']) ? trim($_GET['referral']) : 'syitech';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);
    $referral_code = trim($_POST['referral_code']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($referral_code)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists.';
                } else {
                    // Check if referral code exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE code = ?");
                    $stmt->execute([$referral_code]);
                    if ($stmt->fetch() === false) {
                        $error = 'The provided referral code does not exist.';
                    } else {
                        // All checks passed, create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("INSERT INTO users (role, username, email, password, first_name, last_name, phone, city, country, referral, code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
                        $stmt->execute(['customer', $username, $email, $hashed_password, $first_name, $last_name, $phone, $state, $country, $referral_code,$username]);
                        
                        $success = 'Registration successful! You can now log in.';
                        // Clear form data after successful registration
                        $_POST = []; 
                        // After clearing post, we need to reset the referral code for the input field
                        $referral_code_for_input = 'syitech';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

?>

<?php
// This block is now only for displaying alerts
if ($error) {
    echo "<script>alert('$error');</script>";
}
if ($success) {
    echo "<script>alert('$success');</script>";
}
?>

<?php
// Fetch countries from the database
$stmt = $pdo->prepare("SELECT * FROM countries");
$stmt->execute();
$countries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <?php include('nav/links.php'); ?>
  <style>
    .feedback {
      font-size: 0.9em;
      margin-top: 5px;
    }
    .feedback.available {
      color: green;
    }
    .feedback.taken, .feedback.invalid {
      color: red;
    }
  </style>
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
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                      <?php echo htmlspecialchars($success); ?>
                      <br><a href="login.php" class="btn btn-primary mt-2">Login Now</a>
                    </div>
                <?php endif; ?>

                <form id="register-form" method="post">
                  <div class="row gy-4">

                    <div class="col-md-6">
                      <label for="first_name" class="form-label">First Name *</label>
                      <input type="text" name="first_name" id="first_name" class="form-control"
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                        required>
                    </div>

                    <div class="col-md-6">
                      <label for="last_name" class="form-label">Last Name *</label>
                      <input type="text" name="last_name" id="last_name" class="form-control"
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                        required>
                    </div>

                    <div class="col-md-6">
                      <label for="username" class="form-label">Username *</label>
                      <input type="text" name="username" id="username" class="form-control"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required>
                      <div id="username-feedback" class="feedback"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="email" class="form-label">Email *</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                      <div id="email-feedback" class="feedback"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="password" class="form-label">Password *</label>
                      <input type="password" name="password" id="password" class="form-control" required minlength="6">
                      <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label">Confirm Password *</label>
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" required
                        minlength="6">
                    </div>

                    <div class="col-md-6">
                      <label for="phone" class="form-label">Phone</label>
                      <input type="tel" name="phone" id="phone" class="form-control"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

      

                    <div class="col-md-6">
                      <label for="Country" class="form-label">Country</label>
                      <select class="form-control" id="Country" name="country">
                        <option value="">Select Country</option>
                        <?php foreach ($countries as $country): ?>
                        <option value="<?php echo $country['id']; ?>"
                          <?php if (isset($_POST['country']) && $_POST['country'] == $country['id']) echo 'selected'; ?> >
                          <?php echo htmlspecialchars($country['name']); ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-6" id="state-container">
                      <label for="state" class="form-label">State</label>
                      <select class="form-control" id="state" name="state">
                        <option value="">Select State</option>
                      </select>
                    </div>


                    <div class="col-12">
                        <label for="referral_code" class="form-label">Referral Code *</label>
                        <input type="text" class="form-control" id="referral_code" name="referral_code" value="<?php echo htmlspecialchars($referral_code_for_input); ?>" required>
                        <div id="referral-feedback" class="feedback"></div>
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
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const countrySelect = document.getElementById('Country');
    const stateSelect = document.getElementById('state');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const referralInput = document.getElementById('referral_code');
    const usernameFeedback = document.getElementById('username-feedback');
    const emailFeedback = document.getElementById('email-feedback');
    const referralFeedback = document.getElementById('referral-feedback');

    let isUsernameAvailable = false;
    let isEmailAvailable = false;
    let isReferralValid = false;

    // --- Event Listeners ---
    countrySelect.addEventListener('change', function () {
        const countryId = this.value;
        if (countryId) {
            fetchStates(countryId);
        } else {
            stateSelect.innerHTML = '<option value="">Select State</option>';
        }
    });

    usernameInput.addEventListener('input', function() {
        isUsernameAvailable = false;
        const username = this.value;
        if (username.length > 2) {
            checkAvailability('username', username, usernameFeedback, false);
        } else {
            usernameFeedback.textContent = '';
        }
    });

    emailInput.addEventListener('input', function() {
        isEmailAvailable = false;
        const email = this.value;
        if (email.length > 5 && email.includes('@')) {
            checkAvailability('email', email, emailFeedback, false);
        } else {
            emailFeedback.textContent = '';
        }
    });

    referralInput.addEventListener('input', function() {
        isReferralValid = false;
        const code = this.value;
        if (code.length > 0) {
            checkAvailability('referral_code', code, referralFeedback, true);
        } else {
            referralFeedback.textContent = '';
        }
    });

    // Trigger validation on page load for referral code
    if (referralInput.value.length > 0) {
        checkAvailability('referral_code', referralInput.value, referralFeedback, true);
    }

    form.addEventListener('submit', function(event) {
        if (isUsernameAvailable || isEmailAvailable || isReferralValid) {
            event.preventDefault();
            let message = 'Please fix the following issues before creating an account:\n';
            if (isUsernameAvailable) {
                message += '- The username is either already taken, too short, or has not been validated.\n';
            }
            if (isEmailAvailable) {
                message += '- The email is either already taken, invalid, or has not been validated.\n';
            }
            if (isReferralValid) {
                message += '- The referral code does not exist or has not been validated.\n';
            }
            alert(message);
        }
    });

    function checkAvailability(field, value, feedbackElement, shouldExist) {
        const formData = new FormData();
        formData.append(field, value);

        fetch('check_availability', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const condition = shouldExist ? data.exists : !data.exists;
            let stateTarget, validMessage, invalidMessage, validClass, invalidClass;

            if (field === 'username') {
                stateTarget = 'isUsernameAvailable';
                validMessage = 'Username is available.';
                invalidMessage = 'Username is already taken.';
                validClass = 'feedback available';
                invalidClass = 'feedback taken';
            } else if (field === 'email') {
                stateTarget = 'isEmailAvailable';
                validMessage = 'Email is available.';
                invalidMessage = 'Email is already taken.';
                validClass = 'feedback available';
                invalidClass = 'feedback taken';
            } else if (field === 'referral_code') {
                stateTarget = 'isReferralValid';
                validMessage = 'Referral code is valid.';
                invalidMessage = 'Referral code does not exist.';
                validClass = 'feedback available';
                invalidClass = 'feedback invalid';
            }

            if (condition) {
                window[stateTarget] = true;
                feedbackElement.textContent = validMessage;
                feedbackElement.className = validClass;
            } else {
                window[stateTarget] = false;
                feedbackElement.textContent = invalidMessage;
                feedbackElement.className = invalidClass;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            feedbackElement.textContent = 'Could not check availability.';
            feedbackElement.className = 'feedback taken';
            if (field === 'username') isUsernameAvailable = false;
            if (field === 'email') isEmailAvailable = false;
            if (field === 'referral_code') isReferralValid = false;
        });
    }

    function fetchStates(countryId) {
        stateSelect.innerHTML = '<option value="">Loading States...</option>';
        if (countryId === '1') { // Assuming 1 is the ID for Nigeria
            fetchNigeriaStates();
        } else {
            fetch(`get_states.php?country_id=${countryId}`)
                .then(response => response.json())
                .then(states => {
                    stateSelect.innerHTML = '<option value="">Select State</option>';
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.text = state.name;
                        stateSelect.add(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading states:', error);
                });
        }
    }

    function fetchNigeriaStates() {
        stateSelect.innerHTML = '<option value="">Loading States...</option>';
        fetch('get_nigeria_states.php')
            .then(response => response.json())
            .then(states => {
                stateSelect.innerHTML = '<option value="">Select State</option>';
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.text = state.state;
                    stateSelect.add(option);
                });
            })
            .catch(error => {
                console.error('Error loading Nigeria states:', error);
            });
    }
});
</script>
</body>

</html>