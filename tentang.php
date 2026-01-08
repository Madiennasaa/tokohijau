<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Toko Hijau</title>
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
                        url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; right: 15%; animation-delay: 2s; }
        .floating-element:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 4s; }

        /* Section Styles */
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-1);
            border-radius: 2px;
        }

        .section-title.center::after {
            left: 50%;
            transform: translateX(-50%);
        }

        /* History Section */
        .history-section {
            padding: 6rem 0;
            background: white;
        }

        .history-image {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transform: rotate(-2deg);
            transition: all 0.3s ease;
        }

        .history-image:hover {
            transform: rotate(0deg) scale(1.02);
        }

        /* Vision Mission Section */
        .vision-mission-section {
            padding: 6rem 0;
            background: var(--gradient-2);
        }

        .vision-mission-card {
            background: white;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .vision-mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .vision-mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .vision-mission-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }

        .mission-list {
            list-style: none;
            padding: 0;
        }

        .mission-item {
            padding: 0.8rem 0;
            display: flex;
            align-items: flex-start;
            font-size: 1.1rem;
            color: #4b5563;
        }

        .mission-icon {
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
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        /* Team Section */
        .team-section {
            padding: 6rem 0;
            background: white;
        }

        .team-card {
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

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .team-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1.5rem;
            border: 5px solid var(--light-green);
            transition: all 0.3s ease;
        }

        .team-card:hover .team-img {
            transform: scale(1.05);
            border-color: var(--primary-green);
        }

        .team-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .team-role {
            color: var(--primary-green);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .team-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.6;
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
            
            .section-title {
                font-size: 2rem;
            }

            .vision-mission-card {
                padding: 2rem 1.5rem;
            }

            .team-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-seedling me-2"></i>Toko Hijau
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lihat_produk.php"><i class="fas fa-store me-1"></i> Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tentang.php"><i class="fas fa-info-circle me-1"></i> Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kontak.php"><i class="fas fa-phone me-1"></i> Kontak</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="login.php" class="btn btn-login">
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
            <i class="fas fa-users text-white opacity-25" style="font-size: 2.5rem;"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-heart text-white opacity-25" style="font-size: 3.5rem;"></i>
        </div>
        
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center hero-content">
                    <h1 class="hero-title">Tentang Toko Hijau</h1>
                    <p class="hero-subtitle">Mengenal lebih dekat visi, misi, perjalanan, dan tim yang berkomitmen mewujudkan pertanian berkelanjutan di Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <!-- History Section -->
    <section class="history-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="section-title">Sejarah Kami</h2>
                    <p class="text-muted mb-4">Toko Hijau didirikan pada tahun 2018 dengan misi sederhana namun kuat: membuat produk alami dan ramah lingkungan lebih mudah diakses oleh masyarakat.</p>
                    <p class="text-muted mb-4">Awalnya hanya sebuah toko kecil di pinggiran kota, kini kami telah berkembang menjadi platform e-commerce terpercaya dengan ribuan pelanggan setia di seluruh Indonesia.</p>
                    <p class="text-muted mb-4">Perjalanan kami tidak selalu mulus, tetapi komitmen kami untuk menyediakan produk berkualitas yang baik untuk konsumen dan baik untuk bumi tidak pernah goyah. Setiap langkah yang kami ambil selalu mengutamakan keberlanjutan dan dampak positif bagi lingkungan.</p>
                    <p class="text-muted">Dari 3 produk awal, kini kami memiliki lebih dari 500 produk pertanian berkualitas yang mendukung petani lokal dan praktik pertanian berkelanjutan.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://cdn.vectorstock.com/i/500p/51/42/letter-s-in-green-shop-logo-and-cloud-symbol-vector-26785142.jpg" 
                         alt="Sejarah Toko Hijau" class="img-fluid history-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Mission Section -->
    <section class="vision-mission-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title center">Visi & Misi Kami</h2>
                    <p class="lead text-muted">Komitmen kami untuk menciptakan masa depan pertanian yang berkelanjutan</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="vision-mission-card">
                        <div class="vision-mission-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-4">Visi</h3>
                        <p class="lead text-muted">Menjadi toko alat dan bahan pertanian terdepan yang mendukung pertanian modern, berkelanjutan, dan ramah lingkungan demi meningkatkan produktivitas dan kesejahteraan petani Indonesia.</p>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="vision-mission-card">
                        <div class="vision-mission-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-4">Misi</h3>
                        <ul class="mission-list">
                            <li class="mission-item">
                                <span class="mission-icon"><i class="fas fa-check"></i></span>
                                Menyediakan Produk Berkualitas – Menjual alat, pupuk, benih, dan bahan pertanian unggulan dengan kualitas terbaik untuk mendukung hasil pertanian yang optimal.
                            </li>
                            <li class="mission-item">
                                <span class="mission-icon"><i class="fas fa-check"></i></span>
                                Mendorong Pertanian Berkelanjutan – Mempromosikan penggunaan produk ramah lingkungan dan praktik pertanian yang berkelanjutan untuk menjaga kelestarian alam.
                            </li>
                            <li class="mission-item">
                                <span class="mission-icon"><i class="fas fa-check"></i></span>
                                Edukasi dan Inovasi – Menyediakan informasi, pelatihan, dan teknologi terbaru di bidang pertanian untuk meningkatkan pengetahuan dan keterampilan petani.
                            </li>
                            <li class="mission-item">
                                <span class="mission-icon"><i class="fas fa-check"></i></span>
                                Harga Kompetitif – Menawarkan harga terjangkau dengan kualitas terjamin agar petani dapat mengakses produk dengan mudah.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title center">Tim Kami</h2>
                    <p class="lead text-muted">Bertemu dengan orang-orang berdedikasi yang menggerakkan misi Toko Hijau</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <img src="assets/sapwon.jpg" alt="Shofwan Ali Santosa" class="team-img">
                        <h4 class="team-name">Shofwan Ali Santosa</h4>
                        <p class="team-role">Founder & CEO</p>
                        <p class="team-description">Visioner di balik Toko Hijau dengan passion mendalam terhadap pertanian berkelanjutan. Wajahnya memancarkan ketenangan alami, dengan sorot mata tajam namun berbinar seperti orang yang selalu terinspirasi oleh alam. Memimpin dengan kepedulian terhadap lingkungan dan komitmen pada kualitas produk.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <img src="assets/akpa.jpg" alt="Akfa Ulin Nuha" class="team-img">
                        <h4 class="team-name">Akfa Ulin Nuha</h4>
                        <p class="team-role">Head of Operations</p>
                        <p class="team-description">Ahli dalam mengelola operasional dengan precision tinggi. Memiliki raut wajah cekatan dan detail, selalu memastikan setiap pengiriman produk ramah lingkungan sampai tepat waktu. Bertanggung jawab atas efisiensi logistik dan kualitas pelayanan pelanggan yang luar biasa.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <img src="assets/pakmi.png" alt="Fahmi Fikanjati" class="team-img">
                        <h4 class="team-name">Fahmi Fikanjati</h4>
                        <p class="team-role">Product Manager</p>
                        <p class="team-description">Inovator produk yang tak kenal lelah mencari dan mengembangkan produk pertanian terbaik. Wajahnya bersahabat dan penuh rasa ingin tahu, dengan antusiasme tinggi dalam memastikan setiap produk memenuhi standar kualitas tertinggi dan ramah lingkungan.</p>
                    </div>
                </div>
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
                    <p class="text-white">Menyediakan produk-produk peralatan dan kebutuhan dalam bertani yang berkualitas, ramah lingkungan, dan mendukung pertanian berkelanjutan di Indonesia.</p>
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
                    <a href="index.php" class="footer-link">Beranda</a>
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
        document.querySelectorAll('.vision-mission-card, .team-card, .history-image').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            observer.observe(el);
        });

        // Parallax effect untuk floating elements
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating-element');
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.5 + (index * 0.1);
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    </script>
</body>
</html>