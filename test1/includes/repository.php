<?php
/**
 * Data access for existing Supabase tables: users, files, pending_files.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/file_mapper.php';
require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/logger.php';

abstract class BaseRepository
{
    protected SupabaseClient $api;

    public function __construct()
    {
        $this->api = supabase();
    }
}

class DocumentRepository extends BaseRepository
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /** @return array<int, array<string, mixed>> */
    public function search(array $filters = []): array
    {
        $rows = $this->fetchAllFiles();
        $mapped = array_map(static fn ($r) => map_file_row($r), $rows);

        return $this->applyFilters($mapped, $filters);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchAllFiles(): array
    {
        return $this->api->from('files', ['select' => '*', 'order' => 'date_uploaded.desc']) ?? [];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function applyFilters(array $rows, array $filters): array
    {
        if (!empty($filters['category'])) {
            $cat = $filters['category'];
            $rows = array_filter($rows, static fn ($d) => ($d['category'] ?? '') === $cat || ($d['file_category'] ?? '') === $cat);
        }
        if (!empty($filters['status'])) {
            $st = $filters['status'];
            $rows = array_filter($rows, static fn ($d) => ($d['status'] ?? '') === $st);
        }
        if (!empty($filters['keyword'])) {
            $kw = strtolower($filters['keyword']);
            $rows = array_filter($rows, static function ($d) use ($kw) {
                $hay = strtolower(implode(' ', [
                    $d['title'] ?? '',
                    $d['reference_number'] ?? '',
                    $d['subject'] ?? '',
                    $d['document_type'] ?? '',
                    $d['description'] ?? '',
                    (string) ($d['document_year'] ?? ''),
                    $d['filed_by'] ?? '',
                ]));

                return str_contains($hay, $kw);
            });
        }
        if (!empty($filters['date_from'])) {
            $rows = array_filter($rows, static fn ($d) => ($d['date_filed'] ?? '') >= $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $rows = array_filter($rows, static fn ($d) => ($d['date_filed'] ?? '') <= $filters['date_to']);
        }
        if (!empty($filters['is_digitized'])) {
            $want = $filters['is_digitized'] === '1';
            $rows = array_filter($rows, static function ($d) use ($want) {
                $isHistorical = ($d['status'] ?? '') === 'digitized'
                    || ($d['category'] ?? '') === 'historical';

                return $want ? $isHistorical : !$isHistorical;
            });
        }
        if (!empty($filters['document_year'])) {
            $y = (int) $filters['document_year'];
            $rows = array_filter($rows, static fn ($d) => (int) ($d['document_year'] ?? 0) === $y);
        }
        if (!empty($filters['decade_start'])) {
            $start = (int) $filters['decade_start'];
            $end = $start + 9;
            $rows = array_filter($rows, static function ($d) use ($start, $end) {
                $y = (int) ($d['document_year'] ?? 0);

                return $y >= $start && $y <= $end;
            });
        }

        return array_values($rows);
    }

    public function findById(int $id, string $source = 'files'): ?array
    {
        if ($source === 'pending') {
            $row = $this->fetchPendingById($id);

            return $row ? map_pending_row($row) : null;
        }

        $row = $this->fetchFileById($id);

        return $row ? map_file_row($row) : null;
    }

    public function findRecordForDownload(int $id): ?array
    {
        $file = $this->findById($id, 'files');
        if ($file !== null) {
            return $file;
        }

        return $this->findById($id, 'pending');
    }

    private function fetchFileById(int $id): ?array
    {
        $rows = $this->api->from('files', ['select' => '*', 'id' => 'eq.' . $id, 'limit' => 1]);

        return $rows[0] ?? null;
    }

    private function fetchPendingById(int $id): ?array
    {
        $rows = $this->api->from('pending_files', ['select' => '*', 'id' => 'eq.' . $id, 'limit' => 1]);

        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     * @param bool $toPending Upload goes to pending_files queue
     */
    public function create(array $data, bool $toPending = false): ?int
    {
        return $toPending ? $this->createPending($data) : $this->createFile($data);
    }

    /** @param array<string, mixed> $data */
    public function createPending(array $data): ?int
    {
        $dbCat = db_file_category((string) $data['category']);
        $displayName = (string) $data['title'];
        if (!empty($data['reference_number'])) {
            $displayName .= ' — ' . $data['reference_number'];
        }

        $payload = [
            'file_name' => $displayName,
            'file_type' => (string) ($data['mime_type'] ?? ''),
            'file_size' => (string) ($data['file_size'] ?? 0),
            'uploaded_by' => (string) $data['filed_by'],
            'file_category' => $dbCat,
            'file_path' => (string) $data['file_path'],
            'status' => (string) ($data['status'] ?? 'pending'),
        ];

        $result = $this->api->request('pending_files', 'POST', [], $payload);
        if ($result['error'] !== null || empty($result['data'][0]['id'])) {
            $this->lastError = $result['error'] ?? 'No id returned from pending_files';
            app_log('REST create pending_files: ' . $this->lastError);

            return null;
        }

        $this->lastError = null;

        return (int) $result['data'][0]['id'];
    }

    /** @param array<string, mixed> $data */
    public function createFile(array $data): ?int
    {
        $dbCat = db_file_category((string) $data['category']);
        $description = meta_pack(
            (string) ($data['reference_number'] ?? ''),
            (string) ($data['subject'] ?? $data['description'] ?? ''),
            $data['document_year'] ?? null,
            (string) $data['category'],
            '',
            isset($data['document_type']) ? (string) $data['document_type'] : null
        );

        $payload = [
            'file_name' => (string) $data['title'],
            'file_type' => (string) ($data['mime_type'] ?? ''),
            'file_size' => (string) ($data['file_size'] ?? 0),
            'uploaded_by' => (string) $data['filed_by'],
            'file_category' => $dbCat,
            'file_path' => (string) $data['file_path'],
            'description' => $description,
            'status' => (string) ($data['status'] ?? 'pending'),
            'target_roles' => 'all',
            'target_role' => 'all',
            'visible_to' => 'all',
        ];

        $result = $this->api->request('files', 'POST', [], $payload);
        if ($result['error'] !== null || empty($result['data'][0]['id'])) {
            $this->lastError = $result['error'] ?? 'No id returned from files table';
            app_log('REST create files: ' . $this->lastError);

            return null;
        }

        $this->lastError = null;

        return (int) $result['data'][0]['id'];
    }

    public function approvePending(int $pendingId, string $actedBy, ?string $remarks, bool $approve): bool
    {
        $pending = $this->fetchPendingById($pendingId);
        if ($pending === null) {
            return false;
        }

        if (!$approve) {
            $path = dirname(__DIR__) . '/' . ltrim((string) ($pending['file_path'] ?? ''), '/');
            if (is_file($path)) {
                @unlink($path);
            }

            return $this->deletePending($pendingId);
        }

        $finalPath = $this->moveToCategoryFolder($pending);
        $pendingName = (string) ($pending['file_name'] ?? 'Document');
        $title = $pendingName;
        $ref = '';
        if (preg_match('/^(.+?) — (.+)$/u', $pendingName, $m)) {
            $title = trim($m[1]);
            $ref = trim($m[2]);
        }

        $fileId = $this->createFile([
            'title' => $title,
            'reference_number' => $ref,
            'category' => (string) ($pending['file_category'] ?? 'report'),
            'description' => '',
            'subject' => '',
            'date_filed' => (string) ($pending['date_uploaded'] ?? date('Y-m-d')),
            'filed_by' => (string) ($pending['uploaded_by'] ?? $actedBy),
            'status' => 'approved',
            'file_path' => $finalPath,
            'mime_type' => (string) ($pending['file_type'] ?? ''),
            'file_size' => $pending['file_size'] ?? 0,
            'document_year' => (int) date('Y'),
        ]);

        if ($fileId === null) {
            return false;
        }

        $this->updateStatus($fileId, 'approved', $actedBy, 'approved', $remarks, 'pending');

        return $this->deletePending($pendingId);
    }

    private function moveToCategoryFolder(array $pending): string
    {
        $category = (string) ($pending['file_category'] ?? 'report');
        if (!in_array($category, DB_FILE_CATEGORIES, true)) {
            $category = 'report';
        }

        $folder = dirname(__DIR__) . '/uploads/' . $category . '/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $oldPath = dirname(__DIR__) . '/' . ltrim((string) ($pending['file_path'] ?? ''), '/');
        $name = basename($oldPath);
        $newRel = 'uploads/' . $category . '/' . $name;
        $newAbs = $folder . $name;

        if (is_file($oldPath) && $oldPath !== $newAbs) {
            rename($oldPath, $newAbs);
        }

        return is_file($newAbs) ? $newRel : (string) ($pending['file_path'] ?? $newRel);
    }

    private function deletePending(int $id): bool
    {
        $r = $this->api->request('pending_files', 'DELETE', ['id' => 'eq.' . $id]);

        return $r['error'] === null;
    }

    public function updateStatus(int $id, string $newStatus, string $actedBy, string $action, ?string $remarks, ?string $previousStatus): bool
    {
        $row = $this->fetchFileById($id);
        if ($row === null) {
            return false;
        }

        $description = meta_append_approval(
            (string) ($row['description'] ?? ''),
            $actedBy,
            $action,
            $newStatus,
            $remarks
        );

        $patch = $this->api->request('files', 'PATCH', ['id' => 'eq.' . $id], [
            'status' => $newStatus,
            'description' => $description,
        ]);

        return $patch['error'] === null;
    }

    public function totalCount(): int
    {
        return $this->api->count('files') ?? 0;
    }

    public function digitizedCount(): int
    {
        $rows = $this->api->from('files', ['select' => 'id', 'status' => 'eq.digitized']) ?? [];

        return count($rows);
    }

    public function pendingApprovalCount(): int
    {
        $n = count($this->api->from('pending_files', ['select' => 'id']) ?? []);
        $pending = $this->api->from('files', ['select' => 'id', 'status' => 'eq.pending']) ?? [];
        $review = $this->api->from('files', ['select' => 'id', 'status' => 'eq.in_review']) ?? [];

        return $n + count($pending) + count($review);
    }

    /** @return array<int, array<string, mixed>> */
    public function queueItems(): array
    {
        $items = [];

        $pendingRows = $this->api->from('pending_files', ['select' => '*', 'order' => 'uploaded_at.asc']) ?? [];
        foreach ($pendingRows as $row) {
            $doc = map_pending_row($row);
            $doc['queue_type'] = 'pending';
            $items[] = $doc;
        }
        foreach (['pending', 'in_review'] as $st) {
            $fileRows = $this->api->from('files', ['select' => '*', 'status' => 'eq.' . $st, 'order' => 'date_uploaded.asc']) ?? [];
            foreach ($fileRows as $row) {
                $doc = map_file_row($row);
                $doc['queue_type'] = 'files';
                $items[] = $doc;
            }
        }

        usort($items, static function (array $a, array $b): int {
            $aTimestamp = strtotime((string) ($a['date_filed'] ?? '')) ?: 0;
            $bTimestamp = strtotime((string) ($b['date_filed'] ?? '')) ?: 0;

            return $bTimestamp <=> $aTimestamp;
        });

        return $items;
    }

    /** Delete a file record and its stored file (if present). Returns true on success. */
    public function deleteFile(int $id): bool
    {
        $row = $this->fetchFileById($id);
        if ($row === null) {
            return false;
        }

        // attempt to delete record via API
        $r = $this->api->request('files', 'DELETE', ['id' => 'eq.' . $id]);
        if ($r['error'] !== null) {
            return false;
        }

        // remove file from disk if path available
        $path = (string) ($row['file_path'] ?? '');
        if ($path !== '') {
            $abs = dirname(__DIR__) . '/' . ltrim($path, '/');
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        return true;
    }

    /** @return array<int, array<string, string>> */
    public function logsForDocument(int $documentId): array
    {
        $row = $this->fetchFileById($documentId);
        if ($row === null) {
            return [];
        }

        $meta = meta_parse($row['description'] ?? null);
        $logs = [];
        foreach ($meta['approvals'] as $a) {
            $logs[] = [
                'created_at' => $a['at'],
                'document_id' => (string) $documentId,
                'action' => $a['action'],
                'new_status' => '',
                'acted_by' => $a['by'],
                'remarks' => $a['remarks'],
            ];
        }

        return $logs;
    }

    /** @return array<int, array{decade: string, start: int, count: int, total_in_range: int, era: string}> */
    public function decadeSummary(): array
    {
        $rows = $this->search(['is_digitized' => '1']);
        $byDecade = [];

        foreach ($rows as $row) {
            $year = (int) ($row['document_year'] ?? 0);
            if ($year < 1900) {
                continue;
            }
            $start = (int) (floor($year / 10) * 10);
            $key = $start . 's';
            if (!isset($byDecade[$key])) {
                $byDecade[$key] = ['decade' => $key, 'start' => $start, 'count' => 0];
            }
            $byDecade[$key]['count']++;
        }

        ksort($byDecade);
        $max = max(1, ...array_column($byDecade, 'count') ?: [1]);
        foreach ($byDecade as &$d) {
            $d['total_in_range'] = $max;
            $d['era'] = match (true) {
                $d['start'] < 1970 => 'Early barangay records',
                $d['start'] < 1980 => 'Post-Martial Law era',
                $d['start'] < 1990 => 'Reconstruction period',
                $d['start'] < 2000 => 'Modern governance begins',
                $d['start'] < 2010 => 'Digital transition era',
                default => 'E-governance adoption',
            };
        }

        return array_values($byDecade);
    }
}
