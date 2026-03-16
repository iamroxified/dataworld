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
  <title>FAQ - DataWorld</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="faq-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="container">
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">FAQ</li>
          </ol>
        </nav>
        <h1>Frequently Asked Questions</h1>
      </div>
    </div><!-- End Page Title -->

    <!-- Faq Section -->
    <section id="faq" class="faq section">

      <div class="container">

        <div class="row">

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">

            <div class="faq-container">

              <div class="faq-item">
                <h3>What is DataWorld?</h3>
                <div class="faq-content">
                  <p>DataWorld is a platform that provides access to a wide range of datasets for research, analysis, and various other purposes. We also offer data-related services to help our users.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

              <div class="faq-item">
                <h3>How can I download a dataset?</h3>
                <div class="faq-content">
                  <p>To download a dataset, you need to be a registered user. Once logged in, you can browse the marketplace, select a dataset, and proceed to download it.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

              <div class="faq-item">
                <h3>What are the pricing plans?</h3>
                <div class="faq-content">
                  <p>We offer various pricing plans to suit different needs, from a basic plan for individual users to enterprise solutions for large organizations. You can find more details on our services page.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

            </div>

          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">

            <div class="faq-container">

              <div class="faq-item">
                <h3>Can I request a specific dataset?</h3>
                <div class="faq-content">
                  <p>Yes, if you can't find the dataset you are looking for, you can make a request, and our team will do its best to source it for you.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

              <div class="faq-item">
                <h3>What kind of data analysis services do you offer?</h3>
                <div class="faq-content">
                  <p>We provide expert data analysis services using tools like SPSS, Excel, and R to help you derive meaningful insights from your data.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

              <div class="faq-item">
                <h3>How can I contact support?</h3>
                <div class="faq-content">
                  <p>You can contact our support team through the contact form on our website. We also offer priority support for our premium plan users.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div><!-- End Faq item-->

            </div>

          </div>
        </div>

      </div>

    </section><!-- /Faq Section -->

  </main>

  <?php include('nav/footer.php') ?>
</body>

</html>
