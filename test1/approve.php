<?php
/**
 * approve.php — Approval queue using pending_files + files (existing Supabase schema).
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_require_login();
if (!auth_can_approve($user)) {
    flash_set('error', 'Only the Kapitan can use the approval queue.');
    header('Location: search.php');
    exit;
}
$repo = new DocumentRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        csrf_fail_redirect('approve.php');
    }

    $docId = (int) ($_POST['document_id'] ?? 0);
    $queueType = $_POST['queue_type'] ?? 'files';
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if ($queueType === 'pending') {
        if ($action === 'approve') {
            $ok = $repo->approvePending($docId, $user['full_name'], $remarks ?: null, true);
            flash_set($ok ? 'success' : 'error', $ok ? 'Pending document approved and moved to files.' : 'Could not approve document.');
        } elseif ($action === 'return') {
            $ok = $repo->approvePending($docId, $user['full_name'], $remarks ?: null, false);
            flash_set($ok ? 'success' : 'error', $ok ? 'Document returned and removed from queue.' : 'Could not return document.');
        } else {
            flash_set('error', 'Invalid action.');
        }
    } else {
        $doc = $repo->findById($docId, 'files');
        if ($doc === null) {
            flash_set('error', 'Document not found.');
        } else {
            $newStatus = match ($action) {
                'approve' => 'approved',
                'return' => 'returned',
                'review' => 'in_review',
                default => '',
            };
            if ($newStatus === '') {
                flash_set('error', 'Invalid action.');
            } elseif ($repo->updateStatus($docId, $newStatus, $user['full_name'], $action, $remarks ?: null, $doc['status'])) {
                flash_set('success', 'Document updated to ' . (DOCUMENT_STATUSES[$newStatus] ?? $newStatus) . '.');
            } else {
                flash_set('error', 'Could not update document.');
            }
        }
    }

    header('Location: approve.php');
    exit;
}

try {
    $queue = $repo->queueItems();
    $pending = $repo->pendingApprovalCount();
} catch (Throwable $e) {
    app_log_exception($e, 'approve.php');
    $queue = [];
    $pending = 0;
    flash_set('error', 'Could not load approval queue.');
}

layout_begin('Approval Queue', 'approve', $user, $pending);
layout_topbar('Approval Queue');
?>
<div class="content">
    <?= flash_render() ?>
    <p class="content-lead">
        <?= count($queue) ?> item(s) awaiting Kapitan review. Open <strong>View document</strong> on each item before approving or returning.
    </p>

    <?php if ($queue === []): ?>
        <div class="empty-state">No documents pending approval.</div>
    <?php else: ?>
        <?php foreach ($queue as $doc): ?>
            <?php render_approval_card($doc); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
