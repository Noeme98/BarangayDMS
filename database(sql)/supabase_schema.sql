-- Run this in Supabase Dashboard → SQL Editor
-- File Archiving System schema (PostgreSQL)

CREATE TYPE file_category_enum AS ENUM ('ordinance', 'permit', 'report');

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  status VARCHAR(20) DEFAULT 'active',
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS files (
  id SERIAL PRIMARY KEY,
  file_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(100),
  file_size VARCHAR(50),
  uploaded_by VARCHAR(100),
  upload_date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  file_path VARCHAR(255) NOT NULL,
  description TEXT,
  file_category file_category_enum NOT NULL,
  date_uploaded TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) DEFAULT 'pending',
  visible_to VARCHAR(255) DEFAULT 'all',
  target_role VARCHAR(50) DEFAULT 'all',
  target_roles TEXT NOT NULL DEFAULT 'all',
  target_users TEXT
);

CREATE TABLE IF NOT EXISTS pending_files (
  id SERIAL PRIMARY KEY,
  file_name VARCHAR(255),
  file_type VARCHAR(100),
  file_size VARCHAR(50),
  uploaded_by VARCHAR(100),
  file_category file_category_enum,
  file_path VARCHAR(255),
  upload_date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  uploaded_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_uploaded TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) DEFAULT 'pending'
);

-- Default system admin (password: Admin@2026) — staff accounts created in the app
INSERT INTO users (full_name, username, password, role, status, created_at) VALUES
('System Administrator', 'barangayadmin', '$2y$10$7UNq8Ur9Zpo7y0WOU8wfrOp2DzyxN3j6Xz4VlhmJ8plH1rehHwghi', 'admin', 'active', NOW())
ON CONFLICT (username) DO NOTHING;

SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1));

-- Optional: allow PHP app access via API key (adjust for production)
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE files ENABLE ROW LEVEL SECURITY;
ALTER TABLE pending_files ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all for authenticated service" ON users FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for authenticated service" ON files FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for authenticated service" ON pending_files FOR ALL USING (true) WITH CHECK (true);
89 