<?php
// ======================
// SESSION PALING ATAS
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 604800,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ======================
// ENV HELPER
// ======================
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

// ======================
// DETEKSI ENV
// ======================
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);

if ($is_local) {
    $dsn  = "mysql:host=localhost;dbname=toko_pertanian;charset=utf8mb4";
    $user = "root";
    $pass = "";
} else {
    $dsn  = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4;sslmode=VERIFY_IDENTITY",
        env('DB_HOST'),
        env('DB_PORT', 4000),
        env('DB_NAME')
    );
    $user = env('DB_USER');
    $pass = env('DB_PASS');
}

// ======================
// KONEKSI PDO (WAJIB SSL)
// ======================
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi database gagal");
}

// ======================
// HELPER
// ======================
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
