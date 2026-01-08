<?php
include 'koneksi.php';

// Periksa apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Ambil ID barang dari parameter URL
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: produk.php");
    exit();
}

$id_barang = (int)$_GET['id'];

// Query untuk mendapatkan detail barang
$query_barang = $conn->prepare("SELECT b.*, k.nama_kategori 
                              FROM barang b 
                              JOIN kategori k ON b.id_kategori = k.id_kategori 
                              WHERE b.id_barang = ?");
$query_barang->bind_param("i", $id_barang);
$query_barang->execute();
$result_barang = $query_barang->get_result();

if($result_barang->num_rows === 0) {
    header("Location: produk.php");
    exit();
}

$barang = $result_barang->fetch_assoc();

// Query untuk mendapatkan barang terkait (dari kategori yang sama)
$query_related = $conn->prepare("SELECT * FROM barang 
                                WHERE id_kategori = ? AND id_barang != ? 
                                ORDER BY RAND() LIMIT 4");
$query_related->bind_param("ii", $barang['id_kategori'], $id_barang);
$query_related->execute();
$related_products = $query_related->get_result();

// Jika user sudah login, ambil data user dan cek keranjang
$nama_user = 'Guest';
$in_cart = 0;

if($is_logged_in) {
    $user_data = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = $user_id")->fetch_assoc();
    $nama_user = $user_data['nama'];
    
    $check_cart = $conn->prepare("SELECT jumlah FROM keranjang 
                                 WHERE id_pengguna = ? AND id_barang = ?");
    $check_cart->bind_param("ii", $user_id, $id_barang);
    $check_cart->execute();
    $cart_result = $check_cart->get_result();
    $in_cart = $cart_result->num_rows > 0 ? $cart_result->fetch_assoc()['jumlah'] : 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($barang['nama_barang']); ?> - Toko Pertanian</title>
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

        /* Product Detail Section */
        .product-gallery {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            background: white;
        }

        .main-image {
            height: 500px;
            object-fit: contain;
            background-color: #f8f9fa;
            width: 100%;
        }

        .thumbnail {
            height: 80px;
            width: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .thumbnail:hover, .thumbnail.active {
            border-color: var(--primary-green);
            transform: translateY(-3px);
        }

        .product-info-card {
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            background: white;
            padding: 2rem;
            position: relative;
        }

        .product-info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .price-tag {
            font-size: 1.8rem;
            color: var(--primary-green);
            font-weight: bold;
        }

        .stock-badge {
            font-size: 0.9rem;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-weight: 600;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .quantity-btn {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: white;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: var(--light-green);
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .related-product-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s;
            background: white;
            position: relative;
        }

        .related-product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .related-product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .related-product-img {
            height: 180px;
            object-fit: contain;
            background-color: #f8f9fa;
            width: 100%;
        }

        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient-1);
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #e5e7eb;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 500;
            padding: 0.75rem 1.5rem !important;
            margin-right: 0.5rem;
            border-radius: 12px 12px 0 0;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-green);
            background: var(--light-green);
            border-bottom: 3px solid var(--primary-green);
        }

        .tab-content {
            padding: 2rem;
            border-radius: 0 0 20px 20px;
            background: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-top: none;
        }

        /* Badges */
        .badge-category {
            background: var(--light-green);
            color: var(--dark-green);
            font-weight: 600;
            border-radius: 20px;
            padding: 0.5rem 1rem;
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

        /* Footer */
        .footer-modern {
            background: #111827;
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
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

        /* Review Stars */
        .rating-input {
            display: flex;
            gap: 10px;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input label {
            color: #e5e7eb;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .rating-input input[type="radio"]:checked ~ label {
            color: #f59e0b;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #f59e0b;
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

        /* Responsive */
        @media (max-width: 768px) {
            .main-image {
                height: 350px;
            }
            
            .product-info-card {
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>">
                <i class="fas fa-seedling me-2"></i>Toko Hijau
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if($is_logged_in): ?>
                        <!-- User Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:history.back()">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                        </li>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($nama_user); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>">
                                <i class="fas fa-home me-1"></i> Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="lihat_produk.php">
                                <i class="fas fa-store me-1"></i> Produk
                            </a>
                        </li>
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

    <!-- Main Content -->
    <div class="container my-5" style="padding-top: 6rem;">
        <div class="row mb-5">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-gallery">
                    <img id="mainImage" src="<?php echo !empty($barang['gambar']) ? htmlspecialchars($barang['gambar']) : 'https://via.placeholder.com/500?text=Produk+Tidak+Tersedia'; ?>" class="img-fluid w-100 main-image" alt="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                    
                    <?php 
                    // Jika ada gambar tambahan, tampilkan thumbnail
                    $additional_images = [];
                    if(!empty($barang['gambar_tambahan'])) {
                        $additional_images = explode(',', $barang['gambar_tambahan']);
                    }
                    
                    if(!empty($additional_images)): ?>
                    <div class="p-3 d-flex flex-wrap gap-2">
                        <img src="<?php echo htmlspecialchars($barang['gambar']); ?>" class="thumbnail active" onclick="changeImage(this)" alt="Gambar Utama">
                        <?php foreach($additional_images as $img): ?>
                        <img src="<?php echo htmlspecialchars(trim($img)); ?>" class="thumbnail" onclick="changeImage(this)" alt="Gambar Tambahan">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info-card">
                    <h2 class="mb-3 fw-bold"><?php echo htmlspecialchars($barang['nama_barang']); ?></h2>
                    
                    <div class="d-flex align-items-center mb-3">
                        <span class="price-tag me-3">Rp <?php echo number_format($barang['harga_eceran'], 0, ',', '.'); ?></span>
                        <span class="badge <?php echo ($barang['stok'] > 0) ? 'bg-success' : 'bg-danger'; ?> stock-badge">
                            <?php echo ($barang['stok'] > 0) ? 'Stok Tersedia' : 'Stok Habis'; ?>
                        </span>
                    </div>
                    
                    <div class="mb-4">
                        <span class="text-muted">Kategori:</span>
                        <a href="lihat_produk.php?kategori=<?php echo $barang['id_kategori']; ?>" class="text-decoration-none">
                            <span class="badge badge-category"><?php echo htmlspecialchars($barang['nama_kategori']); ?></span>
                        </a>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-title">Deskripsi Produk</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($barang['deskripsi'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-title">Spesifikasi</h5>
                        <ul class="list-unstyled">
                            <?php if(!empty($barang['merk'])): ?>
                            <li class="mb-2"><strong>Merk:</strong> <?php echo htmlspecialchars($barang['merk']); ?></li>
                            <?php endif; ?>
                            
                            <li class="mb-2"><strong>Stok:</strong> <?php echo htmlspecialchars($barang['stok'] . ' ' . $barang['satuan']); ?></li>
                            
                            <li class="mb-2"><strong>Harga Eceran:</strong> Rp <?php echo number_format($barang['harga_eceran'], 0, ',', '.'); ?></li>
                            
                            <?php if(!empty($barang['harga_grosir'])): ?>
                            <li class="mb-2"><strong>Harga Grosir:</strong> Rp <?php echo number_format($barang['harga_grosir'], 0, ',', '.'); ?></li>
                            <?php endif; ?>
                            
                            <?php if(!empty($barang['id_penjual'])): 
                                $query_penjual = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = ".$barang['id_penjual']);
                                $penjual = $query_penjual->fetch_assoc();
                            ?>
                            <li class="mb-2"><strong>Penjual:</strong> <?php echo htmlspecialchars($penjual['nama']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if($barang['stok'] > 0): ?>
                        <?php if($is_logged_in): ?>
                            <form action="proses_keranjang.php" method="post" class="mb-4">
                                <input type="hidden" name="id_barang" value="<?php echo $id_barang; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-4 mb-2">
                                        <label for="quantity" class="form-label">Jumlah:</label>
                                        <div class="input-group">
                                            <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                                            <input type="number" class="form-control text-center quantity-input" id="quantity" name="quantity" 
                                                value="<?php echo max(1, $in_cart); ?>" min="1" max="<?php echo $barang['stok']; ?>">
                                            <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-8 mt-4">
                                        <button type="submit" name="add_to_cart" class="btn btn-success w-100 py-2">
                                            <i class="fas fa-cart-plus me-2"></i> Tambah ke Keranjang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning border-0" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-3 text-warning" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-1">Anda belum login</h5>
                                        <p class="mb-0">Silakan <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-success fw-bold">login</a> untuk dapat menambahkan produk ke keranjang.</p>
                                    </div>
                                </div>
                            </div>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-success w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i> Login untuk Belanja
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning border-0" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left: 4px solid #ef4444;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 text-danger" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">Stok Habis</h5>
                                    <p class="mb-0">Produk ini sedang tidak tersedia.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="row mb-5">
            <div class="col-12">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab">Detail Produk</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">Spesifikasi Lengkap</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Ulasan</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="productTabsContent">
                    <div class="tab-pane fade show active" id="detail" role="tabpanel">
                        <h5 class="fw-bold mb-4">Informasi Produk</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($barang['deskripsi'] ?: 'Tidak ada deskripsi tambahan')); ?></p>
                        
                        <?php if(!empty($barang['gambar'])): ?>
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <img src="<?php echo htmlspecialchars($barang['gambar']); ?>" class="img-fluid rounded" alt="Gambar Produk">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="specs" role="tabpanel">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th width="30%">Nama Produk</th>
                                    <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                </tr>
                                <tr>
                                    <th>Kategori</th>
                                    <td><?php echo htmlspecialchars($barang['nama_kategori']); ?></td>
                                </tr>
                                <?php if(!empty($barang['merk'])): ?>
                                <tr>
                                    <th>Merk</th>
                                    <td><?php echo htmlspecialchars($barang['merk']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Stok</th>
                                    <td><?php echo htmlspecialchars($barang['stok'] . ' ' . $barang['satuan']); ?></td>
                                </tr>
                                <tr>
                                    <th>Harga Eceran</th>
                                    <td>Rp <?php echo number_format($barang['harga_eceran'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php if(!empty($barang['harga_grosir'])): ?>
                                <tr>
                                    <th>Harga Grosir</th>
                                    <td>Rp <?php echo number_format($barang['harga_grosir'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if(!empty($barang['id_penjual'])): ?>
                                <tr>
                                    <th>Penjual</th>
                                    <td><?php echo htmlspecialchars($penjual['nama']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Tanggal Ditambahkan</th>
                                    <td><?php echo date('d F Y', strtotime($barang['tanggal_tambah'])); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-0">Ulasan Produk</h5>
                                <div class="d-flex align-items-center mt-2">
                                    <div class="text-warning me-2">
                                        <?php 
                                        $avg_rating = 4.5; // Ini bisa diambil dari database
                                        $full_stars = floor($avg_rating);
                                        $half_star = ($avg_rating - $full_stars) >= 0.5;
                                        
                                        for($i = 1; $i <= 5; $i++) {
                                            if($i <= $full_stars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif($half_star && $i == $full_stars + 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-muted"><?php echo $avg_rating; ?> dari 5 (12 ulasan)</span>
                                </div>
                            </div>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-pen me-2"></i>Tulis Ulasan
                            </button>
                        </div>
                        
                        <div class="review-list">
                            <!-- Contoh ulasan -->
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Budi Santoso</h6>
                                            <small class="text-muted">12 Juni 2023</small>
                                        </div>
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-muted">Produk sangat bagus, sesuai dengan deskripsi. Pengiriman juga cepat. Akan beli lagi lain kali.</p>
                                </div>
                            </div>
                            
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Ani Wijaya</h6>
                                            <small class="text-muted">5 Juni 2023</small>
                                        </div>
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-muted">Kualitas produk baik, tapi pengemasan kurang rapi. Overall puas dengan pembelian ini.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-secondary">
                                <i class="fas fa-chevron-down me-2"></i>Lihat Semua Ulasan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <div class="row">
            <div class="col-12">
                <h4 class="section-title">Produk Terkait</h4>
                <div class="row">
                    <?php if($related_products->num_rows > 0): 
                    while($related = $related_products->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="related-product-card h-100">
                            <a href="detail_barang.php?id=<?php echo $related['id_barang']; ?>" class="text-decoration-none">
                                <img src="<?php echo !empty($related['gambar']) ? htmlspecialchars($related['gambar']) : 'https://via.placeholder.com/300?text=Produk+Tidak+Tersedia'; ?>" class="img-fluid related-product-img" alt="<?php echo htmlspecialchars($related['nama_barang']); ?>">
                                <div class="p-3">
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($related['nama_barang']); ?></h6>
                                    <p class="text-success fw-bold mb-1">Rp <?php echo number_format($related['harga_eceran'], 0, ',', '.'); ?></p>
                                    <small class="text-muted">Stok: <?php echo htmlspecialchars($related['stok'] . ' ' . $related['satuan']); ?></small>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; 
                    else: ?>
                    <div class="col-12">
                        <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle me-3 text-blue" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">Tidak ada produk terkait</h5>
                                    <p class="mb-0">Tidak ada produk lain dalam kategori ini.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tulis Ulasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_ulasan.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id_barang" value="<?php echo $id_barang; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3">Rating</label>
                            <div class="rating-input">
                                <input type="radio" id="star5" name="rating" value="5">
                                <label for="star5"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                                            <div class="mb-3">
                        <label for="reviewText" class="form-label fw-bold">Ulasan Anda</label>
                        <textarea class="form-control" id="reviewText" name="review" rows="5" placeholder="Bagikan pengalaman Anda menggunakan produk ini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Kirim Ulasan</button>
                </div>
            </form>
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
                    <a href="index.php" class="footer-link">Beranda</a>
                    <a href="#" class="footer-link">Produk</a>
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
    // Fungsi untuk mengganti gambar utama
    function changeImage(element) {
        const mainImage = document.getElementById('mainImage');
        mainImage.src = element.src;
        
        // Hapus kelas active dari semua thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        
        // Tambahkan kelas active ke thumbnail yang diklik
        element.classList.add('active');
    }
    
    // Fungsi untuk menambah jumlah produk
    function incrementQuantity() {
        const quantityInput = document.getElementById('quantity');
        const maxStock = parseInt(quantityInput.max);
        let currentValue = parseInt(quantityInput.value);
        
        if(currentValue < maxStock) {
            quantityInput.value = currentValue + 1;
        }
    }
    
    // Fungsi untuk mengurangi jumlah produk
    function decrementQuantity() {
        const quantityInput = document.getElementById('quantity');
        let currentValue = parseInt(quantityInput.value);
        
        if(currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    }
    
    // Validasi input quantity
    document.getElementById('quantity').addEventListener('change', function() {
        const maxStock = parseInt(this.max);
        let currentValue = parseInt(this.value);
        
        if(isNaN(currentValue) || currentValue < 1) {
            this.value = 1;
        } else if(currentValue > maxStock) {
            this.value = maxStock;
        }
    });
</script>
</body> 
</html>