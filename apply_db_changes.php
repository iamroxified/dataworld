<?php
require('db/config.php');

$db_dir = 'db/';
$sql_files = glob($db_dir . '*.sql');
$selected_file = $_GET['file'] ?? null;
$message = '';

if ($selected_file) {
    // Basic validation to ensure the file is a .sql file in the db directory
    if (in_array($db_dir . $selected_file, $sql_files)) {
        try {
            $sql = file_get_contents($db_dir . $selected_file);
            if (empty(trim($sql))) {
                $message = "<div class='alert alert-warning'>SQL file '{$selected_file}' is empty. Nothing to execute.</div>";
            } else {
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Database changes from '{$selected_file}' applied successfully.</div>";
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $message = "<div class='alert alert-danger'>Error applying changes from '{$selected_file}': " . ($errorInfo[2] ?? 'Unknown error') . "</div>";
                }
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Database error while executing '{$selected_file}': " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Invalid file specified.</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Database Changes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .container {
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Apply Database Changes</h1>

        <?php echo $message; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Select an SQL file to apply</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($sql_files)): ?>
                    <div class="list-group-item">No SQL files found in the 'db' directory.</div>
                <?php else: ?>
                    <?php foreach ($sql_files as $file): ?>
                        <?php $file_name = basename($file); ?>
                        <a href="?file=<?php echo urlencode($file_name); ?>" class="list-group-item list-group-item-action">
                            <?php echo htmlspecialchars($file_name); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <p class="mt-3 text-muted"><strong>Note:</strong> Running these scripts can make irreversible changes to your database. Please be sure you want to run the script before clicking.</p>
    </div>
</body>
</html>
