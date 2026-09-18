<?php
/**
 * Included at the top of every api/*.php file.
 * Starts the session, enforces auth as JSON (no redirect - this is an API),
 * and connects to the database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
