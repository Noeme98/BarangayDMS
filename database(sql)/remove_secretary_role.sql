-- Remove secretary role from existing users
-- Run in Supabase SQL Editor

UPDATE users
SET role = 'member'
WHERE role = 'secretary';

SELECT role, COUNT(*) AS total
FROM users
GROUP BY role
ORDER BY role;
