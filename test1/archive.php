<?php
/**
 * archive.php — Historical digitization upload and archive view by decade.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/document_file.php';

$user = auth_require_login();
$repo = new DocumentRepository();
$pending = $repo->pendingApprovalCount();

$tab = $_GET['tab'] ?? 'history';
$decadeStart = isset($_GET['decade']) ? (int) $_GET['decade'] : 0;
$histKeyword = trim($_GET['keyword'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        csrf_fail_redirect('archive.php?tab=digitize');
    }

    $title = trim($_POST['title'] ?? '');
    $reference = trim($_POST['reference_number'] ?? '');
    $docType = trim($_POST['document_type'] ?? '');
    $year = (int) ($_POST['document_year'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');

    if ($title === '' || $year < 1900 || $year > (int) date('Y')) {
        flash_set('error', 'Title and a valid document year are required.');
        header('Location: archive.php?tab=digitize');
        exit;
    }

    $stored = upload_store($_FILES['document'] ?? [], 'historical', true);
    if (!$stored['ok']) {
        flash_set('error', $stored['error']);
        header('Location: archive.php?tab=digitize');
        exit;
    }

    try {
        $id = $repo->create([
            'title' => $title,
            'reference_number' => $reference ?: ('HIST-' . $year . '-' . time()),
            'category' => 'historical',
            'description' => $subject,
            'document_type' => $docType !== '' ? $docType : 'Historical record',
            'date_filed' => date('Y-m-d'),
            'filed_by' => $user['full_name'],
            'status' => 'digitized',
            'file_path' => $stored['file_path'],
            'mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
            'is_digitized' => true,
            'document_year' => $year,
            'subject' => $subject,
        ]);

        if ($id) {
            flash_set('success', 'Historical record digitized and indexed for search.');
            header('Location: archive.php?tab=history');
            exit;
        }
    } catch (Throwable $e) {
        app_log_exception($e, 'archive digitize');
    }

        $detail = $repo->getLastError();
        flash_set('error', $detail
            ? 'Could not save digitized record: ' . $detail
            : 'Could not save digitized record to the database.');
    header('Location: archive.php?tab=digitize');
    exit;
}

try {
    $decades = $repo->decadeSummary();
    $decadeFilters = ['decade_start' => $decadeStart, 'is_digitized' => '1'];
    if ($histKeyword !== '') {
        $decadeFilters['keyword'] = $histKeyword;
    }
    $decadeDocs = $decadeStart > 0
        ? $repo->search($decadeFilters)
        : [];
    if ($histKeyword !== '' && $decadeStart === 0) {
        $decadeDocs = $repo->search(['is_digitized' => '1', 'keyword' => $histKeyword]);
    }
} catch (Throwable $e) {
    app_log_exception($e, 'archive.php');
    $decades = [];
    $decadeDocs = [];
}

$showHistResults = $tab === 'history' && ($histKeyword !== '' || $decadeStart > 0);
$activeNav = $tab === 'digitize' ? 'archive' : 'history';
layout_begin($tab === 'digitize' ? 'Digitize Records' : 'Historical Archive', $activeNav, $user, $pending);
?>
<div class="topbar">
    <h2><?= $tab === 'digitize' ? 'Digitize Records' : 'Historical Archive' ?></h2>
    <div class="topbar-tabs">
        <a class="filter-chip <?= $tab === 'history' ? 'active' : '' ?>" href="archive.php?tab=history">Archive</a>
        <a class="filter-chip <?= $tab === 'digitize' ? 'active' : '' ?>" href="archive.php?tab=digitize">Digitize</a>
    </div>
</div>
<div class="content">
    <?= flash_render() ?>

    <?php if ($tab === 'digitize'): ?>
        <form method="post" enctype="multipart/form-data" action="archive.php?tab=digitize">
            <?= csrf_field() ?>
            <div class="upload-zone">
                <i class="ti ti-scan"></i>
                <p>Scan or upload old paper records</p>
                <span style="font-size:11px;color:var(--color-text-tertiary);">PDF, JPG, PNG · Max 50 MB</span>
                <br><input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png" style="margin-top:12px;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label for="title">Record title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="reference_number">Reference number</label>
                    <input type="text" id="reference_number" name="reference_number">
                </div>
                <div class="form-group">
                    <label for="document_type">Document type</label>
                    <input type="text" id="document_type" name="document_type" placeholder="e.g. Birth register, Minutes">
                </div>
                <div class="form-group">
                    <label for="document_year">Year</label>
                    <input type="number" id="document_year" name="document_year" min="1900" max="<?= (int) date('Y') ?>" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label for="subject">Subject / notes</label>
                    <textarea id="subject" name="subject"></textarea>
                </div>
            </div>
            <button type="submit" class="btn-primary"><i class="ti ti-scan"></i> Save digitized record</button>
        </form>
    <?php elseif ($showHistResults): ?>
        <form class="search-advanced" method="get" action="archive.php">
            <input type="hidden" name="tab" value="history">
            <?php if ($decadeStart > 0): ?>
                <input type="hidden" name="decade" value="<?= (int) $decadeStart ?>">
            <?php endif; ?>
            <div class="row">
                <input type="search" name="keyword" placeholder="Search title, reference, year, type…"
                       value="<?= e($histKeyword) ?>" style="flex:1;min-width:200px;">
                <button type="submit" class="btn-primary">Search archive</button>
                <a class="btn-upload" href="archive.php?tab=history">Clear</a>
            </div>
        </form>
        <p style="margin-bottom:12px;">
            <?php if ($decadeStart > 0): ?>
            <a href="archive.php?tab=history">← Back to decades</a>
            · <?= e($decadeStart) ?>s
            <?php else: ?>
            <a href="archive.php?tab=history">← Back to decades</a>
            · All decades
            <?php endif; ?>
            (<?= count($decadeDocs) ?> records)
        </p>
        <?php if ($decadeDocs === []): ?>
            <div class="empty-state">No digitized records match your search. Try another keyword (title, year, subject) or use the Digitize tab to add records.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="doc-table">
                <thead><tr><th>Document</th><th>Year</th><th>Type</th><th>Subject</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($decadeDocs as $doc): ?>
                    <tr>
                        <td>
                            <div class="doc-name"><?= e($doc['title'] ?? '') ?></div>
                            <div class="doc-sub"><?= e($doc['reference_number'] ?? '') ?></div>
                        </td>
                        <td><?= (int) ($doc['document_year'] ?? 0) ?></td>
                        <td><?= e($doc['document_type'] ?? '—') ?></td>
                        <td><?= e($doc['subject'] ?? '') ?></td>
                        <td>
                            <a href="<?= e(document_preview_url((int) $doc['id'], 'files', 'archive.php?tab=history' . ($decadeStart > 0 ? '&decade=' . $decadeStart : '') . ($histKeyword !== '' ? '&keyword=' . urlencode($histKeyword) : ''))) ?>">View</a>
                            · <a href="download.php?id=<?= (int) $doc['id'] ?>">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form class="search-advanced" method="get" action="archive.php">
            <input type="hidden" name="tab" value="history">
            <div class="row">
                <input type="search" name="keyword" placeholder="Search by title, year (e.g. 1985), type, subject…"
                       value="<?= e($histKeyword) ?>" style="flex:1;min-width:200px;">
                <button type="submit" class="btn-primary">Search archive</button>
            </div>
        </form>
        <p class="content-lead">
            Browse by decade below, or search digitized records by title, year, reference, type, or subject.
        </p>
        <?php if ($decades === []): ?>
            <div class="empty-state">No digitized historical records yet. Use the Digitize tab to upload scans.</div>
        <?php else: ?>
            <div class="history-grid">
                <?php foreach ($decades as $d): ?>
                    <?php $pct = $d['total_in_range'] > 0 ? round(100 * $d['count'] / $d['total_in_range']) : 0; ?>
                    <a class="hist-card" href="archive.php?tab=history&decade=<?= (int) $d['start'] ?>">
                        <div class="year"><?= e($d['decade']) ?></div>
                        <div class="era"><?= e($d['era']) ?></div>
                        <div class="count"><?= (int) $d['count'] ?> records digitized</div>
                        <div class="bar"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
