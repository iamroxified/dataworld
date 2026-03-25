<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));

// Get program pricing from database
$program_pricing = [];
if ($pdo) {
    $pricing_query = "SELECT * FROM program_pricing ORDER BY price_naira";
    $result = $pdo->query($pricing_query);
    $program_pricing = $result->fetchAll();
}

// Load states data
$states_data = json_decode(file_get_contents('../states.json'), true);

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
    $payment_amount = $_POST['payment_amount'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $agreed_policy = isset($_POST['agreed_policy']);

    // Handle file upload if chapter 3 is uploaded
    $chapter3_file = null;
    if (isset($_FILES['chapter3']) && $_FILES['chapter3']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/chapter3/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['chapter3']['name'], PATHINFO_EXTENSION);
        $chapter3_file = 'uploads/chapter3/' . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['chapter3']['tmp_name'], '../' . $chapter3_file);
    }

    // Handle payment receipt upload
    $questionaire = null;
    if (isset($_FILES['questionaire']) && $_FILES['questionaire']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/questionaire/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['questionaire']['name'], PATHINFO_EXTENSION);
        $questionaire = 'uploads/questionaire/' . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['questionaire']['tmp_name'], '../' . $questionaire);
    }

    // Save to database
    if ($pdo) {
        // Create order for analytics request
        $order_number = generateOrderNumber();
        $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status) VALUES (?, ?, ?, 'pending', 'paystack', 'pending')");
        $order_stmt->execute([$user_id, $order_number, $payment_amount]);
        $order_id = $pdo->lastInsertId();

        // Insert analytics request with order reference
        $has_topic = $project_topic !== '' ? 'yes' : 'no';
        $completed_work = '';
        $stmt = $pdo->prepare("INSERT INTO analytics_requests (user_id, state, institution, department, program_type, country, software, project_topic, has_topic, chapter3_file, questionaire, payment_amount, currency, order_id, completed_work) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $state, $institution, $department, $program_type, $country, $software, $project_topic, $has_topic, $chapter3_file, $questionaire, $payment_amount, $currency, $order_id, $completed_work]);
        
        // Redirect to payment page
        header("Location: initialize_payment.php?order_id=" . $order_id);
        exit();
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Make a Request - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">My Analytics Requests</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="analytics_request.php">My Analytics Requests</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Make Request</a></li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                  <div class="d-flex align-items-center">
                    <h4 class="mb-0">
                      <i class="fas fa-wallet"></i> Project Analytics Request Form
                    </h4>
                    <a href="analytics_request" class="btn btn-primary btn-round ms-auto">
                      <i class="fa fa-caret-left"></i>
                      Analytics Request
                    </a>
                  </div>
                </div>

              </div>
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Project Analytics Request Form</h4>
                </div>
                <div class="card-body">
                  <form method="POST" enctype="multipart/form-data" id="analyticsForm">

                    <!-- Academic Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-graduation-cap"></i> Academic Information
                      </h4>
                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <label for="state" class="form-label">State <span class="required">*</span></label>
                          <select class="form-select" id="state" name="state" required onchange="loadInstitutions()">
                            <option value="">Select State</option>
                            <?php foreach ($states_data as $state): ?>
                            <?php $state_name = key($state); ?>
                            <option value="<?php echo htmlspecialchars($state_name); ?>">
                              <?php echo htmlspecialchars($state_name); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="institution" class="form-label">Institution <span
                              class="required">*</span></label>
                          <select class="form-select" id="institution" name="institution" required disabled>
                            <option value="">Select State First</option>
                          </select>
                        </div>
                        <div class="col-md-4 mb-3">
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
                        <div class="col-md-4 mb-3">
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
                        <div class="col-md-4 mb-3">
                          <label for="country" class="form-label">Country <span class="required">*</span></label>
                          <select class="form-select" id="country" name="country" required onchange="updateCurrency()">
                            <!-- <option value="">Select Country</option> -->
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
                          <label for="project_topic" class="form-label">Project Topic <span
                              class="required">*</span></label>
                          <textarea class="form-control" id="project_topic" name="project_topic" rows="3" required
                            placeholder="Enter your project topic or indicate if you don't have one"></textarea>
                        </div>
                        <div class="col-md-6 mb-3" id="chapter3Upload">
                          <label for="chapter3" class="form-label">Upload Chapter 1- 3 <span
                              class="required">*</span></label>
                          <input type="file" class="form-control" id="chapter3" name="chapter3"
                            accept=".pdf,.doc,.docx">
                          <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                        </div>
                        <div class="col-md-6 mb-3" id="chapter3Upload">
                          <label for="questionaire" class="form-label">Upload Your Questionaire <span
                              class="required">*</span></label>
                          <input type="file" class="form-control" id="questionaire" name="questionaire"
                            accept=".pdf,.doc,.docx">
                          <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                        </div>
                      </div>
                    </div>
                    <!-- Payment Information -->
                    <div class="form-section">
                      <h4 class="mb-3"><i class="fas fa-credit-card"></i> Payment Information</h4>
                      <div class="row">
                        <div class="mb-3">
                          <label class="form-label">Payment Amount <span class="required">*</span></label>
                          <div class="price-display mb-3" id="priceDisplay">
                            <span id="currencySymbol">₦</span><span id="priceAmount">0</span>
                          </div>
                          <input type="hidden" id="payment_amount" name="payment_amount" required>
                          <input type="hidden" id="currency" name="currency" required>
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
                    </div>

                    <div class="text-center">
                      <button type="submit" class="btn btn-primary btn-lg px-5">
                        Submit Request
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include('nav/footer.php'); ?>
      <script>
        // States and institutions data
        const statesData = <?php echo json_encode($states_data); ?> ;

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
      </script>
    </div>
  </div>
</body>

</html>
