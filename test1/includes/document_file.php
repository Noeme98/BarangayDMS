<?php
/**
 * Resolve uploaded document paths for download/preview.
 */

require_once __DIR__ . '/repository.php';

const DOCUMENT_VIEWABLE_MIMES = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];

/**
 * @return array{doc: array<string, mixed>, path: string, mime: string, name: string}|null
 */
function document_resolve_file(int $id, string $from = 'files'): ?array
{
    if ($id <= 0) {
        return null;
    }

    $source = $from === 'pending' ? 'pending' : 'files';

    try {
        $repo = new DocumentRepository();
        $doc = $repo->findById($id, $source) ?? $repo->findRecordForDownload($id);
    } catch (Throwable $e) {
        app_log_exception($e, 'document_resolve_file');

        return null;
    }

    if ($doc === null) {
        return null;
    }

    $relative = ltrim((string) ($doc['file_path'] ?? ''), '/');
    $absolute = dirname(__DIR__) . '/' . $relative;
    $realBase = realpath(dirname(__DIR__) . '/uploads');
    $realFile = realpath($absolute);

    if ($realBase === false) {
        return null;
    }

    if ($realFile === false || !str_starts_with($realFile, $realBase)) {
        $fallback = document_find_file_by_basename(basename($relative));
        if ($fallback !== null) {
            $realFile = $fallback;
        }
    }

    if ($realFile === false || !str_starts_with($realFile, $realBase)) {
        return null;
    }

    $mime = (string) ($doc['mime_type'] ?? '');
    if ($mime === '' || $mime === 'application/octet-stream') {
        $detected = mime_content_type($realFile);
        $mime = is_string($detected) ? $detected : 'application/octet-stream';
    }

    return [
        'doc' => $doc,
        'path' => $realFile,
        'mime' => $mime,
        'name' => basename($realFile),
    ];
}

function document_is_viewable(string $mime): bool
{
    return in_array($mime, DOCUMENT_VIEWABLE_MIMES, true);
}

function document_preview_url(int $id, string $from = 'files', ?string $back = null): string
{
    $url = 'preview.php?id=' . $id . '&from=' . urlencode($from === 'pending' ? 'pending' : 'files');
    if ($back !== null && $back !== '') {
        $url .= '&back=' . urlencode($back);
    }

    return $url;
}

function document_view_url(int $id, string $from = 'files'): string
{
    return 'view.php?id=' . $id . '&from=' . urlencode($from === 'pending' ? 'pending' : 'files');
}

function document_find_file_by_basename(string $basename): ?string
{
    $uploads = realpath(dirname(__DIR__) . '/uploads');
    if ($uploads === false) {
        return null;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if (strtolower($file->getFilename()) === strtolower($basename)) {
            return $file->getRealPath();
        }
    }

    return null;
}
