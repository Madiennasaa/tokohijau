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

// Kode checkout yang sudah ada...
if(isset($_POST['checkout'])) {
    // ... (kode checkout yang sudah ada)
}

// Kode query pesanan
$pesanan_result = $conn->query("
    SELECT p.*, 
           mp.nama_metode,
           GROUP_CONCAT(b.nama_barang SEPARATOR ', ') AS nama_barang, 
           GROUP_CONCAT(b.gambar SEPARATOR '||') AS gambar_barang,
           GROUP_CONCAT(
               CASE 
                   WHEN dp.jumlah >= 50 AND b.harga_grosir > 0 THEN b.harga_grosir
                   ELSE b.harga_eceran
               END 
               SEPARATOR '||'
           ) AS harga_item,
           GROUP_CONCAT(b.harga_eceran SEPARATOR '||') AS harga_eceran,
           GROUP_CONCAT(b.harga_grosir SEPARATOR '||') AS harga_grosir,
           GROUP_CONCAT(dp.jumlah SEPARATOR '||') AS jumlah_item
    FROM pesanan p
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN barang b ON dp.id_barang = b.id_barang
    LEFT JOIN pembayaran pm ON p.id_pembayaran = pm.id_pembayaran
    LEFT JOIN metode_pembayaran mp ON pm.id_metode = mp.id_metode
    WHERE p.id_pengguna = $user_id
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_pesan DESC
");

$processed_orders = [];
if($pesanan_result) {
    while($pesanan = $pesanan_result->fetch_assoc()) {
        // Proses data harga
        $harga_items = explode('||', $pesanan['harga_item']);
        $jumlah_items = explode('||', $pesanan['jumlah_item']);
        
        // Hitung ulang total harga berdasarkan harga yang sudah dipilih (eceran/grosir)
        $total_harga = 0;
        for($i = 0; $i < count($harga_items); $i++) {
            $total_harga += $harga_items[$i] * $jumlah_items[$i];
        }
        
        $pesanan['total_harga'] = $total_harga;
        $processed_orders[] = $pesanan;
    }
}

if(isset($_POST['cancel_order'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    
    // Validasi kepemilikan pesanan
    $check_order = $conn->query("SELECT id_pengguna FROM pesanan WHERE id_pesanan = $id_pesanan");
    if($check_order->num_rows == 0 || $check_order->fetch_assoc()['id_pengguna'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak valid atau tidak ditemukan']);
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        // Ambil detail pesanan untuk mengembalikan stok
        $detail_query = $conn->query("SELECT id_barang, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
        
        // Kembalikan stok untuk setiap item
        while($item = $detail_query->fetch_assoc()) {
            $conn->query("UPDATE barang SET stok = stok + {$item['jumlah']} WHERE id_barang = {$item['id_barang']}");
        }
        
        // Update status pesanan menjadi 'dibatalkan'
        $update = $conn->query("UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = $id_pesanan");
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan dan stok dikembalikan']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Toko Hijau</title>
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

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .dropdown-item:hover {
            background: var(--light-green);
            color: var(--primary-green);
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

        /* Main Content */
        .main-content {
            padding: 4rem 0;
        }

        /* Checkout Section */
        .checkout-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .checkout-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .checkout-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .checkout-title i {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .btn-checkout {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem 3rem;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.3);
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(34, 197, 94, 0.4);
            color: white;
        }

        /* Order Cards */
        .orders-section {
            margin-top: 2rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2rem;
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

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        /* Tambahkan di bagian CSS */
        .order-header img {
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease;
        }

        .order-header img:hover {
            transform: scale(1.1);
        }

        .order-code {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
        }

        .status-badge {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-disetujui {
            background: #d1fae5;
            color: #065f46;
        }

        .status-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-info {
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: #6b7280;
        }

        .info-item i {
            width: 20px;
            margin-right: 0.8rem;
            color: var(--primary-green);
        }

        .total-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-green);
        }

        .order-actions {
            display: flex;
            margin-top: auto; /* Ini akan mendorong tombol ke bawah */
            padding-top: 1rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .order-actions {
            display: flex;
            margin-top: auto;
            padding-top: 1rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-modern {
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1 0 auto;
            min-width: 100px;
        }

        .btn-detail {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-detail:hover {
            background: var(--light-green);
            color: var(--primary-green);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-cancel:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .btn-add-order {
            background: var(--gradient-1);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-add-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .empty-description {
            color: #9ca3af;
            margin-bottom: 2rem;
        }

        /* Alert Styles */
        .alert-modern {
            border: none;
            border-radius: 15px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid #f3f4f6;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-weight: 700;
            color: #1f2937;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #f3f4f6;
            padding: 1.5rem 2rem;
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

        @media (max-width: 992px) {
            .order-card {
                padding: 1.5rem;
            }
            
            .order-code {
                font-size: 1rem;
            }
            
            .total-price {
                font-size: 1.1rem;
            }
            
            .btn-modern {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 2rem 1rem; /* Tambahkan padding kiri-kanan */
            }

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

            .orders-section .section-title {
                font-size: 1.5rem;
            }
            
            .order-card {
                padding: 1.25rem;
            }

            .orders-section {
                padding: 0 0.5rem; /* Sedikit padding untuk section */
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .status-badge {
                align-self: flex-start;
            }
            
            .order-actions {
                flex-direction: row;
            }
            
            .btn-modern {
                flex: 1 1 calc(50% - 0.5rem);
                min-width: 0;
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

            .orders-section {
                margin-top: 1rem;
            }
            
            .orders-section .section-title {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }
            
            .order-card {
                margin: 0 0.5rem 1rem 0.5rem; /* Margin kiri-kanan */
                width: calc(100% - 1rem); /* Sesuaikan lebar dengan margin */
            }
            
            .row {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }
            
            .col-12 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .info-item {
                font-size: 0.9rem;
            }
            
            .checkout-section {
                padding: 1.5rem;
            }
            
            .checkout-title {
                font-size: 1.25rem;
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
                        <a class="nav-link" href="keranjang.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Pesanan</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($nama_user); ?>
                        </a>
                        <ul class="dropdown-menu">
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
                <h1 class="page-title">Pesanan Saya</h1>
                <p class="lead mb-0">Detail pemesanan Anda. Pastikan alamat dan item sudah benar sebelum bayar.</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <!-- Checkout Section -->
        <?php
        $keranjang_check = $conn->query("SELECT COUNT(*) as total FROM keranjang WHERE id_pengguna = $user_id");
        $items_in_cart = $keranjang_check->fetch_assoc()['total'];
        
        if($items_in_cart > 0):
        ?>
        <div class="checkout-section">
            <h4 class="checkout-title">
                <i class="fas fa-shopping-cart"></i>
                Checkout Keranjang
            </h4>
            <p class="text-muted mb-4">Anda memiliki <strong><?php echo $items_in_cart; ?></strong> item di keranjang. Silakan lengkapi informasi berikut untuk membuat pesanan.</p>
            
            <form action="pesanan.php" method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="alamat" class="form-label">Alamat Pengiriman <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="4" required placeholder="Masukkan alamat lengkap untuk pengiriman"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="catatan" class="form-label">Catatan Tambahan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Catatan khusus untuk pesanan (opsional)"></textarea>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" name="checkout" class="btn btn-checkout">
                        <i class="fas fa-check me-2"></i>Buat Pesanan
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Orders Section -->
        <div class="orders-section">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                <h2 class="section-title mb-3 mb-md-0">Daftar Pesanan</h2>
                <a href="lihat_produk.php" class="btn btn-add-order">
                    <i class="fas fa-plus me-2"></i>Tambah Pesanan
                </a>
            </div>

            <!-- Pesanan List -->
            <?php if(!empty($processed_orders)): ?>
            <div class="row mx-0 mx-md-3">
                <?php foreach($processed_orders as $pesanan): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4 px-2 px-md-3">
                    <div class="order-card h-100 mx-1 mx-md-0 mt-3">
                        <div class="order-header">
                            <div class="d-flex align-items-center">
                                <?php 
                                $gambar_barang = !empty($pesanan['gambar_barang']) ? explode('||', $pesanan['gambar_barang'])[0] : 'https://via.placeholder.com/300';
                                ?>
                                <img src="<?php echo htmlspecialchars($gambar_barang); ?>" 
                                    alt="<?php echo htmlspecialchars($pesanan['nama_barang']); ?>" 
                                    class="rounded me-2" 
                                    style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="order-code" title="<?php echo htmlspecialchars($pesanan['nama_barang']); ?>">
                                    <?php 
                                    $nama_barang = $pesanan['nama_barang'] ?? 'Tidak ada barang';
                                    echo htmlspecialchars(substr($nama_barang, 0, 20));
                                    echo strlen($nama_barang) > 20 ? '...' : '';
                                    ?>
                                </div>
                            </div>
                            <?php
                            $status_class = '';
                            switch($pesanan['status_pesanan']) {
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'disetujui':
                                    $status_class = 'status-disetujui';
                                    break;
                                case 'ditolak':
                                    $status_class = 'status-ditolak';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($pesanan['status_pesanan']); ?>
                            </span>
                        </div>
                        
                        <div class="order-info">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d F Y, H:i', strtotime($pesanan['tanggal_pesan'])); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span class="total-price">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
                            </div>
                            
                            <?php if(!empty($pesanan['alamat_pengiriman'])): ?>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars(substr($pesanan['alamat_pengiriman'], 0, 40)) . (strlen($pesanan['alamat_pengiriman']) > 40 ? '...' : ''); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($pesanan['catatan'])): ?>
                            <div class="info-item">
                                <i class="fas fa-sticky-note"></i>
                                <span><?php echo htmlspecialchars(substr($pesanan['catatan'], 0, 40)) . (strlen($pesanan['catatan']) > 40 ? '...' : ''); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-actions">
                            <button class="btn btn-detail btn-modern" onclick="showOrderDetail(<?php echo $pesanan['id_pesanan']; ?>)">
                                <i class="fas fa-eye me-1"></i>Detail
                            </button>
                            
                            <?php if($pesanan['status_pesanan'] == 'pending'): ?>
                            <button class="btn btn-cancel btn-modern" onclick="cancelOrder(<?php echo $pesanan['id_pesanan']; ?>)">
                                <i class="fas fa-times me-1"></i>Batal
                            </button>
                            <a href="struk.php?id=<?php echo $pesanan['id_pesanan']; ?>" class="btn btn-info btn-modern" target="_blank">
                                <i class="fas fa-receipt me-1"></i>Struk
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list empty-icon"></i>
                <h4 class="empty-title">Belum Ada Pesanan</h4>
                <p class="empty-description">Anda belum memiliki pesanan apapun. Mulai berbelanja sekarang dan temukan produk pertanian berkualitas!</p>
                <a href="lihat_produk.php" class="btn btn-checkout">
                    <i class="fas fa-shopping-cart me-2"></i>Mulai Belanja
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Pesanan -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembatalan -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-warning"></i> Konfirmasi Pembatalan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
                    <input type="hidden" id="orderIdToCancel">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancelOrder()">Ya, Batalkan Pesanan</button>
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
                    <a href="keranjang.php" class="footer-link">Keranjang</a>
                    <a href="#" class="footer-link">Pesanan</a>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Show order detail modal
        function showOrderDetail(orderId) {
            // Fetch order details via AJAX
            fetch('detail_pesanan.php?id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailContent').innerHTML = data;
                    var modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailContent').innerHTML = 
                        '<div class="alert alert-danger">Gagal memuat detail pesanan. Silakan coba lagi.</div>';
                    var modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
                    modal.show();
                });
        }

        // Show cancel order confirmation
        function cancelOrder(orderId) {
            document.getElementById('orderIdToCancel').value = orderId;
            var modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            modal.show();
        }

        // Confirm cancel order
        function confirmCancelOrder() {
            const orderId = document.getElementById('orderIdToCancel').value;
            
            fetch('pesanan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cancel_order=true&id_pesanan=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        confirmButtonColor: '#22c55e'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message,
                        confirmButtonColor: '#22c55e'
                    });
                }
                var modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
                modal.hide();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat membatalkan pesanan',
                    confirmButtonColor: '#22c55e'
                });
                var modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
                modal.hide();
            });
        }

        // Auto-dismiss alerts after 5 seconds
        window.addEventListener('DOMContentLoaded', (event) => {
            const alerts = document.querySelectorAll('.alert-modern');
            alerts.forEach(alert => {
                setTimeout(() => {
                    bootstrap.Alert.getInstance(alert).close();
                }, 5000);
            });
        });
    </script>
</body>
</html>