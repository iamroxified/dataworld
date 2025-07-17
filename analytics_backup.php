<?php
require_once 'db/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the form submission here
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $institution = $_POST['institution'] ?? '';
    $department = $_POST['department'] ?? '';
    $program_type = $_POST['program_type'] ?? '';
    $country = $_POST['country'] ?? '';
    $software = $_POST['software'] ?? '';
    $project_topic = $_POST['project_topic'] ?? '';
    $has_topic = $_POST['has_topic'] ?? '';
    $payment_type = $_POST['payment_type'] ?? '';
    $agreed_policy = isset($_POST['agreed_policy']);
    
    // Handle file upload if chapter 3 is uploaded
    $chapter3_file = null;
    if (isset($_FILES['chapter3']) && $_FILES['chapter3']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload logic here
        $upload_dir = 'uploads/chapter3/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['chapter3']['name'], PATHINFO_EXTENSION);
        $chapter3_file = $upload_dir . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['chapter3']['tmp_name'], $chapter3_file);
    }
    
    // Here you would save to database or send email
    // For now, we'll just show success message
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
        <div class="">
          <!-- Datasets Section -->
          <section id="datasets" class="datasets section">
            <div class="container">
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

                  <form method="POST" enctype="multipart/form-data" id="analyticsForm">
                    <!-- Personal Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-user"></i> Personal Information</h4>
                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                          <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="middle_name" class="form-label">Middle Name</label>
                          <input type="text" class="form-control" id="middle_name" name="middle_name">
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="last_name" class="form-label">Last Name/Surname <span
                              class="required">*</span></label>
                          <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="email" class="form-label">Valid Email <span class="required">*</span></label>
                          <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="phone" class="form-label">Phone Contact <span class="required">*</span></label>
                          <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                      </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-graduation-cap"></i> Academic Information
                      </h4>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="institution" class="form-label">Institution <span
                              class="required">*</span></label>
                          <select class="form-select" id="institution" name="institution" required>
                            <option value="">Select Institution</option>
                            <option value="University of Lagos">University of Lagos</option>
                            <option value="University of Ibadan">University of Ibadan
                            </option>
                            <option value="Ahmadu Bello University">Ahmadu Bello University
                            </option>
                            <option value="University of Nigeria">University of Nigeria
                            </option>
                            <option value="Obafemi Awolowo University">Obafemi Awolowo
                              University</option>
                            <option value="University of Benin">University of Benin</option>
                            <option value="Federal University of Technology, Akure">Federal
                              University of Technology, Akure</option>
                            <option value="University of Port Harcourt">University of Port
                              Harcourt</option>
                            <option value="Lagos State University">Lagos State University
                            </option>
                            <option value="Covenant University">Covenant University</option>
                            <option value="Other">Other</option>
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
                            <option value="ND/NCE" data-price="8400">ND/NCE</option>
                            <option value="B.Sc./HND" data-price="10200">B.Sc./HND</option>
                            <option value="PGD" data-price="12500">PGD</option>
                            <option value="M.Sc./M.Phil" data-price="15500">M.Sc./M.Phil
                            </option>
                            <option value="PHD" data-price="20500">PHD</option>
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
                        <input type="hidden" id="payment_type" name="payment_type" required>
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
                          <li>ND/NCE: ₦8,400 / $8,400</li>
                          <li>B.Sc./HND: ₦10,200 / $10,200</li>
                          <li>PGD: ₦12,500 / $12,500</li>
                          <li>M.Sc./M.Phil: ₦15,500 / $15,500</li>
                          <li>PHD: ₦20,500 / $20,500</li>
                        </ul>
                      </li>
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
        const price = selectedOption.getAttribute('data-price');
        const paymentType = document.getElementById('payment_type');

        if (price) {
          paymentType.value = programType.value + ' (' + price + ')';
          updateCurrency();
        }
      }

      function updateCurrency() {
        const country = document.getElementById('country');
        const selectedCountry = country.options[country.selectedIndex];
        const currency = selectedCountry.getAttribute('data-currency') || '₦';
        const programType = document.getElementById('program_type');
        const selectedProgram = programType.options[programType.selectedIndex];
        const price = selectedProgram.getAttribute('data-price');

        const currencySymbol = document.getElementById('currencySymbol');
        const priceAmount = document.getElementById('priceAmount');

        if (currency && price) {
          currencySymbol.textContent = currency;
          priceAmount.textContent = parseInt(price).toLocaleString();
        }
      }

      // Form validation
      document.getElementById('analyticsForm').addEventListener('submit', function (e) {
        const paymentType = document.getElementById('payment_type').value;
        if (!paymentType) {
          e.preventDefault();
          alert('Please select a program type to see pricing information.');
          return false;
        }
      });
    </script>
</body>

</html>