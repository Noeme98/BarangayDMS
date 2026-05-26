<?php
/**
 * CSRF token generation and validation for POST forms.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';

    if ($token === '' || $session === '' || !hash_equals($session, $token)) {
        return false;
    }

    return true;
}

function csrf_fail_redirect(string $url): never
{
    flash_set('error', 'Invalid or expired security token. Please try again.');
    header('Location: ' . $url);
    exit;
}
