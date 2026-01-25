<?php
include 'koneksi.php';

// Error handling untuk debugging
try {
    // ===== FILTER =====
    $where = [];
    $params = [];

    if (!empty($_GET['kategori'])) {
        $ids = array_filter(array_map('intval', explode(',', $_GET['kategori'])));
        if ($ids) {
            $where[] = "id_kategori IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
            $params = array_merge($params, $ids);
        }
    }

    if (!empty($_GET['max_price'])) {
        $where[] = "harga_eceran <= ?";
        $params[] = (int)$_GET['max_price'];
    }

    $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // ===== TOTAL DATA (WAJIB BUAT PAGINATION) =====
    $count_sql = "SELECT COUNT(*) FROM barang $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = (int) $count_stmt->fetchColumn();

    // ===== PAGINATION =====
    $items_per_page = 6;
    $current_page = max(1, (int)($_GET['page'] ?? 1));
    
    // Hitung total halaman
    $total_pages = ($total_items > 0) ? ceil($total_items / $items_per_page) : 1;
    
    // Pastikan current_page tidak melebihi total_pages
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }
    
    $offset = ($current_page - 1) * $items_per_page;
    
    // Pastikan offset tidak negatif
    if ($offset < 0) {
        $offset = 0;
    }

    // ===== QUERY PRODUK (FINAL) - FIXED: Gunakan placeholder untuk LIMIT =====
    $sql = "SELECT * FROM barang $where_clause LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters dengan type integer untuk LIMIT
    $bind_params = array_merge($params, [$offset, $items_per_page]);
    $stmt->execute($bind_params);
    $products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "PDO ERROR:\n";
    echo $e->getMessage();
    echo "\n\nSQL:\n";
    echo $sql ?? 'SQL TIDAK ADA';
    echo "\n\nPARAMS:\n";
    var_dump($params ?? []);
    echo "</pre>";
    exit;
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    die("Terjadi kesalahan. Silakan coba lagi nanti.");
}

// Cek status login
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Hijau - Produk</title>
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
                        url('https://static.vecteezy.com/system/resources/thumbnails/044/527/228/small_2x/ai-generated-farmer-in-a-hat-in-his-field-generative-ai-photo.jpg');
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
            margin-bottom: 1rem;
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

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
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
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .product-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .price {
            color: var(--primary-green);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .badge-category {
            background: var(--light-green);
            color: var(--dark-green);
            font-weight: 600;
            border-radius: 20px;
            padding: 0.5rem 1rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            position: sticky;
            top: 100px;
        }

        .filter-section h5 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .filter-section h6 {
            color: #4b5563;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .form-range::-webkit-slider-thumb {
            background: var(--primary-green);
        }

        .form-range::-moz-range-thumb {
            background: var(--primary-green);
        }

        /* Buttons */
        .btn-success {
            background: var(--gradient-1);
            border: none;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.7rem 1.5rem;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            background: var(--gradient-1);
        }

        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.7rem 1.5rem;
        }

        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination .page-item.active .page-link {
            background: var(--gradient-1);
            border-color: var(--primary-green);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .pagination .page-link {
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin: 0 0.2rem;
            padding: 0.7rem 1rem;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            background: var(--light-green);
            color: var(--primary-green);
            border-color: var(--primary-green);
            transform: translateY(-2px);
        }

        /* Alerts */
        .guest-notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: none;
            border-left: 4px solid #f59e0b;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.1);
        }

        .filter-active {
            background: var(--light-green);
            border: none;
            border-left: 4px solid var(--primary-green);
            border-radius: 15px;
            color: var(--dark-green);
        }

        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.8rem;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-weight: 600;
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
            
            .filter-section {
                position: static;
                margin-bottom: 2rem;
            }
        }

        /* Dropdown styles */
        .dropdown-menu {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .dropdown-item {
            border-radius: 8px;
            margin: 0.2rem;
            padding: 0.7rem 1rem;
        }

        .dropdown-item:hover {
            background: var(--light-green);
            color: var(--primary-green);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= $is_logged_in ? 'dashboard.php' : 'index.php'; ?>">
                <i class="fas fa-seedling me-2"></i>Toko Hijau
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">

                    <!-- BERANDA -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $is_logged_in ? 'dashboard.php' : 'index.php'; ?>">
                            <i class="fas fa-home me-1"></i> Beranda
                        </a>
                    </li>

                    <!-- PRODUK -->
                    <li class="nav-item">
                        <a class="nav-link active" href="lihat_produk.php">
                            <i class="fas fa-store me-1"></i> Produk
                        </a>
                    </li>

                    <?php if ($is_logged_in): ?>
                        <!-- USER -->
                        <li class="nav-item">
                            <a class="nav-link" href="keranjang.php">
                                <i class="fas fa-shopping-cart me-1"></i> Keranjang
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="pesanan.php">
                                <i class="fas fa-clipboard-list me-1"></i> Pesanan
                            </a>
                        </li>

                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle"
                            href="#"
                            id="navbarDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>

                    <?php else: ?>
                        <!-- GUEST -->
                        <li class="nav-item">
                            <a class="nav-link" href="tentang.php">
                                <i class="fas fa-info-circle me-1"></i> Tentang
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="kontak.php">
                                <i class="fas fa-phone me-1"></i> Kontak
                            </a>
                        </li>

                        <li class="nav-item ms-3">
                            <a href="login.php" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-1"></i> Masuk
                            </a>
                        </li>
                    <?php endif; ?>

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
                <div class="col-lg-8 text-center hero-content">
                    <h1 class="hero-title">Produk Kami</h1>
                    <p class="hero-subtitle">Temukan produk pertanian berkualitas tinggi untuk mendukung aktivitas bertani Anda</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container mb-5" style="padding-top: 2rem;">
        <?php if(!$is_logged_in): ?>
        <div class="guest-notice">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3 text-warning" style="font-size: 1.5rem;"></i>
                <div>
                    <h5 class="mb-1">Anda belum login</h5>
                    <p class="mb-0">Silakan <a href="login.php" class="text-success fw-bold">login</a> untuk dapat menambahkan produk ke keranjang dan melakukan pemesanan.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['kategori']) || isset($_GET['max_price'])): ?>
        <div class="alert filter-active alert-dismissible fade show">
            <i class="fas fa-filter me-2"></i> <strong>Filter aktif:</strong> 
            <?php 
            if(isset($_GET['kategori'])) {
                $kategori_names = [];
                $kategori_ids_display = explode(',', $_GET['kategori']);
                foreach($kategori_ids_display as $id) {
                    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
                    $stmt->execute([(int)$id]);
                    $kat = $stmt->fetch();
                    if($kat) $kategori_names[] = $kat['nama_kategori'];
                }
                echo 'Kategori: '.implode(', ', $kategori_names).' ';
            }
            if(isset($_GET['max_price'])) {
                echo 'Harga maks: Rp '.number_format((int)$_GET['max_price'], 0, ',', '.');
            }
            ?>
            <a href="lihat_produk.php" class="float-end text-decoration-none">
                <i class="fas fa-times"></i> Hapus semua filter
            </a>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filter Section -->
            <div class="col-md-3 mb-4">
                <div class="filter-section">
                    <h5><i class="fas fa-filter me-2"></i>Filter Produk</h5>
                    
                    <!-- Kategori Filter -->
                    <div class="mb-4">
                        <h6><i class="fas fa-tags me-2"></i>Kategori</h6>
                        <?php
                        $categories = $pdo->query("SELECT * FROM kategori");
                        while($cat = $categories->fetch()):
                        ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="<?php echo $cat['id_kategori']; ?>" id="cat<?php echo $cat['id_kategori']; ?>"
                                <?php echo (isset($_GET['kategori']) && in_array($cat['id_kategori'], explode(',', $_GET['kategori']))) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cat<?php echo $cat['id_kategori']; ?>">
                                <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                            </label>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Harga Filter -->
                    <div class="mb-4">
                        <h6><i class="fas fa-money-bill-wave me-2"></i>Range Harga</h6>
                        <input type="range" class="form-range" min="0" max="1000000" step="10000" id="priceRange" 
                               value="<?php echo isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000; ?>">
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Rp 0</small>
                            <small class="text-muted" id="priceValue">Rp <?php echo isset($_GET['max_price']) ? number_format($_GET['max_price'], 0, ',', '.') : '1.000.000'; ?></small>
                        </div>
                    </div>
                    
                    <!-- Tombol Filter -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" id="applyFilter">
                            <i class="fas fa-filter me-2"></i>Terapkan Filter
                            <?php if(isset($_GET['kategori']) || isset($_GET['max_price'])): ?>
                            <span class="badge bg-white text-success ms-2">Aktif</span>
                            <?php endif; ?>
                        </button>
                        <button class="btn btn-outline-secondary" id="resetFilter">
                            <i class="fas fa-sync-alt me-2"></i>Reset Filter
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Produk Section -->
            <div class="col-md-9">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted">Menampilkan <span class="text-success fw-bold"><?php echo count($products); ?></span> produk dari <span class="text-success fw-bold"><?php echo $total_items; ?></span> total produk</h5>
                    </div>
                </div>
                
                <?php if(empty($products)): ?>
                <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 15px;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-3 text-blue" style="font-size: 1.5rem;"></i>
                        <div>
                            <h5 class="mb-1">Tidak ada produk ditemukan</h5>
                            <p class="mb-0">Tidak ada produk yang ditemukan dengan filter saat ini. <a href="lihat_produk.php" class="text-primary fw-bold">Tampilkan semua produk</a></p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card h-100 d-flex flex-column">
                            <div class="position-relative" style="overflow: hidden; border-radius: 20px 20px 0 0;">
                                <a href="detail_barang.php?id=<?php echo $product['id_barang']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['gambar'] ?? 'https://via.placeholder.com/300'); ?>" 
                                        class="product-img" 
                                        alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                                </a>
                                <span class="badge <?php 
                                    if ($product['stok'] == 0) {
                                        echo 'bg-danger'; 
                                    } elseif ($product['stok'] < 25) {
                                        echo 'bg-warning text-dark'; 
                                    } else {
                                        echo 'bg-success';
                                    }
                                ?> stock-badge">
                                    <?php echo $product['stok'] > 0 ? 'Stok: '.$product['stok'] : 'Habis'; ?>
                                </span>
                            </div>

                            <div class="p-3 d-flex flex-column h-100">
                                <div class="mb-2">
                                    <span class="badge badge-category"><?php 
                                        $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
                                        $stmt->execute([$product['id_kategori']]);
                                        $kategori = $stmt->fetch();
                                        echo htmlspecialchars($kategori['nama_kategori']);
                                    ?></span>
                                </div>
                                <h5 class="fw-bold mb-2">
                                    <a href="detail_barang.php?id=<?php echo $product['id_barang']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($product['nama_barang']); ?>
                                    </a>
                                </h5>
                                <p class="price mb-2">Rp <?php echo number_format($product['harga_eceran'], 0, ',', '.'); ?></p>
                                <p class="text-muted small mb-3 flex-grow-1"><?php 
                                    echo strlen($product['deskripsi']) > 100 ? 
                                        substr(htmlspecialchars($product['deskripsi']), 0, 100).'...' : 
                                        htmlspecialchars($product['deskripsi']);
                                ?></p>

                                <div class="d-grid gap-2 mt-auto">
                                    <a href="detail_barang.php?id=<?php echo $product['id_barang']; ?>" class="btn btn-success">
                                        <i class="fas fa-eye me-2"></i>Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page-1; ?><?php echo isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>" tabindex="-1">
                                <i class="fas fa-chevron-left me-1"></i>Sebelumnya
                            </a>
                        </li>
                        
                        <?php 
                        // Tampilkan maksimal 5 nomor halaman di sekitar current page
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1'.(isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : '').(isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : '').'">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php 
                        endfor;
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : '').(isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : '').'">'.$total_pages.'</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page+1; ?><?php echo isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">
                                Selanjutnya <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php if($is_logged_in): ?>
    <!-- Footer untuk anggota (sudah login) -->
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
                    <h6 class="text-white fw-bold mb-3">Akun</h6>
                    <a href="dashboard.php" class="footer-link">Dashboard</a>
                    <a href="#" class="footer-link">Produk</a>
                    <a href="keranjang.php" class="footer-link">Keranjang</a>
                    <a href="pesanan.php" class="footer-link">Pesanan</a>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-white fw-bold mb-3">Produk</h6>
                    <a href="#" class="footer-link">Alat Pertanian</a>
                    <a href="#" class="footer-link">Pupuk Organik</a>
                    <a href="#" class="footer-link">Benih Unggul</a>
                    <a href="#" class="footer-link">Pestisida Alami</a>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h6 class="text-white fw-bold mb-3">Layanan Pelanggan</h6>
                    <div class="footer-link">
                        <i class="fas fa-envelope me-2"></i>
                        cs@tokohijau.com
                    </div>
                    <div class="footer-link">
                        <i class="fas fa-phone me-2"></i>
                        +62 123 4567 890 (CS)
                    </div>
                    <div class="footer-link">
                        <i class="fas fa-clock me-2"></i>
                        Senin-Jumat, 08:00-17:00
                    </div>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: #374151;">
            <div class="text-center">
                <p class="text-white mb-0">&copy; 2024 Toko Hijau. Semua hak dilindungi undang-undang.</p>
            </div>
        </div>
    </footer>
    <?php else: ?>
    <!-- Footer untuk user guest (belum login) -->
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
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceRange = document.getElementById('priceRange');
            const priceValue = document.getElementById('priceValue');
            
            // Update tampilan harga saat slider diubah
            priceRange.addEventListener('input', function() {
                priceValue.textContent = 'Rp ' + parseInt(priceRange.value).toLocaleString('id-ID');
            });
            
            // Apply Filter
            document.getElementById('applyFilter').addEventListener('click', function() {
                const selectedCategories = [];
                document.querySelectorAll('.form-check-input:checked').forEach(checkbox => {
                    selectedCategories.push(checkbox.value);
                });
                
                const maxPrice = priceRange.value;
                let queryParams = [];
                
                if (selectedCategories.length > 0) {
                    queryParams.push('kategori=' + encodeURIComponent(selectedCategories.join(',')));
                }
                
                if (maxPrice < 1000000) {
                    queryParams.push('max_price=' + encodeURIComponent(maxPrice));
                }
                
                window.location.href = 'lihat_produk.php' + (queryParams.length > 0 ? '?' + queryParams.join('&') : '');
            });
            
            // Reset Filter
            document.getElementById('resetFilter').addEventListener('click', function() {
                // Reset semua input filter
                document.querySelectorAll('.form-check-input').forEach(checkbox => {
                    checkbox.checked = false;
                });
                priceRange.value = 1000000;
                priceValue.textContent = 'Rp 1.000.000';
                
                // Redirect tanpa parameter
                window.location.href = 'lihat_produk.php';
            });
        });
    </script>
</body>
</html>