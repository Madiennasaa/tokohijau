<?php
include 'koneksi.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah':
                // ... (keep existing tambah logic) ...
                break;
                
            case 'edit':
                // ... (keep existing edit logic) ...
                break;
                
            case 'hapus':
                // ... (keep existing hapus logic) ...
                break;
        }
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM kategori ORDER BY nama_kategori";
$categories_result = mysqli_query($conn, $categories_query);

// Pagination settings
$items_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$where_clause = "WHERE 1=1";
if ($search) {
    $where_clause .= " AND b.nama_barang LIKE '%$search%'";
}
if ($kategori_filter) {
    $where_clause .= " AND b.id_kategori = $kategori_filter";
}

// Handle upload gambar
$gambar = '';
if (!empty($_POST['gambar_url'])) {
    // Validasi URL gambar
    if (filter_var($_POST['gambar_url'], FILTER_VALIDATE_URL)) {
        $gambar = $_POST['gambar_url'];
    } else {
        $_SESSION['error'] = "URL gambar tidak valid";
    }
} elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $target_dir = "uploads/barang/";
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
        
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            // Upload berhasil
        } else {
            $_SESSION['error'] = "Gagal mengunggah gambar";
        }
    } else {
        $_SESSION['error'] = "File harus berupa gambar (JPEG/PNG/GIF) dan maksimal 2MB";
    }
}

// Count total items for pagination
$count_query = "SELECT COUNT(*) as total FROM barang b $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Adjust current page if it's beyond total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Calculate offset
$offset = ($current_page - 1) * $items_per_page;

$barang_query = "SELECT b.*, k.nama_kategori, p.nama as nama_penjual 
                FROM barang b 
                LEFT JOIN kategori k ON b.id_kategori = k.id_kategori 
                LEFT JOIN pengguna p ON b.id_penjual = p.id_pengguna 
                $where_clause 
                ORDER BY b.tanggal_tambah DESC
                LIMIT $items_per_page OFFSET $offset";
$barang_result = mysqli_query($conn, $barang_query);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM barang")->fetch_assoc()['count'],
    'high_stock' => $conn->query("SELECT COUNT(*) as count FROM barang WHERE stok > 25")->fetch_assoc()['count'],
    'medium_stock' => $conn->query("SELECT COUNT(*) as count FROM barang WHERE stok > 0 AND stok <= 25")->fetch_assoc()['count'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM barang WHERE stok = 0")->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang - Toko Hijau Admin</title>
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
        .stat-card.stat-high::before { background: var(--gradient-1); }
        .stat-card.stat-medium::before { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.stat-low::before { background: linear-gradient(135deg, #ef4444, #dc2626); }

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
        .stat-icon.icon-high { background: var(--gradient-1); }
        .stat-icon.icon-medium { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.icon-low { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }

        /* Product Cards */
        .product-card {
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

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .product-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .product-category {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1f2937;
        }

        .stock-badge {
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stock-high {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: var(--dark-green);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .stock-medium {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .stock-low {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
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
            
            .product-card {
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
                        <a class="nav-link active" href="kelola_barang.php"><i class="fas fa-box me-1"></i> Kelola Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Kelola Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_berita.php"><i class="fas fa-newspaper me-1"></i> Kelola Berita</a>
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
                    <i class="fas fa-box me-3"></i>Kelola Barang
                </h1>
                <p class="admin-subtitle">Kelola semua produk dalam sistem dengan mudah dan efisien</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistik Barang -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-total fade-in-up">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-number text-primary"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-high fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-icon icon-high">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number" style="color: var(--primary-green);"><?= $stats['high_stock'] ?></div>
                    <div class="stat-label">Stok Tinggi</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-medium fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-icon icon-medium">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-number text-warning"><?= $stats['medium_stock'] ?></div>
                    <div class="stat-label">Stok Menipis</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-low fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-icon icon-low">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number text-danger"><?= $stats['low_stock'] ?></div>
                    <div class="stat-label">Stok Habis</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section fade-in-up">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0"><i class="fas fa-filter me-2 text-success"></i>Filter Produk</h5>
                
                <div class="d-flex flex-wrap gap-2">
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <div class="input-group" style="min-width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control search-input border-start-0" name="search" 
                                   placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <select name="kategori" class="form-select" style="min-width: 200px;">
                            <option value="">Semua Kategori</option>
                            <?php while ($kategori = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?= $kategori['id_kategori'] ?>" <?= $kategori_filter == $kategori['id_kategori'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                            <i class="fas fa-plus me-2"></i>Tambah Produk
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Barang -->
        <?php if (mysqli_num_rows($barang_result) > 0): ?>
            <?php $animation_delay = 0; ?>
            <?php while ($barang = mysqli_fetch_assoc($barang_result)): ?>
                <div class="product-card fade-in-up" style="animation-delay: <?= $animation_delay ?>s;">
                    <div class="row align-items-center">
                        <div class="col-lg-1 col-md-2 mb-3 mb-md-0 text-center">
                            <?php 
                            $gambar_barang = explode('|', $barang['gambar'])[0] ?? '';
                            ?>
                            <img src="<?= htmlspecialchars($gambar_barang) ?>" alt="Gambar Barang" class="product-image" style="width: 50px; height: 50px; object-fit: cover;">
                        </div>
                        
                        <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                            <div class="product-id">#<?= $barang['id_barang'] ?></div>
                            <h5 class="product-name"><?= htmlspecialchars($barang['nama_barang']) ?></h5>
                            <div class="product-category">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($barang['nama_kategori']) ?>
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0">
                            <div class="product-price">
                                <i class="fas fa-tags me-1"></i>Rp <?= number_format($barang['harga_eceran'], 0, ',', '.') ?>
                            </div>
                            <small class="text-muted">Grosir: Rp <?= number_format($barang['harga_grosir'], 0, ',', '.') ?></small>
                        </div>
                        
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0">
                            <span class="stock-badge <?= $barang['stok'] > 25 ? 'stock-high' : ($barang['stok'] > 0 ? 'stock-medium' : 'stock-low') ?>">
                                <i class="fas fa-boxes me-1"></i>
                                <?= $barang['stok'] . ' ' . $barang['satuan'] ?>
                            </span>
                        </div>
                        
                        <div class="col-lg-4 col-md-12">
                            <div class="d-flex flex-wrap justify-content-end">
                                <button class="action-btn btn-edit" 
                                        onclick="editProduct(
                                            <?= $barang['id_barang'] ?>, 
                                            '<?= addslashes($barang['nama_barang']) ?>', 
                                            <?= $barang['id_kategori'] ?>, 
                                            '<?= addslashes($barang['deskripsi']) ?>', 
                                            <?= $barang['harga_eceran'] ?>, 
                                            <?= $barang['harga_grosir'] ?>, 
                                            <?= $barang['stok'] ?>, 
                                            '<?= $barang['satuan'] ?>', 
                                            '<?= $barang['gambar'] ?>'
                                        )"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editBarangModal">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                
                                <button class="action-btn btn-delete" 
                                        onclick="confirmDelete(<?= $barang['id_barang'] ?>, '<?= addslashes($barang['nama_barang']) ?>')">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php $animation_delay += 0.1; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state fade-in-up">
                <div class="empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3 class="empty-title">Tidak Ada Produk</h3>
                <p class="empty-text">Tidak ada produk yang ditemukan dengan filter yang dipilih.</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                    <i class="fas fa-plus me-2"></i>Tambah Produk
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
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Tambah Barang -->
    <div class="modal fade" id="tambahBarangModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="tambah">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk *</label>
                                    <input type="text" class="form-control" name="nama_barang" required placeholder="Masukkan nama produk">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori *</label>
                                    <select name="id_kategori" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($kategori = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?= $kategori['id_kategori'] ?>"><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea class="form-control" name="deskripsi" rows="3" placeholder="Deskripsikan produk Anda..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Eceran *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="harga_eceran" step="1000" required placeholder="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Grosir *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="harga_grosir" step="1000" required placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok *</label>
                                    <input type="number" class="form-control" name="stok" required placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Satuan *</label>
                                    <select name="satuan" class="form-select" required>
                                        <option value="">Pilih Satuan</option>
                                        <option value="pcs">Pcs</option>
                                        <option value="kg">Kg</option>
                                        <option value="liter">Liter</option>
                                        <option value="pack">Pack</option>
                                        <option value="karung">Karung</option>
                                        <option value="ikat">Ikat</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control mb-2" name="gambar" accept="image/*">
                            <small class="text-muted">Atau masukkan URL gambar:</small>
                            <input type="url" class="form-control" name="gambar_url" placeholder="https://example.com/image.jpg">
                            <small class="text-muted">Format: JPG, PNG (Maksimal 2MB untuk upload file)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Barang -->
    <div class="modal fade" id="editBarangModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id_barang" id="editIdBarang">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk *</label>
                                    <input type="text" class="form-control" name="nama_barang" id="editNamaBarang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori *</label>
                                    <select name="id_kategori" id="editKategori" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($kategori = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?= $kategori['id_kategori'] ?>"><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Eceran *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="harga_eceran" id="editHargaEceran" step="1000" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Grosir *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="harga_grosir" id="editHargaGrosir" step="1000" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok *</label>
                                    <input type="number" class="form-control" name="stok" id="editStok" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Satuan *</label>
                                    <select name="satuan" id="editSatuan" class="form-select" required>
                                        <option value="pcs">Pcs</option>
                                        <option value="kg">Kg</option>
                                        <option value="liter">Liter</option>
                                        <option value="pack">Pack</option>
                                        <option value="karung">Karung</option>
                                        <option value="ikat">Ikat</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control mb-2" name="gambar" accept="image/*">
                            <small class="text-muted">Atau masukkan URL gambar:</small>
                            <input type="url" class="form-control" name="gambar_url" placeholder="https://example.com/image.jpg" id="editGambarUrl">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus produk <strong id="deleteProductName"></strong>?</p>
                    <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="hapus">
                        <input type="hidden" name="id_barang" id="deleteIdBarang">
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Edit product function
        function editProduct(id, nama, kategori, deskripsi, hargaEceran, hargaGrosir, stok, satuan, gambar) {
            $('#editIdBarang').val(id);
            $('#editNamaBarang').val(nama);
            $('#editKategori').val(kategori);
            $('#editDeskripsi').val(deskripsi);
            $('#editHargaEceran').val(hargaEceran);
            $('#editHargaGrosir').val(hargaGrosir);
            $('#editStok').val(stok);
            $('#editSatuan').val(satuan);
            
            // Image preview handling
            const imgPreview = $('#editGambarPreview');
            if (gambar) {
                if (gambar.startsWith('http')) {
                    imgPreview.attr('src', gambar).show();
                    $('#editGambarUrl').val(gambar);
                } else {
                    imgPreview.attr('src', 'uploads/barang/' + gambar).show();
                }
            } else {
                imgPreview.hide();
            }
        }

        // Delete confirmation
        function confirmDelete(id, name) {
            $('#deleteProductName').text(name);
            $('#deleteIdBarang').val(id);
            $('#deleteModal').modal('show');
        }

        // Auto-dismiss alerts after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeTo(500, 0).slideUp(500, function() {
                    $(this).remove(); 
                });
            }, 5000);
        });
    </script>
</body>
</html>