<?php
session_start();

// Periksa role user dari session
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $message = 'Goodbye Admin!';
        $redirect = 'login_form.php';
    } else {
        $message = 'Terimakasih telah berbelanja di Toko Hijau!';
        $redirect = 'index.php';
    }
} else {
    // Default jika tidak ada session role
    $message = 'Anda telah logout';
    $redirect = 'index.php';
}

session_unset();    
session_destroy();

echo "<script>alert('$message'); window.location.href = '$redirect';</script>";
?>