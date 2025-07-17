<?php
require_once 'db/config.php';
requireLogin();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Portfolio - DataWorld</title>
  <?php include('nav/links.php'); ?>
</head>

<body class="portfolio-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Our Latest Work</h1>
              <p class="mb-0">Showcasing our data analysis projects and solutions across various industries</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index">Home</a></li>

            <li class="current">Testimonial</li>
          </ol>
        </div>
      </nav>
    </div>

    <!-- Portfolio Section -->
    <section id="portfolio" class="portfolio section">

      <!-- Section Title -->
      <div class="container">
        <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">

          <ul class="portfolio-filters isotope-filters" data-aos="fade-up" data-aos-delay="100">
            <li data-filter="*" class="filter-active">All</li>
            <li data-filter=".filter-analytics">Analytics</li>
            <li data-filter=".filter-visualization">Visualization</li>
            <li data-filter=".filter-ml">Machine Learning</li>
            <li data-filter=".filter-consulting">Consulting</li>
          </ul><!-- End Portfolio Filters -->

          <div class="row gy-4 isotope-container" data-aos="fade-up" data-aos-delay="200">

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-analytics">
              <div class="portfolio-content h-100">
                <img src="assets/img/values-1.png" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Financial Risk Analysis</h4>
                  <p>Comprehensive risk assessment for a major banking institution</p>
                  <a href="assets/img/values-1.png" title="Financial Risk Analysis" data-gallery="portfolio-gallery-analytics" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-visualization">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/visualization-1.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Healthcare Dashboard</h4>
                  <p>Interactive dashboard for patient data visualization</p>
                  <a href="assets/img/portfolio/visualization-1.jpg" title="Healthcare Dashboard" data-gallery="portfolio-gallery-visualization" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-ml">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/ml-1.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Predictive Maintenance</h4>
                  <p>Machine learning model for industrial equipment maintenance</p>
                  <a href="assets/img/portfolio/ml-1.jpg" title="Predictive Maintenance" data-gallery="portfolio-gallery-ml" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-consulting">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/consulting-1.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Digital Transformation</h4>
                  <p>Strategic consulting for retail chain data modernization</p>
                  <a href="assets/img/portfolio/consulting-1.jpg" title="Digital Transformation" data-gallery="portfolio-gallery-consulting" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-analytics">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/analytics-2.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Market Research Analysis</h4>
                  <p>Consumer behavior analysis for e-commerce platform</p>
                  <a href="assets/img/portfolio/analytics-2.jpg" title="Market Research Analysis" data-gallery="portfolio-gallery-analytics" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-visualization">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/visualization-2.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Supply Chain Visualization</h4>
                  <p>Real-time supply chain monitoring dashboard</p>
                  <a href="assets/img/portfolio/visualization-2.jpg" title="Supply Chain Visualization" data-gallery="portfolio-gallery-visualization" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-ml">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/ml-2.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Customer Segmentation</h4>
                  <p>AI-powered customer segmentation for targeted marketing</p>
                  <a href="assets/img/portfolio/ml-2.jpg" title="Customer Segmentation" data-gallery="portfolio-gallery-ml" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-consulting">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/consulting-2.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Data Governance</h4>
                  <p>Enterprise data governance framework implementation</p>
                  <a href="assets/img/portfolio/consulting-2.jpg" title="Data Governance" data-gallery="portfolio-gallery-consulting" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-analytics">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/analytics-3.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Performance Analytics</h4>
                  <p>Sports performance analysis using advanced statistics</p>
                  <a href="assets/img/portfolio/analytics-3.jpg" title="Performance Analytics" data-gallery="portfolio-gallery-analytics" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-visualization">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/visualization-3.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Environmental Monitoring</h4>
                  <p>Climate data visualization for environmental research</p>
                  <a href="assets/img/portfolio/visualization-3.jpg" title="Environmental Monitoring" data-gallery="portfolio-gallery-visualization" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-ml">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/ml-3.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Fraud Detection</h4>
                  <p>Real-time fraud detection system using deep learning</p>
                  <a href="assets/img/portfolio/ml-3.jpg" title="Fraud Detection" data-gallery="portfolio-gallery-ml" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-consulting">
              <div class="portfolio-content h-100">
                <img src="assets/img/portfolio/consulting-3.jpg" class="img-fluid" alt="">
                <div class="portfolio-info">
                  <h4>Cloud Migration</h4>
                  <p>Enterprise cloud data migration and optimization</p>
                  <a href="assets/img/portfolio/consulting-3.jpg" title="Cloud Migration" data-gallery="portfolio-gallery-consulting" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                  <a href="portfolio-details.html" title="More Details" class="details-link"><i class="bi bi-link-45deg"></i></a>
                </div>
              </div>
            </div><!-- End Portfolio Item -->

          </div><!-- End Portfolio Container -->

        </div>

      </div>

    </section><!-- /Portfolio Section -->

    <!-- Stats Section -->
    <section id="stats" class="stats section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-briefcase color-blue flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="150" data-purecounter-duration="1" class="purecounter"></span>
                <p>Completed Projects</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-people color-orange flex-shrink-0" style="color: #ee6c20;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="85" data-purecounter-duration="1" class="purecounter"></span>
                <p>Satisfied Clients</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-award color-green flex-shrink-0" style="color: #15be56;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="25" data-purecounter-duration="1" class="purecounter"></span>
                <p>Industry Awards</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-globe color-pink flex-shrink-0" style="color: #bb0852;"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="12" data-purecounter-duration="1" class="purecounter"></span>
                <p>Countries Served</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

        </div>

      </div>

    </section><!-- /Stats Section -->

    <!-- Call to Action Section -->
    <section id="call-to-action" class="call-to-action section">

      <div class="container" data-aos="fade-up">
        <div class="row g-5">

          <div class="col-lg-8 col-md-6 content d-flex flex-column justify-content-center order-last order-md-first">
            <h3>Ready to Transform Your Data?</h3>
            <p>Let's discuss how our data analysis expertise can drive your business forward. Our team is ready to tackle your most complex data challenges.</p>
            <a class="cta-btn align-self-start" href="#contact">Start Your Project</a>
          </div>

          <div class="col-lg-4 col-md-6 order-first order-md-last d-flex align-items-center">
            <div class="img">
              <img src="assets/img/cta.jpg" alt="" class="img-fluid">
            </div>
          </div>

        </div>
      </div>

    </section><!-- /Call to Action Section -->

  </main>

  <?php include('nav/footer.php') ?>
</body>

</html>
