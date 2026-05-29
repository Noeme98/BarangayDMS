<?php
/**
 * Secure file upload validation and storage.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/logger.php';

function upload_ensure_dirs(): void
{
    $base = dirname(__DIR__) . '/uploads';
    foreach (['ordinance', 'permit', 'report', 'historical', 'demo', 'pending'] as $dir) {
        $path = $base . '/' . $dir;
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            app_log('Cannot create upload directory: ' . $path);
        }
    }
}

function upload_validate(array $file): ?string
{
    if (!class_exists('finfo')) {
        return 'PHP fileinfo extension is disabled. Enable extension=fileinfo in php.ini and restart Apache.';
    }

    if (!isset($file['error']) || is_array($file['error'])) {
        return 'Invalid upload.';
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'Please select a file to upload.';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the 50 MB limit.',
            UPLOAD_ERR_PARTIAL => 'Upload was incomplete. Please try again.',
            default => 'Upload failed. Please try again.',
        };
    }

    if (($file['size'] ?? 0) > APP_UPLOAD_MAX_BYTES) {
        return 'File exceeds the 50 MB limit.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime === false || !isset(APP_ALLOWED_MIMES[$mime])) {
        return 'Only PDF, JPG, PNG, DOC, DOCX, and ODT files are allowed.';
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $expectedExt = APP_ALLOWED_MIMES[$mime];
    if ($ext !== $expectedExt && !($mime === 'image/jpeg' && $ext === 'jpeg')) {
        return 'File extension does not match file type.';
    }

    return null;
}

function upload_store(array $file, string $category, bool $digitized = false): array
{
    upload_ensure_dirs();

    $error = upload_validate($file);
    if ($error !== null) {
        return ['ok' => false, 'error' => $error];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = APP_ALLOWED_MIMES[$mime] ?? 'bin';

    $subdir = $digitized ? 'historical' : preg_replace('/[^a-z]/', '', strtolower($category));
    $baseDir = dirname(__DIR__) . '/uploads/' . $subdir;
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = time() . '_' . $safeName . '.' . $ext;
    $dest = $baseDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        app_log('Failed to move upload to ' . $dest);
        return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
    }

    return [
        'ok' => true,
        'file_path' => 'uploads/' . $subdir . '/' . $filename,
        'mime_type' => $mime,
        'file_size' => (int) $file['size'],
    ];
}
