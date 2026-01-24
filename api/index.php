<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Hijau - Beranda</title>
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
            overflow-x: hidden;
        }

        /* Navbar Modern */
        .navbar-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: #374151 !important;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.2rem;
        }

        .nav-link:hover {
            color: var(--primary-green) !important;
            background: var(--light-green);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: var(--primary-green) !important;
            background: var(--light-green);
        }

        .btn-login {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(155, 244, 188, 0.9) 0%, rgba(93, 133, 95, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1560493676-04071c5f467b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding-top: 80px; /* Space for fixed navbar */
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(2rem, 6vw, 3.5rem); /* Responsive font size */
            font-weight: 800;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease-out;
            line-height: 1.2;
        }
        .hero-subtitle {
            font-size: clamp(1rem, 3vw, 1.3rem); /* Responsive font size */
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.2s both;
            line-height: 1.5;
        }

        .hero-cta {
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .btn-hero {
            background: white;
            color: var(--primary-green);
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem 2.5rem;
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            margin: 0.5rem;
            transition: all 0.3s ease-out;
        }

        .btn-hero:hover {
            background: var(--dark-green);
            color: var(--light-green);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 1rem 2.5rem;
            border-radius: 15px;
            margin: 0.5rem;
            transition: all 0.3s ease-out; /* Tambahkan ini */
        }

        .btn-hero, .btn-hero-outline {
            font-weight: 600;
            padding: 0.8rem 1.8rem;
            border-radius: 15px;
            margin: 0.5rem;
            transition: all 0.3s ease-out;
            font-size: clamp(0.9rem, 2vw, 1.1rem); /* Responsive font size */
            white-space: nowrap;
        }

        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-green);
            transform: translateY(-3px);
        }
        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            display: none; /* Hide on small screens */
        }

        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; right: 15%; animation-delay: 2s; }
        .floating-element:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 4s; }

        /* About Section */
        .about-section {
            padding: 6rem 0;
            background: white;
            position: relative;
        }

        .about-image {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transform: rotate(-2deg);
            transition: all 0.3s ease;
        }

        .about-image:hover {
            transform: rotate(0deg) scale(1.02);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block; /* Tambahkan ini */
            text-align: center; /* Tambahkan ini */
            width: 100%; /* Tambahkan ini */
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%; /* Ubah dari left: 0 ke left: 50% */
            transform: translateX(-50%); /* Tambahkan ini untuk menengahkan */
            width: 60px;
            height: 4px;
            background: var(--gradient-1);
            border-radius: 2px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-item {
            padding: 0.8rem 0;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            color: #4b5563;
        }

        .feature-icon {
            width: 24px;
            height: 24px;
            background: var(--gradient-1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 0.8rem;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: var(--gradient-2);
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .feature-card-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }

        .feature-card h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: var(--gradient-1);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="25" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="25" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        /* Footer */
        .footer-modern {
            background: #111827;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-link {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            padding: 0.3rem 0;
        }

        .footer-link:hover {
            color: var(--primary-green);
            transform: translateX(5px);
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
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .btn-hero, .btn-hero-outline {
                font-size: 1rem;
                padding: 0.8rem 2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }

            .floating-element {
                display: block; /* Show on larger screens */
            }
            
            .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
            .floating-element:nth-child(2) { top: 60%; right: 15%; animation-delay: 2s; }
            .floating-element:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 4s; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-seedling me-2"></i>Toko Hijau
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="/">
                            <i class="fas fa-home me-1"></i> Beranda
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/lihat_produk">
                            <i class="fas fa-store me-1"></i> Produk
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/tentang">
                            <i class="fas fa-info-circle me-1"></i> Tentang
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/kontak">
                            <i class="fas fa-phone me-1"></i> Kontak
                        </a>
                    </li>

                    <li class="nav-item ms-3">
                        <a href="/login" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-1"></i> Masuk
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-element">
            <i class="fas fa-leaf text-white opacity-25" style="font-size: 3rem;"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-seedling text-white opacity-25" style="font-size: 2.5rem;"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-tree text-white opacity-25" style="font-size: 3.5rem;"></i>
        </div>
        
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 col-11 text-center hero-content">
                    <h1 class="hero-title">Selamat Datang di<br>Toko Hijau</h1>
                    <p class="hero-subtitle">Platform terpercaya untuk produk pertanian berkualitas, ramah lingkungan, dan mendukung pertanian berkelanjutan</p>
                    <div class="hero-cta d-flex flex-wrap justify-content-center">
                        <a href="lihat_produk.php" class="btn btn-hero m-2">
                            <i class="fas fa-shopping-bag me-2"></i>Jelajahi Produk
                        </a>
                        <a href="tentang.php" class="btn btn-hero-outline m-2">
                            <i class="fas fa-info-circle me-2"></i>Pelajari Lebih
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="section-title">Tentang Toko Hijau</h2>
                    <p class="text-muted mb-4">Toko Hijau adalah platform e-commerce yang berkomitmen menyediakan produk pertanian berkualitas tinggi dengan fokus pada keberlanjutan dan ramah lingkungan.</p>
                    <p class="text-muted mb-4">Kami hadir untuk mendukung para petani dan penggiat pertanian dalam mewujudkan hasil panen optimal sambil menjaga kelestarian alam untuk generasi mendatang.</p>
                    
                    <ul class="feature-list">
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-leaf"></i></span>
                            Produk 100% alami dan berkualitas
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-recycle"></i></span>
                            Kemasan ramah lingkungan
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-handshake"></i></span>
                            Mendukung petani lokal
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-tag"></i></span>
                            Harga kompetitif dan terjangkau
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <img src="https://mediatani.co/wp-content/uploads/2015/01/sapta-usaha-tani-kesejahteraan-petani.jpg" 
                         alt="Pertanian Modern" class="img-fluid about-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row mb-4"> <!-- Mengubah mb-5 menjadi mb-4 untuk jarak yang lebih rapat -->
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title mb-3">Mengapa Memilih Kami?</h2> <!-- Menambahkan mb-3 untuk jarak judul dan paragraf -->
                    <p class="lead text-muted mb-0">Komitmen kami terhadap kualitas, keberlanjutan, dan kepuasan pelanggan menjadikan Toko Hijau pilihan terbaik</p> <!-- Menambahkan mb-0 untuk menghilangkan margin bawah -->
                </div>
            </div>
            
            <div class="row g-4 mt-3"> <!-- Menambahkan mt-3 untuk jarak dari judul section -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4>Ramah Lingkungan</h4>
                        <p class="text-muted mb-0">Setiap produk dipilih dengan pertimbangan dampak lingkungan minimal dan mendukung praktik pertanian berkelanjutan.</p> <!-- Menambahkan mb-0 -->
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4>Kualitas Terbaik</h4>
                        <p class="text-muted mb-0">Kami hanya menyediakan produk berkualitas tinggi dari sumber terpercaya dan telah teruji kualitasnya.</p> <!-- Menambahkan mb-0 -->
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4>Pengiriman Cepat</h4>
                        <p class="text-muted mb-0">Sistem logistik yang efisien dengan kemasan ramah lingkungan untuk memastikan produk sampai dengan aman.</p> <!-- Menambahkan mb-0 -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="display-5 fw-bold mb-4">Mulai Berbelanja Sekarang</h2>
                <p class="lead mb-5">Temukan berbagai produk pertanian berkualitas yang telah kami sediakan khusus untuk mendukung aktivitas pertanian Anda</p>
                <a href="lihat_produk.php" class="btn btn-hero btn-lg">
                    <i class="fas fa-arrow-right me-2"></i>Jelajahi Produk Kami
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="footer-brand mb-3">
                        <i class="fas fa-seedling me-2"></i>Toko Hijau
                    </h5>
                    <p class="text-white">Platform terpercaya untuk produk pertanian berkualitas, ramah lingkungan, dan mendukung pertanian berkelanjutan di Indonesia.</p>
                    <div class="mt-4">
                        <a href="#" class="footer-link d-inline-block me-3">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="footer-link d-inline-block me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="footer-link d-inline-block me-3">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-white fw-bold mb-3">Menu</h6>
                    <a href="#" class="footer-link">Beranda</a>
                    <a href="lihat_produk.php" class="footer-link">Produk</a>
                    <a href="tentang.php" class="footer-link">Tentang</a>
                    <a href="kontak.php" class="footer-link">Kontak</a>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-white fw-bold mb-3">Produk</h6>
                    <a href="#" class="footer-link">Alat Pertanian</a>
                    <a href="#" class="footer-link">Pupuk Organik</a>
                    <a href="#" class="footer-link">Benih Unggul</a>
                    <a href="#" class="footer-link">Pestisida Alami</a>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h6 class="text-white fw-bold mb-3">Kontak</h6>
                    <div class="footer-link">
                        <i class="fas fa-envelope me-2"></i>
                        info@tokohijau.com
                    </div>
                    <div class="footer-link">
                        <i class="fas fa-phone me-2"></i>
                        +62 123 4567 890
                    </div>
                    <div class="footer-link">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Jakarta, Indonesia
                    </div>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: #374151;">
            <div class="text-center">
                <p class="text-white mb-0">&copy; 2024 Toko Hijau. Semua hak dilindungi undang-undang.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-modern');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Intersection Observer untuk animasi
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elemen yang ingin dianimasi
        document.querySelectorAll('.feature-card, .about-section img').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            observer.observe(el);
        });
    </script>
</body>
</html>