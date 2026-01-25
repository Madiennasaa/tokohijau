<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login');
}

$email    = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    redirect('/login?error=empty');
}

// ambil user berdasarkan email
$sql = "SELECT * FROM pengguna WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    redirect('/login?error=invalid');
}

$user = $result->fetch_assoc();

// verifikasi password
if (!password_verify($password, $user['password'])) {
    redirect('/login?error=invalid');
}

// cek status
if ($user['status'] !== 'aktif') {
    redirect('/login?error=inactive');
}

// set session
$_SESSION['user_id'] = $user['id_pengguna'];
$_SESSION['nama']    = $user['nama'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

// optional: update last login (JANGAN tanggal_daftar)
$conn->query(
    "UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = {$user['id_pengguna']}"
);

redirect('/dashboard');
