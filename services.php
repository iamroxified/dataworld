<?php
require_once 'db/config.php';
// requireLogin();

// $user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Services - DataWorld</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="services-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="container">
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Services</li>
          </ol>
        </nav>
        <h1>Our Services</h1>
      </div>
    </div><!-- End Page Title -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Our Services</h2>
        <p>Comprehensive academic and research solutions</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item item-cyan position-relative">
              <i class="bi bi-pencil-square icon"></i>
              <h3>Project writing and editing</h3>
              <p>Professional assistance in writing and editing academic and research projects to ensure clarity, coherence, and academic rigor.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item item-orange position-relative">
              <i class="bi bi-bar-chart-line icon"></i>
              <h3>Data analysis (SPSS, Excel, or R)</h3>
              <p>Expert data analysis services using SPSS, Excel, or R to help you uncover insights and draw meaningful conclusions from your data.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item item-teal position-relative">
              <i class="bi bi-easel icon"></i>
              <h3>Seminar and presentation preparation</h3>
              <p>Comprehensive support in preparing for seminars and presentations, including content development and slide design.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item item-red position-relative">
              <i class="bi bi-file-earmark-text icon"></i>
              <h3>Proposal writing</h3>
              <p>Crafting compelling research proposals to help you secure funding and approval for your projects.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="service-item item-indigo position-relative">
              <i class="bi bi-book icon"></i>
              <h3>Assignment and report assistance</h3>
              <p>Reliable assistance with assignments and reports to ensure you meet your academic deadlines with high-quality work.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
            <div class="service-item item-pink position-relative">
              <i class="bi bi-file-font icon"></i>
              <h3>Typing and document formatting</h3>
              <p>Professional typing and document formatting services in various academic styles such as APA, MLA, and more.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="700">
            <div class="service-item item-purple position-relative">
              <i class="bi bi-search icon"></i>
              <h3>Plagiarism checking and correction</h3>
              <p>Ensure the originality of your work with our plagiarism checking and correction services.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="800">
            <div class="service-item item-brown position-relative">
              <i class="bi bi-question-circle icon"></i>
              <h3>Questionnaire design and data entry</h3>
              <p>Expert assistance in designing effective questionnaires and accurate data entry to support your research.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="900">
            <div class="service-item item-dark position-relative">
              <i class="bi bi-lightbulb icon"></i>
              <h3>Research topic selection and development</h3>
              <p>Guidance in selecting and developing a research topic that is both interesting and feasible for your academic level.</p>
              <a href="contact.php" class="read-more stretched-link"><span>Get Started</span> <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- Features Section -->
    <section id="features" class="features section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Why Choose Our Services</h2>
        <p>Our advanced capabilities and proven methodologies</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-5">

          <div class="col-xl-6" data-aos="zoom-out" data-aos-delay="100">
            <img src="assets/img/features.png" class="img-fluid" alt="">
          </div>

          <div class="col-xl-6 d-flex">
            <div class="row align-self-center gy-4">

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>Expert Team</h3>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>Proven Methodologies</h3>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>Scalable Solutions</h3>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>24/7 Support</h3>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>Secure & Compliant</h3>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="700">
                <div class="feature-box d-flex align-items-center">
                  <i class="bi bi-check"></i>
                  <h3>Custom Solutions</h3>
                </div>
              </div><!-- End Feature Item -->

            </div>
          </div>

        </div>

      </div>

    </section><!-- /Features Section -->

    <!-- Pricing Section -->
    <section id="pricing" class="pricing section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Service Pricing</h2>
        <p>Choose the perfect plan for your data needs</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="100">
            <div class="pricing-tem">
              <h3 style="color: #20c997;"> ND Plan</h3>
              <div class="price"><sup>₦</sup>8400<span> / Analyis</span></div>
              <div class="icon">
                <i class="bi bi-box" style="color: #20c997;"></i>
              </div>
              <ul>
                <li>Chapter 1-3 Required</li>
                <li>Basic Analytics Reports</li>
                <li>Email Support</li>
                <li class="na">Custom Dashboards</li>
                <li class="na">Priority Support</li>
              </ul>
              <a href="user/analytics_request" class="btn-buy">Get Started</a>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="pricing-tem">
              <span class="featured">Most Popular</span>
              <h3 style="color: #0dcaf0;"> BSc / HND Plans</h3>
              <div class="price"><sup>₦</sup>10,200<span> / analysis</span></div>
              <div class="icon">
                <i class="bi bi-send" style="color: #0dcaf0;"></i>
              </div>
              <ul>
                <li>Chapter 1-3 Required</li>
                <li>Advanced Analytics</li>
                <li>Custom Dashboards</li>
                <li>Priority Support</li>
                <li class="na">White-label Solutions</li>
              </ul>
              <a href="user/analytics_request" class="btn-buy">Get Started</a>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="pricing-tem">
              <h3 style="color: #fd7e14;"> PGD Plans</h3>
              <div class="price"><sup>₦</sup> 12,500 <span> / analysis</span></div>
              <div class="icon">
                <i class="bi bi-airplane" style="color: #fd7e14;"></i>
              </div>
              <ul>
                <li>Chapter 1-3 Required</li>
                <li>Custom Analytics</li>
                <li>API Access</li>
                <li>Dedicated Support</li>
                <li>White-label Solutions</li>
              </ul>
              <a href="user/analytics_request" class="btn-buy">Get Started</a>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="400">
            <div class="pricing-tem">
              <h3 style="color: #0d6efd;">MSc Plan</h3>
              <div class="price"><sup>₦</sup>15,500<span> / analysis</span></div>
              <div class="icon">
                <i class="bi bi-rocket" style="color: #0d6efd;"></i>
              </div>
              <ul>
                <li>Chapter 1-3</li>
                <li>Custom Development</li>
                <li>On-premise Deployment</li>
                <li>24/7 Phone Support</li>
                <li>SLA Guarantees</li>
              </ul>
              <a href="user/analytics_request" class="btn-buy">Contact Us</a>
            </div>
          </div><!-- End Pricing Item -->
          
          <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="400">
            <div class="pricing-tem">
              <h3 style="color: #0d6efd;">PHd Plan</h3>
              <div class="price"><sup>₦</sup>25,500<span> / analysis</span></div>
              <div class="icon">
                <i class="bi bi-rocket" style="color: #0d6efd;"></i>
              </div>
              <ul>
                <li>Chapter 1-3</li>
                <li>Custom Development</li>
                <li>On-premise Deployment</li>
                <li>24/7 Phone Support</li>
                <li>SLA Guarantees</li>
              </ul>
              <a href="user/analytics_request" class="btn-buy">Contact Us</a>
            </div>
          </div><!-- End Pricing Item -->

        </div><!-- End pricing row -->

      </div>

    </section><!-- /Pricing Section -->

  </main>

  <?php include('nav/footer.php') ?>
</body>

</html>
