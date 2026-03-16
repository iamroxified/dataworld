<?php
require_once 'db/config.php';

header('Content-Type: application/json');


$country_id = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 0;

if ($country_id > 0) {
    $stmt = $pdo->prepare("SELECT id, name FROM states WHERE country_id = ?");
    $stmt->execute([$country_id]);
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($states);
} else {
    echo json_encode([]);
}
?>