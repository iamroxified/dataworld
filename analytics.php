<?php
require_once 'db/config.php';

// Check if user is logged in
requireLogin();

// Get current user information
$current_user = getCurrentUser();

// Create program pricing table if it doesn't exist
if ($pdo) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS program_pricing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_name VARCHAR(100) NOT NULL,
        price_naira DECIMAL(10, 2) NOT NULL,
        price_usd DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($create_table_sql);
        
    // Insert default pricing if table is empty
    $check_count = $pdo->query("SELECT COUNT(*) as count FROM program_pricing");
    $row = $check_count->fetch();
    
    if ($row['count'] == 0) {
        $insert_pricing = "INSERT INTO program_pricing (program_name, price_naira, price_usd) VALUES 
            ('ND/NCE', 8400.00, 25.00),
            ('B.Sc./HND', 10200.00, 30.00),
            ('PGD', 12500.00, 35.00),
            ('M.Sc./M.Phil', 15500.00, 45.00),
            ('PHD', 20500.00, 60.00)";
        $pdo->exec($insert_pricing);
    }
    
    // Create analytics requests table
    $create_requests_table = "CREATE TABLE IF NOT EXISTS analytics_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        state VARCHAR(100),
        institution VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        program_type VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        software VARCHAR(100) NOT NULL,
        project_topic TEXT NOT NULL,
        has_topic ENUM('yes', 'no') NOT NULL,
        chapter3_file VARCHAR(255),
        payment_receipt VARCHAR(255),
        payment_amount DECIMAL(10, 2),
        currency VARCHAR(10),
        status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )";
    $pdo->exec($create_requests_table);
    
    // Add user_id column if it doesn't exist (for existing tables)
    try {
        $pdo->exec("ALTER TABLE analytics_requests ADD COLUMN user_id BIGINT(20) UNSIGNED NOT NULL AFTER id");
    } catch (PDOException $e) {
        // Column probably already exists, ignore error
    }
    
    // Add index if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE analytics_requests ADD INDEX idx_user_id (user_id)");
    } catch (PDOException $e) {
        // Index probably already exists, ignore error
    }
}

// Get program pricing from database
$program_pricing = [];
if ($pdo) {
    $pricing_query = "SELECT * FROM program_pricing ORDER BY price_naira";
    $result = $pdo->query($pricing_query);
    $program_pricing = $result->fetchAll();
}

// Load states data
$states_data = json_decode(file_get_contents('states.json'), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the form submission here
    $user_id = $_SESSION['user_id'];
    $state = $_POST['state'] ?? '';
    $institution = $_POST['institution'] ?? '';
    $department = $_POST['department'] ?? '';
    $program_type = $_POST['program_type'] ?? '';
    $country = $_POST['country'] ?? '';
    $software = $_POST['software'] ?? '';
    $project_topic = $_POST['project_topic'] ?? '';
    $has_topic = $_POST['has_topic'] ?? '';
    $payment_amount = $_POST['payment_amount'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $agreed_policy = isset($_POST['agreed_policy']);
    
    // Handle file upload if chapter 3 is uploaded
    $chapter3_file = null;
    if (isset($_FILES['chapter3']) && $_FILES['chapter3']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/chapter3/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['chapter3']['name'], PATHINFO_EXTENSION);
        $chapter3_file = $upload_dir . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['chapter3']['tmp_name'], $chapter3_file);
    }
    
    // Handle payment receipt upload
    $payment_receipt = null;
    if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION);
        $payment_receipt = $upload_dir . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $payment_receipt);
    }
    
    // Save to database
    if ($pdo) {
        // Create order for analytics request
        $order_number = generateOrderNumber();
        $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status) VALUES (?, ?, ?, 'pending', 'bank_transfer', 'pending')");
        $order_stmt->execute([$user_id, $order_number, $payment_amount]);
        $order_id = $pdo->lastInsertId();
        
        // Insert analytics request with order reference
        $stmt = $pdo->prepare("INSERT INTO analytics_requests (user_id, state, institution, department, program_type, country, software, project_topic, has_topic, chapter3_file, payment_receipt, payment_amount, currency, order_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $state, $institution, $department, $program_type, $country, $software, $project_topic, $has_topic, $chapter3_file, $payment_receipt, $payment_amount, $currency, $order_id]);
    }
    
    $success_message = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Analytics Request - DataWorld</title>
  <?php include('nav/links.php'); ?>
  <style>
    .form-section {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .required {
      color: red;
    }

    .price-display {
      font-weight: bold;
      color: #28a745;
      font-size: 1.25rem;
    }

    .success-message {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
      margin: 20px 0;
    }

    .navbar {
      background: linear-gradient(135deg, #007bff, #0056b3) !important;
    }
  </style>
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
              <h1>Request for Analytics</h1>
              <!-- <p class="mb-0">Discover high-quality datasets for your research and business needs</p> -->
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index">Home</a></li>
            <li class="current">Analytics</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->

    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <!-- Datasets Section -->

          <!-- Starter Section Section -->
          <section id="starter-section" class="starter-section section">

            <!-- Section Title -->
            <div class="container" data-aos="fade-up">
              <?php if (isset($success_message)): ?>
              <div class="success-message">
                <h2><i class="fas fa-check-circle"></i> Payment Confirmed / Request Submitted</h2>
                <h4>Congratulations!</h4>
                <p class="mb-0">I kindly note that in 3-5 working days your project work will be sent to
                  your email.</p>
              </div>
              <?php else: ?>
              <div class="row justify-content-center">
                <div class="col-md-10">
                  <h1 class="text-center mb-4">Project Analytics Request Form</h1>
                  <p class="text-center text-muted mb-4">Get professional data analysis services for
                    your research project</p>
                  
                  <!-- User Greeting -->
                  <div class="alert alert-info mb-4">
                    <i class="fas fa-user-circle"></i> Welcome, <strong><?php echo htmlspecialchars($current_user['name']); ?></strong>!
                    <small class="d-block mt-1">Email: <?php echo htmlspecialchars($current_user['email']); ?> | Phone: <?php echo htmlspecialchars($current_user['phone'] ?? 'Not provided'); ?></small>
                  </div>

                  <form method="POST" enctype="multipart/form-data" id="analyticsForm">

                    <!-- Academic Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-graduation-cap"></i> Academic Information
                      </h4>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="state" class="form-label">State <span
                              class="required">*</span></label>
                          <select class="form-select" id="state" name="state" required onchange="loadInstitutions()">
                            <option value="">Select State</option>
                            <?php foreach ($states_data as $state): ?>
                              <?php $state_name = key($state); ?>
                              <option value="<?php echo htmlspecialchars($state_name); ?>"><?php echo htmlspecialchars($state_name); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="institution" class="form-label">Institution <span
                              class="required">*</span></label>
                          <select class="form-select" id="institution" name="institution" required disabled>
                            <option value="">Select State First</option>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="department" class="form-label">Department <span class="required">*</span></label>
                          <select class="form-select" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Statistics">Statistics</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Economics">Economics</option>
                            <option value="Business Administration">Business Administration
                            </option>
                            <option value="Psychology">Psychology</option>
                            <option value="Sociology">Sociology</option>
                            <option value="Political Science">Political Science</option>
                            <option value="Public Administration">Public Administration
                            </option>
                            <option value="Engineering">Engineering</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Agriculture">Agriculture</option>
                            <option value="Education">Education</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="program_type" class="form-label">Program Type <span
                              class="required">*</span></label>
                          <select class="form-select" id="program_type" name="program_type" required
                            onchange="updatePrice()">
                            <option value="">Select Program Type</option>
                            <?php foreach ($program_pricing as $pricing): ?>
                              <option value="<?php echo htmlspecialchars($pricing['program_name']); ?>" 
                                      data-price-naira="<?php echo $pricing['price_naira']; ?>" 
                                      data-price-usd="<?php echo $pricing['price_usd']; ?>">
                                <?php echo htmlspecialchars($pricing['program_name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="country" class="form-label">Country <span class="required">*</span></label>
                          <select class="form-select" id="country" name="country" required onchange="updateCurrency()">
                            <option value="">Select Country</option>
                            <option value="Nigeria" data-currency="₦">Nigeria</option>
                            <option value="USA" data-currency="$">USA</option>
                            <option value="UK" data-currency="$">UK</option>
                            <option value="Europe" data-currency="$">Europe</option>
                            <option value="Ghana" data-currency="$">Ghana</option>
                            <option value="Benin Republic" data-currency="$">Benin Republic
                            </option>
                            <option value="Cameroon" data-currency="$">Cameroon</option>
                            <option value="Others" data-currency="$">Others</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <!-- Project Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-chart-bar"></i> Project Information</h4>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="software" class="form-label">Software to be Used <span
                              class="required">*</span></label>
                          <select class="form-select" id="software" name="software" required>
                            <option value="">Select Software</option>
                            <option value="SPSS">SPSS</option>
                            <option value="Minitab">Minitab</option>
                            <option value="Python">Python</option>
                            <option value="Anyone">Anyone</option>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="has_topic" class="form-label">Do you have project topic?
                            <span class="required">*</span></label>
                          <select class="form-select" id="has_topic" name="has_topic" required
                            onchange="toggleTopicFields()">
                            <option value="">Select Option</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                          </select>
                        </div>
                      </div>
                      <div class="mb-3">
                        <label for="project_topic" class="form-label">Project Topic <span
                            class="required">*</span></label>
                        <textarea class="form-control" id="project_topic" name="project_topic" rows="3" required
                          placeholder="Enter your project topic or indicate if you don't have one"></textarea>
                      </div>
                      <div class="mb-3" id="chapter3Upload" style="display: none;">
                        <label for="chapter3" class="form-label">Upload Chapter 3 (Research
                          Methodology)</label>
                        <input type="file" class="form-control" id="chapter3" name="chapter3" accept=".pdf,.doc,.docx">
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                      </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-credit-card"></i> Payment Information</h4>
                      <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="required">*</span></label>
                        <div class="price-display mb-3" id="priceDisplay">
                          <span id="currencySymbol">₦</span><span id="priceAmount">0</span>
                        </div>
                        <input type="hidden" id="payment_amount" name="payment_amount" required>
                        <input type="hidden" id="currency" name="currency" required>
                      </div>
                      
                      <div class="mb-3">
                        <div class="alert alert-info">
                          <h6><i class="fas fa-university"></i> Payment Details</h6>
                          <p><strong>Bank:</strong> Opay (Paycom)</p>
                          <p><strong>Account Name:</strong> Kasali Midowiwe Stephen</p>
                          <p><strong>Account Number:</strong> 9037205456</p>
                          <p><strong>Note:</strong> Please use "<?php echo htmlspecialchars($current_user['name']); ?>" as payment reference and upload your payment receipt below.</p>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="payment_receipt" class="form-label">Upload Payment Receipt <span class="required">*</span></label>
                        <input type="file" class="form-control" id="payment_receipt" name="payment_receipt" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
                      </div>
                      
                      <div class="mb-3">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="agreed_policy" name="agreed_policy"
                            required>
                          <label class="form-check-label" for="agreed_policy">
                            I agree to the policy <span class="required">*</span>
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="text-center">
                      <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="fas fa-credit-card"></i> Payment/Submit
                      </button>
                    </div>
                  </form>

                  <div class="mt-4 p-3 bg-light rounded">
                    <h5><i class="fas fa-info-circle"></i> Important Notes:</h5>
                    <ul>
                      <li><strong>Currency:</strong> Only Nigeria will pay in Naira (₦), all other
                        countries will pay in US Dollars ($)</li>
                      <li><strong>Pricing:</strong>
                        <ul>
                          <?php foreach ($program_pricing as $pricing): ?>
                            <li><?php echo htmlspecialchars($pricing['program_name']); ?>: ₦<?php echo number_format($pricing['price_naira']); ?> / $<?php echo number_format($pricing['price_usd']); ?></li>
                          <?php endforeach; ?>
                        </ul>
                      </li>
                      <li><strong>Payment:</strong> Upload payment receipt after making payment to the provided account details</li>
                      <li><strong>Delivery:</strong> Project completion time: 3-5 working days
                      </li>
                      <li><strong>Communication:</strong> Results will be sent to your registered
                        email</li>
                    </ul>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>
  <?php include('nav/footer.php'); ?>
  <script>
    // States and institutions data
    const statesData = <?php echo json_encode($states_data); ?>;
    
    function loadInstitutions() {
      const stateSelect = document.getElementById('state');
      const institutionSelect = document.getElementById('institution');
      const selectedState = stateSelect.value;
      
      // Clear previous options
      institutionSelect.innerHTML = '<option value="">Select Institution</option>';
      
      if (selectedState) {
        // Find the state data
        const stateData = statesData.find(state => state.hasOwnProperty(selectedState));
        if (stateData) {
          const institutions = stateData[selectedState];
          
          // Add institutions to select
          for (const key in institutions) {
            if (institutions[key]) {
              const option = document.createElement('option');
              option.value = institutions[key];
              option.textContent = institutions[key];
              institutionSelect.appendChild(option);
            }
          }
        }
        institutionSelect.disabled = false;
      } else {
        institutionSelect.disabled = true;
      }
    }
    
    function toggleTopicFields() {
      const hasTopic = document.getElementById('has_topic').value;
      const chapter3Upload = document.getElementById('chapter3Upload');

      if (hasTopic === 'yes') {
        chapter3Upload.style.display = 'block';
      } else {
        chapter3Upload.style.display = 'none';
      }
    }

    function updatePrice() {
      const programType = document.getElementById('program_type');
      const selectedOption = programType.options[programType.selectedIndex];
      
      if (selectedOption.value) {
        updateCurrency();
      }
    }

    function updateCurrency() {
      const country = document.getElementById('country');
      const selectedCountry = country.options[country.selectedIndex];
      const currency = selectedCountry.getAttribute('data-currency') || '₦';
      const programType = document.getElementById('program_type');
      const selectedProgram = programType.options[programType.selectedIndex];
      
      const currencySymbol = document.getElementById('currencySymbol');
      const priceAmount = document.getElementById('priceAmount');
      const paymentAmount = document.getElementById('payment_amount');
      const currencyField = document.getElementById('currency');
      
      if (selectedProgram.value) {
        let price;
        if (currency === '₦') {
          price = selectedProgram.getAttribute('data-price-naira');
        } else {
          price = selectedProgram.getAttribute('data-price-usd');
        }
        
        if (price) {
          currencySymbol.textContent = currency;
          priceAmount.textContent = parseInt(price).toLocaleString();
          paymentAmount.value = price;
          currencyField.value = currency;
        }
      }
    }

    // Form validation
    document.getElementById('analyticsForm').addEventListener('submit', function (e) {
      const paymentAmount = document.getElementById('payment_amount').value;
      const paymentReceipt = document.getElementById('payment_receipt').files[0];
      
      if (!paymentAmount) {
        e.preventDefault();
        alert('Please select a program type to see pricing information.');
        return false;
      }
      
      if (!paymentReceipt) {
        e.preventDefault();
        alert('Please upload your payment receipt.');
        return false;
      }
      
      // Check file size (5MB limit)
      if (paymentReceipt.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('Payment receipt file size must be less than 5MB.');
        return false;
      }
    });
  </script>
</body>

</html>
