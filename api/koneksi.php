<?php
function get_env_var($key, $default = null) {
    $value = getenv($key);
    return ($value !== false) ? $value : $default;
}

$host_url = $_SERVER['HTTP_HOST'];
$is_localhost = ($host_url == 'localhost' || strpos($host_url, '127.0.0.1') !== false);

if ($is_localhost) {
    $host     = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'toko_pertanian';
    $port     = 3306;
    $options  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
} else {
    $host     = get_env_var('DB_HOST');
    $username = get_env_var('DB_USER');
    $password = get_env_var('DB_PASS');
    $database = get_env_var('DB_NAME');
    $port     = get_env_var('DB_PORT', 4000);

    // Opsi SSL Wajib untuk TiDB Cloud
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password, $options);
    
    $conn = mysqli_init();
    if (!$is_localhost) {
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    }
    mysqli_real_connect($conn, $host, $username, $password, $database, $port);
    
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