<?php
declare(strict_types=1);

session_start();

$target = 'user/make_request.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . rawurlencode($target));
    exit;
}

header('Location: ' . $target);
exit;
