<?php
// Simulate a POST request to test login functionality
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'john@test.com';
$_POST['password'] = 'password123';

echo "<h2>Testing Login Submission</h2>";
echo "<p>Simulating form submission with:</p>";
echo "<p>Email: " . $_POST['email'] . "</p>";
echo "<p>Password: " . $_POST['password'] . "</p>";
echo "<hr>";

// Include the login processing logic
include 'login.php';
?>
