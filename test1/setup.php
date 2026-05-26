<?php
/**
 * setup.php — One-time: reset users to single system admin (run once, then delete this file).
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/supabase.php';
require_once __DIR__ . '/includes/layout.php';

header('Content-Type: text/html; charset=utf-8');

$api = supabase();
$hash = '$2y$10$7UNq8Ur9Zpo7y0WOU8wfrOp2DzyxN3j6Xz4VlhmJ8plH1rehHwghi';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $existing = $api->from('users', ['select' => 'id,username']) ?? [];
    foreach ($existing as $row) {
        $api->request('users', 'DELETE', ['id' => 'eq.' . (int) $row['id']]);
    }

    $result = $api->request('users', 'POST', [], [
        'full_name' => 'System Administrator',
        'username' => 'barangayadmin',
        'password' => $hash,
        'role' => 'admin',
        'status' => 'active',
    ]);

    if ($result['error'] === null) {
        $messages[] = 'Done. Sign in at index.php with barangayadmin / Admin@2026';
        $messages[] = 'Delete setup.php from the server for security.';
    } else {
        $messages[] = 'Error: ' . $result['error'];
    }
}

$count = count($api->from('users', ['select' => 'id']) ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(APP_LOGO) ?>">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-shell">
        <div class="login-brand">
            <?php layout_brand('One-time setup', true); ?>
        </div>
        <div class="login-body">
            <p class="content-lead">This removes all <?= (int) $count ?> user(s) and creates one system admin.</p>
            <?php foreach ($messages as $m): ?>
                <div class="alert alert-success"><?= e($m) ?></div>
            <?php endforeach; ?>
            <?php if ($messages === []): ?>
            <form method="post">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn-primary btn-login">Reset &amp; create barangayadmin</button>
            </form>
            <?php else: ?>
            <p><a href="index.php">Go to sign in</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
