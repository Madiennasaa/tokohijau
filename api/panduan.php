<?php
include 'koneksi.php';

// Tidak perlu cek login karena bisa diakses guest
$is_admin = isset($_SESSION['user_id']) ? isAdmin() : false;

// Ambil data user jika sudah login
if(isset($_SESSION['user_id']) && !$is_admin) {
    $user_data = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = ".$_SESSION['user_id'])->fetch_assoc();
    $nama_user = $user_data['nama'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Pertanian - Toko Hijau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #22c55e;
            --dark-green: #16a34a;
            --light-green: #dcfce7;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-modern {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .navbar-brand {
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .guide-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        
        .guide-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(34, 197, 94, 0.1);
        }
        
        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.15);
        }
        
        .guide-card-header {
            background: var(--light-green);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            color: var(--dark-green);
            font-weight: 600;
        }
        
        .guide-card-body {
            padding: 1.5rem;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: var(--primary-green);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .step-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed #eee;
        }
        
        .step-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--light-green);
            color: var(--dark-green);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .planting-tips {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-green);
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .seasonal-calendar {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .season-month {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .month-name {
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 0.5rem;
        }
        
        .month-plants {
            font-size: 0.9rem;
        }
        
        /* Updated Footer Styles */
        .footer-modern {
            background: #111827;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
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

        .footer-brand-gradient {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Back button style */
        .btn-back {
            background-color: var(--primary-green);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background-color: var(--dark-green);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-seedling me-2"></i>Toko Hijau
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> Kembali ke Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="detail_kalender.php"><i class="fas fa-calendar-alt me-1"></i> Pergi ke Kalender</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($nama_user); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <header class="guide-header" style="margin-top: 55px;">
        <div class="container text-center">
            <h1><i class="fas fa-seedling me-3"></i>Panduan Pertanian</h1>
            <p class="lead">Tips dan trik bercocok tanam untuk hasil optimal</p>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Panduan Umum Pertanian -->
                <div class="guide-card">
                    <div class="guide-card-header">
                        <i class="fas fa-calendar-alt me-2"></i> Kalender Tanam Musiman
                    </div>
                    <div class="guide-card-body">
                        <p>Berikut adalah panduan tanaman yang cocok ditanam setiap bulannya:</p>
                        
                        <div class="seasonal-calendar">
                            <?php
                            $bulan_tanam = [
                                'Januari' => 'Kangkung, Bayam, Sawi, Selada, Lobak',
                                'Februari' => 'Kacang Panjang, Terong, Timun, Tomat, Buncis',
                                'Maret' => 'Cabai, Paprika, Kailan, Pakcoy, Seledri',
                                'April' => 'Jagung, Kedelai, Kacang Tanah, Kacang Hijau',
                                'Mei' => 'Mentimun, Oyong, Pare, Labu Siam',
                                'Juni' => 'Ubi Kayu, Pepaya, Pisang, Cabai Rawit, Cabai Besar',
                                'Juli' => 'Padi, Jagung, Kedelai, Kacang Tanah',
                                'Agustus' => 'Bawang Merah, Bawang Putih, Bawang Daun',
                                'September' => 'Wortel, Kentang, Kubis, Brokoli, Kembang Kol',
                                'Oktober' => 'Selada, Bayam, Kangkung, Sawi, Pakcoy',
                                'November' => 'Kacang Panjang, Buncis, Terong, Timun',
                                'Desember' => 'Cabai, Tomat, Paprika, Kailan'
                            ];
                            
                            foreach ($bulan_tanam as $bulan => $tanaman): ?>
                                <div class="season-month">
                                    <div class="month-name"><?= $bulan ?></div>
                                    <div class="month-plants"><?= $tanaman ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tips Perawatan Tanaman -->
                <div class="guide-card">
                    <div class="guide-card-header">
                        <i class="fas fa-lightbulb me-2"></i> Tips Perawatan Tanaman
                    </div>
                    <div class="guide-card-body">
                        <div class="step-item">
                            <span class="step-number">1</span>
                            <strong>Penyiraman yang Tepat</strong>
                            <div class="planting-tips">
                                <p>Siram tanaman di pagi hari sebelum jam 9 atau sore hari setelah jam 4. Hindari menyiram saat matahari terik karena dapat menyebabkan penguapan cepat dan daun terbakar.</p>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <span class="step-number">2</span>
                            <strong>Pemupukan Berkala</strong>
                            <div class="planting-tips">
                                <p>Gunakan pupuk organik setiap 2 minggu sekali. Untuk tanaman buah, berikan pupuk dengan kandungan fosfor dan kalium tinggi saat mulai berbunga.</p>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <span class="step-number">3</span>
                            <strong>Pengendalian Hama</strong>
                            <div class="planting-tips">
                                <p>Gunakan pestisida alami seperti larutan bawang putih atau daun mimba. Untuk hama seperti ulat, bisa dibuang manual di pagi hari.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Panduan Cepat -->
                <div class="guide-card">
                    <div class="guide-card-header">
                        <i class="fas fa-clock me-2"></i> Panduan Cepat
                    </div>
                    <div class="guide-card-body">
                        <div class="row text-center">
                            <div class="col-md-6 col-6 mb-4">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <h5>Penyemaian</h5>
                                <p class="small">Gunakan media semai steril, jaga kelembaban</p>
                            </div>
                            <div class="col-md-6 col-6 mb-4">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <h5>Pengairan</h5>
                                <p class="small">Sesuaikan dengan jenis tanaman</p>
                            </div>
                            <div class="col-md-6 col-6 mb-4">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-sun"></i>
                                </div>
                                <h5>Sinar Matahari</h5>
                                <p class="small">4-8 jam sehari untuk kebanyakan tanaman</p>
                            </div>
                            <div class="col-md-6 col-6 mb-4">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <h5>Hama</h5>
                                <p class="small">Deteksi dini untuk penanganan tepat</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Video Tutorial -->
                <div class="guide-card">
                    <div class="guide-card-header">
                        <i class="fas fa-video me-2"></i> Video Tutorial
                    </div>
                    <div class="guide-card-body">
                        <div class="ratio ratio-16x9 mb-3">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID" allowfullscreen></iframe>
                        </div>
                        <h5>Teknik Menanam Cabai</h5>
                        <p class="small">Pelajari cara menanam cabai dari penyemaian hingga panen</p>
                        
                        <div class="ratio ratio-16x9 mt-3">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID" allowfullscreen></iframe>
                        </div>
                        <h5 class="mt-3">Pupuk Organik DIY</h5>
                        <p class="small">Cara membuat pupuk organik dari bahan dapur</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="guide-card">
                    <div class="guide-card-header">
                        <i class="fas fa-question-circle me-2"></i> FAQ Pertanian
                    </div>
                    <div class="guide-card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        Kapan waktu terbaik untuk menanam sayuran?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Waktu terbaik adalah awal musim hujan atau akhir musim kemarau. Untuk sayuran daun seperti kangkung dan bayam bisa ditanam sepanjang tahun asal pengairan cukup.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        Bagaimana cara mengatasi hama pada tanaman organik?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Gunakan pestisida alami seperti larutan bawang putih, daun mimba, atau sabun cair organik. Rotasi tanaman juga membantu memutus siklus hidup hama.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                        Berapa lama waktu yang dibutuhkan dari tanam hingga panen?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Tergantung jenis tanaman: Sayuran daun (20-30 hari), Cabai (2-3 bulan), Tomat (2.5-3 bulan), Padi (3-4 bulan). Waktu bisa bervariasi tergantung varietas dan perawatan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="footer-brand mb-3 footer-brand-gradient">
                        <i class="fas fa-seedling me-2 footer-brand-gradient"></i>Toko Hijau
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
                    <a href="dashboard.php" class="footer-link">Beranda</a>
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
</body>
</html>