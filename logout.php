<?php
session_start();

// Clear session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear remember_me cookie if exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page
header('Location: login.php');
exit;
?>