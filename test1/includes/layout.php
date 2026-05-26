<?php
/**
 * Shared layout — BarangayDMS branding
 */

const APP_LOGO = 'assets/images/barangaydms-logo.png';
const APP_NAME = 'BarangayDMS';

/** Reusable logo + wordmark */
function layout_brand(string $tagline = 'Document Management System', bool $largeLogo = false): void
{
    $logoClass = $largeLogo ? 'brand-logo brand-logo--lg' : 'brand-logo';
    ?>
    <a class="brand" href="search.php" title="<?= e(APP_NAME) ?>">
        <img src="<?= e(APP_LOGO) ?>" alt="<?= e(APP_NAME) ?> logo" class="<?= $logoClass ?>" width="40" height="44">
        <div class="brand-text">
            <span class="brand-name"><?= e(APP_NAME) ?></span>
            <?php if ($tagline !== ''): ?>
            <p class="brand-tagline"><?= $tagline ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php
}

function layout_begin(string $title, string $activeNav, array $user, int $pendingBadge = 0): void
{
    $pendingBadge = max(0, $pendingBadge);
    $isAdmin = $user['role'] === 'admin';
    $canUpload = auth_can_upload($user);
    $canSubmit = auth_can_submit_for_approval($user);
    $canApprove = auth_can_approve($user);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(APP_LOGO) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-shell">
<h2 class="sr-only">BarangayDMS document management system</h2>
<div class="page-wrap">
<div class="app">
    <aside class="sidebar">
        <div class="sidebar-header">
            <?php layout_brand('Records &amp; Archives'); ?>
        </div>
        <div class="sidebar-user">
            <strong><?= e($user['full_name']) ?></strong>
            <?= e($isAdmin ? 'System Administrator' : ucfirst($user['role'])) ?>
        </div>

        <?php if ($isAdmin): ?>
        <div class="sidebar-section">Administration</div>
        <a class="nav-item <?= $activeNav === 'accounts' ? 'active' : '' ?>" href="accounts.php">
            <i class="ti ti-users" aria-hidden="true"></i> Manage Accounts
        </a>
        <?php endif; ?>

        <div class="sidebar-section">Filing</div>
        <a class="nav-item <?= $activeNav === 'search' ? 'active' : '' ?>" href="search.php">
            <i class="ti ti-files" aria-hidden="true"></i> All Documents
            <?php if ($activeNav !== 'search' && $pendingBadge > 0 && $canApprove): ?>
            <span class="badge"><?= (int) $pendingBadge ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item <?= $activeNav === 'ordinance' ? 'active' : '' ?>" href="search.php?category=ordinance">
            <i class="ti ti-scale" aria-hidden="true"></i> Ordinances
        </a>
        <a class="nav-item <?= $activeNav === 'permit' ? 'active' : '' ?>" href="search.php?category=permit">
            <i class="ti ti-id-badge" aria-hidden="true"></i> Permits
        </a>
        <a class="nav-item <?= $activeNav === 'report' ? 'active' : '' ?>" href="search.php?category=report">
            <i class="ti ti-report" aria-hidden="true"></i> Reports
        </a>
        <?php if ($canUpload): ?>
        <a class="nav-item <?= $activeNav === 'upload' ? 'active' : '' ?>" href="upload.php">
            <i class="ti ti-upload" aria-hidden="true"></i> Upload Document
        </a>
        <?php elseif ($canSubmit): ?>
        <a class="nav-item <?= $activeNav === 'upload' ? 'active' : '' ?>" href="upload.php">
            <i class="ti ti-send" aria-hidden="true"></i> Submit for Approval
        </a>
        <?php endif; ?>

        <?php if ($canApprove): ?>
        <div class="sidebar-section">Workflow</div>
        <a class="nav-item <?= $activeNav === 'approve' ? 'active' : '' ?>" href="approve.php">
            <i class="ti ti-checks" aria-hidden="true"></i> Approval Queue
            <?php if ($pendingBadge > 0): ?><span class="badge"><?= (int) $pendingBadge ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <div class="sidebar-section">Archive</div>
        <a class="nav-item <?= $activeNav === 'archive' ? 'active' : '' ?>" href="archive.php?tab=digitize">
            <i class="ti ti-scan" aria-hidden="true"></i> Digitize Records
        </a>
        <a class="nav-item <?= $activeNav === 'history' ? 'active' : '' ?>" href="archive.php?tab=history">
            <i class="ti ti-history" aria-hidden="true"></i> Historical Archive
        </a>

        <div class="sidebar-section">Account</div>
        <a class="nav-item" href="logout.php">
            <i class="ti ti-logout" aria-hidden="true"></i> Sign out
        </a>
    </aside>
    <div class="main">
<?php
}

function layout_end(): void
{
    ?>
        </div>
</div>
</div>
<script>
    (function () {
        var mobileToggle = document.querySelector('.mobile-menu-toggle');
        var collapseToggle = document.querySelector('.sidebar-collapse-toggle');

        function openSidebar() { document.body.classList.add('sidebar-open'); }
        function closeSidebar() { document.body.classList.remove('sidebar-open'); }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function () {
                if (document.body.classList.contains('sidebar-open')) closeSidebar(); else openSidebar();
            });
        }

        // backdrop click closes mobile sidebar
        document.addEventListener('click', function (ev) {
            if (!document.body.classList.contains('sidebar-open')) return;
            var sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;
            var target = ev.target;
            if (!sidebar.contains(target) && !target.closest('.mobile-menu-toggle')) closeSidebar();
        });

        // Escape closes mobile sidebar
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closeSidebar(); });

        // Sidebar collapse toggle (desktop)
        function setCollapsedState(collapsed) {
            if (collapsed) document.body.classList.add('sidebar-collapsed'); else document.body.classList.remove('sidebar-collapsed');
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) { /* ignore */ }
        }

        // initialize from storage
        try {
            var saved = localStorage.getItem('sidebarCollapsed');
            if (saved === '1') setCollapsedState(true);
        } catch (e) { /* ignore */ }

        if (collapseToggle) {
            collapseToggle.addEventListener('click', function () {
                var isCollapsed = document.body.classList.contains('sidebar-collapsed');
                setCollapsedState(!isCollapsed);
            });
        }
    })();
</script>
</body>
</html>
<?php
}

/** Top bar matching design mockup */
function layout_topbar(string $pageTitle, array $options = []): void
{
    $showSearch = $options['search'] ?? false;
    $searchAction = $options['search_action'] ?? 'search.php';
    $keyword = $options['keyword'] ?? '';
    $uploadHref = $options['upload_href'] ?? null;
    ?>
    <div class="topbar">
        <button class="mobile-menu-toggle" aria-label="Open menu"><i class="ti ti-menu" aria-hidden="true"></i></button>
        <button class="sidebar-collapse-toggle" aria-label="Collapse sidebar" title="Collapse sidebar"><i class="ti ti-chevrons-left" aria-hidden="true"></i></button>
        <h2><?= e($pageTitle) ?></h2>
        <?php if ($showSearch): ?>
        <form class="search-box" method="get" action="<?= e($searchAction) ?>" role="search">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input type="search" name="keyword" placeholder="Search documents…"
                   value="<?= e($keyword) ?>" aria-label="Search documents">
        </form>
        <?php endif; ?>
        <?php if ($uploadHref): ?>
        <a class="btn-upload" href="<?= e($uploadHref) ?>">
            <i class="ti ti-upload" aria-hidden="true"></i> Upload
        </a>
        <?php endif; ?>
    </div>
    <?php
}

function layout_stats(int $total, int $digitized, int $pending, string $storageLabel = '—'): void
{
    ?>
    <div class="stats-bar">
        <div class="stat-card">
            <div class="label">Total Documents</div>
            <div class="value"><?= (int) $total ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Digitized Records</div>
            <div class="value"><?= (int) $digitized ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Pending Approval</div>
            <div class="value"><?= (int) $pending ?></div>
            <?php if ($pending > 0): ?>
            <div class="sub" style="color: var(--color-text-warning);">Needs review</div>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="label">Storage Used</div>
            <div class="value"><?= e($storageLabel) ?></div>
        </div>
    </div>
    <?php
}

function render_document_row(array $doc): void
{
    require_once __DIR__ . '/document_file.php';

    $cat = (string) ($doc['category'] ?? $doc['file_category'] ?? 'report');
    $status = (string) ($doc['status'] ?? 'pending');
    $iconClass = category_icon($cat);
    $bg = category_icon_bg($cat);
    $fg = category_icon_color($cat);
    $catLabel = DOCUMENT_CATEGORIES[$cat] ?? ucfirst($cat);
    if ($cat === 'historical') {
        $catLabel = 'Historical';
    }
    ?>
    <tr>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="doc-icon" style="background:<?= $bg ?>;">
                    <i class="ti <?= e($iconClass) ?>" style="color:<?= $fg ?>;" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="doc-name"><?= e($doc['title'] ?? '') ?></div>
                    <div class="doc-sub"><?= e($doc['reference_number'] ?? $doc['subject'] ?? '') ?></div>
                </div>
            </div>
        </td>
        <td style="color:var(--color-text-secondary);"><?= e($catLabel) ?></td>
        <td style="color:var(--color-text-secondary);"><?= format_date($doc['date_filed'] ?? null) ?></td>
        <td>
            <span class="status-badge <?= status_badge_class($status) ?>">
                <i class="ti <?= e(status_icon($status)) ?>" aria-hidden="true"></i>
                <?= e(DOCUMENT_STATUSES[$status] ?? $status) ?>
            </span>
        </td>
        <td>
            <div class="doc-actions">
                <?php
                $docId = (int) ($doc['id'] ?? 0);
                $docFrom = ($doc['source'] ?? 'files') === 'pending' ? 'pending' : 'files';
                ?>
                <a class="action-btn" href="<?= e(document_preview_url($docId, $docFrom)) ?>"
                   title="View" aria-label="View document">
                    <i class="ti ti-eye"></i>
                </a>
                <a class="action-btn" href="download.php?id=<?= $docId ?>&amp;from=<?= e($docFrom) ?>"
                   title="Download" aria-label="Download">
                    <i class="ti ti-download"></i>
                </a>
            </div>
        </td>
    </tr>
    <?php
}

function render_approval_card(array $doc): void
{
    require_once __DIR__ . '/document_file.php';

    $cat = (string) ($doc['category'] ?? $doc['file_category'] ?? 'report');
    $qType = $doc['queue_type'] ?? 'files';
    $docId = (int) ($doc['id'] ?? 0);
    $docFrom = $qType === 'pending' ? 'pending' : 'files';
    $previewUrl = document_preview_url($docId, $docFrom, 'approve.php');
    ?>
    <div class="approval-card">
        <div class="doc-icon" style="background:<?= category_icon_bg($cat) ?>;">
            <i class="ti <?= e(category_icon($cat)) ?>" style="color:<?= category_icon_color($cat) ?>;" aria-hidden="true"></i>
        </div>
        <div class="info">
            <div class="doc-title"><?= e($doc['title'] ?? '') ?></div>
            <div class="doc-meta">
                Ref: <?= e($doc['reference_number'] ?? '—') ?> ·
                Filed by <?= e($doc['filed_by'] ?? '') ?> ·
                <?= format_date($doc['date_filed'] ?? null) ?>
            </div>
            <div class="doc-meta">
                Source: <?= e(($doc['queue_type'] ?? 'files') === 'pending' ? 'pending_files' : 'files') ?> ·
                <?= e(DOCUMENT_STATUSES[$doc['status'] ?? ''] ?? $doc['status'] ?? '') ?>
            </div>
        </div>
        <div class="approval-actions">
            <a class="btn-view" href="<?= e($previewUrl) ?>">
                <i class="ti ti-eye"></i> View document
            </a>
            <form method="post" class="approval-form">
                <?= csrf_field() ?>
                <input type="hidden" name="document_id" value="<?= $docId ?>">
                <input type="hidden" name="queue_type" value="<?= e($qType) ?>">
                <input type="text" name="remarks" placeholder="Remarks (optional)" class="remarks-input">
                <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
                <button type="submit" name="action" value="return" class="btn-reject">Return</button>
            </form>
        </div>
    </div>
    <?php
}

/** Map search category filter to sidebar active state */
function layout_active_nav_from_filters(array $filters): string
{
    $cat = $filters['category'] ?? '';
    if (in_array($cat, ['ordinance', 'permit', 'report'], true)) {
        return $cat;
    }

    return 'search';
}
