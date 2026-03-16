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
  <title>About Us - DataWorld</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="about-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="container">
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">About Us</li>
          </ol>
        </nav>
        <h1>About Us</h1>
      </div>
    </div><!-- End Page Title -->
    <!-- About Section -->
    <section id="about" class="about section">

      <div class="container" data-aos="fade-up">
        <div class="row gx-0">

          <div class="col-lg-6 d-flex flex-column justify-content-center" data-aos="fade-up" data-aos-delay="200">
            <div class="content">
              <h3>Who We Are</h3>
              <h2>SYi-Tech Global Services - Your Gateway to Data Excellence</h2>
              <p>
                We are a leading provider of comprehensive data solutions, offering centralized access to high-quality datasets, professional analytics services, and expert consultation. Our platform serves businesses, researchers, and organizations seeking to harness the power of data for informed decision-making and strategic growth.
              </p>
              <div class="text-center text-lg-start">
                <a href="#" class="btn-read-more d-inline-flex align-items-center justify-content-center align-self-center">
                  <span>Read More</span>
                  <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>

          <div class="col-lg-6 d-flex align-items-center" data-aos="zoom-out" data-aos-delay="200">
            <img src="assets/img/about.jpg" class="img-fluid" alt="">
          </div>

        </div>
      </div>

    </section><!-- /About Section -->

    <!-- Values Section -->
    <section id="values" class="values section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Our Core Services</h2>
        <p>Comprehensive data analysis solutions for your project Work<br></p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card">
              <img src="assets/img/values-1.png" class="img-fluid" alt="">
              <h3>Analysis Data Solutions</h3>
              <p>Browse and purchase high-quality datasets across various industries including finance, healthcare, education, and agriculture.</p>
            </div>
          </div><!-- End Card Item -->

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card">
              <img src="assets/img/values-2.png" class="img-fluid" alt="">
              <h3>Analytics Services</h3>
              <p>Submit custom analysis requests and receive expert insights from our team of data professionals with real-time tracking.</p>
            </div>
          </div><!-- End Card Item -->

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card">
              <img src="assets/img/values-3.png" class="img-fluid" alt="">
              <h3>Expert Consultation</h3>
              <p>Book personalized consultation sessions with our data experts for strategic guidance and professional insights.</p>
            </div>
          </div><!-- End Card Item -->

        </div>

      </div>

    </section><!-- /Values Section -->

    <!-- Stats Section -->
    <section id="stats" class="stats section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-emoji-smile color-blue flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="232" data-purecounter-duration="1" class="purecounter"></span>
                <p>Happy Clients</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-journal-richtext color-orange flex-shrink-0" style="color: #ee6c20;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="521" data-purecounter-duration="1" class="purecounter"></span>
                <p>Projects</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-headset color-green flex-shrink-0" style="color: #15be56;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="1463" data-purecounter-duration="1" class="purecounter"></span>
                <p>Hours Of Support</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-people color-pink flex-shrink-0" style="color: #bb0852;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="15" data-purecounter-duration="1" class="purecounter"></span>
                <p>Hard Workers</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

        </div>

      </div>

    </section><!-- /Stats Section -->

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

    <!-- Alt Features Section -->
    <section id="alt-features" class="alt-features section">
     <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>What we do</h2>
        <p>Our Services<br></p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-5">

          <div class="col-xl-7 d-flex order-2 order-xl-1" data-aos="fade-up" data-aos-delay="200">

            <div class="row align-self-center gy-5">

              <div class="col-md-6 icon-box">
                <i class="bi bi-pencil-square"></i>
                <div>
                  <h4>Project writing and editing</h4>
                  <p>We help you with writing and editing your projects to ensure they are of high quality.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-bar-chart-line"></i>
                <div>
                  <h4>Data analysis (SPSS, Excel, or R)</h4>
                  <p>We provide data analysis services using SPSS, Excel, or R to help you make sense of your data.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-easel"></i>
                <div>
                  <h4>Seminar and presentation preparation</h4>
                  <p>We assist you in preparing for your seminars and presentations, ensuring you deliver a compelling message.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-file-earmark-text"></i>
                <div>
                  <h4>Proposal writing</h4>
                  <p>We help you write convincing proposals to get your research or project approved.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-book"></i>
                <div>
                  <h4>Assignment and report assistance</h4>
                  <p>We provide assistance with your assignments and reports to help you achieve academic success.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-fonts"></i>
                <div>
                  <h4>Typing and document formatting (APA, MLA, etc.)</h4>
                  <p>We offer typing and document formatting services to ensure your documents are well-structured and adhere to citation styles like APA and MLA.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-search"></i>
                <div>
                  <h4>Plagiarism checking and correction</h4>
                  <p>We check for plagiarism in your work and help you correct it to maintain academic integrity.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-question-square"></i>
                <div>
                  <h4>Questionnaire design and data entry</h4>
                  <p>We assist in designing effective questionnaires and accurately entering the collected data for analysis.</p>
                </div>
              </div><!-- End Feature Item -->

              <div class="col-md-6 icon-box">
                <i class="bi bi-lightbulb"></i>
                <div>
                  <h4>Research topic selection and development</h4>
                  <p>We guide you in selecting and developing a research topic that is both interesting and feasible.</p>
                </div>
              </div><!-- End Feature Item -->

            </div>

          </div>

          <div class="col-xl-5 d-flex align-items-center order-1 order-xl-2" data-aos="fade-up" data-aos-delay="100">
            <img src="assets/img/alt-features.png" class="img-fluid" alt="">
          </div>

        </div>

      </div>

    </section><!-- /Alt Features Section -->


  </main>

  <?php include('nav/footer.php') ?>
</body>

</html>
