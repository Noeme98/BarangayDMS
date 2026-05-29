<?php
/**
 * upload.php — Upload new barangay documents (PDF, JPG, PNG, DOC, DOCX, ODT — max 50MB).
 * All uploads are submitted to the Captain approval queue and are not immediately approved.
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

    if ($title === '' || $reference === '') {
        flash_set('error', 'Title and reference number are required.');
        header('Location: upload.php');
        exit;
    }

    // allow arbitrary UI categories; map to DB category (report) for storage when unknown
    if ($category === '') {
        $category = 'report';
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
        $payload['status'] = 'pending';
        $id = $repo->create($payload, true);
        $msg = 'Document submitted to the Kapitan approval queue.';

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
    <p class="content-lead">All uploaded documents are submitted to the Kapitan approval queue and will be approved before filing.</p>
    <form method="post" enctype="multipart/form-data" action="upload.php">
        <?= csrf_field() ?>
        <div class="upload-zone">
            <i class="ti ti-upload"></i>
            <p>Select PDF, JPG, PNG, DOC, DOCX, or ODT (max 50 MB)</p>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" required style="margin-top:12px;">
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
                <input list="category_list" id="category" name="category" required maxlength="100" value="<?= e($_POST['category'] ?? '') ?>">
                <datalist id="category_list">
                    <?php foreach (DOCUMENT_CATEGORIES as $key => $label): ?>
                        <?php if ($key === 'historical') continue; ?>
                        <option value="<?= e($label) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </datalist>
                <small style="color:var(--color-text-tertiary);">You may enter a custom category. Unknown categories are stored as "Reports" in Supabase but the UI preserves your category label.</small>
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
