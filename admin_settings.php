<?php
declare(strict_types=1);

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$location = 'admin/admin_settings.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $location);
exit;
