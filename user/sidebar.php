<?php
// Assuming getCurrentUser() is available and returns user details including role
$current_user_role = getCurrentUser()['role'];
?>
<div class="sidebar sidebar-style-2">
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-primary">
                <li class="nav-item active">
                    <a href="index.php" class="collapsed" aria-expanded="false">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <?php if ($current_user_role === 'admin') : ?>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">Admin Management</h4>
                    </li>
                    <li class="nav-item">
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <p>Manage Users</p>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($current_user_role, ['admin', 'operator'])) : ?>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">Operator Tasks</h4>
                    </li>
                    <li class="nav-item">
                        <a href="operator_analytics_requests.php">
                            <i class="fas fa-chart-bar"></i>
                            <p>Analytics Requests</p>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="../user/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>