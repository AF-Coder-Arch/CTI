<?php
/**
 * Logout page
 *
 * Destroys the current session and redirects the user back to the
 * login page. This page should be accessible only to logged in users,
 * but it will operate correctly regardless of the session state.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Finally destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;