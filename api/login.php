<?php
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        redirect('/login?error=empty');
    }

    $password_hash = md5($password);

    $sql = "SELECT * FROM pengguna WHERE email='$email' AND password='$password_hash'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] === 'aktif') {

            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            $user_id = (int)$user['id_pengguna'];
            $conn->query("UPDATE pengguna SET tanggal_daftar = NOW() WHERE id_pengguna = $user_id");

            redirect('/dashboard');

        } else {
            redirect('/login?error=inactive');
        }
    } else {
        redirect('/login?error=invalid');
    }

} else {
    redirect('/login');
}
