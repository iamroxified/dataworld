<?php
require_once 'db/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE analytics_requests SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $request_id]);
}

// Get all analytics requests with user information
$requests_query = "SELECT ar.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                   FROM analytics_requests ar 
                   JOIN users u ON ar.user_id = u.id 
                   ORDER BY ar.created_at DESC";
$result = $pdo->query($requests_query);
$all_requests = $result->fetchAll();

// Get statistics
$stats = [
    'total' => count($all_requests),
    'pending' => count(array_filter($all_requests, fn($r) => $r['status'] === 'pending')),
    'processing' => count(array_filter($all_requests, fn($r) => $r['status'] === 'processing')),
    'completed' => count(array_filter($all_requests, fn($r) => $r['status'] === 'completed'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Requests Management - DataWorld Admin</title>
    <?php include('nav/links.php'); ?>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .status-badge {
            font-size: 0.8em;
        }
        .file-link {
            color: #007bff;
            text-decoration: none;
        }
        .file-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include('nav/header.php'); ?>
    
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-clipboard-list"></i> Analytics Requests Management</h1>
            <p class="mb-0">View and manage all project analytics requests</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Requests</h5>
                        <h2><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Processing</h5>
                        <h2><?php echo $stats['processing']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h2><?php echo $stats['completed']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Requests Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-table"></i> All Analytics Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Institution</th>
                                <th>Program Type</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_requests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($request['user_name']); ?>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($request['user_phone'] ?? 'No phone'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['user_email']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($request['institution']); ?>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($request['department']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['program_type']); ?></td>
                                <td>
                                    <?php echo $request['currency'] . number_format($request['payment_amount']); ?>
                                    <?php if ($request['payment_receipt']): ?>
                                        <br><a href="<?php echo $request['payment_receipt']; ?>" target="_blank" class="file-link">
                                            <i class="fas fa-receipt"></i> View Receipt
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($request['status']) {
                                        'pending' => 'bg-warning',
                                        'processing' => 'bg-info',
                                        'completed' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> status-badge">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'pending')">Pending</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'processing')">Processing</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'completed')">Completed</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Request Details Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <?php include('nav/footer.php'); ?>
    
    <script>
        function viewRequest(id) {
            // Find the request data
            const requests = <?php echo json_encode($all_requests); ?>;
            const request = requests.find(r => r.id == id);
            
            if (request) {
                const modalContent = document.getElementById('modalContent');
                modalContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <p><strong>Name:</strong> ${request.user_name}</p>
                            <p><strong>Email:</strong> ${request.user_email}</p>
                            <p><strong>Phone:</strong> ${request.user_phone || 'Not provided'}</p>
                            <p><strong>Country:</strong> ${request.country}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Academic Information</h6>
                            <p><strong>State:</strong> ${request.state || 'N/A'}</p>
                            <p><strong>Institution:</strong> ${request.institution}</p>
                            <p><strong>Department:</strong> ${request.department}</p>
                            <p><strong>Program Type:</strong> ${request.program_type}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Project Information</h6>
                            <p><strong>Software:</strong> ${request.software}</p>
                            <p><strong>Has Topic:</strong> ${request.has_topic}</p>
                            <p><strong>Project Topic:</strong></p>
                            <p class="border p-2">${request.project_topic}</p>
                            ${request.chapter3_file ? `<p><strong>Chapter 3:</strong> <a href="${request.chapter3_file}" target="_blank">Download File</a></p>` : ''}
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Information</h6>
                            <p><strong>Amount:</strong> ${request.currency}${parseFloat(request.payment_amount).toLocaleString()}</p>
                            ${request.payment_receipt ? `<p><strong>Receipt:</strong> <a href="${request.payment_receipt}" target="_blank">View Receipt</a></p>` : ''}
                            <p><strong>Status:</strong> <span class="badge bg-info">${request.status}</span></p>
                            <p><strong>Submitted:</strong> ${new Date(request.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('requestModal'));
                modal.show();
            }
        }
        
        function updateStatus(requestId, newStatus) {
            if (confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="request_id" value="${requestId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
