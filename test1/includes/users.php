<?php
/**
 * User accounts via Supabase REST (users table).
 */

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/logger.php';

const USER_ROLES = [
    'captain' => 'Kapitan (Barangay Captain)',
    'member' => 'Barangay Member',
];

class UserRepository
{
    private SupabaseClient $api;

    public function __construct()
    {
        $this->api = supabase();
    }

    public function findByUsername(string $username): ?array
    {
        $rows = $this->api->from('users', [
            'select' => '*',
            'username' => 'eq.' . $username,
            'limit' => 1,
        ]);

        return $rows[0] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listAll(): array
    {
        $rows = $this->api->from('users', [
            'select' => '*',
            'order' => 'role.asc,created_at.desc',
        ]);

        return $rows ?? [];
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $row = $this->findByUsername($username);
        if ($row === null) {
            return false;
        }

        return $exceptId === null || (int) ($row['id'] ?? 0) !== $exceptId;
    }

    public function create(string $fullName, string $username, string $plainPassword, string $role): ?int
    {
        if (!isset(USER_ROLES[$role])) {
            return null;
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $result = $this->api->request('users', 'POST', [], [
            'full_name' => $fullName,
            'username' => $username,
            'password' => $hash,
            'role' => $role,
            'status' => 'active',
        ]);

        if ($result['error'] !== null) {
            app_log('Create user failed: ' . $result['error']);

            return null;
        }

        return isset($result['data'][0]['id']) ? (int) $result['data'][0]['id'] : null;
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['active', 'disabled'], true)) {
            return false;
        }

        $result = $this->api->request('users', 'PATCH', ['id' => 'eq.' . $id], ['status' => $status]);

        return $result['error'] === null;
    }

    public function verifyPassword(array $user, string $plain): bool
    {
        $stored = (string) ($user['password'] ?? '');
        if ($stored === '') {
            return false;
        }
        if (password_get_info($stored)['algo'] !== 0) {
            return password_verify($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }
}
