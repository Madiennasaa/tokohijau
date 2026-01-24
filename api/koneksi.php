<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        env('DB_HOST'),
        env('DB_PORT', 4000),
        env('DB_NAME')
    );

    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // 🔥 INI KUNCI TIADB CLOUD
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CA => null,
    ]);

    echo "✅ Koneksi TiDB Cloud BERHASIL";
} catch (PDOException $e) {
    die("❌ ERROR: " . $e->getMessage());
}
