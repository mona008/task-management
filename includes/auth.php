<?php
/**
 * Authorization guard.
 * Include this at the very top of any page/API that requires a logged-in user:
 *   require_once __DIR__ . '/../includes/auth.php';
 *
 * It starts the session (if not already started) and redirects to login.php
 * if the user isn't authenticated. After this file runs, you can trust:
 *   $_SESSION['user_id']
 *   $_SESSION['user_name']
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}
