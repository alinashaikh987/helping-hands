<?php
session_start();
require __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && hash_equals($_SESSION['csrf_admin'] ?? '', $_POST['csrf'] ?? '')) {
    $_SESSION = [];
    session_destroy();
}
header('Location: login.php');
exit;