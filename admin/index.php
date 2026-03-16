<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:login.php');
}else{

  // Get current user
  $user_id = $_SESSION['user_id'];
  $user_details = get_user_details($user_id);
  $last_name = $user_details['last_name'] ?? '';
  $first_name = $user_details['first_name'] ?? '';
  $username = $user_details['username'] ?? '';
  $email = $user_details['email'] ?? '';

// Get summary statistics for admin
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_amount = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn();
$total_commission = $pdo->query("SELECT SUM(amount) FROM wallet_transactions WHERE transaction_type = 'credit' ")->fetchColumn();
$total_pcommission_withdrawn = $pdo->query("SELECT SUM(amount) FROM wallet_transactions WHERE transaction_type = 'debit' and status = 'pending'")->fetchColumn();
$total_acommission_withdrawn = $pdo->query("SELECT SUM(amount) FROM wallet_transactions WHERE transaction_type = 'debit' and status = 'approved'")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_pending_requests = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$total_completed_requests = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$total_pending_payments = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>SYi - Tech Global Services - Dashboard</title>
  <?php include('nav/links.php'); ?>
  <link rel="stylesheet" href="assets/css/downline-tree.css">
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
          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Dashboard</h3>
              <h6 class="op-7 mb-2"><?php echo _greetin().', '. $_SESSION['user_name']; ?>! Welcome back to your
                SYi - Tech Dashboard</h6>
            </div>

          </div>
          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-users"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Users</p>
                        <h4 class="card-title"><?php echo $total_users; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-money-bill-wave"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Income</p>
                        <h4 class="card-title">N<?php echo @number_format($total_amount, 2); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-shopping-cart"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Analytics Requests</p>
                        <h4 class="card-title"><?php echo $total_requests; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-warning bubble-shadow-small">
                        <i class="fas fa-spinner"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Analytics Requests</p>
                        <h4 class="card-title"><?php echo $total_pending_requests; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-spinner"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Completed Analytics Requests</p>
                        <h4 class="card-title"><?php echo $total_completed_requests; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-credit-card"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Payments</p>
                        <h4 class="card-title"><?php echo $total_pending_payments; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-danger bubble-shadow-small">
                        <i class="fas fa-credit-card"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Commissions Earned</p>
                        <h4 class="card-title"><?php echo $total_commission; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-warning bubble-shadow-small">
                        <i class="fas fa-credit-card"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Pending Commissions</p>
                        <h4 class="card-title"><?php echo $total_pcommission_withdrawn; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
              <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-credit-card"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Approved Commissions</p>
                        <h4 class="card-title"><?php echo $total_acommission_withdrawn; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- <div class="row">
            <div class="col-md-4">
              <div class="card card-secondary">
                <div class="card-body skew-shadow">
                  <h1><?php //echo number_format(0,2); ?> </h1>
                  <h5 class="op-8">E-Wallet Balance</h5>
                  <div class="mt-2">
                    <a href="deposit.php" class="btn btn-success btn-sm me-2">View Deposits</a>
                    <a href="withdrawal.php" class="btn btn-warning btn-sm">View Withdrawals</a>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-7 col-lg-7">
              <div class="card">
           
              </div>
            </div>


          </div> -->

          <div class="row">
            <div class="col-sm-5 col-lg-5">
              <div class="col-sm-12 col-lg-12">
                <div class="card card-stats  card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-11">
                        <div class="col col-stats ms-3 ms-sm-0">
                          <div class="numbers">
                            <h4 class="card-title"> <i class="fas fa-link"></i> Quick Action</h4>
                            <!-- <p class="card-category">Share Referral Link </p> -->
                          </div>
                        </div>
                      </div>
                      <div class="col-1 ">
                        <div class="icon-big text-center ">
                          <i class="fa fa-solid fa-arrow-right"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
      <?php include('nav/footer.php'); ?>
      <script>
        var
          barChart = document.getElementById("barChart").getContext("2d");

        // lineChart = document.getElementById("lineChart").getContext("2d");

        // var myLineChart = new Chart(lineChart, {
        //   type: "line",
        //   data: {
        //     labels: [
        //       "Jan",
        //       "Feb",
        //       "Mar",
        //       "Apr",
        //       "May",
        //       "Jun",
        //       "Jul",
        //       "Aug",
        //       "Sep",
        //       "Oct",
        //       "Nov",
        //       "Dec",
        //     ],
        //     datasets: [
        //       {
        //         label: "Active Users",
        //         borderColor: "#1d7af3",
        //         pointBorderColor: "#FFF",
        //         pointBackgroundColor: "#1d7af3",
        //         pointBorderWidth: 2,
        //         pointHoverRadius: 4,
        //         pointHoverBorderWidth: 1,
        //         pointRadius: 4,
        //         backgroundColor: "transparent",
        //         fill: true,
        //         borderWidth: 2,
        //         data: [
        //           542, 480, 430, 550, 530, 453, 380, 434, 568, 610, 700, 900,
        //         ],
        //       },
        //     ],
        //   },
        //   options: {
        //     responsive: true,
        //     maintainAspectRatio: false,
        //     legend: {
        //       position: "bottom",
        //       labels: {
        //         padding: 10,
        //         fontColor: "#1d7af3",
        //       },
        //     },
        //     tooltips: {
        //       bodySpacing: 4,
        //       mode: "nearest",
        //       intersect: 0,
        //       position: "nearest",
        //       xPadding: 10,
        //       yPadding: 10,
        //       caretPadding: 10,
        //     },
        //     layout: {
        //       padding: { left: 15, right: 15, top: 15, bottom: 15 },
        //     },
        //   },
        // });

        var myBarChart = new Chart(barChart, {
          type: "bar",
          data: {
            labels: [
              "Jan",
              "Feb",
              "Mar",
              "Apr",
              "May",
              "Jun",
              "Jul",
              "Aug",
              "Sep",
              "Oct",
              "Nov",
              "Dec",
            ],
            datasets: [{
              label: "Sales",
              backgroundColor: "rgb(23, 125, 255)",
              borderColor: "rgb(23, 125, 255)",
              data: [3, 2, 9, 5, 4, 6, 4, 6, 7, 8, 7, 4],
            }, ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                },
              }, ],
            },
          },
        });

        // Chart with HTML Legends
      </script>
      <script>
        function copyToClipboard2() {
          const copyInput = document.getElementById("copyText2");
          copyInput.select();
          copyInput.setSelectionRange(0, 99999); // for mobile
          document.execCommand("copy");
          alert("Copied: " + copyInput.value);
        }

        function copyToClipboard() {
          const copyInput = document.getElementById("copyText");
          copyInput.select();
          copyInput.setSelectionRange(0, 99999); // for mobile
          document.execCommand("copy");
          alert("Copied: " + copyInput.value);
        }

        // Tree functionality
        document.addEventListener('DOMContentLoaded', function () {
          const treeContainer = document.querySelector('.tree');
          const expandAllBtn = document.getElementById('expandAll');
          const collapseAllBtn = document.getElementById('collapseAll');
          const resetViewBtn = document.getElementById('resetView');
          const depthSelector = document.getElementById('depthSelector');

          // Add click events to user cards for additional info
          const userCards = document.querySelectorAll('.node.user-card');
          userCards.forEach(card => {
            card.addEventListener('click', function (e) {
              e.preventDefault();
              const username = this.dataset.username;
              const level = this.dataset.level;

              // Show user info modal or expand details
              showUserDetails(username, level);
            });
          });

          // Expand all functionality
          expandAllBtn.addEventListener('click', function () {
            const collapsedNodes = treeContainer.querySelectorAll('.tree-item.collapsed');
            collapsedNodes.forEach(node => {
              node.classList.remove('collapsed');
            });
          });

          // Collapse all functionality
          collapseAllBtn.addEventListener('click', function () {
            const treeItems = treeContainer.querySelectorAll('.tree-item');
            treeItems.forEach((item, index) => {
              if (index > 0) { // Don't collapse the root
                item.classList.add('collapsed');
              }
            });
          });

          // Reset view functionality
          resetViewBtn.addEventListener('click', function () {
            location.reload();
          });

          // Depth selector change
          depthSelector.addEventListener('change', function () {
            const selectedDepth = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('depth', selectedDepth);
            window.location.href = currentUrl.toString();
          });

          // Show user details function
          function showUserDetails(username, level) {
            // Get the clicked node to extract data
            const clickedNode = document.querySelector(`[data-username="${username}"]`);
            if (!clickedNode) return;

            const fullName = clickedNode.dataset.fullname || 'N/A';
            const joinDate = clickedNode.dataset.joindate || 'N/A';
            const downlines = clickedNode.dataset.downlines || '0';
            const email = clickedNode.dataset.email || 'N/A';
            const phone = clickedNode.dataset.phone || 'N/A';
            const sponsor = clickedNode.dataset.sponsor || 'N/A';

            // Create a detailed modal
            const modal = document.createElement('div');
            modal.className = 'user-detail-modal';
            modal.innerHTML = `
            <div class="modal-content">
              <h4>👤 User Details</h4>
              <div class="user-detail">
                <span class="label">Username:</span>
                <span class="value">${username}</span>
              </div>
              <div class="user-detail">
                <span class="label">Full Name:</span>
                <span class="value">${fullName}</span>
              </div>
              <div class="user-detail">
                <span class="label">Email:</span>
                <span class="value">${email}</span>
              </div>
              <div class="user-detail">
                <span class="label">Phone:</span>
                <span class="value">${phone}</span>
              </div>
              <div class="user-detail">
                <span class="label">Sponsor:</span>
                <span class="value">${sponsor}</span>
              </div>
              <div class="user-detail">
                <span class="label">Level:</span>
                <span class="value">${level}</span>
              </div>
              <div class="user-detail">
                <span class="label">Join Date:</span>
                <span class="value">${joinDate}</span>
              </div>
              <div class="user-detail">
                <span class="label">Total Downlines:</span>
                <span class="value">${downlines}</span>
              </div>
              <button class="close-btn" onclick="this.parentElement.parentElement.remove()">Close</button>
            </div>
          `;

            modal.addEventListener('click', function (e) {
              if (e.target === modal) {
                document.body.removeChild(modal);
              }
            });

            document.body.appendChild(modal);
          }

          // Add hover effects
          const nodes = document.querySelectorAll('.tree .node');
          nodes.forEach(node => {
            node.addEventListener('mouseenter', function () {
              this.style.transform = 'translateY(-2px) scale(1.02)';
            });

            node.addEventListener('mouseleave', function () {
              this.style.transform = 'translateY(0) scale(1)';
            });
          });
        });
      </script>
</body>

</html>