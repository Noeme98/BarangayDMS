<?php
/**
 * Session authentication against Supabase users table.
 */

require_once __DIR__ . '/users.php';
require_once __DIR__ . '/logger.php';

function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? ''),
        'full_name' => (string) ($_SESSION['full_name'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
    ];
}

function auth_require_login(): array
{
    $user = auth_user();
    if ($user === null) {
        header('Location: index.php');
        exit;
    }

    if (($_SESSION['status'] ?? 'active') === 'disabled') {
        auth_logout();
        header('Location: index.php?error=disabled');
        exit;
    }

    return $user;
}

function auth_require_admin(): array
{
    $user = auth_require_login();
    if ($user['role'] !== 'admin') {
        flash_set('error', 'Only the system administrator can access that page.');
        header('Location: ' . auth_home_url($user));
        exit;
    }

    return $user;
}

function auth_require_role(array $roles): array
{
    $user = auth_require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash_set('error', 'You do not have permission to access that page.');
        header('Location: ' . auth_home_url($user));
        exit;
    }

    return $user;
}

function auth_home_url(array $user): string
{
    return 'search.php';
}

/** Roles that can browse and search documents */
function auth_document_roles(): array
{
    return ['admin', 'captain', 'member'];
}

/** Captain/admin: file directly. Members use submit flow. */
function auth_can_upload(array $user): bool
{
    return in_array($user['role'], ['admin', 'captain'], true);
}

/** Members submit ordinances, permits, and reports for Kapitan approval. */
function auth_can_submit_for_approval(array $user): bool
{
    return $user['role'] === 'member';
}

function auth_can_use_upload_page(array $user): bool
{
    return auth_can_upload($user) || auth_can_submit_for_approval($user);
}

function auth_can_approve(array $user): bool
{
    return $user['role'] === 'captain';
}

function auth_attempt(string $username, string $password): bool
{
    if ($username === '' || $password === '') {
        flash_set('error', 'Enter both username and password.');

        return false;
    }

    try {
        $repo = new UserRepository();
        $row = $repo->findByUsername($username);

        if ($row === null) {
            flash_set('error', 'Account not found. Run reset_users.sql in Supabase if this is a new install.');
            app_log("Login: user not found [{$username}]");

            return false;
        }

        if (($row['status'] ?? 'active') === 'disabled') {
            flash_set('error', 'This account has been disabled.');

            return false;
        }

        if (!$repo->verifyPassword($row, $password)) {
            flash_set('error', 'Incorrect password.');
            app_log("Login: bad password [{$username}]");

            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['username'] = (string) $row['username'];
        $_SESSION['full_name'] = (string) $row['full_name'];
        $_SESSION['role'] = (string) $row['role'];
        $_SESSION['status'] = (string) ($row['status'] ?? 'active');

        return true;
    } catch (Throwable $e) {
        app_log_exception($e, 'auth_attempt');
        flash_set('error', 'Cannot reach Supabase. Enable PHP curl in Apache and check .env keys.');

        return false;
    }
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
