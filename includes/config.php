<?php
// ============================================================
// TOKONESIA — Konfigurasi Aplikasi
// ============================================================

// --- Koneksi Database ---
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP
define('DB_NAME',     'jsnimmnm_tokonesia');
define('DB_CHARSET',  'utf8mb4');

// --- Pengaturan Aplikasi ---
define('BASE_URL',    'https://tokonesia.corefive.my.id');
define('UPLOAD_DIR',  __DIR__ . '/../uploads/products/');
define('UPLOAD_URL',  BASE_URL . '/uploads/products/');

// --- Koneksi PDO ---
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
