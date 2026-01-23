<?php
require_once 'koneksi.php';

$alert = '';
$alertClass = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pastikan tabel users ada sebelum eksekusi query
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pengguna (
                id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                no_telepon VARCHAR(15),
                alamat TEXT,
                tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('aktif', 'nonaktif') DEFAULT 'aktif'
            )
        ");
        
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $password = md5($_POST['password']);
        $no_telepon = sanitize($_POST['no_telepon']);
        $alamat = sanitize($_POST['alamat']);
        $role = 'user';

        // Validasi email unik
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $alert = 'Email sudah terdaftar. Gunakan email lain.';
            $alertClass = 'danger';
        } else {
            $stmt = $pdo->prepare("INSERT INTO pengguna (nama, email, password, no_telepon, alamat) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $password, $no_telepon, $alamat]);

            $alert = 'Registrasi berhasil! Silakan login.';
            $alertClass = 'success';
            $_POST = array(); // Reset form
        }
    } catch (PDOException $e) {
        $alert = 'Terjadi kesalahan sistem: ' . $e->getMessage();
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
            --gradient-2: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            margin: 0;
            padding: 1rem;
        }

        /* Background Decorations */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }

        .bg-decoration:nth-child(1) {
            width: 250px;
            height: 250px;
            background: var(--gradient-1);
            top: -125px;
            left: -125px;
            animation: float 8s ease-in-out infinite;
        }

        .bg-decoration:nth-child(2) {
            width: 180px;
            height: 180px;
            background: var(--gradient-1);
            bottom: -90px;
            right: -90px;
            animation: float 6s ease-in-out infinite reverse;
        }

        .bg-decoration:nth-child(3) {
            width: 120px;
            height: 120px;
            background: var(--gradient-1);
            top: 20%;
            right: 10%;
            animation: float 10s ease-in-out infinite;
        }

        .bg-decoration:nth-child(4) {
            width: 80px;
            height: 80px;
            background: var(--gradient-1);
            bottom: 30%;
            left: 5%;
            animation: float 12s ease-in-out infinite reverse;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            opacity: 0.3;
        }

        .floating-element:nth-child(5) { 
            top: 10%; 
            left: 10%; 
            animation-delay: 0s; 
            font-size: 1.5rem;
            color: var(--primary-green);
        }
        .floating-element:nth-child(6) { 
            top: 70%; 
            right: 15%; 
            animation-delay: 2s; 
            font-size: 1.2rem;
            color: var(--dark-green);
        }
        .floating-element:nth-child(7) { 
            bottom: 15%; 
            left: 20%; 
            animation-delay: 4s; 
            font-size: 1.8rem;
            color: var(--primary-green);
        }
        .floating-element:nth-child(8) { 
            top: 40%; 
            left: 5%; 
            animation-delay: 1s; 
            font-size: 1.3rem;
            color: var(--dark-green);
        }

        /* Main Register Container */
        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 550px;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            padding: 2rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.12);
        }

        /* Header Section */
        .register-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
            animation: pulse 2s infinite;
        }

        .brand-title {
            font-size: 1.6rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-weight: 500;
            font-size: 0.9rem;
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

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--primary-green);
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.6rem 0.9rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.25);
            background: white;
            transform: translateY(-1px);
        }

        .form-control:hover, .form-select:hover {
            border-color: var(--primary-green);
            background: white;
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .form-check-input:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.25);
        }

        .form-check-label {
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.2rem !important;
        }

        /* Password Strength Indicator */
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: var(--gradient-1);
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

        /* Button Styles */
        .btn-register {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.7rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            color: white;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* Links */
        .register-links {
            text-align: center;
            margin-top: 1.2rem;
        }

        .register-links a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.9rem;
        }

        .register-links a:hover {
            color: var(--dark-green);
            transform: translateY(-1px);
        }

        .register-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: var(--gradient-1);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .register-links a:hover::after {
            width: 100%;
        }

        .text-muted {
            color: #6b7280 !important;
        }

        .divider {
            padding: 0 0 10px 0;
        }

        .form-control {
            min-height: 42px; /* Sesuaikan sesuai kebutuhan */
        }

        /* Validation States */
        .form-control.is-valid {
            border-color: var(--primary-green);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3e%3cpath fill='%2322c55e' d='M10.8 3.6L4.95 9.45 2.25 6.75' stroke='%2322c55e' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 18px 18px;
            padding-right: 2.5rem;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='5.5'/%3e%3cpath stroke='%23ef4444' stroke-width='1.5' stroke-linecap='round' d='M3.5 3.5l5 5m0-5l-5 5'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 18px 18px;
            padding-right: 2.5rem;
        }

        /* Feedback text styling */
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #ef4444;
        }

        .is-invalid ~ .invalid-feedback,
        .is-invalid ~ .invalid-tooltip {
            display: block;
        }

        .valid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: var(--primary-green);
        }

        .is-valid ~ .valid-feedback,
        .is-valid ~ .valid-tooltip {
            display: block;
        }
        /* Terms and Conditions */
        .terms-text {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .terms-text a {
            color: var(--primary-green);
            text-decoration: none;
        }

        .terms-text a:hover {
            text-decoration: underline;
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
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Loading State */
        .btn-register.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-register.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }
            
            .register-card {
                padding: 1.5rem 1.5rem;
            }
            
            .brand-title {
                font-size: 1.4rem;
            }

            .floating-element {
                display: none;
            }

            .bg-decoration {
                display: none;
            }
        }

        @media (max-height: 800px) {
            .brand-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .brand-title {
                font-size: 1.4rem;
            }
            
            .register-header {
                margin-bottom: 1rem;
            }
            
            .form-control, .form-select {
                padding: 0.5rem 0.8rem;
            }
            
            .btn-register {
                padding: 0.6rem 1.5rem;
            }
        }

        /* Page Load Animation */
        .register-container {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>
    
    <!-- Floating Elements -->
    <div class="floating-element">
        <i class="fas fa-seedling"></i>
    </div>
    <div class="floating-element">
        <i class="fas fa-leaf"></i>
    </div>
    <div class="floating-element">
        <i class="fas fa-tree"></i>
    </div>
    <div class="floating-element">
        <i class="fas fa-spa"></i>
    </div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2 class="brand-title">Toko Hijau</h2>
                <p class="brand-subtitle">Bergabunglah dengan komunitas pertanian kami</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($alert): ?>
                <div class="alert alert-<?= $alertClass ?>">
                    <i class="fas <?= $alertClass === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                    <?= $alert ?>
                </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form id="registerForm" method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama" class="form-label">
                            <i class="fas fa-user"></i>
                            Nama Lengkap
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama" required 
                            value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" 
                            placeholder="Masukkan nama lengkap Anda">
                        <div class="invalid-feedback">
                            Nama lengkap harus diisi.
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required 
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                            placeholder="nama@email.com">
                        <div class="invalid-feedback">
                            Email harus valid.
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Minimal 6 karakter">
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="invalid-feedback">
                            Password minimal 6 karakter.
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Konfirmasi Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Ulangi password">
                        <div class="invalid-feedback">
                            Password tidak sama.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="no_telepon" class="form-label">
                        <i class="fas fa-phone"></i>
                        No. Telepon
                    </label>
                    <input type="tel" class="form-control" id="no_telepon" name="no_telepon" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="mb-3">
                    <label for="alamat" class="form-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Alamat Lengkap
                    </label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap Anda"></textarea>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label terms-text" for="terms">
                            Saya setuju dengan <a href="#" id="termsLink">Syarat & Ketentuan</a> dan <a href="#" id="privacyLink">Kebijakan Privasi</a> Toko Hijau
                        </label>
                        <div class="invalid-feedback">
                            Anda harus menyetujui syarat & ketentuan.
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>
                    Daftar Sekarang
                </button>
            </form>
            
            <!-- Links -->
            <div class="register-links">
                <div class="mb-2">
                    <span class="text-muted">Sudah punya akun?</span> <a href="login_form.php">Masuk disini</a>
                </div>
                
                <div class="divider">
                    <span>atau</span>
                </div>
                
                <a href="index.php" class="text-muted">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                showLoadingOverlay();
                
                // Submit form secara native (tanpa AJAX)
                this.submit();
            } else {
                scrollToFirstError();
                showAlert('⚠️ Mohon lengkapi semua field yang diperlukan dengan benar.', 'danger');
            }
        });

        // Form validation function
        function validateForm() {
            let isValid = true;
            const form = document.getElementById('registerForm');
            const formData = new FormData(form);
            
            // Clear previous validation states
            clearValidationStates();
            
            // Validate required fields
            const requiredFields = ['nama', 'email', 'password', 'confirm_password'];
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.add('is-valid');
                }
            });
            
            // Validate email format
            const email = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value && !emailPattern.test(email.value)) {
                email.classList.remove('is-valid');
                email.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password length
            const password = document.getElementById('password');
            if (password.value && password.value.length < 6) {
                password.classList.remove('is-valid');
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password confirmation
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value !== password.value) {
                confirmPassword.classList.remove('is-valid');
                confirmPassword.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate terms checkbox
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                terms.classList.add('is-invalid');
                isValid = false;
            }
            
            return isValid;
        }

        // Clear validation states
        function clearValidationStates() {
            const inputs = document.querySelectorAll('.form-control, .form-check-input');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });
        }

        // Real-time validation
        const formInputs = document.querySelectorAll('.form-control');
        formInputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateSingleField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateSingleField(this);
                }
                
                // Update password strength
                if (this.id === 'password') {
                    updatePasswordStrength(this.value);
                }
                
                // Validate password confirmation
                if (this.id === 'confirm_password') {
                    const password = document.getElementById('password');
                    if (this.value && password.value) {
                        if (this.value === password.value) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    }
                }
            });
        });

        // Single field validation
        function validateSingleField(field) {
            const value = field.value.trim();
            
            if (field.hasAttribute('required') && !value) {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                return false;
            }
            
            if (field.type === 'email' && value) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(value)) {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    return false;
                }
            }
            
            if (field.id === 'password' && value && value.length < 6) {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                return false;
            }
            
            if (value || field.id === 'password') {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
            
            return true;
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
                // No class added
            } else if (strength <= 1) {
                strengthIndicator.classList.add('weak');
            } else if (strength <= 2) {
                strengthIndicator.classList.add('medium');
            } else {
                strengthIndicator.classList.add('strong');
            }
        }

        // Show alert messages
        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="fas ${icon} me-2"></i>
                    ${message}
                </div>
            `;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        // Terms and privacy links
        document.getElementById('termsLink').addEventListener('click', function(e) {
            e.preventDefault();
            showAlert('Halaman Syarat & Ketentuan akan segera tersedia.', 'info');
        });

        document.getElementById('privacyLink').addEventListener('click', function(e) {
            e.preventDefault();
            showAlert('Halaman Kebijakan Privasi akan segera tersedia.', 'info');
        });

        // Parallax effect for floating elements
        window.addEventListener('mousemove', (e) => {
            const floatingElements = document.querySelectorAll('.floating-element');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            floatingElements.forEach((element, index) => {
                const speed = (index + 1) * 0.3;
                const x = (mouseX - 0.5) * speed * 15;
                const y = (mouseY - 0.5) * speed * 15;
                
                element.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Auto focus on first input
        window.addEventListener('load', () => {
            document.getElementById('nama').focus();
        });

        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement.type !== 'submit' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const form = document.getElementById('registerForm');
                const inputs = Array.from(form.querySelectorAll('input:not([type="checkbox"]), textarea, select'));
                const currentIndex = inputs.indexOf(document.activeElement);
                
                if (currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    document.querySelector('.btn-register').focus();
                }
            }
        });

        // Input animations
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Phone number formatting
        document.getElementById('no_telepon').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to Indonesian phone number format
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            // Format the number
            if (value.startsWith('62')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                // Keep as is for local format
            } else if (value.length > 0) {
                value = '0' + value;
            }
            
            e.target.value = value;
        });

        // Smooth scroll to error fields
        function scrollToFirstError() {
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                firstError.focus();
            }
        }

        // Add loading overlay
        function showLoadingOverlay() {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.innerHTML = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    backdrop-filter: blur(5px);
                ">
                    <div style="text-align: center;">
                        <div style="
                            width: 50px;
                            height: 50px;
                            border: 4px solid #e5e7eb;
                            border-top: 4px solid var(--primary-green);
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                            margin: 0 auto 1rem;
                        "></div>
                        <p style="color: var(--dark-green); font-weight: 600;">Memproses registrasi...</p>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Enhanced form submission with better UX
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                showLoadingOverlay();
                
                // Simulate API call
                setTimeout(() => {
                    hideLoadingOverlay();
                    showAlert('🎉 Registrasi berhasil! Email verifikasi telah dikirim ke ' + document.getElementById('email').value, 'success');
                    
                    // Reset form after successful registration
                    setTimeout(() => {
                        document.getElementById('registerForm').reset();
                        updatePasswordStrength('');
                        clearValidationStates();
                        document.getElementById('nama').focus();
                    }, 2000);
                }, 2500);
            } else {
                scrollToFirstError();
                showAlert('⚠️ Mohon lengkapi semua field yang diperlukan dengan benar.', 'danger');
            }
        });

        // Add success animation
        function showSuccessAnimation() {
            const card = document.querySelector('.register-card');
            card.style.transform = 'scale(1.02)';
            card.style.boxShadow = '0 20px 60px rgba(34, 197, 94, 0.2)';
            
            setTimeout(() => {
                card.style.transform = 'scale(1)';
                card.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.08)';
            }, 300);
        }

        // Add input character counter for text areas
        document.getElementById('alamat').addEventListener('input', function() {
            const maxLength = 200;
            const currentLength = this.value.length;
            
            let counter = document.getElementById('alamatCounter');
            if (!counter) {
                counter = document.createElement('small');
                counter.id = 'alamatCounter';
                counter.className = 'text-muted';
                counter.style.float = 'right';
                this.parentElement.appendChild(counter);
            }
            
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.style.color = currentLength > maxLength ? '#ef4444' : '#6b7280';
            
            if (currentLength > maxLength) {
                this.classList.add('is-invalid');
            } else if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });

        // Add tooltip for password requirements
        const passwordTooltip = document.createElement('div');
        passwordTooltip.innerHTML = `
            <div style="
                position: absolute;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0.75rem;
                font-size: 0.8rem;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                z-index: 1000;
                display: none;
                width: 200px;
            " id="passwordTooltip">
                <strong>Password harus memiliki:</strong>
                <ul style="margin: 0.5rem 0 0 0; padding-left: 1rem;">
                    <li>Minimal 6 karakter</li>
                    <li>Kombinasi huruf & angka (disarankan)</li>
                    <li>Karakter khusus (opsional)</li>
                </ul>
            </div>
        `;

        document.getElementById('password').parentElement.appendChild(passwordTooltip);

        document.getElementById('password').addEventListener('focus', function() {
            const tooltip = document.getElementById('passwordTooltip');
            tooltip.style.display = 'block';
            tooltip.style.left = '0';
            tooltip.style.top = '100%';
            tooltip.style.marginTop = '0.5rem';
        });

        document.getElementById('password').addEventListener('blur', function() {
            setTimeout(() => {
                const tooltip = document.getElementById('passwordTooltip');
                tooltip.style.display = 'none';
            }, 200);
        });

        // Add email validation with better feedback
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && emailPattern.test(email)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                
                // Check if email already exists (simulation)
                setTimeout(() => {
                    if (Math.random() > 0.8) { // 20% chance of "already exists"
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                        this.nextElementSibling.textContent = 'Email ini sudah terdaftar.';
                    }
                }, 1000);
            }
        });

        // Add confetti effect for successful registration
        function showConfetti() {
            const colors = ['#22c55e', '#16a34a', '#15803d', '#166534'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    width: 8px;
                    height: 8px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    left: ${Math.random() * 100}vw;
                    top: -10px;
                    z-index: 10000;
                    pointer-events: none;
                    border-radius: 50%;
                    animation: confettiFall 3s linear forwards;
                `;
                
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 3000);
            }
        }

        // Add confetti animation to CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confettiFall {
                to {
                    transform: translateY(100vh) rotate(720deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus first input
            document.getElementById('nama').focus();
            
            // Add subtle entrance animation
            document.querySelector('.register-container').style.opacity = '0';
            document.querySelector('.register-container').style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                document.querySelector('.register-container').style.opacity = '1';
                document.querySelector('.register-container').style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>