<?php
// 1. Fungsi penarik variabel env yang lebih aman untuk Vercel
function get_env_var($key, $default = null) {
    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    return ($value !== false) ? $value : $default;
}

// 2. Deteksi lokasi (Lokal vs Vercel)
$host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = ($host_url == 'localhost' || strpos($host_url, '127.0.0.1') !== false);

if ($is_localhost) {
    // KONFIGURASI LOKAL (XAMPP/Laragon)
    $host     = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'toko_pertanian';
    $port     = 3306;
    $options  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
} else {
    // KONFIGURASI ONLINE (TiDB Cloud + Vercel)
    $host     = get_env_var('DB_HOST');
    $username = get_env_var('DB_USER');
    $password = get_env_var('DB_PASS');
    $database = get_env_var('DB_NAME');
    $port     = get_env_var('DB_PORT', 4000);

    // Opsi SSL Wajib untuk TiDB Cloud Serverless
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ];
}

try {
    // Koneksi menggunakan PDO (Utama)
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password, $options);
    
    // Koneksi menggunakan MySQLi (Opsional, jika script lama kamu pakai ini)
    $conn = mysqli_init();
    if (!$is_localhost) {
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    }
    
    // @ digunakan untuk meredam error mentah agar tidak jadi 403/500 di Vercel
    $db_connect = @mysqli_real_connect($conn, $host, $username, $password, $database, $port);
    
    if (!$db_connect && !$is_localhost) {
        // Jika mysqli gagal tapi PDO mungkin jalan, atau sebaliknya
        // Kita tidak matikan script agar tidak muncul error 403 mentah
    }

} catch(PDOException $e) {
    // Jika gagal konek, tampilkan pesan yang rapi (bukan error sistem)
    die("Maaf, koneksi database sedang bermasalah. Error: " . $e->getMessage());
}

// 3. Fungsi Helper Umum
if (!function_exists('sanitize')) {
    function sanitize($data) {
        global $conn;
        if ($conn) {
            return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
        }
        return htmlspecialchars(trim($data));
    }
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

// 4. Pengaturan Session Aman
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