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

// ================= AMBIL USER =================
$stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: /login?error=invalid');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

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

// Optional: update last login
$user_id = (int) $user['id_pengguna'];
$conn->query("UPDATE pengguna SET tanggal_login = NOW() WHERE id_pengguna = $user_id");

// ================= REDIRECT =================
header('Location: /dashboard');
exit;
