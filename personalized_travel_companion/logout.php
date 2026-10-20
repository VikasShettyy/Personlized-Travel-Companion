<?php
require 'config.php';

// Clear all session variables
$_SESSION = [];

// Remove the session cookie
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to the home page
header('Location: index.php');
exit;