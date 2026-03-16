<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin'){
  header('Location: login.php');
  exit;
}

  // Get current user
  $user_id = $_SESSION['user_id'];
  $user_details = get_user_details($user_id);
  $last_name = $user_details['last_name'] ?? '';
  $first_name = $user_details['first_name'] ?? '';
  $username = $user_details['username'] ?? '';
  $email = $user_details['email'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_price'])) {
        $service_name = $_POST['service_name'];
        $item_name = $_POST['item_name'];
        $price = $_POST['price'];
        $stmt = $pdo->prepare("INSERT INTO prices (service_name, item_name, price) VALUES (?, ?, ?)");
        $stmt->execute([$service_name, $item_name, $price]);
    } elseif (isset($_POST['edit_price'])) {
        $id = $_POST['id'];
        $service_name = $_POST['service_name'];
        $item_name = $_POST['item_name'];
        $price = $_POST['price'];
        $stmt = $pdo->prepare("UPDATE prices SET service_name = ?, item_name = ?, price = ? WHERE id = ?");
        $stmt->execute([$service_name, $item_name, $price, $id]);
    } elseif (isset($_POST['delete_price'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM prices WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: prices.php');
    exit;
}

// Fetch all prices
$stmt = $pdo->query("SELECT * FROM prices ORDER BY service_name, item_name");
$prices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Manage Prices - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
  <link rel="stylesheet" href="assets/css/datatables.min.css">
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
            <h3 class="fw-bold mb-3">Manage Prices</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Prices</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Add New Price</h4>
                </div>
                <div class="card-body">
                  <form method="post" action="">
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label for="service_name">Service Name</label>
                          <input type="text" name="service_name" id="service_name" class="form-control" required>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label for="item_name">Item Name</label>
                          <input type="text" name="item_name" id="item_name" class="form-control" required>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label for="price">Price</label>
                          <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="add_price" class="btn btn-primary form-control">Add Price</button>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">All Prices</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="prices-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Service Name</th>
                          <th>Item Name</th>
                          <th>Price</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($prices as $price): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($price['service_name']); ?></td>
                          <td><?php echo htmlspecialchars($price['item_name']); ?></td>
                          <td><?php echo number_format($price['price'], 2); ?></td>
                          <td>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?php echo $price['id']; ?>" data-service-name="<?php echo htmlspecialchars($price['service_name']); ?>" data-item-name="<?php echo htmlspecialchars($price['item_name']); ?>" data-price="<?php echo $price['price']; ?>">Edit</button>
                            <form method="post" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this price?');">
                                <input type="hidden" name="id" value="<?php echo $price['id']; ?>">
                                <button type="submit" name="delete_price" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include('nav/footer.php'); ?>
    </div>
  </div>

  <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label for="edit_service_name">Service Name</label>
                            <input type="text" class="form-control" id="edit_service_name" name="service_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_item_name">Item Name</label>
                            <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_price">Price</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                        </div>
                        <button type="submit" name="edit_price" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

  <script src="assets/js/datatables.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#prices-table').DataTable();
      
      var editModal = document.getElementById('editModal')
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget
            var id = button.getAttribute('data-id')
            var serviceName = button.getAttribute('data-service-name')
            var itemName = button.getAttribute('data-item-name')
            var price = button.getAttribute('data-price')

            var modalBodyInputId = editModal.querySelector('.modal-body #edit_id')
            var modalBodyInputServiceName = editModal.querySelector('.modal-body #edit_service_name')
            var modalBodyInputItemName = editModal.querySelector('.modal-body #edit_item_name')
            var modalBodyInputPrice = editModal.querySelector('.modal-body #edit_price')

            modalBodyInputId.value = id
            modalBodyInputServiceName.value = serviceName
            modalBodyInputItemName.value = itemName
            modalBodyInputPrice.value = price
        })
    });
  </script>
</body>
</html>