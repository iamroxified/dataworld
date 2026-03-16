<?php

 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db/config.php';
require_once 'db/functions.php';

requireLogin();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Transactions</title>
  <?php include('nav/links.php'); ?>
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
              <h1>My Dashboard</h1>
              <p class="mb-0">Track your transactions and analytics requests</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index">Home</a></li>
            <li class="current">Dashboard</li>
          </ol>
        </div>
      </nav>
    </div>

    <div class="container section">
      <!-- User Welcome -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="alert alert-info">
            <h4><i class="bi bi-person-circle"></i> Welcome back,
              <?php echo htmlspecialchars($current_user['username']); ?>!</h4>
            <p class="mb-0">Here's a summary of your activity on DataWorld</p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row mb-5">
        <div class="col-md-3 mb-3">
          <div class="card text-center bg-primary text-white">
            <div class="card-body">
              <h3><?php echo $total_orders; ?></h3>
              <p class="card-text">Dataset Orders</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card text-center bg-success text-white">
            <div class="card-body">
              <h3><?php echo $total_analytics; ?></h3>
              <p class="card-text">Analytics Requests</p>
            </div>
          </div>
        </div>
          <div class="col-md-3 mb-3">
          <div class="card text-center bg-success text-white">
            <div class="card-body">
              <h3><?php echo get_ref_count($code); ?></h3>
              <p class="card-text">Referral Count</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card text-center bg-info text-white">
            <div class="card-body">
              <h3><?php echo formatPrice($total_spent); ?></h3>
              <p class="card-text">Total Spent</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card text-center bg-warning text-white">
            <div class="card-body">
              <h3><?php echo count($pending_orders) + count($pending_analytics); ?></h3>
              <p class="card-text">Pending Items</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs for different sections -->
      <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="datasets-tab" data-bs-toggle="tab" data-bs-target="#datasets"
            type="button" role="tab" aria-controls="datasets" aria-selected="true">
            <i class="bi bi-database"></i> Dataset Orders
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button"
            role="tab" aria-controls="analytics" aria-selected="false">
            <i class="bi bi-graph-up"></i> Analytics Requests
          </button>
        </li>
      </ul>

      <div class="tab-content" id="dashboardTabsContent">
        <!-- Dataset Orders Tab -->
        <div class="tab-pane fade show active" id="datasets" role="tabpanel" aria-labelledby="datasets-tab">
          <div class="card">
            <div class="card-header">
              <h5><i class="bi bi-cart-check"></i> Your Dataset Orders</h5>
            </div>
            <div class="card-body">
              <?php if (empty($orders)): ?>
              <div class="text-center py-4">
                <i class="bi bi-cart-x" style="font-size: 3rem; color: #ccc;"></i>
                <h4 class="mt-3">No orders yet</h4>
                <p>Start exploring our datasets to make your first purchase!</p>
                <a href="datasets.php" class="btn btn-primary">Browse Datasets</a>
              </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>SN</th>
                      <th>Order #</th>
                      <th>Items</th>
                      <th>Total</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $sn =1; foreach ($orders as $order): ?>
                    <tr>
                      <td><?php echo $sn++; ?></td>
                      <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                      <td>
                        <?php
                                                    $items_stmt->execute([$order['id']]);
                                                    $items = $items_stmt->fetchAll();
                                                    foreach ($items as $item):
                                                    ?>
                        <div class="mb-1">
                          <small class="text-muted"><?php echo htmlspecialchars($item['title']); ?></small>
                        </div>
                        <?php endforeach; ?>
                      </td>
                      <td><?php echo formatPrice($order['total_amount']); ?></td>
                      <td>
                        <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'completed' => 'success',
                                                        'failed' => 'danger',
                                                        'refunded' => 'secondary'
                                                    ];
                                                    $class = $status_class[$order['status']] ?? 'secondary';
                                                    ?>
                        <span class="badge bg-<?php echo $class; ?>">
                          <?php echo ucfirst($order['status']); ?>
                        </span>
                      </td>
                      <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                      <td>
                        <?php if ($order['status'] === 'completed'): ?>
                        <button class="btn btn-sm btn-success" disabled>
                          <i class="bi bi-download"></i> Downloaded
                        </button>
                        <?php elseif ($order['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-warning" disabled>
                          <i class="bi bi-clock"></i> Processing
                        </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Analytics Requests Tab -->
        <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
          <div class="card">
            <div class="card-header">
              <h5><i class="bi bi-graph-up"></i> Your Analytics Requests</h5>
            </div>
            <div class="card-body">
              <?php if (empty($analytics_requests)): ?>
              <div class="text-center py-4">
                <i class="bi bi-graph-down" style="font-size: 3rem; color: #ccc;"></i>
                <h4 class="mt-3">No analytics requests yet</h4>
                <p>Get professional data analysis for your research projects!</p>
                <a href="analytics.php" class="btn btn-primary">Request Analytics Service</a>
              </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Order #</th>
                      <th>Project Topic</th>
                      <th>Program Type</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Progress</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($analytics_requests as $request): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($request['order_number'] ?? 'N/A'); ?></strong></td>
                      <td>
                        <strong><?php echo htmlspecialchars(substr($request['project_topic'], 0, 50)); ?>...</strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($request['software']); ?>
                          Analysis</small>
                      </td>
                      <td><?php echo htmlspecialchars($request['program_type']); ?></td>
                      <td>
                        <?php echo $request['currency']; ?><?php echo number_format($request['payment_amount'], 2); ?>
                      </td>
                      <td>
                        <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'completed' => 'success'
                                                    ];
                                                    $class = $status_class[$request['status']] ?? 'secondary';
                                                    ?>
                        <span class="badge bg-<?php echo $class; ?>">
                          <?php echo ucfirst($request['status']); ?>
                        </span>
                      </td>
                      <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                      <td>
                        <?php if ($request['status'] === 'completed'): ?>
                        <div class="progress">
                          <div class="progress-bar bg-success" role="progressbar" style="width: 100%"
                            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100%</div>
                        </div>
                        <small class="text-success">Delivered to email</small>
                        <?php elseif ($request['status'] === 'processing'): ?>
                        <div class="progress">
                          <div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60"
                            aria-valuemin="0" aria-valuemax="100">60%</div>
                        </div>
                        <small class="text-info">In progress (3-5 days)</small>
                        <?php else: ?>
                        <div class="progress">
                          <div class="progress-bar bg-warning" role="progressbar" style="width: 25%" aria-valuenow="25"
                            aria-valuemin="0" aria-valuemax="100">25%</div>
                        </div>
                        <small class="text-warning">Payment verification</small>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>