<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="index.html" class="logo d-flex align-items-center me-auto">
      <!-- Uncomment the line below if you also wish to use an image logo -->
      <img src="assets/img/logo_dark.jpg" alt="">
      <h1 class="sitename">DAGS</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="index" class="active">Home<br></a></li>
        <li><a href="blog">Blog</a></li>
        <li><a href="datasets">Datasets</a></li>
        <li><a href="analytics">Analytics</a></li>
        <li><a href="services">Services</a></li>
        <li><a href="portfolio">Testimonial</a></li>
        <li class="dropdown"><a href="#"><span>About Us</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
          <ul>
            <li><a href="about">About DAGS</a></li>
            <li><a href="contact">Contact</a></li>
            <li><a href="faq">FAQ</a></li>
            <li><a href="#">Contact Support</a></li>
          </ul>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="dropdown"><a href="#"><span>Account</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
          <ul>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
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