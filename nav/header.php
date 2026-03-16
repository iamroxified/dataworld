<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="index.html" class="logo d-flex align-items-center me-auto">
      <!-- Uncomment the line below if you also wish to use an image logo -->
      <img src="assets/img/logo_dark.jpg" alt="">
      <h1 class="sitename">SYi-Tech</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="index.php" class="active">Home<br></a></li>
        <li><a href="blog.html">Blog</a></li>
        <li><a href="#">Datasets</a></li>
        <li><a href="user/analytics_request.php">Analytics</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="testimonials.php">Testimonial</a></li>
        <li class="dropdown"><a href="#"><span>About Us</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
          <ul>
            <li><a href="about.php">About SYi-Tech</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <!-- <li><a href="#">Contact Support</a></li> -->
          </ul>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="dropdown"><a href="#"><span>Account</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
          <ul>
            <li><a href="user/cart.php">Cart</a></li>
            <li><a href="user/index">Dashboard</a></li>
            <li><a href="user/referral">Referral</a></li>
            <li><a href="#">Profile</a></li>
            <li><a href="logout">Logout</a></li>
          </ul>
        </li>
        <?php  endif;?>
      </ul>

      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <?php if (!isLoggedIn()): ?>
    <a class="btn-getstarted flex-md-shrink-0" href="login">Get Started</a>
    <?php endif; ?>


  </div>
</header>