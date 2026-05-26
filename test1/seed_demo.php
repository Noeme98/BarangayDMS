<?php
/**
 * seed_demo.php — Create demo PDF files + insert rows via Supabase REST.
 * Run once: http://localhost/3RD-YEARS/test1/seed_demo.php
 * Or paste database(sql)/demo_documents.sql in Supabase SQL Editor, then run this for PDF files.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/supabase.php';
require_once __DIR__ . '/includes/file_mapper.php';

header('Content-Type: text/html; charset=utf-8');

$messages = [];
$errors = [];

function demo_write_pdf(string $absolutePath, string $label): bool
{
    $dir = dirname($absolutePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $text = preg_replace('/[^\x20-\x7E]/', '', $label);
    $content = "BT /F1 14 Tf 72 720 Td ({$text}) Tj ET";
    $len = strlen($content);

    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
    $pdf .= "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n";
    $pdf .= "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n";
    $pdf .= "4 0 obj<</Length {$len}>>stream\n{$content}\nendstream\nendobj\n";
    $pdf .= "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    $pdf .= "trailer<</Size 6/Root 1 0 R>>\nstartxref\n0\n%%EOF";

    return file_put_contents($absolutePath, $pdf) !== false;
}

/** @return array<int, array<string, mixed>> */
function demo_file_rows(): array
{
    return [
        [
            'file_name' => 'Ordinance No. 2026-04',
            'file_type' => 'application/pdf',
            'file_size' => '245760',
            'uploaded_by' => 'Hon. Juan dela Cruz (Kapitan)',
            'file_path' => 'uploads/demo/ordinance_2026_04.pdf',
            'description' => meta_pack('ORD-2026-04', 'Solid Waste Management and Collection', 2026, 'ordinance'),
            'file_category' => 'ordinance',
            'date_uploaded' => '2026-05-12T09:00:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Business Permit BP-2026-118',
            'file_type' => 'application/pdf',
            'file_size' => '189440',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/permit_bp_2026_118.pdf',
            'description' => meta_pack('BP-2026-118', 'Sari-sari Store — Juan dela Cruz', 2026, 'permit'),
            'file_category' => 'permit',
            'date_uploaded' => '2026-05-20T14:30:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Barangay Budget Report Q1 2026',
            'file_type' => 'application/pdf',
            'file_size' => '512000',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/report_budget_q1_2026.pdf',
            'description' => meta_pack('BR-2026-Q1', 'First quarter financial statement', 2026, 'report'),
            'file_category' => 'report',
            'date_uploaded' => '2026-04-15T11:00:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Resolution No. 2026-12',
            'file_type' => 'application/pdf',
            'file_size' => '98304',
            'uploaded_by' => 'Hon. Juan dela Cruz (Kapitan)',
            'file_path' => 'uploads/demo/resolution_2026_12.pdf',
            'description' => meta_pack('RES-2026-12', 'Barangay clean-up drive schedule', 2026, 'resolution'),
            'file_category' => 'report',
            'date_uploaded' => '2026-05-08T16:00:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Certificate of Residency CR-2026-089',
            'file_type' => 'application/pdf',
            'file_size' => '65536',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/certificate_residency_089.pdf',
            'description' => meta_pack('CR-2026-089', 'Ana Reyes — Purok 3', 2026, 'certificate'),
            'file_category' => 'report',
            'date_uploaded' => '2026-05-22T10:15:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Ordinance No. 2025-11 — Curfew Hours',
            'file_type' => 'application/pdf',
            'file_size' => '204800',
            'uploaded_by' => 'Hon. Juan dela Cruz (Kapitan)',
            'file_path' => 'uploads/demo/ordinance_2025_11.pdf',
            'description' => meta_pack('ORD-2025-11', 'Minor curfew and public safety', 2025, 'ordinance'),
            'file_category' => 'ordinance',
            'date_uploaded' => '2025-11-30T08:00:00+00:00',
            'status' => 'approved',
        ],
        [
            'file_name' => 'Barangay Assembly Minutes — March 2026',
            'file_type' => 'application/pdf',
            'file_size' => '307200',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/report_assembly_mar_2026.pdf',
            'description' => meta_pack('MIN-2026-03', 'Monthly assembly minutes', 2026, 'report'),
            'file_category' => 'report',
            'date_uploaded' => '2026-03-28T13:00:00+00:00',
            'status' => 'in_review',
        ],
        [
            'file_name' => 'Historical Record — 1985 Barangay Census',
            'file_type' => 'application/pdf',
            'file_size' => '890880',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/historical_census_1985.pdf',
            'description' => meta_pack('HIST-1985-001', 'Digitized population census ledger', 1985, 'historical'),
            'file_category' => 'report',
            'date_uploaded' => '2026-05-01T09:00:00+00:00',
            'status' => 'digitized',
        ],
        [
            'file_name' => 'Historical Record — 1992 Election Results',
            'file_type' => 'application/pdf',
            'file_size' => '456704',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/historical_election_1992.pdf',
            'description' => meta_pack('HIST-1992-014', 'SK and barangay election tally sheets', 1992, 'historical'),
            'file_category' => 'report',
            'date_uploaded' => '2026-05-02T11:30:00+00:00',
            'status' => 'digitized',
        ],
        [
            'file_name' => 'Historical Record — 1978 Land Survey',
            'file_type' => 'application/pdf',
            'file_size' => '1204224',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_path' => 'uploads/demo/historical_land_1978.pdf',
            'description' => meta_pack('HIST-1978-003', 'Cadastral map and lot boundaries', 1978, 'historical'),
            'file_category' => 'report',
            'date_uploaded' => '2026-05-03T15:00:00+00:00',
            'status' => 'digitized',
        ],
    ];
}

/** @return array<int, array<string, mixed>> */
function demo_pending_rows(): array
{
    return [
        [
            'file_name' => 'Business Permit BP-2026-142 — Pending',
            'file_type' => 'application/pdf',
            'file_size' => '172032',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_category' => 'permit',
            'file_path' => 'uploads/demo/pending_permit_142.pdf',
            'status' => 'pending',
            'date_uploaded' => '2026-05-23T09:00:00+00:00',
        ],
        [
            'file_name' => 'Ordinance Draft — Market Stall Fees',
            'file_type' => 'application/pdf',
            'file_size' => '221184',
            'uploaded_by' => 'Maria Santos (Member)',
            'file_category' => 'ordinance',
            'file_path' => 'uploads/demo/pending_ordinance_market.pdf',
            'status' => 'pending',
            'date_uploaded' => '2026-05-24T08:30:00+00:00',
        ],
        [
            'file_name' => 'Incident Report IR-2026-07',
            'file_type' => 'application/pdf',
            'file_size' => '143360',
            'uploaded_by' => 'Pedro Lim (Member)',
            'file_category' => 'report',
            'file_path' => 'uploads/demo/pending_report_incident.pdf',
            'status' => 'pending',
            'date_uploaded' => '2026-05-24T14:00:00+00:00',
        ],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    try {
        $api = supabase();

        foreach ($api->from('files', ['select' => 'id,file_path']) ?? [] as $row) {
            if (!str_contains((string) ($row['file_path'] ?? ''), 'uploads/demo/')) {
                continue;
            }
            $api->request('files', 'DELETE', ['id' => 'eq.' . (int) $row['id']]);
        }
        foreach ($api->from('pending_files', ['select' => 'id,file_path']) ?? [] as $row) {
            if (!str_contains((string) ($row['file_path'] ?? ''), 'uploads/demo/')) {
                continue;
            }
            $api->request('pending_files', 'DELETE', ['id' => 'eq.' . (int) $row['id']]);
        }

        $allPaths = [];
        foreach (demo_file_rows() as $row) {
            $allPaths[] = $row;
        }
        foreach (demo_pending_rows() as $row) {
            $allPaths[] = $row;
        }

        foreach ($allPaths as $row) {
            $rel = (string) $row['file_path'];
            $abs = __DIR__ . '/' . $rel;
            $label = (string) ($row['file_name'] ?? 'Demo');
            if (!demo_write_pdf($abs, $label)) {
                $errors[] = 'Could not write file: ' . $rel;
            } else {
                $messages[] = 'Created ' . $rel;
            }
        }

        foreach (demo_file_rows() as $row) {
            $payload = array_merge($row, [
                'visible_to' => 'all',
                'target_role' => 'all',
                'target_roles' => 'all',
            ]);
            $result = $api->request('files', 'POST', [], $payload);
            if ($result['error'] !== null) {
                $errors[] = 'files insert: ' . $row['file_name'] . ' — ' . $result['error'];
            } else {
                $messages[] = 'DB: ' . $row['file_name'];
            }
        }

        foreach (demo_pending_rows() as $row) {
            $result = $api->request('pending_files', 'POST', [], $row);
            if ($result['error'] !== null) {
                $errors[] = 'pending insert: ' . $row['file_name'] . ' — ' . $result['error'];
            } else {
                $messages[] = 'Queue: ' . $row['file_name'];
            }
        }

        if ($errors === []) {
            $messages[] = 'Done. Open search.php to view demo documents.';
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
        app_log_exception($e, 'seed_demo');
    }
}

$existingFiles = 0;
$existingPending = 0;
try {
    $api = supabase();
    $existingFiles = count(array_filter(
        $api->from('files', ['select' => 'id,file_path']) ?? [],
        static fn ($r) => str_contains((string) ($r['file_path'] ?? ''), 'uploads/demo/')
    ));
    $existingPending = count(array_filter(
        $api->from('pending_files', ['select' => 'id,file_path']) ?? [],
        static fn ($r) => str_contains((string) ($r['file_path'] ?? ''), 'uploads/demo/')
    ));
} catch (Throwable $e) {
    $errors[] = 'Supabase: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed demo documents</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-shell">
        <div class="login-body">
            <h2 style="font-family:var(--font-brand);color:var(--dms-ice);margin-bottom:12px;">Demo documents</h2>
            <p class="content-lead">
                Inserts 10 sample files + 3 pending approvals. Creates PDFs under <code>uploads/demo/</code>.
                Current demo rows: <?= (int) $existingFiles ?> files, <?= (int) $existingPending ?> pending.
            </p>
            <?php foreach ($messages as $m): ?>
                <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
            <?php endforeach; ?>
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
            <?php if ($messages === [] || $errors !== []): ?>
            <form method="post">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn-primary btn-login">Load demo data (replace existing demo rows)</button>
            </form>
            <?php endif; ?>
            <p style="margin-top:16px;font-size:12px;color:var(--color-text-secondary);">
                Or run <code>database(sql)/demo_documents.sql</code> in Supabase, then this page for PDF files.<br>
                <a href="search.php">All Documents</a> · <a href="index.php">Sign in</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
