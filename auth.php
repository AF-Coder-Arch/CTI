<?php
// auth.php - User authentication helper

// Start the session if it hasn't been started already. Pages include this file to
// ensure that a session is active and that a user is authenticated before
// accessing protected resources. If the user is not logged in they will be
// redirected to the login page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If there is no user_id set in the current session then the user is not
// authenticated. In that case redirect them to the login page and stop
// further execution. Redirects must occur before any HTML is sent to the
// browser.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * Determine if the current user has administrative privileges.
 *
 * @return bool True if the logged in user has role 'admin'
 */
function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
