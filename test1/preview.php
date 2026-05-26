<?php
/**
 * preview.php — Full-page document preview before approval or download.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/document_file.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_require_login();

$id = (int) ($_GET['id'] ?? 0);
$from = ($_GET['from'] ?? 'files') === 'pending' ? 'pending' : 'files';
$back = trim($_GET['back'] ?? '');
if ($back === '' || str_contains($back, '://') || str_starts_with($back, '/')) {
    $back = auth_can_approve($user) ? 'approve.php' : 'search.php';
}

$resolved = document_resolve_file($id, $from);
if ($resolved === null) {
    flash_set('error', 'Document not found.');
    header('Location: ' . $back);
    exit;
}

$doc = $resolved['doc'];
$mime = $resolved['mime'];
$canView = document_is_viewable($mime);
$viewUrl = document_view_url($id, $from);
$downloadUrl = 'download.php?id=' . $id . '&from=' . urlencode($from);

$pending = 0;
try {
    require_once __DIR__ . '/includes/repository.php';
    $pending = (new DocumentRepository())->pendingApprovalCount();
} catch (Throwable $e) {
    // ignore
}

layout_begin('Preview Document', 'approve', $user, $pending);
layout_topbar('Preview Document');
?>
<div class="content">
    <p style="margin-bottom:12px;">
        <a href="<?= e($back) ?>">← Back</a>
    </p>

    <div class="preview-meta panel-box">
        <h3><?= e($doc['title'] ?? 'Document') ?></h3>
        <p class="content-lead" style="margin-bottom:0;">
            Ref: <?= e($doc['reference_number'] ?? '—') ?> ·
            Filed by <?= e($doc['filed_by'] ?? '—') ?> ·
            <?= format_date($doc['date_filed'] ?? null) ?> ·
            <?= e(DOCUMENT_STATUSES[$doc['status'] ?? ''] ?? $doc['status'] ?? '') ?>
        </p>
        <div class="preview-actions">
            <?php if ($canView): ?>
            <a class="btn-primary" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener">
                <i class="ti ti-external-link"></i> Open in new tab
            </a>
            <?php endif; ?>
            <a class="btn-upload" href="<?= e($downloadUrl) ?>">
                <i class="ti ti-download"></i> Download
            </a>
            <?php if (auth_can_approve($user) && $back === 'approve.php'): ?>
            <a class="btn-upload" href="approve.php">Return to approval queue</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canView): ?>
        <div class="preview-frame panel-box">
            <?php if ($mime === 'application/pdf'): ?>
                <iframe class="doc-preview-iframe" src="<?= e($viewUrl) ?>" title="Document preview"></iframe>
            <?php else: ?>
                <img class="doc-preview-image" src="<?= e($viewUrl) ?>" alt="<?= e($doc['title'] ?? 'Document preview') ?>">
            <?php endif; ?>
        </div>
        <p class="content-lead" style="margin-top:10px;">
            Review the document above before approving or returning it.
        </p>
    <?php else: ?>
        <div class="empty-state">
            Preview is not available for this file type. Use Download to open it on your device.
        </div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
