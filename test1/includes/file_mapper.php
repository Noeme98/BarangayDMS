<?php
/**
 * Maps existing Supabase tables (files, pending_files) to the app document shape.
 */

/** Categories stored in file_category enum */
const DB_FILE_CATEGORIES = ['ordinance', 'permit', 'report'];

function meta_pack(string $ref, string $subject, ?int $year = null, ?string $uiCategory = null, string $notes = '', ?string $documentType = null): string
{
    $lines = [];
    if ($ref !== '') {
        $lines[] = 'REF:' . $ref;
    }
    if ($year !== null && $year > 0) {
        $lines[] = 'YEAR:' . $year;
    }
    if ($documentType !== null && $documentType !== '') {
        $lines[] = 'TYPE:' . $documentType;
    }
    if ($subject !== '') {
        $lines[] = 'SUBJECT:' . $subject;
    }
    if ($uiCategory !== null && !in_array($uiCategory, DB_FILE_CATEGORIES, true)) {
        $lines[] = 'CAT:' . $uiCategory;
    }
    if ($notes !== '' && $notes !== $subject) {
        $lines[] = $notes;
    }

    return implode("\n", $lines);
}

/** @return array{ref: string, year: ?int, subject: string, document_type: string, ui_category: ?string, approvals: array<int, array<string, string>>} */
function meta_parse(?string $description): array
{
    $meta = [
        'ref' => '',
        'year' => null,
        'subject' => '',
        'document_type' => '',
        'ui_category' => null,
        'approvals' => [],
    ];

    if ($description === null || $description === '') {
        return $meta;
    }

    $parts = preg_split('/\n---APPROVAL---\n/', $description, 2);
    $body = $parts[0];
    if (isset($parts[1])) {
        foreach (explode("\n", trim($parts[1])) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $bits = explode('|', $line, 4);
            $meta['approvals'][] = [
                'at' => $bits[0] ?? '',
                'by' => $bits[1] ?? '',
                'action' => $bits[2] ?? '',
                'remarks' => $bits[3] ?? '',
            ];
        }
    }

    foreach (explode("\n", $body) as $line) {
        $line = trim($line);
        if (preg_match('/^REF:(.+)$/i', $line, $m)) {
            $meta['ref'] = trim($m[1]);
        } elseif (preg_match('/^YEAR:(\d+)$/i', $line, $m)) {
            $meta['year'] = (int) $m[1];
        } elseif (preg_match('/^SUBJECT:(.+)$/i', $line, $m)) {
            $meta['subject'] = trim($m[1]);
        } elseif (preg_match('/^TYPE:(.+)$/i', $line, $m)) {
            $meta['document_type'] = trim($m[1]);
        } elseif (preg_match('/^CAT:(.+)$/i', $line, $m)) {
            $meta['ui_category'] = trim($m[1]);
        }
    }

    return $meta;
}

function meta_append_approval(string $description, string $actedBy, string $action, string $newStatus, ?string $remarks): string
{
    $line = implode('|', [
        date('c'),
        $actedBy,
        $action . ':' . $newStatus,
        $remarks ?? '',
    ]);

    if (str_contains($description, '---APPROVAL---')) {
        return $description . "\n" . $line;
    }

    return rtrim($description) . "\n---APPROVAL---\n" . $line;
}

function db_file_category(string $uiCategory): string
{
    if (in_array($uiCategory, DB_FILE_CATEGORIES, true)) {
        return $uiCategory;
    }

    return 'report';
}

function map_file_row(array $row, string $source = 'files'): array
{
    $meta = meta_parse($row['description'] ?? null);
    $dbCat = (string) ($row['file_category'] ?? 'report');
    $uiCat = $meta['ui_category'] ?? $dbCat;
    $fileName = (string) ($row['file_name'] ?? '');
    $title = $fileName;
    $ref = $meta['ref'];
    if ($ref === '' && preg_match('/^(.+?) — (.+)$/u', $fileName, $m)) {
        $title = trim($m[1]);
        $ref = trim($m[2]);
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'title' => $title,
        'reference_number' => $ref,
        'category' => $uiCat,
        'file_category' => $dbCat,
        'description' => $meta['subject'] ?: ($row['description'] ?? ''),
        'date_filed' => (string) ($row['date_uploaded'] ?? $row['upload_date'] ?? ''),
        'filed_by' => (string) ($row['uploaded_by'] ?? ''),
        'status' => (string) ($row['status'] ?? 'pending'),
        'file_path' => (string) ($row['file_path'] ?? ''),
        'mime_type' => (string) ($row['file_type'] ?? ''),
        'file_size' => $row['file_size'] ?? 0,
        'is_digitized' => (($row['status'] ?? '') === 'digitized') || ($meta['ui_category'] ?? '') === 'historical',
        'document_year' => $meta['year'],
        'subject' => $meta['subject'],
        'document_type' => $meta['document_type'],
        'source' => $source,
        '_raw' => $row,
    ];
}

function map_pending_row(array $row): array
{
    $doc = map_file_row($row, 'pending');
    $doc['status'] = (string) ($row['status'] ?? 'pending');

    return $doc;
}
