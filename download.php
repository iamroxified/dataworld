<?php
declare(strict_types=1);

require_once __DIR__ . '/db/config.php';
require_once __DIR__ . '/db/functions.php';

$downloadName = trim((string) ($_GET['file'] ?? $_GET['download'] ?? ''));

if ($downloadName === '') {
    http_response_code(404);
    exit('Download not found.');
}

$statement = $pdo->prepare('SELECT * FROM student_jobs WHERE download_name = ? LIMIT 1');
$statement->execute([$downloadName]);
$job = $statement->fetch();

if (!$job || !in_array((string) $job['status'], ['ready', 'reviewed'], true)) {
    http_response_code(404);
    exit('Download not found.');
}

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$isOwner = $currentUser && (int) $currentUser['id'] === (int) $job['user_id'];
$isAdmin = $currentUser && in_array((string) ($currentUser['role'] ?? ''), ['admin', 'operator'], true);
$expiresAt = !empty($job['download_expires_at']) ? strtotime((string) $job['download_expires_at']) : false;
$publicAccessStillValid = $expiresAt === false || $expiresAt >= time();

// PDF workflow step 7: the document is served through a secure route instead of direct file access.
if (!$isOwner && !$isAdmin && !$publicAccessStillValid) {
    http_response_code(403);
    exit('This secure download link has expired.');
}

$absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $job['generated_file_path']);
if (!is_file($absolutePath)) {
    http_response_code(404);
    exit('Generated file not found.');
}

$pdo->prepare('UPDATE student_jobs SET download_count = download_count + 1, last_downloaded_at = NOW() WHERE id = ?')
    ->execute([(int) $job['id']]);

$extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
$mimeType = match ($extension) {
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename((string) $job['download_name']) . '"');
header('Content-Length: ' . filesize($absolutePath));
header('Cache-Control: private, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($absolutePath);
exit;
