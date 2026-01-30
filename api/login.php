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

// ================= VALIDASI =================
if ($email === '' || $password === '') {
    header('Location: /login?error=empty');
    exit;
}

// ================= AMBIL USER (Gunakan $pdo) =================
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

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

// Update last login (Gunakan PDO)
$stmtUpdate = $pdo->prepare("UPDATE pengguna SET tanggal_daftar = NOW() WHERE id_pengguna = ?");
$stmtUpdate->execute([$user['id_pengguna']]);

// ================= REDIRECT =================
header('Location: /dashboard');
exit;
