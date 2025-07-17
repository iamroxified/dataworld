<?php
// Simulate a POST request with wrong credentials
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'john@test.com';
$_POST['password'] = 'wrongpassword';

echo "<h2>Testing Login with Wrong Password</h2>";
echo "<p>Email: " . $_POST['email'] . "</p>";
echo "<p>Password: " . $_POST['password'] . "</p>";
echo "<hr>";

// Include the login processing logic
include 'login.php';
?>
