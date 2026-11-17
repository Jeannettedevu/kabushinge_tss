<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure fallback photo exists in session
if (empty($_SESSION['admin_photo']) || !file_exists($_SESSION['admin_photo'])) {
    $_SESSION['admin_photo'] = 'uploads/default_avatar.svg';
}
?>
