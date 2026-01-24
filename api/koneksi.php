<?php
// ======================
// SESSION HARUS PALING ATAS
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 604800,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ======================
// ENV HELPER
// ======================
function get_env_var($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

// ======================
// DETEKSI ENV
// ======================
$host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = ($host_url === 'localhost' || str_contains($host_url, '127.0.0.1'));

if ($is_local) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'toko_pertanian';
    $port = 3306;
} else {
    $host = get_env_var('DB_HOST');
    $user = get_env_var('DB_USER');
    $pass = get_env_var('DB_PASS');
    $db   = get_env_var('DB_NAME');
    $port = get_env_var('DB_PORT', 4000);
}

// ======================
// KONEKSI MYSQLI (UTAMA)
// ======================
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port)) {
    die("Database connection failed");
}

// ======================
// HELPER
// ======================
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
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
