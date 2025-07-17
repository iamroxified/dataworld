<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Data Marketplace - DataWorld</title>
    <?php include('nav/links.php'); ?>
</head>
<body>
    <?php include('nav/header.php'); ?>
    <main class="main">
        <!-- Page Title -->
        <section id="page-title" class="page-title section dark-background">
            <div class="container position-relative">
                <h1>Data Marketplace</h1>
                <p>Discover and purchase high-quality datasets across various industries</p>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="index.php">Home</a></li>
                        <li class="current">Marketplace</li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Search and Filter Section -->
        <section id="search-filter" class="search-filter section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="Search datasets by keywords, industry, or data type...">
                            <button type="button" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <select class="form-select">
                            <option selected>All Categories</option>
                            <option value="finance">Finance</option>
                            <option value="healthcare">Healthcare</option>
                            <option value="education">Education</option>
                            <option value="agriculture">Agriculture</option>
                            <option value="technology">Technology</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <!-- Datasets Section -->
        <section id="datasets" class="datasets section">
            <div class="container">
                <div class="row gy-4">
                    <!-- Finance Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/product-1.jpg" class="img-fluid" alt="Finance Dataset">
                                <div class="dataset-price">$299</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Global Financial Markets Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Finance</p>
                                <p class="description">Comprehensive financial data including stock prices, market indices, trading volumes, and economic indicators from major global markets.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> 10+ years of historical data</li>
                                    <li><i class="bi bi-check"></i> Real-time updates</li>
                                    <li><i class="bi bi-check"></i> CSV, JSON, Excel formats</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=1" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=1" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Healthcare Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/product-2.jpg" class="img-fluid" alt="Healthcare Dataset">
                                <div class="dataset-price">$450</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Healthcare Analytics Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Healthcare</p>
                                <p class="description">Medical research data, patient demographics, treatment outcomes, and healthcare facility performance metrics.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> HIPAA compliant</li>
                                    <li><i class="bi bi-check"></i> Anonymized patient data</li>
                                    <li><i class="bi bi-check"></i> Multiple file formats</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=2" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=2" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Education Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/product-3.jpg" class="img-fluid" alt="Education Dataset">
                                <div class="dataset-price">$199</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Educational Performance Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Education</p>
                                <p class="description">Student performance metrics, curriculum effectiveness, institutional rankings, and educational outcome analysis.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> K-12 and Higher Ed data</li>
                                    <li><i class="bi bi-check"></i> Privacy protected</li>
                                    <li><i class="bi bi-check"></i> Statistical analysis ready</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=3" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=3" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agriculture Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/branding-1.jpg" class="img-fluid" alt="Agriculture Dataset">
                                <div class="dataset-price">$350</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Agricultural Production Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Agriculture</p>
                                <p class="description">Crop yields, weather patterns, soil quality, farming techniques, and agricultural market trends across multiple regions.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> Global coverage</li>
                                    <li><i class="bi bi-check"></i> Weather integration</li>
                                    <li><i class="bi bi-check"></i> Seasonal analysis</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=4" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=4" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technology Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/branding-2.jpg" class="img-fluid" alt="Technology Dataset">
                                <div class="dataset-price">$550</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Technology Trends Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Technology</p>
                                <p class="description">Software adoption rates, technology investment patterns, innovation metrics, and digital transformation indicators.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> Enterprise focus</li>
                                    <li><i class="bi bi-check"></i> Trend analysis</li>
                                    <li><i class="bi bi-check"></i> API access included</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=5" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=5" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Dataset -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                        <div class="dataset-card">
                            <div class="dataset-img">
                                <img src="assets/img/portfolio/branding-3.jpg" class="img-fluid" alt="Social Media Dataset">
                                <div class="dataset-price">$399</div>
                            </div>
                            <div class="dataset-info">
                                <h4>Social Media Analytics Dataset</h4>
                                <p class="category"><i class="bi bi-tag"></i> Social Media</p>
                                <p class="description">Social media engagement metrics, sentiment analysis data, trending topics, and user behavior patterns.</p>
                                <ul class="dataset-features">
                                    <li><i class="bi bi-check"></i> Multi-platform data</li>
                                    <li><i class="bi bi-check"></i> Sentiment scores</li>
                                    <li><i class="bi bi-check"></i> Real-time feeds</li>
                                </ul>
                                <div class="dataset-actions">
                                    <a href="dataset-details.php?id=6" class="btn btn-outline-primary">View Details</a>
                                    <a href="purchase.php?id=6" class="btn btn-primary">Purchase Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="row mt-5">
                    <div class="col-12">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include('nav/footer.php'); ?>
</body>
</html>

