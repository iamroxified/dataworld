<?php
require_once 'db/config.php';

header('Content-Type: application/json');


$stmt = $pdo->prepare("SELECT id, state FROM state");
$stmt->execute();
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$states) {
    echo json_encode([]);
    exit;
}


echo json_encode($states);
?>