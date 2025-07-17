<?php
require_once 'db/config.php';

// Get categories for filter
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';

// Build query
$where_conditions = ["d.is_active = 1"];
$params = [];

if ($category_filter) {
    $where_conditions[] = "d.category_id = ?";
    $params[] = $category_filter;
}

if ($search_filter) {
    $where_conditions[] = "(d.title LIKE ? OR d.description LIKE ? OR d.tags LIKE ?)";
    $search_term = "%$search_filter%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($min_price !== '') {
    $where_conditions[] = "d.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== '') {
    $where_conditions[] = "d.price <= ?";
    $params[] = $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'created_at' => 'Newest First',
    'title' => 'Title A-Z',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'download_count' => 'Most Popular'
];

$order_clause = '';
switch ($sort_filter) {
    case 'title':
        $order_clause = 'ORDER BY d.title ASC';
        break;
    case 'price_asc':
        $order_clause = 'ORDER BY d.price ASC';
        break;
    case 'price_desc':
        $order_clause = 'ORDER BY d.price DESC';
        break;
    case 'download_count':
        $order_clause = 'ORDER BY d.download_count DESC';
        break;
    default:
        $order_clause = 'ORDER BY d.created_at DESC';
}

// Get datasets
$sql = "SELECT d.*, c.name as category_name 
        FROM datasets d 
        LEFT JOIN categories c ON d.category_id = c.id 
        WHERE $where_clause 
        $order_clause";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$datasets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Datasets - DataWorld</title>
  <?php include('nav/links.php'); ?>
  <style>
    .dataset-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }

    .dataset-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .price-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: linear-gradient(45deg, #007bff, #0056b3);
      color: white;
      padding: 8px 12px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 14px;
    }

    .dataset-meta {
      font-size: 0.85em;
      color: #666;
    }

    .dataset-tags {
      margin-top: 10px;
    }

    .dataset-tags .badge {
      margin-right: 5px;
      margin-bottom: 5px;
    }

    .filter-card {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
    }
  </style>
</head>

<body class="blog-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Dataset Marketplace</h1>
              <p class="mb-0">Discover high-quality datasets for your research and business needs</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index">Home</a></li>
            <li class="current">Datasets</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->

      <div class="container">
      <div class="row">

        <div class="col-md-12">

          <!-- Blog Posts Section -->
   
    <!-- Starter Section Section -->
    <section id="starter-section" class="starter-section section">

      <!-- Section Title -->
      <div class="container " data-aos="fade-up">
              <!-- Filters -->
              <div class="filter-card" data-aos="fade-up">
                <form method="get" class="row g-3">
                  <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                      value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Search datasets...">
                  </div>
                  <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                      <option value="">All Categories</option>
                      <?php foreach ($categories as $category): ?>
                      <option value="<?php echo $category['id']; ?>"
                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" class="form-control" id="min_price" name="min_price"
                      value="<?php echo htmlspecialchars($min_price); ?>" placeholder="0" step="0.01" min="0">
                  </div>

                  <div class="col-md-2">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" class="form-control" id="max_price" name="max_price"
                      value="<?php echo htmlspecialchars($max_price); ?>" placeholder="1000" step="0.01" min="0">
                  </div>

                  <div class="col-md-2">
                    <label for="sort" class="form-label">Sort By</label>
                    <select class="form-select" id="sort" name="sort">
                      <?php foreach ($sort_options as $value => $label): ?>
                      <option value="<?php echo $value; ?>" <?php echo $sort_filter == $value ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                  </div>
                </form>
              </div>

              <!-- Results Info -->
              <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12">
                  <p class="text-muted">
                    Found <?php echo count($datasets); ?> dataset(s)
                    <?php if ($search_filter): ?>
                    for "<?php echo htmlspecialchars($search_filter); ?>"
                    <?php endif; ?>
                  </p>
                </div>
              </div>

              <!-- Datasets Grid -->
              <div class="row gy-4">
                <?php if (empty($datasets)): ?>
                <div class="col-12 text-center" data-aos="fade-up">
                  <div class="alert alert-info">
                    <h4>No datasets found</h4>
                    <p>Try adjusting your search criteria or browse all datasets.</p>
                    <a href="datasets.php" class="btn btn-primary">View All Datasets</a>
                  </div>
                </div>
                <?php else: ?>
                <?php foreach ($datasets as $index => $dataset): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up"
                  data-aos-delay="<?php echo ($index % 3) * 100 + 200; ?>">
                  <div class="card dataset-card position-relative">
                    <div class="price-badge">
                      <?php echo formatPrice($dataset['price']); ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                      <h5 class="card-title"><?php echo htmlspecialchars($dataset['title']); ?></h5>

                      <div class="dataset-meta mb-2">
                        <small>
                          <i class="bi bi-folder"></i> <?php echo htmlspecialchars($dataset['category_name']); ?> |
                          <i class="bi bi-file-earmark"></i> <?php echo htmlspecialchars($dataset['format']); ?> |
                          <i class="bi bi-hdd"></i> <?php echo htmlspecialchars($dataset['file_size']); ?>
                        </small>
                      </div>

                      <p class="card-text">
                        <?php echo htmlspecialchars(substr($dataset['description'], 0, 120)) . '...'; ?>
                      </p>

                      <div class="dataset-meta mb-2">
                        <small>
                          <i class="bi bi-grid"></i> <?php echo number_format($dataset['rows_count']); ?> rows,
                          <?php echo $dataset['columns_count']; ?> columns |
                          <i class="bi bi-download"></i> <?php echo $dataset['download_count']; ?> downloads
                        </small>
                      </div>

                      <?php if ($dataset['tags']): ?>
                      <div class="dataset-tags">
                        <?php 
                        $tags = explode(',', $dataset['tags']);
                        foreach (array_slice($tags, 0, 3) as $tag): 
                        ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>

                      <div class="mt-auto pt-3">
                        <div class="d-grid gap-2">
                          <a href="view_dataset.php?id=<?php echo $dataset['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> View Details
                          </a>
                          <?php if (isLoggedIn()): ?>
                          <a href="add_to_cart.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                          </a>
                          <?php else: ?>
                          <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                            class="btn btn-primary">
                            <i class="bi bi-person"></i> Login to Purchase
                          </a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
              </div>

            </div>
          </section><!-- /Datasets Section -->
        </div>
      </div>
    </div>

    </main>

    <?php include('nav/footer.php'); ?>
</body>

</html>