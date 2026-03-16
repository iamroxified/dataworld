<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h1>POST request successfully received!</h1>";
    echo "<p>This confirms the server is processing POST requests correctly.</p>";
    echo "<h3>Data Received:</h3>";
    echo "<pre style='background: #eee; padding: 10px; border: 1px solid #ccc;'>";
    print_r($_POST);
    echo "</pre>";
    echo "<a href='test_post.php'>Go back</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POST Test</title>
    <style>
        body { font-family: sans-serif; padding: 2em; }
        input { padding: 5px; margin-bottom: 10px; }
        button { padding: 10px 15px; }
    </style>
</head>
<body>
    <h1>POST Test Form</h1>
    <p>This form will submit data to itself to check if POST requests are working.</p>
    <form action="test_post.php" method="POST">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" value="Test Name"><br><br>
        <label for="data">Some Data:</label><br>
        <input type="text" id="data" name="data" value="Some test data"><br><br>
        <button type="submit">Submit POST Request</button>
    </form>
</body>
</html>
