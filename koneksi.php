<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'toko_pertanian';

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

session_set_cookie_params([
    'lifetime' => 604800, // 7 hari dalam detik (60*60*24*7)
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => isset($_SERVER['HTTPS']), // secure jika menggunakan HTTPS
    'httponly' => true, // mencegah akses cookie via JavaScript
    'samesite' => 'Lax' // perlindungan CSRF
]);

session_start();
?>