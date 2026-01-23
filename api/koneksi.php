<?php
// 1. Deteksi Base URL otomatis agar assets tidak pecah
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host_url = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host_url/";

// 2. Konfigurasi Database
// Gunakan environment variables (rekomendasi Vercel) atau isi manual jika untuk testing
if ($host_url == 'localhost' || strpos($host_url, '127.0.0.1') !== false) {
    // KONEKSI LOKAL
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'toko_pertanian';
} else {
    // KONEKSI VERCEL (Ganti dengan data dari database online kamu)
    $host = 'alamat_host_online_kamu'; 
    $username = 'user_db_online';
    $password = 'pass_db_online';
    $database = 'nama_db_online';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// --- Fungsi-fungsi lainnya tetap sama ---

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Pengaturan Session
session_set_cookie_params([
    'lifetime' => 604800,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']), 
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>