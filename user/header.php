<div class="main-header">
    <div class="nav-top">
        <div class="container d-flex flex-row">
            <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <i class="icon-menu"></i>
                </span>
            </button>
            <button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
            <!-- Logo Header -->
            <a href="index.php" class="logo d-flex align-items-center">
                <img src="assets/img/logo.svg" alt="navbar brand" class="navbar-brand">
                <span class="text-white fw-bold ms-2">SYi-Tech Admin</span>
            </a>
            <!-- End Logo Header -->

            <!-- Navbar Right -->
            <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
                <li class="nav-item dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                        <div class="dropdown-user-scroll scrollbar-outer">
                            <li>
                                <div class="user-box">
                                    <div class="avatar-lg"><img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded"></div>
                                    <div class="u-text">
                                        <h4><?php echo htmlspecialchars(getCurrentUser()['first_name'] . ' ' . getCurrentUser()['last_name']); ?></h4>
                                        <p class="text-muted"><?php echo htmlspecialchars(getCurrentUser()['email']); ?></p>
                                        <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../user/logout.php">Logout</a>
                            </li>
                        </div>
                    </ul>
                </li>
            </ul>
            <!-- End Navbar Right -->
        </div>
    </div>
</div>