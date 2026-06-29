<?php
// ============================================================
//  config/db.php  –  Database connection
//  Edit DB_HOST, DB_NAME, DB_USER, DB_PASS to match your server
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'asu_portal');
define('DB_USER', 'root');       // change to your MySQL username
define('DB_PASS', '');           // change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}
