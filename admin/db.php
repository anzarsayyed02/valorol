<?php
/**
 * Database Connection Configuration
 * ──────────────────────────────────
 * Update the constants below with your Hostinger MySQL credentials.
 * Find them in: hPanel → Databases → MySQL Databases
 */

define('DB_HOST', 'localhost');           // Usually 'localhost' on Hostinger
define('DB_NAME', 'your_database_name'); // e.g. u123456789_valorol
define('DB_USER', 'your_database_user'); // e.g. u123456789_admin
define('DB_PASS', 'your_database_pass'); // The password you set in hPanel

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed. Check db.php credentials.']));
}
