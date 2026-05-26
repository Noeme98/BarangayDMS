-- Demo documents for BarangayDMS
-- Run in Supabase Dashboard → SQL Editor AFTER supabase_schema.sql
--
-- Also run test1/seed_demo.php once on your XAMPP server so PDF files exist
-- under test1/uploads/ (downloads require real files on disk).

-- Clear previous demo rows (safe to re-run)
DELETE FROM pending_files WHERE file_path LIKE 'uploads/demo/%';
DELETE FROM files WHERE file_path LIKE 'uploads/demo/%';

-- Approved & filed documents (files table)
INSERT INTO files (
  file_name, file_type, file_size, uploaded_by, file_path, description,
  file_category, date_uploaded, status, visible_to, target_role, target_roles
) VALUES
(
  'Ordinance No. 2026-04',
  'application/pdf', '245760', 'Hon. Juan dela Cruz (Kapitan)',
  'uploads/demo/ordinance_2026_04.pdf',
  E'REF:ORD-2026-04\nSUBJECT:Solid Waste Management and Collection\nYEAR:2026',
  'ordinance', '2026-05-12 09:00:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Business Permit BP-2026-118',
  'application/pdf', '189440', 'Maria Santos (Member)',
  'uploads/demo/permit_bp_2026_118.pdf',
  E'REF:BP-2026-118\nSUBJECT:Sari-sari Store — Juan dela Cruz\nYEAR:2026',
  'permit', '2026-05-20 14:30:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Barangay Budget Report Q1 2026',
  'application/pdf', '512000', 'Maria Santos (Member)',
  'uploads/demo/report_budget_q1_2026.pdf',
  E'REF:BR-2026-Q1\nSUBJECT:First quarter financial statement\nYEAR:2026',
  'report', '2026-04-15 11:00:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Resolution No. 2026-12',
  'application/pdf', '98304', 'Hon. Juan dela Cruz (Kapitan)',
  'uploads/demo/resolution_2026_12.pdf',
  E'REF:RES-2026-12\nSUBJECT:Barangay clean-up drive schedule\nCAT:resolution\nYEAR:2026',
  'report', '2026-05-08 16:00:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Certificate of Residency CR-2026-089',
  'application/pdf', '65536', 'Maria Santos (Member)',
  'uploads/demo/certificate_residency_089.pdf',
  E'REF:CR-2026-089\nSUBJECT:Ana Reyes — Purok 3\nCAT:certificate\nYEAR:2026',
  'report', '2026-05-22 10:15:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Ordinance No. 2025-11 — Curfew Hours',
  'application/pdf', '204800', 'Hon. Juan dela Cruz (Kapitan)',
  'uploads/demo/ordinance_2025_11.pdf',
  E'REF:ORD-2025-11\nSUBJECT:Minor curfew and public safety\nYEAR:2025',
  'ordinance', '2025-11-30 08:00:00+00', 'approved', 'all', 'all', 'all'
),
(
  'Barangay Assembly Minutes — March 2026',
  'application/pdf', '307200', 'Maria Santos (Member)',
  'uploads/demo/report_assembly_mar_2026.pdf',
  E'REF:MIN-2026-03\nSUBJECT:Monthly assembly minutes\nYEAR:2026',
  'report', '2026-03-28 13:00:00+00', 'in_review', 'all', 'all', 'all'
),
(
  'Historical Record — 1985 Barangay Census',
  'application/pdf', '890880', 'Maria Santos (Member)',
  'uploads/demo/historical_census_1985.pdf',
  E'REF:HIST-1985-001\nSUBJECT:Digitized population census ledger\nCAT:historical\nYEAR:1985',
  'report', '2026-05-01 09:00:00+00', 'digitized', 'all', 'all', 'all'
),
(
  'Historical Record — 1992 Election Results',
  'application/pdf', '456704', 'Maria Santos (Member)',
  'uploads/demo/historical_election_1992.pdf',
  E'REF:HIST-1992-014\nSUBJECT:SK and barangay election tally sheets\nCAT:historical\nYEAR:1992',
  'report', '2026-05-02 11:30:00+00', 'digitized', 'all', 'all', 'all'
),
(
  'Historical Record — 1978 Land Survey',
  'application/pdf', '1204224', 'Maria Santos (Member)',
  'uploads/demo/historical_land_1978.pdf',
  E'REF:HIST-1978-003\nSUBJECT:Cadastral map and lot boundaries\nCAT:historical\nYEAR:1978',
  'report', '2026-05-03 15:00:00+00', 'digitized', 'all', 'all', 'all'
);

-- Approval queue (pending_files — awaiting Kapitan)
INSERT INTO pending_files (
  file_name, file_type, file_size, uploaded_by, file_category, file_path, status, date_uploaded
) VALUES
(
  'Business Permit BP-2026-142 — Pending',
  'application/pdf', '172032', 'Maria Santos (Member)',
  'permit', 'uploads/demo/pending_permit_142.pdf', 'pending', '2026-05-23 09:00:00+00'
),
(
  'Ordinance Draft — Market Stall Fees',
  'application/pdf', '221184', 'Maria Santos (Member)',
  'ordinance', 'uploads/demo/pending_ordinance_market.pdf', 'pending', '2026-05-24 08:30:00+00'
),
(
  'Incident Report IR-2026-07',
  'application/pdf', '143360', 'Pedro Lim (Member)',
  'report', 'uploads/demo/pending_report_incident.pdf', 'pending', '2026-05-24 14:00:00+00'
);

SELECT 'Demo documents inserted. Run test1/seed_demo.php on XAMPP for PDF files.' AS note;
