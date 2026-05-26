<?php
/**
 * index.php — BarangayDMS sign in
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_user();

if ($user !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . auth_home_url($user));
    exit;
}

$error = flash_get('error');
if (isset($_GET['error']) && $_GET['error'] === 'disabled') {
    $error = 'This account has been disabled.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Session expired. Refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (auth_attempt($username, $password)) {
            header('Location: ' . auth_home_url(auth_user()));
            exit;
        }
        $error = flash_get('error') ?? 'Sign in failed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(APP_LOGO) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-shell">
        <div class="login-brand">
            <?php layout_brand('Authority · Security · Protection', true); ?>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-primary btn-login">
                    <i class="ti ti-login"></i> Sign in
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
