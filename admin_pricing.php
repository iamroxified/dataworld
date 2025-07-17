<?php
require_once 'db/config.php';

// Check if user is logged in (you can modify this based on your auth system)
session_start();
if (!isLoggedIn() || !getCurrentUser()['is_admin']) {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $program_name = $_POST['program_name'];
                $price_naira = $_POST['price_naira'];
                $price_usd = $_POST['price_usd'];
                
                $stmt = $pdo->prepare("INSERT INTO program_pricing (program_name, price_naira, price_usd) VALUES (?, ?, ?)");
                $stmt->execute([$program_name, $price_naira, $price_usd]);
                break;
                
            case 'update':
                $id = $_POST['id'];
                $program_name = $_POST['program_name'];
                $price_naira = $_POST['price_naira'];
                $price_usd = $_POST['price_usd'];
                
                $stmt = $pdo->prepare("UPDATE program_pricing SET program_name = ?, price_naira = ?, price_usd = ? WHERE id = ?");
                $stmt->execute([$program_name, $price_naira, $price_usd, $id]);
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM program_pricing WHERE id = ?");
                $stmt->execute([$id]);
                break;
        }
    }
}

// Get all program pricing
$pricing_query = "SELECT * FROM program_pricing ORDER BY price_naira";
$result = $pdo->query($pricing_query);
$all_pricing = $result->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Pricing Management - DataWorld Admin</title>
    <?php include('nav/links.php'); ?>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include('nav/header.php'); ?>
    
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-cog"></i> Program Pricing Management</h1>
            <p class="mb-0">Manage analytics project pricing for different program types</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Current Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Program Type</th>
                                        <th>Price (Naira)</th>
                                        <th>Price (USD)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_pricing as $pricing): ?>
                                    <tr id="row-<?php echo $pricing['id']; ?>">
                                        <td><?php echo htmlspecialchars($pricing['program_name']); ?></td>
                                        <td>₦<?php echo number_format($pricing['price_naira']); ?></td>
                                        <td>$<?php echo number_format($pricing['price_usd']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editPricing(<?php echo $pricing['id']; ?>, '<?php echo htmlspecialchars($pricing['program_name']); ?>', <?php echo $pricing['price_naira']; ?>, <?php echo $pricing['price_usd']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePricing(<?php echo $pricing['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Add/Edit Program Pricing</h5>
                    </div>
                    <div class="card-body">
                        <form id="pricingForm" method="POST">
                            <input type="hidden" name="action" id="action" value="add">
                            <input type="hidden" name="id" id="pricing_id">
                            
                            <div class="mb-3">
                                <label for="program_name" class="form-label">Program Type</label>
                                <input type="text" class="form-control" id="program_name" name="program_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_naira" class="form-label">Price (Naira)</label>
                                <input type="number" step="0.01" class="form-control" id="price_naira" name="price_naira" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_usd" class="form-label">Price (USD)</label>
                                <input type="number" step="0.01" class="form-control" id="price_usd" name="price_usd" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> <span id="submit-text">Add Pricing</span>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-refresh"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Total Programs:</strong> <?php echo count($all_pricing); ?></p>
                        <p><strong>Price Range (Naira):</strong> 
                            ₦<?php echo !empty($all_pricing) ? number_format(min(array_column($all_pricing, 'price_naira'))) : '0'; ?> - 
                            ₦<?php echo !empty($all_pricing) ? number_format(max(array_column($all_pricing, 'price_naira'))) : '0'; ?>
                        </p>
                        <p><strong>Price Range (USD):</strong> 
                            $<?php echo !empty($all_pricing) ? number_format(min(array_column($all_pricing, 'price_usd'))) : '0'; ?> - 
                            $<?php echo !empty($all_pricing) ? number_format(max(array_column($all_pricing, 'price_usd'))) : '0'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('nav/footer.php'); ?>
    
    <script>
        function editPricing(id, programName, priceNaira, priceUsd) {
            document.getElementById('action').value = 'update';
            document.getElementById('pricing_id').value = id;
            document.getElementById('program_name').value = programName;
            document.getElementById('price_naira').value = priceNaira;
            document.getElementById('price_usd').value = priceUsd;
            document.getElementById('submit-text').textContent = 'Update Pricing';
        }
        
        function deletePricing(id) {
            if (confirm('Are you sure you want to delete this pricing entry?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function resetForm() {
            document.getElementById('pricingForm').reset();
            document.getElementById('action').value = 'add';
            document.getElementById('pricing_id').value = '';
            document.getElementById('submit-text').textContent = 'Add Pricing';
        }
    </script>
</body>
</html>
