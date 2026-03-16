<?php
error_reporting(E_ALL);

ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));

// Fetch binding prices
$stmt = $pdo->prepare("SELECT item_name, price FROM prices WHERE service_name = 'binding'");
$stmt->execute();
$binding_prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$departments = [
    "Computer Science",
    "Mathematics",
    "Physics",
    "Chemistry",
    "Biology",
    "Accounting",
    "Agricultural Economics",
    "Agricultural Engineering",
    "Anatomy",
    "Animal Science",
    "Architecture",
    "Banking and Finance",
    "Biochemistry",
    "Botany",
    "Building",
    "Business Administration",
    "Chemical Engineering",
    "Civil Engineering",
    "Computer Engineering",
    "Economics",
    "Education and Biology",
    "Education and Chemistry",
    "Education and Physics",
    "Electrical Engineering",
    "English Language",
    "Estate Management",
    "Fisheries and Aquaculture",
    "Food Science and Technology",
    "Forestry and Wildlife Management",
    "French",
    "Geography",
    "Geology",
    "History and International Studies",
    "Industrial Chemistry",
    "Industrial Engineering",
    "Insurance",
    "Law",
    "Library and Information Science",
    "Linguistics",
    "Marketing",
    "Mass Communication",
    "Mechanical Engineering",
    "Mechatronics Engineering",
    "Medicine and Surgery",
    "Metallurgical and Materials Engineering",
    "Microbiology",
    "Music",
    "Nursing",
    "Pharmacy",
    "Philosophy",
    "Physiology",
    "Political Science",
    "Psychology",
    "Quantity Surveying",
    "Radiography",
    "Religious Studies",
    "Sociology",
    "Soil Science",
    "Statistics",
    "Surveying and Geoinformatics",
    "Telecommunication Science",
    "Theatre Arts",
    "Urban and Regional Planning",
    "Veterinary Medicine",
    "Zoology"
];

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // print("<script>alert('POST request received.');</script>");
    error_log("add_bind_request.php: POST request received.");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    if (!isset($_FILES['cover_page']) || $_FILES['cover_page']['error'] == UPLOAD_ERR_NO_FILE) {
 error_log("Validation failed: No file uploaded.");
        $_SESSION['message'] = 'Please select a cover page file to upload.';
        $_SESSION['message_type'] = 'danger';
        header('Location: add_bind_request.php');
        exit;
    }

    if ($_FILES['cover_page']['error'] != UPLOAD_ERR_OK) {
 error_log("Validation failed: File upload error code: " . $_FILES['cover_page']['error']);
        $_SESSION['message'] = 'An error occurred during file upload. Please try again.';
        $_SESSION['message_type'] = 'danger';
        header('Location: add_bind_request.php');
        exit;
    }

    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $color = $_POST['color'];
    $programe = $_POST['programe'];
    $pages = $_POST['pages'];

    $target_dir = "../uploads/binding_covers/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["cover_page"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (file_exists($target_file)) {
 error_log("Validation failed: File already exists at " . $target_file);
        $_SESSION['message'] = "Sorry, a file with that name already exists.";
        $_SESSION['message_type'] = "danger";
        $uploadOk = 0;
    }

    if ($uploadOk && $_FILES["cover_page"]["size"] > 5000000) {
        error_log("Validation failed: File size too large.");
 $_SESSION['message'] = "Sorry, your file is too large (max 5MB).";
        $_SESSION['message_type'] = "danger";
        $uploadOk = 0;
    }

    $allowed_extensions = ["pdf", "jpg", "png", "jpeg"];
    if ($uploadOk && !in_array($imageFileType, $allowed_extensions)) {
        error_log("Validation failed: Invalid file type.");
 $_SESSION['message'] = "Sorry, only PDF, JPG, PNG & JPEG files are allowed.";
        $_SESSION['message_type'] = "danger";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        header('Location: add_bind_request.php');
        exit;
    }
    
    if (move_uploaded_file($_FILES["cover_page"]["tmp_name"], $target_file)) {
        error_log("File moved successfully to: " . $target_file);
 $cover_page_path = $target_file;

        try {
            $copies = (int)$_POST['copies'];
            $price_per_copy = $binding_prices[$programe] ?? 0;
            $payment_amount = $price_per_copy * $copies;
            error_log("Payment amount calculated: " . $payment_amount);

            $order_number = generateOrderNumber();
            error_log("Generated order number: " . $order_number);

            $order_sql = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status) VALUES (?, ?, ?, 'pending', 'paystack', 'pending')";
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([$user_id, $order_number, $payment_amount]);
            $order_id = $pdo->lastInsertId();
            error_log("Order created with ID: " . $order_id);

            if ($order_id) {
                $sql = "INSERT INTO binding_requests (user_id, order_id, full_name, department, color, cover_page_path, programe, pages, copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$user_id, $order_id, $full_name, $department, $color, $cover_page_path, $programe, $pages, $copies])) {
                    error_log("Binding request created successfully for order ID: " . $order_id . ". Redirecting to payment.");
                    header("Location: initialize_payment.php?order_id=" . $order_id);
                    exit;
                } else {
                    error_log("Failed to insert binding_request. SQL Error: " . print_r($stmt->errorInfo(), true));
                    $_SESSION['message'] = "Error: Could not save the binding request.";
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                error_log("Failed to create order. lastInsertId() returned no ID.");
                $_SESSION['message'] = "Error: Could not create an order.";
                $_SESSION['message_type'] = 'danger';
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $_SESSION['message'] = "A database error occurred. Please try again.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        error_log("Failed to move uploaded file.");
        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
        $_SESSION['message_type'] = 'danger';
    }
    
    error_log("Redirecting back to add_bind_request.php at the end of the script.");
    header('Location: add_bind_request.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Add Binding Request - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('nav/sidebar.php'); ?>
        <!-- End Sidebar -->

        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Add Binding Request</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="binding_request.php">Binding Requests</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Add Binding Request</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">New Binding Request</h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($message): ?>
                                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                                    <?php endif; ?>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="full_name">Full Name</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="department">Department</label>
                                            <select class="form-control" id="department" name="department" required>
                                                <?php foreach ($departments as $department): ?>
                                                    <option value="<?php echo $department; ?>"><?php echo $department; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="programe">Programe</label>
                                            <select class="form-control" id="programe" name="programe" required>
                                                <?php foreach ($binding_prices as $program => $price): ?>
                                                    <option value="<?php echo $program; ?>"><?php echo $program; ?> - ₦<?php echo number_format($price, 2); ?> per copy</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="pages">Number of Pages</label>
                                            <input type="number" class="form-control" id="pages" name="pages" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="copies">Number of Copies</label>
                                            <input type="number" class="form-control" id="copies" name="copies" value="1" min="1" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="color">Color</label>
                                            <input type="color" class="form-control" id="color" name="color" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="cover_page">Cover Page (PDF, DOC, DOCX)</label>
                                            <input type="file" class="form-control-file" id="cover_page" name="cover_page" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Request</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
</body>

</html>
