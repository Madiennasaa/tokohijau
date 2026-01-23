<?php
include 'koneksi.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = $user_id")->fetch_assoc();
$nama_user = $user_data['nama'];

// Dapatkan bulan dan tahun dari parameter GET atau gunakan bulan/tahun saat ini
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$currentDay = date('j');

// Validasi bulan
if($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}

// Daftar nama bulan dalam bahasa Indonesia
$bulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Rekomendasi tanaman berdasarkan bulan
$rekomendasiTanaman = [
    1 => 'Kangkung, Bayam, Sawi, Selada, Lobak',
    2 => 'Kacang Panjang, Terong, Timun, Tomat, Buncis',
    3 => 'Cabai, Paprika, Kailan, Pakcoy, Seledri',
    4 => 'Jagung, Kedelai, Kacang Tanah, Kacang Hijau',
    5 => 'Mentimun, Oyong, Pare, Labu Siam',
    6 => 'Ubi Kayu, Pepaya, Pisang, Cabai Rawit, Cabai Besar',
    7 => 'Padi, Jagung, Kedelai, Kacang Tanah',
    8 => 'Bawang Merah, Bawang Putih, Bawang Daun',
    9 => 'Wortel, Kentang, Kubis, Brokoli, Kembang Kol',
    10 => 'Selada, Bayam, Kangkung, Sawi, Pakcoy',
    11 => 'Kacang Panjang, Buncis, Terong, Timun',
    12 => 'Cabai, Tomat, Paprika, Kailan'
];

$monthName = $bulanIndo[$currentMonth];
$tanamanRekomendasi = $rekomendasiTanaman[$currentMonth];

// Hitung jumlah hari dalam bulan ini
$totalDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Dapatkan hari pertama bulan ini (1=Senin, 7=Minggu)
$firstDayOfMonth = date('N', strtotime("$currentYear-$currentMonth-01"));

// URL untuk navigasi bulan
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Tanam - Toko Hijau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #22c55e;
            --dark-green: #16a34a;
            --light-green: #dcfce7;
            --gradient-1: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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
        }

        .nav-link:hover {
            color: var(--primary-green) !important;
        }

        .page-header {
            background: var(--gradient-1);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="25" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="25" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .page-header-content {
            position: relative;
            z-index: 2;
        }

        .calendar-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .calendar-header {
            background: var(--light-green);
            padding: 1.5rem;
            border-bottom: 2px solid var(--primary-green);
        }

        .month-title {
            color: var(--dark-green);
            font-weight: 700;
            margin: 0;
        }

        .calendar-nav {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: #f1f5f9;
            padding: 2px;
        }

        .calendar-day-header {
            background: var(--light-green);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: var(--dark-green);
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            background: white;
            border-radius: 8px;
            padding: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .calendar-day:hover {
            background: #f0fdf4;
        }

        .calendar-day.empty-day {
            background: transparent;
            cursor: default;
        }

       .calendar-day:not(.empty-day):hover {
            background: #f0fdf4 !important;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .calendar-day.has-event:hover {
            background: #e6f7ed !important;
        }

        .day-number {
            font-size: 0.9rem;
            font-weight: 500;
            color: #334155;
        }

        .current-day .day-number {
            color: #16a34a;
            font-weight: 700;
        }

        .current-day {
            border: 2px solid #16a34a;
            background: #f0fdf4;
        }

        .event-indicator {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            margin-top: 2px;
        }

        .has-event .day-number {
            font-weight: 600;
        }

        .event-details-panel {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
            border-radius: 0 0 12px 12px;
        }

        .event-details-header {
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .event-details-content .planting-event {
            margin-bottom: 0.75rem;
        }

        .planting-event {
            background: rgba(34, 197, 94, 0.1);
            border-left: 3px solid var(--primary-green);
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .planting-event .plant-name {
            font-weight: 600;
            color: var(--dark-green);
        }

        .planting-event .plant-desc {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .current-day {
            background: rgba(34, 197, 94, 0.05);
            border: 2px solid var(--primary-green);
        }

        .current-day .day-number {
            color: var(--primary-green);
            font-weight: 700;
        }

        .planting-tips {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }

        .tips-title {
            color: var(--dark-green);
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .tips-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient-1);
        }

        .tips-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .tips-icon {
            background: var(--light-green);
            color: var(--primary-green);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .tips-content h5 {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .tips-content p {
            color: #6b7280;
            margin-bottom: 0;
        }

        .planting-guide {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .guide-title {
            color: var(--dark-green);
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .guide-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient-1);
        }

        .guide-item {
            margin-bottom: 1.5rem;
        }

        .guide-item h5 {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .guide-item p {
            color: #6b7280;
            margin-bottom: 0;
        }

        .footer-modern {
            background: #111827;
            color: white;
            padding: 3rem 0 1.5rem;
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

        @media (max-width: 768px) {
            .calendar-day-header {
                font-size: 0.7rem;
                padding: 0.5rem 0.25rem;
            }
            
            .day-number {
                font-size: 0.8rem;
            }
            
            .month-title {
                font-size: 1.1rem;
            }
            
            .event-details-panel {
                padding: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .calendar-container {
                border-radius: 12px;
            }
            
            .calendar-header {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
                    </li>
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($nama_user); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <header class="page-header" style="margin-top: 60px;">
        <div class="container">
            <div class="page-header-content text-center">
                <h1 class="mb-3">
                    <i class="fas fa-calendar-alt me-3"></i>Kalender Tanam
                </h1>
                <p class="lead mb-0">Panduan waktu tanam optimal untuk berbagai jenis tanaman</p>
            </div>
        </div>
    </header>

     <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="calendar-container">
                    <div class="calendar-header d-flex justify-content-between align-items-center">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-sm btn-outline-success calendar-nav">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <h3 class="month-title mb-0"><?php echo $monthName . ' ' . $currentYear; ?></h3>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-sm btn-outline-success calendar-nav">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    
                    <!-- Mobile-friendly calendar grid -->
                    <div class="calendar-grid">
                        <!-- Day headers - abbreviated for mobile -->
                        <div class="calendar-day-header">Min</div>
                        <div class="calendar-day-header">Sen</div>
                        <div class="calendar-day-header">Sel</div>
                        <div class="calendar-day-header">Rab</div>
                        <div class="calendar-day-header">Kam</div>
                        <div class="calendar-day-header">Jum</div>
                        <div class="calendar-day-header">Sab</div>
                        
                        <?php 
                        // Tampilkan hari kosong di awal bulan
                        for ($i = 1; $i < $firstDayOfMonth; $i++) {
                            echo '<div class="calendar-day empty-day"></div>';
                        }

                        // Tampilkan hari-hari dalam bulan
                        for ($day = 1; $day <= $totalDays; $day++): 
                            // Hanya highlight hari ini jika kita sedang melihat bulan dan tahun saat ini
                            $isCurrentDay = ($day == $currentDay && $currentMonth == date('n') && $currentYear == date('Y'));
                            $hasEvent = ($day >= 5 && $day <= 15) || ($day >= 10 && $day <= 25) || ($day >= 20 && $day <= $totalDays);
                        ?>
                            <div class="calendar-day <?php echo $isCurrentDay ? 'current-day' : ''; ?> <?php echo $hasEvent ? 'has-event' : ''; ?>" 
                                data-day="<?php echo $day; ?>" 
                                data-month="<?php echo $currentMonth; ?>" 
                                data-year="<?php echo $currentYear; ?>">
                                <div class="day-number"><?php echo $day; ?></div>
                                <?php if ($hasEvent): ?>
                                    <div class="event-indicator"></div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Event details panel (shown when day is clicked) -->
                    <div class="event-details-panel d-none">
                        <div class="event-details-header d-flex justify-content-between align-items-center">
                            <h5 class="event-date mb-0"><?php echo date('j') . ' ' . $monthName . ' ' . $currentYear; ?></h5>
                            <button class="btn btn-sm btn-close event-details-close"></button>
                        </div>
                        <div class="event-details-content">
                        </div>
                    </div>
                </div>
                
                <div class="planting-tips">
                    <h4 class="tips-title">
                        <i class="fas fa-lightbulb me-2"></i>Tips Tanam Bulan Ini
                    </h4>
                    
                    <?php if ($currentMonth >= 6 && $currentMonth <= 10): ?>
                        <!-- Tips untuk musim kemarau -->
                        <div class="tips-item">
                            <div class="tips-icon">
                                <i class="fas fa-droplet"></i>
                            </div>
                            <div class="tips-content">
                                <h5>Pengairan</h5>
                                <p>Di musim kemarau ini, pastikan tanaman mendapat cukup air. Siram pagi dan sore hari untuk menghindari penguapan berlebihan.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Tips untuk musim hujan -->
                        <div class="tips-item">
                            <div class="tips-icon">
                                <i class="fas fa-droplet"></i>
                            </div>
                            <div class="tips-content">
                                <h5>Pengairan</h5>
                                <p>Di musim hujan, perhatikan drainase tanah. Pastikan tidak ada genangan air yang bisa menyebabkan busuk akar.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tips lainnya tetap sama -->
                    <div class="tips-item">
                        <div class="tips-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <div class="tips-content">
                            <h5>Sinar Matahari</h5>
                            <p>Manfaatkan sinar matahari pagi yang optimal untuk pertumbuhan tanaman. Hindari paparan langsung saat siang hari yang terik.</p>
                        </div>
                    </div>
                    
                    <div class="tips-item">
                        <div class="tips-icon">
                            <i class="fas fa-bug"></i>
                        </div>
                        <div class="tips-content">
                            <h5>Pengendalian Hama</h5>
                            <p>Periksa tanaman secara rutin untuk mendeteksi serangan hama sejak dini. Gunakan pestisida alami untuk hasil yang lebih sehat.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="planting-guide">
                    <h4 class="guide-title">
                        <i class="fas fa-book me-2"></i>Panduan Tanam
                    </h4>
                    
                    <div class="guide-item">
                        <h5>Tanaman Rekomendasi Bulan Ini</h5>
                        <p><?php echo $tanamanRekomendasi; ?></p>
                    </div>
                    
                    <?php
                    // Dynamic planting guide based on current month
                    switch ($currentMonth) {
                        case 1: // Januari
                            echo '
                            <div class="guide-item">
                                <h5>Kangkung (1-15 '.$monthName.')</h5>
                                <p>Tanam kangkung di tanah lembab dengan jarak 20 cm antar tanaman. Bisa dipanen dalam 3-4 minggu.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Bayam (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Bayam tumbuh optimal di tanah gembur dengan pH 6-7. Beri pupuk organik setiap 2 minggu.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Sawi (5-25 '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan jarak 30 cm. Butuh sinar matahari penuh dan penyiraman teratur.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Lobak (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah berpasir yang gembur. Jarak tanam 5 cm dalam barisan dengan kedalaman 1 cm.</p>
                            </div>';
                            break;
                            
                        case 2: // Februari
                            echo '
                            <div class="guide-item">
                                <h5>Kacang Panjang (5-20 '.$monthName.')</h5>
                                <p>Butuh penyangga untuk merambat. Jarak tanam 50 cm dengan kedalaman 2-3 cm.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Terong (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di area terkena sinar matahari penuh. Beri jarak 60 cm antar tanaman.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Timun (1-15 '.$monthName.')</h5>
                                <p>Butuh penyangga untuk merambat. Siram secara teratur untuk hasil optimal.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Buncis (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah gembur dengan jarak 40 cm. Pemupukan penting saat berbunga.</p>
                            </div>';
                            break;
                            
                        case 3: // Maret
                            echo '
                            <div class="guide-item">
                                <h5>Cabai (1-15 '.$monthName.')</h5>
                                <p>Gunakan tanah berdrainase baik. Beri pupuk NPK seimbang setelah 2 minggu.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Paprika (5-20 '.$monthName.')</h5>
                                <p>Butuh sinar matahari penuh dan tanah subur. Jarak tanam 50 cm x 50 cm.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kailan (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan pH 6-7. Bisa dipanen dalam 4-5 minggu.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Seledri (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh tanah lembab dan teduh parsial. Jarak tanam 25 cm x 25 cm.</p>
                            </div>';
                            break;
                            
                        case 4: // April
                            echo '
                            <div class="guide-item">
                                <h5>Jagung (1-20 '.$monthName.')</h5>
                                <p>Tanam dalam barisan dengan jarak 75 cm antar baris dan 25 cm dalam baris.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kedelai (5-25 '.$monthName.')</h5>
                                <p>Tanam di tanah gembur dengan jarak 40 cm. Pemupukan penting saat berbunga.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kacang Tanah (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah berpasir dengan pH 5.8-6.2. Butuh sinar matahari penuh.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kacang Hijau (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah berdrainase baik. Jarak tanam 30 cm x 30 cm.</p>
                            </div>';
                            break;
                            
                        case 5: // Mei
                            echo '
                            <div class="guide-item">
                                <h5>Mentimun (5-25 '.$monthName.')</h5>
                                <p>Butuh penyangga untuk merambat. Siram secara teratur untuk hasil optimal.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Oyong (1-20 '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan jarak 1m x 1m. Butuh banyak ruang untuk tumbuh.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Pare (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh penyangga kuat. Tanam di area terkena sinar matahari penuh.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Labu Siam (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan jarak 2-3 meter. Butuh banyak ruang untuk tumbuh.</p>
                            </div>';
                            break;
                            
                        case 6: // Juni
                            echo '
                            <div class="guide-item">
                                <h5>Ubi Kayu (1-20 '.$monthName.')</h5>
                                <p>Tanam stek batang sepanjang 20 cm dengan jarak 1m x 1m. Toleran kekeringan.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Pepaya (5-25 '.$monthName.')</h5>
                                <p>Tanam bibit dengan jarak 3m x 3m. Butuh banyak pupuk organik.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Pisang (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam anakan pisang dengan jarak 3m x 3m. Butuh banyak pupuk organik.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Cabai Rawit (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tahan panas, bisa ditanam di lahan kering. Beri pupuk secara berkala.</p>
                            </div>';
                            break;
                            
                        case 7: // Juli
                            echo '
                            <div class="guide-item">
                                <h5>Padi (1-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh lahan basah atau sawah. Gunakan varietas unggul untuk hasil maksimal.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Jagung (5-25 '.$monthName.')</h5>
                                <p>Tanam dalam barisan dengan jarak 75 cm antar baris dan 25 cm dalam baris.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kedelai (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah gembur dengan jarak 40 cm. Pemupukan penting saat berbunga.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kacang Tanah (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah berpasir dengan pH 5.8-6.2. Butuh sinar matahari penuh.</p>
                            </div>';
                            break;
                            
                        case 8: // Agustus
                            echo '
                            <div class="guide-item">
                                <h5>Bawang Merah (5-25 '.$monthName.')</h5>
                                <p>Gunakan umbi berkualitas. Jarak tanam 15 cm x 15 cm di tanah gembur.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Bawang Putih (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam siung bawang dengan ujung runcing menghadap atas. Butuh tanah berdrainase baik.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Bawang Daun (1-20 '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan jarak 20 cm. Bisa dipanen secara bertahap.</p>
                            </div>';
                            break;
                            
                        case 9: // September
                            echo '
                            <div class="guide-item">
                                <h5>Wortel (1-20 '.$monthName.')</h5>
                                <p>Tanam di tanah berpasir yang gembur. Jarak tanam 5 cm dalam barisan.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kentang (5-25 '.$monthName.')</h5>
                                <p>Gunakan umbi berkualitas. Tanam di tanah gembur dengan pH 5-6.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kubis (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh tanah subur dengan pH 6-7. Jarak tanam 45 cm x 45 cm.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Brokoli (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh tanah subur dengan pH 6-7. Jarak tanam 45 cm x 45 cm.</p>
                            </div>';
                            break;
                            
                        case 10: // Oktober
                            echo '
                            <div class="guide-item">
                                <h5>Selada (1-15 '.$monthName.')</h5>
                                <p>Tanam di tempat teduh parsial. Jarak tanam 25-30 cm antar tanaman.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Bayam (5-20 '.$monthName.')</h5>
                                <p>Bayam tumbuh optimal di tanah gembur dengan pH 6-7. Beri pupuk organik setiap 2 minggu.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kangkung (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam kangkung di tanah lembab dengan jarak 20 cm antar tanaman.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Pakcoy (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh tanah lembab dan subur. Bisa dipanen muda (baby pakcoy) atau dewasa.</p>
                            </div>';
                            break;
                            
                        case 11: // November
                            echo '
                            <div class="guide-item">
                                <h5>Kacang Panjang (5-25 '.$monthName.')</h5>
                                <p>Panen teratur merangsang produksi lebih banyak. Butuh penyangga kuat.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Buncis (1-20 '.$monthName.')</h5>
                                <p>Tanam di tanah gembur dengan jarak 40 cm. Pemupukan penting saat berbunga.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Terong (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di area terkena sinar matahari penuh. Beri jarak 60 cm antar tanaman.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Timun (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah kaya humus. Panen saat buah masih muda untuk rasa terbaik.</p>
                            </div>';
                            break;
                            
                        case 12: // Desember
                            echo '
                            <div class="guide-item">
                                <h5>Cabai (1-20 '.$monthName.')</h5>
                                <p>Proteksi dari hujan lebat. Gunakan mulsa plastik untuk menjaga kelembaban.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Tomat (5-25 '.$monthName.')</h5>
                                <p>Butuh penyangga. Hindari daun basah untuk mencegah penyakit jamur.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Paprika (10-'.$totalDays.' '.$monthName.')</h5>
                                <p>Butuh sinar matahari penuh dan tanah subur. Jarak tanam 50 cm x 50 cm.</p>
                            </div>
                            <div class="guide-item">
                                <h5>Kailan (15-'.$totalDays.' '.$monthName.')</h5>
                                <p>Tanam di tanah subur dengan pH 6-7. Bisa dipanen dalam 4-5 minggu.</p>
                            </div>';
                            break;
                    }
                    ?>
                    
                    <div class="text-center mt-4">
                        <a href="panduan.php" class="btn btn-success">
                            <i class="fas fa-book-open me-2"></i> Pelajari Lebih Lanjut
                        </a>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight current day on page load
        const currentDayElement = document.querySelector('.current-day');
        if (currentDayElement) {
            currentDayElement.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
        
        // Calendar day click handler
        const calendarDays = document.querySelectorAll('.calendar-day:not(.empty-day)');
        const eventDetailsPanel = document.querySelector('.event-details-panel');
        const eventDetailsContent = document.querySelector('.event-details-content');
        const eventDateHeader = document.querySelector('.event-date');
        
        calendarDays.forEach(day => {
            day.addEventListener('click', function() {
                const dayNum = this.getAttribute('data-day');
                const month = this.getAttribute('data-month');
                const year = this.getAttribute('data-year');
                
                // Format date
                const date = new Date(year, month-1, dayNum);
                const options = { day: 'numeric', month: 'long', year: 'numeric' };
                const formattedDate = date.toLocaleDateString('id-ID', options);
                
                                // Update panel header
                eventDateHeader.textContent = formattedDate;
                
                // Generate event content based on day
                let eventContent = '';
                
                // Example events - in a real app, you'd fetch these from a database
                if (dayNum >= 5 && dayNum <= 15) {
                    eventContent += `
                        <div class="planting-event">
                            <div class="plant-name">Tanam Cabai</div>
                            <div class="plant-desc">Waktu ideal untuk menanam cabai merah dan rawit. Gunakan pupuk organik untuk hasil terbaik.</div>
                        </div>
                    `;
                }
                
                if (dayNum >= 10 && dayNum <= 25) {
                    eventContent += `
                        <div class="planting-event">
                            <div class="plant-name">Tanam Ubi Kayu</div>
                            <div class="plant-desc">Masa tanam ubi kayu di lahan kering. Pastikan drainase baik dan beri jarak tanam 1m x 1m.</div>
                        </div>
                    `;
                }
                
                if (dayNum >= 20) {
                    eventContent += `
                        <div class="planting-event">
                            <div class="plant-name">Tanam Pepaya</div>
                            <div class="plant-desc">Waktu tepat untuk menanam pepaya california. Pilih bibit unggul dari sumber terpercaya.</div>
                        </div>
                    `;
                }
                
                // If no events, show message
                if (eventContent === '') {
                    eventContent = '<p class="text-muted">Tidak ada jadwal tanam untuk hari ini.</p>';
                }
                
                // Update content and show panel
                eventDetailsContent.innerHTML = eventContent;
                eventDetailsPanel.classList.remove('d-none');
                
                // Scroll to panel
                eventDetailsPanel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            });
        });
        
        // Close event details panel
        document.querySelector('.event-details-close').addEventListener('click', function() {
            eventDetailsPanel.classList.add('d-none');
        });
    });
</script>
</body>
</html>