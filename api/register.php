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
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0;
            padding: 0;
        }

        /* Background Decorations */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }

        .bg-decoration:nth-child(1) {
            width: 200px;
            height: 200px;
            background: var(--gradient-1);
            top: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
        }

        .bg-decoration:nth-child(2) {
            width: 150px;
            height: 150px;
            background: var(--gradient-1);
            bottom: -75px;
            right: -75px;
            animation: float 6s ease-in-out infinite reverse;
        }

        .bg-decoration:nth-child(3) {
            width: 100px;
            height: 100px;
            background: var(--gradient-1);
            top: 30%;
            right: 5%;
            animation: float 10s ease-in-out infinite;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            opacity: 0.3;
        }

        .floating-element:nth-child(4) { 
            top: 10%; 
            left: 10%; 
            animation-delay: 0s; 
            font-size: 1.5rem;
            color: var(--primary-green);
        }
        .floating-element:nth-child(5) { 
            top: 70%; 
            right: 15%; 
            animation-delay: 2s; 
            font-size: 1.2rem;
            color: var(--dark-green);
        }
        .floating-element:nth-child(6) { 
            bottom: 15%; 
            left: 20%; 
            animation-delay: 4s; 
            font-size: 1.8rem;
            color: var(--primary-green);
        }

        /* Main Register Container */
        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 520px;
            padding: 0 1rem;
            max-height: 100vh;
            overflow-y: auto;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            position: relative;
            margin: 1rem 0;
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
            transform: translateY(-3px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
        }

        /* Header Section */
        .register-header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.6rem;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
            animation: pulse 2s infinite;
        }

        .brand-title {
            font-size: 1.3rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            color: #dc2626;
            border-left: 3px solid #ef4444;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
            color: var(--dark-green);
            border-left: 3px solid var(--primary-green);
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            font-size: 0.8rem;
        }

        .form-label i {
            margin-right: 0.4rem;
            color: var(--primary-green);
            font-size: 0.75rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.45rem 0.75rem;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.15rem rgba(34, 197, 94, 0.2);
            background: white;
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
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.2);
        }

        .form-check-label {
            color: #374151;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .mb-3 {
            margin-bottom: 0.7rem !important;
        }

        .mb-4 {
            margin-bottom: 0.85rem !important;
        }

        /* Password Strength Indicator */
        .password-strength {
            height: 3px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.4rem;
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
            padding: 0.55rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
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
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
            color: white;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        /* Links */
        .register-links {
            text-align: center;
            margin-top: 0.85rem;
        }

        .register-links a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.8rem;
        }

        .register-links a:hover {
            color: var(--dark-green);
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
            font-size: 0.8rem;
        }

        .divider {
            padding: 0 0 6px 0;
            font-size: 0.8rem;
        }

        .mb-2 {
            margin-bottom: 0.4rem !important;
        }

        /* Validation States */
        .form-control.is-valid {
            border-color: var(--primary-green);
            padding-right: 2.25rem;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            padding-right: 2.25rem;
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.2rem;
            font-size: 0.75rem;
            color: #ef4444;
        }

        .is-invalid ~ .invalid-feedback {
            display: block;
        }

        .valid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.2rem;
            font-size: 0.75rem;
            color: var(--primary-green);
        }

        .is-valid ~ .valid-feedback {
            display: block;
        }

        /* Terms and Conditions */
        .terms-text {
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.3;
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

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-card {
                padding: 1rem 1.25rem;
            }
            
            .brand-title {
                font-size: 1.2rem;
            }

            .floating-element, .bg-decoration {
                display: none;
            }
        }

        @media (max-height: 800px) {
            .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
            
            .brand-title {
                font-size: 1.2rem;
            }
            
            .register-header {
                margin-bottom: 0.85rem;
            }
            
            .form-control, .form-select {
                padding: 0.4rem 0.7rem;
            }
            
            .btn-register {
                padding: 0.5rem 1.25rem;
            }

            .mb-3 {
                margin-bottom: 0.6rem !important;
            }
        }

        /* Page Load Animation */
        .register-container {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Scrollbar styling */
        .register-container::-webkit-scrollbar {
            width: 6px;
        }

        .register-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .register-container::-webkit-scrollbar-thumb {
            background: var(--primary-green);
            border-radius: 10px;
        }

        .register-container::-webkit-scrollbar-thumb:hover {
            background: var(--dark-green);
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
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

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2 class="brand-title">Toko Hijau</h2>
                <p class="brand-subtitle">Bergabunglah dengan komunitas kami</p>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Register Form -->
            <form id="registerForm" method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama" class="form-label">
                            <i class="fas fa-user"></i>
                            Nama Lengkap
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama" required placeholder="Nama lengkap">
                        <div class="invalid-feedback">Nama harus diisi.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="nama@email.com">
                        <div class="invalid-feedback">Email harus valid.</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Min. 6 karakter">
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="invalid-feedback">Min. 6 karakter.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Konfirmasi
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Ulangi password">
                        <div class="invalid-feedback">Password tidak sama.</div>
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
                        Alamat
                    </label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label terms-text" for="terms">
                            Saya setuju dengan <a href="#" id="termsLink">Syarat & Ketentuan</a> dan <a href="#" id="privacyLink">Kebijakan Privasi</a>
                        </label>
                        <div class="invalid-feedback">Anda harus menyetujui.</div>
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
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                showLoadingOverlay();
                
                setTimeout(() => {
                    hideLoadingOverlay();
                    showAlert('🎉 Registrasi berhasil! Silakan login.', 'success');
                    
                    setTimeout(() => {
                        this.reset();
                        clearValidationStates();
                    }, 2000);
                }, 1500);
            } else {
                scrollToFirstError();
                showAlert('⚠️ Mohon lengkapi semua field dengan benar.', 'danger');
            }
        });

        function validateForm() {
            let isValid = true;
            clearValidationStates();
            
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
            
            const email = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value && !emailPattern.test(email.value)) {
                email.classList.remove('is-valid');
                email.classList.add('is-invalid');
                isValid = false;
            }
            
            const password = document.getElementById('password');
            if (password.value && password.value.length < 6) {
                password.classList.remove('is-valid');
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value !== password.value) {
                confirmPassword.classList.remove('is-valid');
                confirmPassword.classList.add('is-invalid');
                isValid = false;
            }
            
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                terms.classList.add('is-invalid');
                isValid = false;
            }
            
            return isValid;
        }

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
                
                if (this.id === 'password') {
                    updatePasswordStrength(this.value);
                }
                
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
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        document.getElementById('termsLink').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Halaman Syarat & Ketentuan akan segera tersedia.');
        });

        document.getElementById('privacyLink').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Halaman Kebijakan Privasi akan segera tersedia.');
        });

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

        window.addEventListener('load', () => {
            document.getElementById('nama').focus();
        });

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
                        <p style="color: var(--dark-green); font-weight: 600; font-size: 0.9rem;">Memproses registrasi...</p>
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
    </script>
</body>
</html>