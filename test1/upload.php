<?php
/**
 * upload.php — Upload new barangay documents (PDF, JPG, PNG — max 50MB).
 * Captain/Admin file directly; members submit to pending_files for Kapitan approval.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_require_login();
if (!auth_can_use_upload_page($user)) {
    flash_set('error', 'You do not have permission to upload documents.');
    header('Location: search.php');
    exit;
}

$isMemberSubmit = auth_can_submit_for_approval($user);

$repo = new DocumentRepository();
$pending = $repo->pendingApprovalCount();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        csrf_fail_redirect('upload.php');
    }

    $title = trim($_POST['title'] ?? '');
    $reference = trim($_POST['reference_number'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dateFiled = trim($_POST['date_filed'] ?? date('Y-m-d'));

    if ($title === '' || $reference === '' || !isset(DOCUMENT_CATEGORIES[$category])) {
        flash_set('error', 'Title, reference number, and category are required.');
        header('Location: upload.php');
        exit;
    }

    if (!in_array(db_file_category($category), DB_FILE_CATEGORIES, true) && $category !== 'historical') {
        // resolution, certificate → stored as report + CAT: tag in metadata
    }

    $stored = upload_store($_FILES['document'] ?? [], db_file_category($category), false);
    if (!$stored['ok']) {
        flash_set('error', $stored['error']);
        header('Location: upload.php');
        exit;
    }

    $payload = [
        'title' => $title,
        'reference_number' => $reference,
        'category' => $category,
        'description' => $description,
        'date_filed' => $dateFiled,
        'filed_by' => $user['full_name'],
        'file_path' => $stored['file_path'],
        'mime_type' => $stored['mime_type'],
        'file_size' => $stored['file_size'],
        'document_year' => (int) date('Y', strtotime($dateFiled)),
        'subject' => $description,
    ];

    try {
        if (auth_can_upload($user)) {
            $payload['status'] = 'approved';
            $id = $repo->create($payload, false);
            $msg = 'Document filed and approved.';
        } else {
            $payload['status'] = 'pending';
            $id = $repo->create($payload, true);
            $msg = 'Document submitted to the Kapitan approval queue.';
        }

        if ($id === null) {
            $detail = $repo->getLastError();
            flash_set('error', $detail
                ? 'Could not save document: ' . $detail
                : 'Could not save document. Check Supabase connection and that PHP curl is enabled.');
        } else {
            flash_set('success', $msg);
            header('Location: search.php');
            exit;
        }
    } catch (Throwable $e) {
        app_log_exception($e, 'upload.php');
        flash_set('error', 'Upload failed. Ensure supabase_schema.sql tables exist in your project.');
    }

    header('Location: upload.php');
    exit;
}

$pageTitle = $isMemberSubmit ? 'Submit for Approval' : 'Upload Document';
layout_begin($pageTitle, 'upload', $user, $pending);
layout_topbar($pageTitle);
?>
<div class="content">
    <?= flash_render() ?>
    <?php if ($isMemberSubmit): ?>
    <p class="content-lead">Submit ordinances, permits, or reports for Kapitan review.</p>
    <?php elseif ($user['role'] === 'captain'): ?>
    <p class="content-lead">As Kapitan, upload and file documents directly — they are approved immediately.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="upload.php">
        <?= csrf_field() ?>
        <div class="upload-zone">
            <i class="ti ti-upload"></i>
            <p>Select PDF, JPG, or PNG (max 50 MB)</p>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required style="margin-top:12px;">
        </div>
        <div class="form-group">
            <label for="title">Document title</label>
            <input type="text" id="title" name="title" required maxlength="255" value="<?= e($_POST['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="reference_number">Reference number</label>
            <input type="text" id="reference_number" name="reference_number" required maxlength="100" value="<?= e($_POST['reference_number'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <?php foreach (DOCUMENT_CATEGORIES as $key => $label): ?>
                    <?php if ($key === 'historical') {
                        continue;
                    } ?>
                    <option value="<?= e($key) ?>" <?= ($_POST['category'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <small style="color:var(--color-text-tertiary);">Resolutions &amp; certificates are stored under Reports in Supabase.</small>
        </div>
        <div class="form-group">
            <label for="date_filed">Date filed</label>
            <input type="date" id="date_filed" name="date_filed" required value="<?= e($_POST['date_filed'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
            <label for="description">Description / subject</label>
            <textarea id="description" name="description"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-primary"><i class="ti ti-upload"></i> Upload</button>
    </form>
</div>
<?php layout_end(); ?>
