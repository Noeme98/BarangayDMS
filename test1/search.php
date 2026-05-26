<?php
/**
 * search.php — All documents (design: panel-all + filter chips + doc table).
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_require_role(auth_document_roles());

$filters = [
    'category' => trim($_GET['category'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'keyword' => trim($_GET['keyword'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
];

try {
    $repo = new DocumentRepository();
    $documents = $repo->search($filters);
    $total = $repo->totalCount();
    $digitized = $repo->digitizedCount();
    $pending = $repo->pendingApprovalCount();
} catch (Throwable $e) {
    app_log_exception($e, 'search.php');
    flash_set('error', 'Could not load documents.');
    $documents = [];
    $total = $digitized = $pending = 0;
}

$filterChips = [
    '' => 'All',
    'ordinance' => 'Ordinances',
    'permit' => 'Permits',
    'report' => 'Reports',
    'resolution' => 'Resolutions',
    'certificate' => 'Certificates',
];

$activeNav = layout_active_nav_from_filters($filters);
$pageTitle = match ($activeNav) {
    'ordinance' => 'Ordinances',
    'permit' => 'Permits',
    'report' => 'Reports',
    default => 'All Documents',
};

layout_begin($pageTitle, $activeNav, $user, $pending);
$uploadHref = auth_can_upload($user) || auth_can_submit_for_approval($user) ? 'upload.php' : null;
layout_topbar($pageTitle, [
    'search' => true,
    'keyword' => $filters['keyword'],
    'upload_href' => $uploadHref,
]);
layout_stats($total, $digitized, $pending);
?>
<div class="content">
    <?= flash_render() ?>

    <div class="search-advanced">
        <form method="get" action="search.php" class="row">
            <?php if ($filters['keyword'] !== ''): ?>
                <input type="hidden" name="keyword" value="<?= e($filters['keyword']) ?>">
            <?php endif; ?>
            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                <?php foreach (DOCUMENT_STATUSES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>" title="From">
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>" title="To">
            <button type="submit" class="btn-primary">Apply filters</button>
            <a href="search.php" class="btn-upload">Clear</a>
        </form>
    </div>

    <div class="filter-row">
        <?php foreach ($filterChips as $key => $label): ?>
            <?php
            $href = $key === '' ? 'search.php' : 'search.php?category=' . urlencode($key);
            if ($filters['keyword'] !== '') {
                $href .= (str_contains($href, '?') ? '&' : '?') . 'keyword=' . urlencode($filters['keyword']);
            }
            ?>
            <a class="filter-chip <?= $filters['category'] === $key ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($documents === []): ?>
        <div class="empty-state">No documents match your filters.</div>
    <?php else: ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Category</th>
                    <th>Date Filed</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <?php render_document_row($doc); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
