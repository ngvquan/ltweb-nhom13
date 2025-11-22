<?php
session_start();

$redirect = 'login.php';
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $redirect = 'admin/admin_login.php';
}

$_SESSION = [];
session_destroy();

header('Location: ' . $redirect);
exit;

