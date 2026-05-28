<?php
/** delete_document.php — Handle document deletion (files and pending_files).
 * POST params: document_id (int), from (files|pending)
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/includes/csrf.php';

$user = auth_require_login();
// allow admin and captain to delete
if (!in_array($user['role'], ['admin', 'captain'], true)) {
    flash_set('error', 'You do not have permission to delete documents.');
    header('Location: search.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search.php');
    exit;
}

if (!csrf_validate()) {
    csrf_fail_redirect('search.php');
}

$id = (int) ($_POST['document_id'] ?? 0);
$from = ($_POST['from'] ?? 'files') === 'pending' ? 'pending' : 'files';

$repo = new DocumentRepository();

if ($from === 'pending') {
    // only delete pending via repository
    $ok = $repo->deletePending($id);
} else {
    $ok = $repo->deleteFile($id);
}

if ($ok) {
    flash_set('success', 'Document deleted.');
} else {
    flash_set('error', 'Could not delete document.');
}

// return to referring page if present
$ref = $_POST['return_to'] ?? 'search.php';
header('Location: ' . $ref);
exit;
