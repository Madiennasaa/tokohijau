<?php
include 'koneksi.php';

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan_berita'])) {
        $judul = $conn->real_escape_string($_POST['judul'] ?? '');
        $deskripsi = $conn->real_escape_string($_POST['deskripsi'] ?? '');
        $id_penulis = $_SESSION['user_id'] ?? 0;
        
        // Handle upload gambar atau URL gambar
        $gambar = '';
        if (!empty($_POST['gambar_url'])) {
            // Validasi URL gambar
            if (filter_var($_POST['gambar_url'], FILTER_VALIDATE_URL)) {
                $gambar = $_POST['gambar_url'];
            } else {
                $_SESSION['error'] = "URL gambar tidak valid";
                header("Location: kelola_berita.php");
                exit();
            }
        } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/berita/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            // Validasi tipe dan ukuran file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['gambar']['type'], $allowed_types) && 
                $_FILES['gambar']['size'] <= $max_size) {
                
                $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $gambar = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $gambar;
                
                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    $_SESSION['error'] = "Gagal mengunggah gambar";
                    header("Location: kelola_berita.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "File harus berupa gambar (JPEG/PNG/GIF) dan maksimal 2MB";
                header("Location: kelola_berita.php");
                exit();
            }
        }
        
        // Jika edit
        if (isset($_POST['id_berita']) && !empty($_POST['id_berita'])) {
            $id = intval($_POST['id_berita']);
            
            // Jika ada gambar baru, update gambar
            if (!empty($gambar)) {
                // Hapus gambar lama jika ada
                $old_img = $conn->query("SELECT gambar FROM berita WHERE id_berita = $id")->fetch_assoc();
                if ($old_img && $old_img['gambar'] && file_exists($old_img['gambar'])) {
                    unlink($old_img['gambar']);
                }
                
                $query = "UPDATE berita SET judul='$judul', deskripsi='$deskripsi', gambar='$gambar' WHERE id_berita=$id";
            } else {
                $query = "UPDATE berita SET judul='$judul', deskripsi='$deskripsi' WHERE id_berita=$id";
            }
            
            if (!$conn->query($query)) {
                $_SESSION['error'] = "Error: " . $conn->error;
                header("Location: kelola_berita.php");
                exit();
            }
            
            $_SESSION['success'] = "Berita berhasil diperbarui!";
        } 
        // Jika tambah baru
        else {
            $query = "INSERT INTO berita (judul, deskripsi, gambar, id_penulis, tanggal_posting) 
                      VALUES ('$judul', '$deskripsi', '$gambar', $id_penulis, NOW())";
            
            if (!$conn->query($query)) {
                $_SESSION['error'] = "Error: " . $conn->error;
                header("Location: kelola_berita.php");
                exit();
            }
            
            $_SESSION['success'] = "Berita baru berhasil ditambahkan!";
        }
        
        header("Location: kelola_berita.php");
        exit();
    }
}

// Fungsi hapus berita
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    
    // Hapus gambar jika ada
    $berita = $conn->query("SELECT gambar FROM berita WHERE id_berita = $id")->fetch_assoc();
    if ($berita && $berita['gambar'] && file_exists($berita['gambar'])) {
        unlink($berita['gambar']);
    }
    
    $conn->query("DELETE FROM berita WHERE id_berita = $id");
    $_SESSION['success'] = "Berita berhasil dihapus!";
    header("Location: kelola_berita.php");
    exit();
}

// Pagination settings
$items_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where_clause = "WHERE 1=1";
if ($search) {
    $where_clause .= " AND (b.judul LIKE '%$search%' OR b.deskripsi LIKE '%$search%')";
}

// Count total items for pagination
$count_query = "SELECT COUNT(*) as total FROM berita b $where_clause";
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Adjust current page if it's beyond total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Calculate offset
$offset = ($current_page - 1) * $items_per_page;

// Ambil data berita untuk ditampilkan
$berita_query = "SELECT b.*, p.nama as nama_penulis 
                FROM berita b 
                LEFT JOIN pengguna p ON b.id_penulis = p.id_pengguna
                $where_clause
                ORDER BY b.tanggal_posting DESC
                LIMIT $items_per_page OFFSET $offset";
$berita_result = $conn->query($berita_query);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM berita")->fetch_assoc()['count'],
    'today' => $conn->query("SELECT COUNT(*) as count FROM berita WHERE DATE(tanggal_posting) = CURDATE()")->fetch_assoc()['count'],
    'this_week' => $conn->query("SELECT COUNT(*) as count FROM berita WHERE YEARWEEK(tanggal_posting) = YEARWEEK(NOW())")->fetch_assoc()['count'],
    'this_month' => $conn->query("SELECT COUNT(*) as count FROM berita WHERE MONTH(tanggal_posting) = MONTH(NOW()) AND YEAR(tanggal_posting) = YEAR(NOW())")->fetch_assoc()['count']
];

// Ambil data berita untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_data = $conn->query("SELECT * FROM berita WHERE id_berita = $id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Toko Hijau Admin</title>
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
            color: #22c55e; /* Warna fallback jika gradien tidak bekerja */
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: #374151 !important; /* Warna teks default */
            font-weight: 500;
            position: relative;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.2rem;
        }

        .navbar-modern .navbar-nav .nav-item .nav-link:hover {
            color: var(--primary-green) !important;
            background: var(--light-green) !important;
        }

        .navbar-modern .navbar-nav .nav-item .nav-link.active {
            color: var(--primary-green) !important;
            background: var(--light-green) !important;
        }

        /* Hero Admin Section */
        .admin-hero {
            background: var(--gradient-1);
            padding: 2rem 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .admin-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="25" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="25" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .admin-hero-content {
            position: relative;
            z-index: 2;
        }

        .admin-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .admin-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            transition: all 0.3s ease;
        }

        .stat-card.stat-total::before { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.stat-today::before { background: var(--gradient-1); }
        .stat-card.stat-week::before { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.stat-month::before { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.icon-total { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.icon-today { background: var(--gradient-1); }
        .stat-icon.icon-week { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.icon-month { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }

        /* News Cards */
        .news-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .news-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .news-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .news-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .news-title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
            margin-right: 1rem;
        }

        .news-desc {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .news-date {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .news-author {
            color: #6b7280;
            font-size: 0.85rem;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            margin: 0.25rem;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .btn-edit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-edit:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn-add {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-add:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            margin-bottom: 2rem;
        }

        /* Pagination */
        .pagination-wrapper {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }

        .pagination .page-link {
            color: var(--primary-green);
            border: 2px solid transparent;
            border-radius: 12px;
            font-weight: 600;
            margin: 0 0.25rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            color: white;
            background: var(--gradient-1);
            border-color: var(--primary-green);
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: var(--gradient-1);
            border-color: var(--primary-green);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        /* Alert Styling */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: var(--dark-green);
            border-left: 4px solid var(--primary-green);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6b7280;
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

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .stat-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .news-card {
                padding: 1rem;
            }
            
            .action-btn {
                width: 100%;
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-seedling me-2"></i>Toko Hijau - Admin
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_barang.php"><i class="fas fa-box me-1"></i> Kelola Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Kelola Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="kelola_berita.php"><i class="fas fa-newspaper me-1"></i> Kelola Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Hero Section -->
    <section class="admin-hero" style="margin-top: 80px;">
        <div class="container">
            <div class="admin-hero-content text-center">
                <h1 class="admin-title">
                    <i class="fas fa-newspaper me-3"></i>Kelola Berita
                </h1>
                <p class="admin-subtitle">Kelola semua berita dalam sistem dengan mudah dan efisien</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistik Berita -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-total fade-in-up">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-number text-primary"><?= htmlspecialchars($stats['total']) ?></div>
                    <div class="stat-label">Total Berita</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-today fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-icon icon-today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number" style="color: var(--primary-green);"><?= htmlspecialchars($stats['today']) ?></div>
                    <div class="stat-label">Berita Hari Ini</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-week fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-icon icon-week">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-number text-warning"><?= htmlspecialchars($stats['this_week']) ?></div>
                    <div class="stat-label">Berita Minggu Ini</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-month fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-icon icon-month">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number" style="color: #8b5cf6;"><?= htmlspecialchars($stats['this_month']) ?></div>
                    <div class="stat-label">Berita Bulan Ini</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section fade-in-up">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0"><i class="fas fa-filter me-2 text-success"></i>Filter Berita</h5>
                
                <div class="d-flex flex-wrap gap-2">
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <div class="input-group" style="min-width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control search-input border-start-0" name="search" 
                                   placeholder="Cari berita..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBeritaModal">
                            <i class="fas fa-plus me-2"></i>Tambah Berita
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($berita_result && $berita_result->num_rows > 0): ?>
            <div class="row">
                <?php $animation_delay = 0; ?>
                <?php while ($berita = $berita_result->fetch_assoc()): ?>
                    <div class="col-lg-6 col-md-12 mb-4 fade-in-up" style="animation-delay: <?= $animation_delay ?>s;">
                        <div class="news-card h-100">
                            <div class="row g-0 h-100">
                                <!-- Gambar Berita -->
                                <div class="col-md-4">
                                    <?php if ($berita['gambar']): ?>
                                        <img src="<?= htmlspecialchars($berita['gambar']) ?>" 
                                            alt="Gambar Berita" 
                                            class="img-fluid rounded-start h-100 w-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                            <i class="fas fa-newspaper fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Konten Berita -->
                                <div class="col-md-8">
                                    <div class="card-body h-100 ms-3 d-flex flex-column">
                                        <!-- Header Berita -->
                                        <div class="mb-2">
                                            <span class="badge bg-success">#<?= htmlspecialchars($berita['id_berita']) ?></span>
                                            <h5 class="news-title mt-2"><?= htmlspecialchars($berita['judul']) ?></h5>
                                        </div>
                                        
                                        <!-- Deskripsi -->
                                        <div class="news-desc flex-grow-1">
                                            <?= strlen($berita['deskripsi']) > 150 ? 
                                                substr(htmlspecialchars($berita['deskripsi']), 0, 150).'...' : 
                                                htmlspecialchars($berita['deskripsi']) ?>
                                        </div>
                                        
                                        <!-- Footer Berita -->
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <!-- Info Tanggal dan Penulis -->
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d M Y', strtotime($berita['tanggal_posting'])) ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($berita['nama_penulis'] ?? 'Admin') ?>
                                                    </small>
                                                </div>
                                                
                                                <!-- Tombol Aksi -->
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-primary" 
                                                            onclick="editNews(
                                                                <?= $berita['id_berita'] ?>, 
                                                                '<?= addslashes(htmlspecialchars($berita['judul'])) ?>', 
                                                                '<?= addslashes(htmlspecialchars($berita['deskripsi'])) ?>', 
                                                                '<?= htmlspecialchars($berita['gambar']) ?>'
                                                            )"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editBeritaModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?= $berita['id_berita'] ?>, '<?= addslashes(htmlspecialchars($berita['judul'])) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $animation_delay += 0.1; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state fade-in-up">
                <div class="empty-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h3 class="empty-title">Tidak Ada Berita</h3>
                <p class="empty-text">Tidak ada berita yang ditemukan dengan filter yang dipilih.</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahBeritaModal">
                    <i class="fas fa-plus me-2"></i>Tambah Berita
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper fade-in-up">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Tambah Berita -->
    <div class="modal fade" id="tambahBeritaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Berita Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="simpan_berita" value="1">
                        <div class="mb-3">
                            <label class="form-label">Judul Berita *</label>
                            <input type="text" class="form-control" name="judul" required placeholder="Masukkan judul berita">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" rows="5" required placeholder="Tulis isi berita..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Berita</label>
                            <input type="file" class="form-control mb-2" name="gambar" accept="image/*">
                            <small class="text-muted">Atau masukkan URL gambar:</small>
                            <input type="url" class="form-control" name="gambar_url" placeholder="https://example.com/image.jpg">
                            <small class="text-muted">Format: JPG, PNG (Maksimal 2MB untuk upload file)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Berita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Berita -->
    <div class="modal fade" id="editBeritaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="simpan_berita" value="1">
                        <input type="hidden" name="id_berita" id="edit_id_berita">
                        <div class="mb-3">
                            <label class="form-label">Judul Berita *</label>
                            <input type="text" class="form-control" name="judul" id="edit_judul" required placeholder="Masukkan judul berita">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="5" required placeholder="Tulis isi berita..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Berita</label>
                            <input type="file" class="form-control mb-2" name="gambar" accept="image/*">
                            <small class="text-muted">Atau masukkan URL gambar:</small>
                            <input type="url" class="form-control" name="gambar_url" placeholder="https://example.com/image.jpg" id="edit_gambar_url">
                            <small class="text-muted">Format: JPG, PNG (Maksimal 2MB)</small>
                            <div class="mt-2" id="current_image_container">
                                <img id="current_image" src="" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                                <p class="text-muted mt-1">Gambar saat ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Update Berita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus berita "<span id="delete_news_title"></span>"?</p>
                    <p class="text-danger">Aksi ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirm_delete_btn" class="btn btn-danger">Ya, Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Fungsi untuk mengisi form edit
        function editNews(id, judul, deskripsi, gambar) {
            document.getElementById('edit_id_berita').value = id;
            document.getElementById('edit_judul').value = judul;
            document.getElementById('edit_deskripsi').value = deskripsi;
            
            const currentImage = document.getElementById('current_image');
            const currentImageContainer = document.getElementById('current_image_container');
            const editGambarUrl = document.getElementById('edit_gambar_url');
            
            if (gambar) {
                currentImage.src = gambar;
                currentImageContainer.style.display = 'block';
                if (gambar.startsWith('http')) {
                    editGambarUrl.value = gambar;
                } else {
                    editGambarUrl.value = '';
                }
            } else {
                currentImageContainer.style.display = 'none';
                editGambarUrl.value = '';
            }
        }
        
        // Fungsi konfirmasi hapus
        function confirmDelete(id, title) {
            document.getElementById('delete_news_title').textContent = title;
            document.getElementById('confirm_delete_btn').href = 'kelola_berita.php?hapus=' + id;
            
            // Tampilkan modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
            deleteModal.show();
        }
        
        // Inisialisasi tooltip
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>