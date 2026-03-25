<?php
declare(strict_types=1);

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$location = 'admin/generate.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $location);
exit;
