<?php
/**
 * view.php — Inline document stream for browser preview (PDF/images).
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

$mime = $resolved['mime'];
if (!document_is_viewable($mime)) {
    http_response_code(415);
    exit('Preview not available for this file type.');
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . rawurlencode($resolved['name']) . '"');
header('Content-Length: ' . (string) filesize($resolved['path']));
header('X-Content-Type-Options: nosniff');
readfile($resolved['path']);
exit;
