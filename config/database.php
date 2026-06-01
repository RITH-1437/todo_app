<?php

/**
 * config/database.php — PDO connection.
 *
 * Credentials are read from .env via config/env.php.
 * config/app.php calls loadEnv() before this file is required,
 * so $_ENV is already populated by the time we get here.
 */

$host     = env('DB_HOST', 'localhost');
$dbname   = env('DB_NAME', '');
$username = env('DB_USER', '');
$password = env('DB_PASS', '');

try {
    $conn = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    exit('Service temporarily unavailable.');
}