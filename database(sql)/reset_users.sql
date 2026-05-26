-- Run once in Supabase SQL Editor — replaces all users with one system admin
-- Login: barangayadmin / Admin@2026

DELETE FROM users;

INSERT INTO users (full_name, username, password, role, status, created_at) VALUES (
  'System Administrator',
  'barangayadmin',
  '$2y$10$7UNq8Ur9Zpo7y0WOU8wfrOp2DzyxN3j6Xz4VlhmJ8plH1rehHwghi',
  'admin',
  'active',
  NOW()
);

SELECT setval(pg_get_serial_sequence('users', 'id'), 1);
