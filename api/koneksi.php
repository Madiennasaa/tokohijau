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
    ]);

} catch (PDOException $e) {
    // UNCOMMENT ini kalau mau lihat error asli
    // die($e->getMessage());
    die("Koneksi database gagal");
}


echo json_encode([
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'db'   => env('DB_NAME'),
    'user' => env('DB_USER'),
]);
exit;
