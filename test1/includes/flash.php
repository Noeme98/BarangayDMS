<?php
/**
 * One-time flash messages for user feedback.
 */

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $msg;
}

function flash_render(): string
{
    $html = '';
    foreach (['error', 'success', 'info'] as $type) {
        $msg = flash_get($type);
        if ($msg === null) {
            continue;
        }
        $class = $type === 'error' ? 'alert-error' : ($type === 'success' ? 'alert-success' : 'alert');
        $html .= '<div class="alert ' . $class . '">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    return $html;
}
