<?php
session_start();
require_once 'koneksi.php'; // pastikan $pdo tersedia

// Fungsi sanitasi input (untuk display saja, bukan untuk data yang di-hash)
function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk membersihkan input (tanpa htmlspecialchars untuk password)
function cleanInput($str) {
    return trim($str);
}

// Inisialisasi alert dari session
$alert = $_SESSION['alert'] ?? '';
$alertClass = $_SESSION['alert_class'] ?? '';
unset($_SESSION['alert'], $_SESSION['alert_class']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Protection (tambahkan token di form jika diperlukan)
        
        // Ambil input - PENTING: jangan sanitize password!
        $nama       = cleanInput($_POST['nama'] ?? '');
        $email      = cleanInput($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? ''; // NO TRIM untuk password
        $confirm    = $_POST['confirm_password'] ?? '';
        $no_telepon = cleanInput($_POST['no_telepon'] ?? '');
        $alamat     = cleanInput($_POST['alamat'] ?? '');

        // Validasi wajib
        if (empty($nama) || empty($email) || empty($password)) {
            throw new Exception('Nama, email, dan password wajib diisi.');
        }

        // Validasi nama (minimal 3 karakter, hanya huruf dan spasi)
        if (strlen($nama) < 3) {
            throw new Exception('Nama minimal 3 karakter.');
        }
        if (!preg_match('/^[a-zA-Z\s]+$/', $nama)) {
            throw new Exception('Nama hanya boleh berisi huruf dan spasi.');
        }

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid.');
        }

        // Validasi password
        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }
        if ($password !== $confirm) {
            throw new Exception('Password dan konfirmasi password tidak sama.');
        }

        // Validasi no_telepon (opsional, format Indonesia)
        if (!empty($no_telepon)) {
            // Hapus karakter non-digit
            $no_telepon_clean = preg_replace('/[^0-9]/', '', $no_telepon);
            
            // Validasi format Indonesia: 08xxxxxxxxxx atau 628xxxxxxxxxx (10-13 digit)
            if (!preg_match('/^(08|628)\d{8,11}$/', $no_telepon_clean)) {
                throw new Exception('Format nomor telepon tidak valid. Gunakan format: 08xxxxxxxxxx');
            }
            
            $no_telepon = $no_telepon_clean;
        }

        // Validasi alamat (opsional, minimal 10 karakter jika diisi)
        if (!empty($alamat) && strlen($alamat) < 10) {
            throw new Exception('Alamat minimal 10 karakter jika diisi.');
        }

        // Cek email unik
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email sudah terdaftar. Silakan gunakan email lain atau login.');
        }

        // Hash password dengan BCRYPT
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insert user dengan prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO pengguna 
            (nama, email, password, no_telepon, alamat, role, status, tanggal_daftar)
            VALUES (?, ?, ?, ?, ?, 'user', 'aktif', NOW())
        ");
        
        $result = $stmt->execute([
            $nama,
            $email,
            $password_hash,
            $no_telepon ?: null, // NULL jika kosong
            $alamat ?: null      // NULL jika kosong
        ]);

        if (!$result) {
            throw new Exception('Gagal menyimpan data. Silakan coba lagi.');
        }

        // Set success message di session
        $_SESSION['alert'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
        $_SESSION['alert_class'] = 'success';

        // Registrasi sukses → redirect ke login
        header('Location: login.php');
        exit;

    } catch (PDOException $e) {
        // Log error untuk debugging (jangan tampilkan ke user)
        error_log("Registration error: " . $e->getMessage());
        $alert = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        $alertClass = 'danger';
        
    } catch (Exception $e) {
        $alert = $e->getMessage();
        $alertClass = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Toko Hijau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #22c55e;
            --dark-green: #16a34a;
            --light-green: #dcfce7;
            --gradient-1: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Background Decorations */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            opacity: 0.08;
            z-index: 1;
        }

        .bg-decoration:nth-child(1) {
            width: 300px;
            height: 300px;
            background: var(--gradient-1);
            top: -150px;
            left: -150px;
            animation: float 8s ease-in-out infinite;
        }

        .bg-decoration:nth-child(2) {
            width: 200px;
            height: 200px;
            background: var(--gradient-1);
            bottom: -100px;
            right: -100px;
            animation: float 6s ease-in-out infinite reverse;
        }

        /* Main Container */
        .register-container {
            width: 100%;
            max-width: 1200px;
            height: 100%;
            max-height: 100vh;
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            position: relative;
            width: 100%;
            max-width: 850px;
            animation: fadeInUp 0.6s ease-out;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
            border-radius: 20px 20px 0 0;
        }

        /* Header */
        .register-header {
            text-align: center;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
            flex-shrink: 0;
        }

        .header-text {
            text-align: left;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 0.75rem;
            margin: 0;
        }

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            padding: 0 0.5rem;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        /* Form */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            font-size: 0.75rem;
        }

        .form-label i {
            margin-right: 0.35rem;
            color: var(--primary-green);
            width: 12px;
            font-size: 0.75rem;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 0.2rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.4rem 0.65rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(34, 197, 94, 0.15);
            background: white;
            outline: none;
        }

        .form-control:hover {
            border-color: var(--primary-green);
            background: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 60px;
        }

        /* Password Strength */
        .password-strength {
            height: 2px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .password-strength.weak .password-strength-bar {
            width: 33%;
            background: #ef4444;
        }

        .password-strength.medium .password-strength-bar {
            width: 66%;
            background: #f59e0b;
        }

        .password-strength.strong .password-strength-bar {
            width: 100%;
            background: var(--gradient-1);
        }

        .password-hint {
            font-size: 0.65rem;
            color: #6b7280;
            margin-top: 0.15rem;
        }

        /* Checkbox */
        .form-check {
            padding-left: 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            margin-top: 0.05rem;
            border: 2px solid #d1d5db;
            cursor: pointer;
            flex-shrink: 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .form-check-input:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(34, 197, 94, 0.15);
        }

        .form-check-label {
            color: #4b5563;
            font-size: 0.72rem;
            line-height: 1.3;
            cursor: pointer;
        }

        .form-check-label a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }

        .form-check-label a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* Button */
        .btn-register {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.55rem 1.25rem;
            border-radius: 8px;
            font-size: 0.85rem;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* Links */
        .register-links {
            text-align: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .register-links p {
            margin: 0;
            color: #6b7280;
            font-size: 0.75rem;
        }

        .register-links a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.75rem;
        }

        .register-links a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* Validation States */
        .form-control.is-valid {
            border-color: var(--primary-green);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2322c55e' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ef4444'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ef4444' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        .invalid-feedback,
        .valid-feedback {
            display: none;
            margin-top: 0.25rem;
            font-size: 0.68rem;
            font-weight: 500;
        }

        .invalid-feedback {
            color: #ef4444;
        }

        .valid-feedback {
            color: var(--primary-green);
        }

        .form-control.is-invalid ~ .invalid-feedback {
            display: block;
        }

        .form-control.is-valid ~ .valid-feedback {
            display: block;
        }

        /* Spacing */
        .mb-3 {
            margin-bottom: 0.5rem !important;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
            }
            50% { 
                transform: translateY(-20px) rotate(5deg); 
            }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--primary-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Alert */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 0.55rem 0.85rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
            color: var(--dark-green);
            border-left: 4px solid var(--primary-green);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .register-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .header-text {
                text-align: center;
            }

            .register-links {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="header-text">
                    <h1 class="brand-title">Toko Hijau</h1>
                    <p class="brand-subtitle">Bergabunglah dengan komunitas kami</p>
                </div>
            </div>

            <!-- Register Form -->
            <form id="registerForm" method="POST" action="" novalidate>
                <div class="form-grid">
                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label for="nama" class="form-label">
                            <i class="fas fa-user"></i>
                            <span>Nama Lengkap <span class="required">*</span></span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="nama" 
                            name="nama" 
                            required 
                            placeholder="Masukkan nama lengkap"
                        >
                        <div class="invalid-feedback">Minimal 3 karakter, hanya huruf</div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            <span>Email <span class="required">*</span></span>
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="nama@email.com"
                        >
                        <div class="invalid-feedback">Format email tidak valid</div>
                    </div>

                    <!-- No. Telepon -->
                    <div class="mb-3">
                        <label for="no_telepon" class="form-label">
                            <i class="fas fa-phone"></i>
                            <span>No. Telepon</span>
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="no_telepon" 
                            name="no_telepon" 
                            placeholder="08xxxxxxxxxx"
                        >
                        <div class="invalid-feedback">Format tidak valid (08xxxxxxxxxx)</div>
                    </div>

                    <!-- Alamat -->
                    <div class="mb-3">
                        <label for="alamat" class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Alamat</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="alamat" 
                            name="alamat" 
                            rows="2" 
                            placeholder="Alamat lengkap (opsional)"
                        ></textarea>
                        <div class="invalid-feedback">Minimal 10 karakter jika diisi</div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            <span>Password <span class="required">*</span></span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required 
                            placeholder="Minimal 6 karakter"
                        >
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <small class="password-hint">Gunakan kombinasi huruf, angka, dan simbol</small>
                        <div class="invalid-feedback">Password minimal 6 karakter</div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            <span>Konfirmasi Password <span class="required">*</span></span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            placeholder="Ulangi password"
                        >
                        <div class="invalid-feedback">Password tidak sama!</div>
                        <div class="valid-feedback">Password cocok!</div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="mb-3 full-width">
                        <div class="form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="terms" 
                                name="terms" 
                                required
                            >
                            <label class="form-check-label" for="terms">
                                Saya setuju dengan <a href="#" data-terms>Syarat & Ketentuan</a> dan <a href="#" data-privacy>Kebijakan Privasi</a>
                            </label>
                            <div class="invalid-feedback">Anda harus menyetujui syarat dan ketentuan</div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="full-width">
                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-user-plus me-2"></i>
                            Daftar Sekarang
                        </button>
                    </div>
                </div>
            </form>

            <!-- Links -->
            <div class="register-links">
                <p>
                    <span>Sudah punya akun?</span> 
                    <a href="/login">Masuk disini</a>
                </p>
                
                <a href="/">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form Validation
        const form = document.getElementById('registerForm');
        const inputs = form.querySelectorAll('.form-control, .form-check-input');

        // Validasi saat submit
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                showLoadingOverlay();
                // form.submit(); // Uncomment untuk submit sebenarnya
            } else {
                // Scroll ke error pertama
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });

        // Validasi form lengkap
        function validateForm() {
            let isValid = true;
            
            // Reset semua validasi
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });
            
            // Validasi Nama
            const nama = document.getElementById('nama');
            if (!nama.value.trim() || nama.value.trim().length < 3 || !/^[a-zA-Z\s]+$/.test(nama.value.trim())) {
                nama.classList.add('is-invalid');
                isValid = false;
            } else {
                nama.classList.add('is-valid');
            }
            
            // Validasi Email
            const email = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !emailPattern.test(email.value.trim())) {
                email.classList.add('is-invalid');
                isValid = false;
            } else {
                email.classList.add('is-valid');
            }
            
            // Validasi No. Telepon (opsional)
            const noTelepon = document.getElementById('no_telepon');
            if (noTelepon.value.trim()) {
                const cleanPhone = noTelepon.value.replace(/[^0-9]/g, '');
                if (!/^(08|628)\d{8,11}$/.test(cleanPhone)) {
                    noTelepon.classList.add('is-invalid');
                    isValid = false;
                } else {
                    noTelepon.classList.add('is-valid');
                }
            }
            
            // Validasi Password
            const password = document.getElementById('password');
            if (!password.value || password.value.length < 6) {
                password.classList.add('is-invalid');
                isValid = false;
            } else {
                password.classList.add('is-valid');
            }
            
            // Validasi Konfirmasi Password
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value !== password.value) {
                confirmPassword.classList.add('is-invalid');
                isValid = false;
            } else if (confirmPassword.value) {
                confirmPassword.classList.add('is-valid');
            }
            
            // Validasi Alamat (opsional)
            const alamat = document.getElementById('alamat');
            if (alamat.value.trim() && alamat.value.trim().length < 10) {
                alamat.classList.add('is-invalid');
                isValid = false;
            } else if (alamat.value.trim()) {
                alamat.classList.add('is-valid');
            }
            
            // Validasi Terms
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                terms.classList.add('is-invalid');
                isValid = false;
            } else {
                terms.classList.add('is-valid');
            }
            
            return isValid;
        }

        // Real-time validation
        inputs.forEach(input => {
            // Validasi on blur
            input.addEventListener('blur', function() {
                validateSingleField(this);
            });
            
            // Validasi on input untuk field yang sudah invalid
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateSingleField(this);
                }
                
                // Password strength indicator
                if (this.id === 'password') {
                    updatePasswordStrength(this.value);
                }
                
                // Validasi confirm password secara real-time
                if (this.id === 'confirm_password' || this.id === 'password') {
                    const password = document.getElementById('password');
                    const confirm = document.getElementById('confirm_password');
                    
                    if (confirm.value) {
                        if (confirm.value === password.value) {
                            confirm.classList.remove('is-invalid');
                            confirm.classList.add('is-valid');
                        } else {
                            confirm.classList.remove('is-valid');
                            confirm.classList.add('is-invalid');
                        }
                    }
                }
            });
        });

        // Validasi single field
        function validateSingleField(field) {
            const value = field.value.trim();
            
            switch(field.id) {
                case 'nama':
                    if (!value || value.length < 3 || !/^[a-zA-Z\s]+$/.test(value)) {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                    break;
                    
                case 'email':
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!value || !emailPattern.test(value)) {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                    break;
                    
                case 'no_telepon':
                    if (value) {
                        const cleanPhone = value.replace(/[^0-9]/g, '');
                        if (!/^(08|628)\d{8,11}$/.test(cleanPhone)) {
                            field.classList.add('is-invalid');
                            field.classList.remove('is-valid');
                        } else {
                            field.classList.remove('is-invalid');
                            field.classList.add('is-valid');
                        }
                    } else {
                        field.classList.remove('is-invalid', 'is-valid');
                    }
                    break;
                    
                case 'password':
                    if (!field.value || field.value.length < 6) {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                    break;
                    
                case 'alamat':
                    if (value && value.length < 10) {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                    } else if (value) {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    } else {
                        field.classList.remove('is-invalid', 'is-valid');
                    }
                    break;
                    
                case 'terms':
                    if (!field.checked) {
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                    break;
            }
        }

        // Password strength indicator
        function updatePasswordStrength(password) {
            const strengthIndicator = document.querySelector('.password-strength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthIndicator.className = 'password-strength';
            
            if (password.length === 0) {
                // No class
            } else if (strength <= 1) {
                strengthIndicator.classList.add('weak');
            } else if (strength <= 2) {
                strengthIndicator.classList.add('medium');
            } else {
                strengthIndicator.classList.add('strong');
            }
        }

        // Loading overlay
        function showLoadingOverlay() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div style="text-align: center;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 1rem; color: var(--dark-green); font-weight: 600; font-size: 1rem;">
                        Memproses registrasi...
                    </p>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        // Terms & Privacy links
        document.querySelector('[data-terms]').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Halaman Syarat & Ketentuan akan segera tersedia.');
        });

        document.querySelector('[data-privacy]').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Halaman Kebijakan Privasi akan segera tersedia.');
        });

        // Auto focus on first input
        window.addEventListener('load', () => {
            document.getElementById('nama').focus();
        });
    </script>
</body>
</html>