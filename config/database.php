<?php
/**
 * Database connection (MySQLi, prepared-statement friendly).
 * Every page/API that needs the database includes this file:
 *   require_once __DIR__ . '/../config/database.php';
 * It exposes a single mysqli object: $conn
 */

$DB_HOST = 'localhost';
$DB_NAME = 'task_manager';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP password is empty

// Report mysqli errors as exceptions so we can catch them cleanly
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Never leak raw DB errors to the browser in a real deployment;
    // for this internship project we log it and show a generic message.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Something went wrong. Please try again later.');
}
