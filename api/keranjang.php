<?php
include 'koneksi.php';

// Periksa apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_data = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = $user_id")->fetch_assoc();
$nama_user = $user_data['nama'];

// Proses hapus item dari keranjang
if(isset($_GET['hapus'])) {
    $id_keranjang = (int)$_GET['hapus'];
    
    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // Dapatkan informasi item keranjang dan kunci baris untuk update
        $item = $conn->query("SELECT k.id_barang, k.jumlah, b.stok 
                             FROM keranjang k 
                             JOIN barang b ON k.id_barang = b.id_barang 
                             WHERE k.id_keranjang = $id_keranjang AND k.id_pengguna = $user_id 
                             FOR UPDATE")->fetch_assoc();
        
        if(!$item) {
            throw new Exception("Item keranjang tidak ditemukan");
        }
        
        // Kembalikan stok
        $new_stock = $item['stok'] + $item['jumlah'];
        $conn->query("UPDATE barang SET stok = $new_stock WHERE id_barang = {$item['id_barang']}");
        
        // Hapus dari keranjang
        $conn->query("DELETE FROM keranjang WHERE id_keranjang = $id_keranjang AND id_pengguna = $user_id");
        
        $conn->commit();
        $_SESSION['success_message'] = "Item berhasil dihapus dari keranjang";
        header("Location: keranjang.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: keranjang.php");
        exit();
    }
}

// Proses update jumlah item
if(isset($_POST['update_keranjang'])) {
    $conn->begin_transaction();
    
    try {
        foreach($_POST['jumlah'] as $id_keranjang => $jumlah) {
            $id_keranjang = (int)$id_keranjang;
            $jumlah = (int)$jumlah;
            
            // Dapatkan info produk
            $item = $conn->query("SELECT k.id_barang, b.stok, k.jumlah as old_jumlah 
                                 FROM keranjang k 
                                 JOIN barang b ON k.id_barang = b.id_barang 
                                 WHERE k.id_keranjang = $id_keranjang 
                                 FOR UPDATE")->fetch_assoc();
            
            if(!$item) continue;
            
            $difference = $jumlah - $item['old_jumlah'];
            
            if($difference > $item['stok']) {
                throw new Exception("Stok tidak mencukupi untuk produk tertentu");
            }
            
            // Update jumlah di keranjang
            $conn->query("UPDATE keranjang SET jumlah = $jumlah WHERE id_keranjang = $id_keranjang");
            
            // Update stok
            $conn->query("UPDATE barang SET stok = stok - $difference WHERE id_barang = {$item['id_barang']}");
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Keranjang berhasil diperbarui";
        header("Location: keranjang.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: keranjang.php");
        exit();
    }
}

// Query untuk mendapatkan isi keranjang
$keranjang = $conn->query("SELECT k.id_keranjang, k.id_barang, k.jumlah, 
                          b.nama_barang, b.harga_eceran, b.harga_grosir, b.gambar, b.stok, b.satuan
                          FROM keranjang k
                          JOIN barang b ON k.id_barang = b.id_barang
                          WHERE k.id_pengguna = $user_id");

// Hitung total keranjang
$total_keranjang = 0;
$items_in_cart = $keranjang->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Hijau</title>
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

        /* Cart Items */
        .cart-item-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Page Header */
        .page-header {
            background: var(--gradient-1);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 80px;
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

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
        }

        .quantity-input:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.25);
        }

        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }

        .empty-cart {
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }

        /* Summary Card */
        .summary-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 20px;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        /* Buttons */
        .btn-outline-success {
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .btn-outline-success:hover {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-success {
            background: var(--gradient-1);
            border: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
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
            .cart-item-img {
                width: 80px;
                height: 80px;
            }
            
            .summary-card {
                position: static;
                margin-top: 2rem;
            }

            .page-header {
                padding: 2rem 0 1rem !important;
                margin-top: 60px;
            }
            
            .page-title {
                font-size: 1.5rem !important;
                margin-bottom: 0.5rem;
            }
            
            .page-header .lead {
                font-size: 0.9rem;
            }

            .main-content {
                padding-top: 1rem;
            }
            
            .cart-item-img {
                width: 60px;
                height: 60px;
            }
            
            .table-responsive {
                border: 0;
            }
            
            .table tbody tr {
                display: flex;
                flex-direction: column;
                border-bottom: 1px solid #dee2e6;
                padding: 1rem 0;
            }
            
            .table tbody td {
                border: none;
                padding: 0.5rem 0;
            }
            
            .table tbody td:before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                width: 100px;
            }
            
            .quantity-input {
                width: 100%;
                max-width: 100px;
            }
            
            .btn-outline-success, 
            .btn-outline-primary {
                width: 100%;
            }
            
            .summary-card {
                margin-top: 0 !important;
            }
        }

        @media (max-width: 576px) {            
            .page-header {
                padding: 1.5rem 0 !important;
            }
            
            .page-title {
                font-size: 1.3rem !important;
            }
            
            .page-header .lead {
                font-size: 0.8rem;
            }
            
            .card-header h4, 
            .card-header h5 {
                font-size: 1.2rem;
            }
            
            .footer-modern {
                padding: 2rem 0 1rem !important;
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
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lihat_produk.php"><i class="fas fa-store me-1"></i> Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="keranjang.php">
                            <i class="fas fa-shopping-cart me-1"></i> Keranjang
                            <?php if($items_in_cart > 0): ?>
                            <span class="badge bg-light text-dark"><?php echo $items_in_cart; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Pesanan</a>
                    </li>
                    <li class="nav-item dropdown">
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
    <section class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title">Keranjang Saya</h1>
                <p class="lead mb-0">Daftar belanjaan Anda. Cek produk, jumlah, dan total harga sebelum checkout.</p>
            </div>
        </div>
    </section>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-5" role="alert" style="margin-top: 80px !important;">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-5" role="alert" style="margin-top: 80px !important;">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container my-5" style="margin-top: 100px !important;">
        <div class="row">
            <div class="col-lg-8 order-lg-1 order-2">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if($items_in_cart > 0): ?>
                        <form action="keranjang.php" method="post">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="d-none d-md-table-header-group">
                                        <tr>
                                            <th width="50%">Produk</th>
                                            <th width="20%">Harga</th>
                                            <th width="20%">Jumlah</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($item = $keranjang->fetch_assoc()): 
                                            $jumlah = $item['jumlah'];
                                            $harga_eceran = $item['harga_eceran'];
                                            $harga_grosir = $item['harga_grosir'];
                                            
                                            // Hitung harga normal (eceran) dan harga grosir
                                            $harga_normal = $jumlah * $harga_eceran;
                                            
                                            // Gunakan harga grosir jika jumlah >= 50, selain itu harga eceran
                                            if ($jumlah >= 50 && $harga_grosir > 0) {
                                                $harga_satuan = $harga_grosir;
                                                $subtotal = $jumlah * $harga_grosir;
                                                $hemat = $harga_normal - $subtotal;
                                            } else {
                                                $harga_satuan = $harga_eceran;
                                                $subtotal = $harga_normal;
                                                $hemat = 0;
                                            }
                                            
                                            $total_keranjang += $subtotal;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo !empty($item['gambar']) ? htmlspecialchars($item['gambar']) : 'https://via.placeholder.com/100?text=Produk'; ?>" 
                                                        class="cart-item-img me-3" 
                                                        alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_barang']); ?></h6>
                                                        <small class="text-muted">Stok: <?php echo htmlspecialchars($item['stok'] . ' ' . $item['satuan']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="mb-0 text-success fw-bold">
                                                    Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?>
                                                </p>
                                                <?php if($harga_grosir > 0): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-boxes me-1"></i> Grosir: Rp <?php echo number_format($harga_grosir, 0, ',', '.'); ?> (min. 50)
                                                    </small>
                                                <?php endif; ?>
                                                <?php if($hemat > 0): ?>
                                                    <br>
                                                    <small class="text-success">
                                                        <i class="fas fa-tag me-1"></i> Hemat Rp <?php echo number_format($hemat, 0, ',', '.'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <input type="number" class="form-control quantity-input" 
                                                        name="jumlah[<?php echo $item['id_keranjang']; ?>]" 
                                                        value="<?php echo $item['jumlah']; ?>" 
                                                        min="1" max="<?php echo $item['stok']; ?>">
                                                    <small class="text-muted ms-2 d-md-none"><?php echo htmlspecialchars($item['satuan']); ?></small>
                                                </div>
                                            </td>
                                            <td class="text-md-center">
                                                <a href="keranjang.php?hapus=<?php echo $item['id_keranjang']; ?>" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash-alt"></i>
                                                    <span class="d-md-none ms-1">Hapus</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex flex-column flex-md-row justify-content-between mt-4 gap-2">
                                <a href="lihat_produk.php" class="btn btn-outline-success order-md-1 order-2">
                                    <i class="fas fa-arrow-left me-1"></i> Lanjutkan Belanja
                                </a>
                                <button type="submit" name="update_keranjang" class="btn btn-outline-primary order-md-2 order-1">
                                    <i class="fas fa-sync-alt me-1"></i> Perbarui Keranjang
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart empty-cart-icon"></i>
                            <h4 class="mb-3">Keranjang Belanja Kosong</h4>
                            <p class="text-muted mb-4">Anda belum menambahkan produk ke keranjang belanja</p>
                            <a href="lihat_produk.php" class="btn btn-success">
                                <i class="fas fa-store me-1"></i> Mulai Belanja
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($items_in_cart > 0): ?>
            <div class="col-lg-4 order-lg-2 order-1 mb-4 mb-lg-0">
                <div class="summary-card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 ms-lg-3 fw-bold text-success"><i class="fas fa-receipt me-2"></i>Ringkasan Belanja</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid #f0f0f0;">
                            <span class="text-muted">Total Item</span>
                            <span class="fw-bold"><?php echo $items_in_cart; ?> produk</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid #f0f0f0;">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold">Rp <?php echo number_format($total_keranjang, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2" style="border-bottom: 1px solid #f0f0f0;">
                            <span class="text-muted">Pengiriman</span>
                            <span class="fw-bold text-success">Gratis</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 py-3 bg-light rounded px-3">
                            <span class="fw-bold fs-5">Total Pembayaran</span>
                            <span class="text-success fw-bold fs-5">Rp <?php echo number_format($total_keranjang, 0, ',', '.'); ?></span>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-success w-100 py-3 d-flex align-items-center justify-content-center mb-4">
                            <i class="fas fa-credit-card me-2"></i> Proses Checkout
                        </a>
                        
                        <div class="text-center pt-3" style="border-top: 1px solid #f0f0f0;">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-lock me-1"></i> Pembayaran aman dan terenkripsi
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i> Garansi 100% uang kembali
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
                    <a href="#" class="footer-link">Keranjang</a>
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

        // Animasi untuk elemen saat muncul
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card, .summary-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>