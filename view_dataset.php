<?php
require_once 'db/config.php';

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$dataset_id) {
    header('Location: datasets.php');
    exit();
}

// Get dataset details
$stmt = $pdo->prepare("SELECT d.*, c.name as category_name, u.first_name, u.last_name 
                       FROM datasets d 
                       LEFT JOIN categories c ON d.category_id = c.id 
                       LEFT JOIN users u ON d.created_by = u.id 
                       WHERE d.id = ? AND d.is_active = 1");
$stmt->execute([$dataset_id]);
$dataset = $stmt->fetch();

if (!$dataset) {
    header('Location: datasets.php');
    exit();
}

// Parse preview data
$preview_rows = [];
if ($dataset['preview_data']) {
    $lines = explode("\n", $dataset['preview_data']);
    foreach ($lines as $line) {
        if (trim($line)) {
            if ($dataset['format'] === 'JSON') {
                $preview_rows[] = json_decode($line, true);
            } else {
                $preview_rows[] = str_getcsv($line);
            }
        }
    }
}

// Check if user has already purchased this dataset
$user_has_access = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_downloads ud 
                           JOIN orders o ON ud.order_id = o.id 
                           WHERE ud.user_id = ? AND ud.dataset_id = ? AND o.status = 'completed'");
    $stmt->execute([$_SESSION['user_id'], $dataset_id]);
    $user_has_access = $stmt->fetchColumn() > 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo htmlspecialchars($dataset['title']); ?> - DataWorld</title>
  <?php include('nav/links.php'); ?>
  <style>
    .dataset-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 40px 0;
    }
    .price-display {
      font-size: 2rem;
      font-weight: bold;
      color: #28a745;
    }
    .preview-table {
      max-width: 100%;
      overflow-x: auto;
    }
    .preview-table table {
      font-size: 0.9em;
    }
    .dataset-meta {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
    }
    .tag-list .badge {
      margin-right: 5px;
      margin-bottom: 5px;
    }
  </style>
</head>

<body class="dataset-view-page">
  <?php include('nav/header.php'); ?>

  <main class="main">

    <!-- Dataset Header -->
    <section class="dataset-header">
      <div class="container">
        <nav class="breadcrumbs mb-3">
          <ol>
            <li><a href="index.php" class="text-white-50">Home</a></li>
            <li><a href="datasets.php" class="text-white-50">Datasets</a></li>
            <li class="text-white"><?php echo htmlspecialchars($dataset['title']); ?></li>
          </ol>
        </nav>
        
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h1 class="mb-3"><?php echo htmlspecialchars($dataset['title']); ?></h1>
            <p class="lead mb-3"><?php echo htmlspecialchars($dataset['description']); ?></p>
            <div class="d-flex align-items-center">
              <span class="badge bg-light text-dark me-3">
                <i class="bi bi-folder"></i> <?php echo htmlspecialchars($dataset['category_name']); ?>
              </span>
              <span class="badge bg-light text-dark me-3">
                <i class="bi bi-file-earmark"></i> <?php echo htmlspecialchars($dataset['format']); ?>
              </span>
              <span class="badge bg-light text-dark">
                <i class="bi bi-download"></i> <?php echo $dataset['download_count']; ?> downloads
              </span>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="price-display mb-3">
              <?php echo formatPrice($dataset['price']); ?>
            </div>
            <?php if ($user_has_access): ?>
              <a href="download_dataset.php?id=<?php echo $dataset['id']; ?>" class="btn btn-success btn-lg">
                <i class="bi bi-download"></i> Download Now
              </a>
            <?php elseif (isLoggedIn()): ?>
              <a href="add_to_cart.php?id=<?php echo $dataset['id']; ?>" class="btn btn-warning btn-lg">
                <i class="bi bi-cart-plus"></i> Add to Cart
              </a>
            <?php else: ?>
              <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-warning btn-lg">
                <i class="bi bi-person"></i> Login to Purchase
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- Dataset Details -->
    <section class="section">
      <div class="container">
        <div class="row">
          
          <!-- Main Content -->
          <div class="col-lg-8">
            
            <!-- Dataset Information -->
            <div class="card mb-4" data-aos="fade-up">
              <div class="card-header">
                <h4><i class="bi bi-info-circle"></i> Dataset Information</h4>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>File Size:</strong> <?php echo htmlspecialchars($dataset['file_size']); ?></p>
                    <p><strong>Format:</strong> <?php echo htmlspecialchars($dataset['format']); ?></p>
                    <p><strong>Rows:</strong> <?php echo number_format($dataset['rows_count']); ?></p>
                    <p><strong>Columns:</strong> <?php echo $dataset['columns_count']; ?></p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($dataset['category_name']); ?></p>
                    <p><strong>Downloads:</strong> <?php echo $dataset['download_count']; ?></p>
                    <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($dataset['created_at'])); ?></p>
                    <p><strong>Updated:</strong> <?php echo date('M j, Y', strtotime($dataset['updated_at'])); ?></p>
                  </div>
                </div>
                
                <?php if ($dataset['tags']): ?>
                  <div class="tag-list mt-3">
                    <strong>Tags:</strong><br>
                    <?php 
                    $tags = explode(',', $dataset['tags']);
                    foreach ($tags as $tag): 
                    ?>
                      <span class="badge bg-secondary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Data Preview -->
            <?php if (!empty($preview_rows)): ?>
              <div class="card mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card-header">
                  <h4><i class="bi bi-table"></i> Data Preview</h4>
                </div>
                <div class="card-body">
                  <div class="preview-table">
                    <?php if ($dataset['format'] === 'JSON'): ?>
                      <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars(json_encode(array_slice($preview_rows, 0, 3), JSON_PRETTY_PRINT)); ?></code></pre>
                    <?php else: ?>
                      <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                          <tr>
                            <?php if (isset($preview_rows[0])): ?>
                              <?php foreach ($preview_rows[0] as $header): ?>
                                <th><?php echo htmlspecialchars($header); ?></th>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </tr>
                        </thead>
                        <tbody>
                          <?php for ($i = 1; $i < min(4, count($preview_rows)); $i++): ?>
                            <tr>
                              <?php foreach ($preview_rows[$i] as $cell): ?>
                                <td><?php echo htmlspecialchars($cell); ?></td>
                              <?php endforeach; ?>
                            </tr>
                          <?php endfor; ?>
                        </tbody>
                      </table>
                    <?php endif; ?>
                  </div>
                  <p class="text-muted mt-2">
                    <small>This is a preview of the first few rows. The complete dataset contains <?php echo number_format($dataset['rows_count']); ?> rows.</small>
                  </p>
                </div>
              </div>
            <?php endif; ?>

          </div>

          <!-- Sidebar -->
          <div class="col-lg-4">
            
            <!-- Purchase Actions -->
            <div class="card mb-4" data-aos="fade-up" data-aos-delay="200">
              <div class="card-header">
                <h5><i class="bi bi-cart"></i> Purchase Options</h5>
              </div>
              <div class="card-body text-center">
                <div class="price-display mb-3">
                  <?php echo formatPrice($dataset['price']); ?>
                </div>
                
                <?php if ($user_has_access): ?>
                  <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> You own this dataset
                  </div>
                  <a href="download_dataset.php?id=<?php echo $dataset['id']; ?>" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-download"></i> Download Now
                  </a>
                <?php elseif (isLoggedIn()): ?>
                  <a href="add_to_cart.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                  </a>
                  <a href="cart.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-cart"></i> View Cart
                  </a>
                <?php else: ?>
                  <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="bi bi-person"></i> Login to Purchase
                  </a>
                  <a href="register.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-person-plus"></i> Create Account
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Dataset Stats -->
            <div class="dataset-meta" data-aos="fade-up" data-aos-delay="300">
              <h6><i class="bi bi-graph-up"></i> Dataset Statistics</h6>
              <hr>
              <div class="row text-center">
                <div class="col-6">
                  <h4 class="text-primary"><?php echo number_format($dataset['rows_count']); ?></h4>
                  <small>Rows</small>
                </div>
                <div class="col-6">
                  <h4 class="text-primary"><?php echo $dataset['columns_count']; ?></h4>
                  <small>Columns</small>
                </div>
              </div>
              <hr>
              <div class="row text-center">
                <div class="col-6">
                  <h4 class="text-success"><?php echo $dataset['download_count']; ?></h4>
                  <small>Downloads</small>
                </div>
                <div class="col-6">
                  <h4 class="text-info"><?php echo htmlspecialchars($dataset['file_size']); ?></h4>
                  <small>File Size</small>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </section>

  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>
