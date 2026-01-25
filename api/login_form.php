<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Hijau</title>
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
            top: 60%; 
            right: 15%; 
            animation-delay: 2s; 
            font-size: 1.2rem;
            color: var(--dark-green);
        }
        .floating-element:nth-child(6) { 
            bottom: 20%; 
            left: 20%; 
            animation-delay: 4s; 
            font-size: 1.8rem;
            color: var(--primary-green);
        }

        /* Main Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 0 1rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.12);
        }

        /* Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .brand-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.3rem;
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
            animation: pulse 2s infinite;
        }

        .brand-title {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 0.6rem 0.9rem;
            margin-bottom: 0.9rem;
            font-weight: 500;
            font-size: 0.85rem;
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
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }

        .form-label i {
            margin-right: 0.4rem;
            color: var(--primary-green);
            font-size: 0.8rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.5rem 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.25);
            background: white;
            transform: translateY(-1px);
        }

        .form-control:hover {
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
            font-size: 0.85rem;
        }

        .mb-3 {
            margin-bottom: 0.9rem !important;
        }

        .mb-4 {
            margin-bottom: 1rem !important;
        }

        /* Button Styles */
        .btn-login {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.6rem 1.75rem;
            border-radius: 12px;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            color: white;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Links */
        .login-links {
            text-align: center;
            margin-top: 1rem;
        }

        .login-links a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.85rem;
        }

        .login-links a:hover {
            color: var(--dark-green);
        }

        .login-links a::after {
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

        .login-links a:hover::after {
            width: 100%;
        }

        .text-muted {
            color: #6b7280 !important;
            font-size: 0.85rem;
        }

        .divider {
            padding: 0 0 8px 0;
            font-size: 0.85rem;
        }

        .mb-2 {
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
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                padding: 1.25rem 1.5rem;
            }
            
            .brand-title {
                font-size: 1.3rem;
            }

            .floating-element {
                display: none;
            }

            .bg-decoration {
                display: none;
            }
        }

        @media (max-height: 700px) {
            .brand-icon {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
            
            .brand-title {
                font-size: 1.3rem;
            }
            
            .login-header {
                margin-bottom: 1rem;
            }
            
            .form-control {
                padding: 0.45rem 0.75rem;
            }
            
            .btn-login {
                padding: 0.55rem 1.5rem;
            }

            .mb-3 {
                margin-bottom: 0.75rem !important;
            }
        }

        /* Page Load Animation */
        .login-container {
            animation: fadeInUp 0.8s ease-out;
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

    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="brand-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h2 class="brand-title">Toko Hijau</h2>
                <p class="brand-subtitle">Masuk ke akun Anda untuk melanjutkan</p>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Login Form -->
            <form action="login.php" method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Masukkan email Anda">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password Anda">
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Ingat saya selama 30 hari
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Masuk ke Akun
                </button>
            </form>
            
            <!-- Links -->
            <div class="login-links">
                <div class="mb-2">
                    <a href="#" id="forgotPassword">Lupa password?</a>
                </div>
                
                <div class="divider">
                    <span>atau</span>
                </div>
                
                <p class="mb-2">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                <a href="/" class="text-muted">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simulate alerts for demo
        const urlParams = new URLSearchParams(window.location.search);
        const alertContainer = document.getElementById('alertContainer');
        
        if (urlParams.get('error')) {
            let message = 'Terjadi kesalahan!';
            switch(urlParams.get('error')) {
                case 'invalid':
                    message = 'Email atau password salah!';
                    break;
                case 'empty':
                    message = 'Semua field harus diisi!';
                    break;
                case 'inactive':
                    message = 'Akun Anda tidak aktif!';
                    break;
            }
            alertContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }
        
        if (urlParams.get('success')) {
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Registrasi berhasil! Silakan login.
                </div>
            `;
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Masuk...';
            submitBtn.classList.add('loading');
        });

        // Enhanced form validation
        const formInputs = document.querySelectorAll('.form-control');
        formInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(239, 68, 68, 0.25)';
                } else if (this.value.trim()) {
                    this.style.borderColor = '#22c55e';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(34, 197, 94, 0.25)';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(239, 68, 68)') {
                    this.style.borderColor = '#e5e7eb';
                    this.style.boxShadow = '';
                }
            });
        });

        // Forgot password handler
        document.getElementById('forgotPassword').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Fitur lupa password akan segera tersedia. Silakan hubungi admin untuk bantuan.');
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
            document.getElementById('email').focus();
        });

        // Keyboard navigation enhancement
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement.type !== 'submit') {
                e.preventDefault();
                const form = document.getElementById('loginForm');
                const inputs = Array.from(form.querySelectorAll('input[type="email"], input[type="password"]'));
                const currentIndex = inputs.indexOf(document.activeElement);
                
                if (currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    form.querySelector('button[type="submit"]').click();
                }
            }
        });
    </script>
</body>
</html>