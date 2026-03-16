<?php
session_start();
require('../db/config.php');
require('../db/functions.php');

echo "<h2>Simple Search Test</h2>";

// Test basic search
if (isset($_GET['search'])) {
    $search_term = $_GET['search'];
    echo "<h3>Searching for: " . htmlspecialchars($search_term) . "</h3>";
    
    try {
        // Test raw SQL query
        $search_pattern = '%' . $search_term . '%';
        $stmt = QueryDB("SELECT username, fname, lname, email FROM users WHERE username LIKE ? OR fname LIKE ? OR lname LIKE ? LIMIT 10", 
                       [$search_pattern, $search_pattern, $search_pattern]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Raw SQL Results:</h4>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
        
        // Test function
        $function_results = search_users_by_username($search_term, 10);
        echo "<h4>Function Results:</h4>";
        echo "<pre>";
        print_r($function_results);
        echo "</pre>";
        
        // Test API simulation
        $current_user = $_SESSION['username'] ?? 'not_logged_in';
        $filtered_users = [];
        foreach ($function_results as $user) {
            if ($user['username'] !== $current_user) {
                $filtered_users[] = [
                    'username' => $user['username'],
                    'name' => trim($user['fname'] . ' ' . $user['lname']),
                    'display' => $user['username'] . ' (' . trim($user['fname'] . ' ' . $user['lname']) . ')'
                ];
            }
        }
        
        echo "<h4>API Simulation Results:</h4>";
        echo "<pre>";
        print_r($filtered_users);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

?>
<form method="GET">
    <input type="text" name="search" placeholder="Enter username to search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    <button type="submit">Search</button>
</form>

<br><a href="debug_search.php">Full Debug Test</a> | <a href="add_funds.php">Back to Add Funds</a>
