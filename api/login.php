<?php
require_once __DIR__ . '/koneksi.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// ================= VALIDASI =================
if ($email === '' || $password === '') {
    header('Location: /login?error=empty');
    exit;
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /login?error=invalid');
    exit;
}

// ================= AMBIL USER (Gunakan PDO) =================
try {
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: /login?error=invalid');
        exit;
    }

    // ================= CEK STATUS =================
    if ($user['status'] !== 'aktif') {
        header('Location: /login?error=inactive');
        exit;
    }

    // ================= CEK PASSWORD =================
    if (!password_verify($password, $user['password'])) {
        header('Location: /login?error=invalid');
        exit;
    }

    // ================= LOGIN SUKSES =================
    $_SESSION['user_id'] = $user['id_pengguna'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    // Set cookie jika "remember me" dicentang
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expired = time() + (30 * 24 * 60 * 60); // 30 hari
        
        setcookie('remember_token', $token, $expired, '/', '', false, true);
        
        // Simpan token ke database
        $stmtToken = $pdo->prepare("UPDATE pengguna SET remember_token = ?, token_expired = ? WHERE id_pengguna = ?");
        $stmtToken->execute([$token, date('Y-m-d H:i:s', $expired), $user['id_pengguna']]);
    }

    // Update last login (Gunakan PDO)
    $stmtUpdate = $pdo->prepare("UPDATE pengguna SET tanggal_daftar = NOW() WHERE id_pengguna = ?");
    $stmtUpdate->execute([$user['id_pengguna']]);

    // ================= REDIRECT BERDASARKAN ROLE =================
    switch ($user['role']) {
        case 'admin':
            header('Location: /admin/dashboard');
            break;
        case 'pelanggan':
            header('Location: /dashboard');
            break;
        default:
            header('Location: /dashboard');
            break;
    }
    exit;

} catch (PDOException $e) {
    // Log error untuk debugging
    error_log("Login error: " . $e->getMessage());
    
    // Redirect dengan pesan error generic
    header('Location: /login?error=system');
    exit;
}