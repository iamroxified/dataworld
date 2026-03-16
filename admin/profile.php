<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{

$user_id = $_SESSION['user_id'];
$uss = extract(get_user_details($user_id));
$user_details = get_user_details($user_id);
// $success_message = '';
// $error_message = '';


$banks_json = file_get_contents('../banks.json');
$banks = json_decode($banks_json, true);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //var_dump($_POST); // Debugging line
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $account_name = $_POST['account_name'];

    $stmt = $pdo->prepare("UPDATE users SET bank_name = ?, account_number = ?, account_name = ? WHERE id = ?");
    if ($stmt->execute([$bank_name, $account_number, $account_name, $user_id])) {
        $success_message = "Bank details updated successfully!";
        // Refresh user details
        $user_details = get_user_details($user_id);
        header('Location: profile'); // Redirect to avoid form resubmission
    } else {
        $error_message = "Failed to update bank details. Please try again.";
        var_dump($stmt->errorInfo()); // Debugging line
    }
}



}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>My Profile - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
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
            <h3 class="fw-bold mb-3">My Profile</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">My Profile</a></li>
            </ul>
          </div>
      
          <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>First Name:</strong> <?php echo htmlspecialchars($user_details['first_name']); ?></p>
                        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($user_details['last_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user_details['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_details['email']); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Bank Account Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($user_details['bank_name'])): ?>
                            <form action="profile" method="POST">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name</label>
                                    <select class="form-control" id="bank_name" name="bank_name" required>
                                        <option value="" data-slug="">Select a bank</option>
                                        <?php foreach ($banks as $bank): ?>
                                            <option value="<?php echo htmlspecialchars($bank['name']); ?>" data-slug="<?php echo htmlspecialchars($bank['slug']); ?>"><?php echo htmlspecialchars($bank['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <img id="bank-logo" src="" alt="Bank Logo" style="max-width: 100px; max-height: 100px; margin-top: 10px;"/>
                                </div>
                                <div class="form-group">
                                    <label for="account_number">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="account_name">Account Name</label>
                                    <input type="text" class="form-control" id="account_name" name="account_name" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Details</button>
                            </form>
                        <?php else: ?>
                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($user_details['bank_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($user_details['account_number']); ?></p>
                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars($user_details['account_name']); ?></p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateDetailsModal">
                                Update Account Details
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </div>
      <?php include('nav/footer.php'); ?>
    </div>
  </div>

  <!-- Modal -->
<div class="modal fade" id="updateDetailsModal" tabindex="-1" aria-labelledby="updateDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateDetailsModalLabel">Update Bank Account Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="profile" method="POST">
            <div class="form-group">
                <label for="bank_name_modal">Bank Name</label>
                <select class="form-control" id="bank_name_modal" name="bank_name" required>
                    <option value="" data-slug="">Select a bank</option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?php echo htmlspecialchars($bank['name']); ?>" data-slug="<?php echo htmlspecialchars($bank['slug']); ?>" <?php echo ($user_details['bank_name'] == $bank['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bank['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <img id="bank-logo-modal" src="" alt="Bank Logo" style="max-width: 100px; max-height: 100px; margin-top: 10px;"/>
            </div>
            <div class="form-group">
                <label for="account_number_modal">Account Number</label>
                <input type="text" class="form-control" id="account_number_modal" name="account_number" value="<?php echo htmlspecialchars($user_details['account_number'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="account_name_modal">Account Name</label>
                <input type="text" class="form-control" id="account_name_modal" name="account_name" value="<?php echo htmlspecialchars($user_details['account_name'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateBankLogo(selectElement, logoElement) {
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var slug = selectedOption.getAttribute('data-slug');
        if (slug) {
            logoElement.src = '../logos/' + slug + '.png';
        } else {
            logoElement.src = '';
        }
    }

    var bankNameSelect = document.getElementById('bank_name');
    var bankLogo = document.getElementById('bank-logo');
    if (bankNameSelect) {
        bankNameSelect.addEventListener('change', function() {
            updateBankLogo(this, bankLogo);
        });
    }

    var bankNameModalSelect = document.getElementById('bank_name_modal');
    var bankLogoModal = document.getElementById('bank-logo-modal');
    if (bankNameModalSelect) {
        bankNameModalSelect.addEventListener('change', function() {
            updateBankLogo(this, bankLogoModal);
        });

        // Trigger change event on load for modal to show the pre-selected bank logo
        updateBankLogo(bankNameModalSelect, bankLogoModal);
    }
});
</script>
</body>

</html>