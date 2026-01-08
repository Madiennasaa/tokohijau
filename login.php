<?php
include 'koneksi.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        redirect('login_form.php?error=empty');
    }
    
    $password_hash = md5($password);
    
    $sql = "SELECT * FROM pengguna WHERE email = '$email' AND password = '$password_hash'";
    $result = $conn->query($sql);
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if($user['status'] == 'aktif') {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            $user_id = $user['id_pengguna'];
            $conn->query("UPDATE pengguna SET tanggal_daftar = NOW() WHERE id_pengguna = $user_id");
            
            $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : 'dashboard.php';
            redirect($redirect_url);
        } else {
            redirect('login_form.php?error=inactive');
        }
    } else {
        redirect('login_form.php?error=invalid');
    }
} else {
    redirect('login_form.php');
}
?>  