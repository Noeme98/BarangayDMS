<?php
/**
 * download.php — Secure document file download (authenticated).
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/document_file.php';

auth_require_login();

$id = (int) ($_GET['id'] ?? 0);
$from = ($_GET['from'] ?? 'files') === 'pending' ? 'pending' : 'files';

$resolved = document_resolve_file($id, $from);
if ($resolved === null) {
    http_response_code(404);
    exit('Document not found.');
}

header('Content-Type: ' . $resolved['mime']);
header('Content-Disposition: attachment; filename="' . rawurlencode($resolved['name']) . '"');
header('Content-Length: ' . (string) filesize($resolved['path']));
readfile($resolved['path']);
exit;
