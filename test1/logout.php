<?php
/**
 * logout.php — Destroy session and return to login.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

auth_logout();
header('Location: index.php');
exit;
