<?php
require __DIR__ . '/../config/session.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Re-verify on every request that this admin still exists and is active
$check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_id = ? AND is_active = 1");
$check->execute([$_SESSION['admin_id']]);
if ((int) $check->fetchColumn() === 0) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}