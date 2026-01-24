<?php
// ======================
// SESSION PALING ATAS
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================
// ENV
// ======================
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

// ======================
// KONEKSI PDO + SSL TiDB
// ======================
try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        env('DB_HOST'),
        env('DB_PORT', 4000),
        env('DB_NAME')
    );

    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/certs/tidb-ca.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ]);

} catch (PDOException $e) {
    die("Koneksi database gagal");
}

// ======================
// HELPER
// ======================
function redirect($url) {
    header("Location: $url");
    exit;
}
