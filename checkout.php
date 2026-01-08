<?php
include 'koneksi.php';

// Periksa apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_data = $conn->query("SELECT * FROM pengguna WHERE id_pengguna = $user_id")->fetch_assoc();

// Proses checkout - diperbaiki
if(isset($_POST['checkout'])) {
    $conn->begin_transaction();
    
    try {
        // Ambil semua item di keranjang
        $keranjang_query = $conn->query("SELECT k.*, b.nama_barang, b.harga_eceran, b.stok 
                                        FROM keranjang k 
                                        JOIN barang b ON k.id_barang = b.id_barang 
                                        WHERE k.id_pengguna = $user_id 
                                        FOR UPDATE");
        
        if($keranjang_query->num_rows == 0) {
            throw new Exception("Keranjang kosong");
        }

        $metode_pembayaran = $conn->real_escape_string($_POST['metode_pembayaran']);
        $is_cod = false;
        
        // Cek apakah metode pembayaran COD
        $metode_data = $conn->query("SELECT * FROM metode_pembayaran WHERE id_metode = $metode_pembayaran")->fetch_assoc();
        if(stripos($metode_data['nama_metode'], 'cod') !== false) {
            $is_cod = true;
        }
        
        $total_harga = 0;
        $items = [];
        
        // Di bagian proses checkout, ubah perhitungan harga
        while($item = $keranjang_query->fetch_assoc()) {
            if($item['jumlah'] > $item['stok']) {
                throw new Exception("Stok tidak mencukupi untuk produk: " . $item['nama_barang']);
            }
            
            // Gunakan harga grosir jika jumlah >= 50
            $harga_satuan = ($item['jumlah'] >= 50 && $item['harga_grosir'] > 0) ? $item['harga_grosir'] : $item['harga_eceran'];
            $subtotal = $harga_satuan * $item['jumlah'];
            $total_harga += $subtotal;
            
            // Simpan harga_satuan untuk detail pesanan
            $item['harga_satuan'] = $harga_satuan;
            $items[] = $item;
        }
        
        // Generate kode pesanan unik
        $kode_pesanan = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert pesanan
        $alamat_pengiriman = $conn->real_escape_string($_POST['alamat_pengiriman']);
        $catatan = $conn->real_escape_string($_POST['catatan']);
        
        $insert_pesanan = $conn->query("INSERT INTO pesanan (id_pengguna, kode_pesanan, tanggal_pesan, total_harga, status_pesanan, alamat_pengiriman, catatan) 
                                      VALUES ($user_id, '$kode_pesanan', NOW(), $total_harga, 'pending', '$alamat_pengiriman', '$catatan')");
        
        if(!$insert_pesanan) {
            throw new Exception("Gagal membuat pesanan: " . $conn->error);
        }
        
        $id_pesanan = $conn->insert_id;

        // Insert pembayaran
        $status_pembayaran = $is_cod ? 'dibayar' : 'pending';
        $insert_pembayaran = $conn->query("INSERT INTO pembayaran (id_pesanan, id_metode, jumlah, status) 
                                         VALUES ($id_pesanan, $metode_pembayaran, $total_harga, '$status_pembayaran')");
        
        if(!$insert_pembayaran) {
            throw new Exception("Gagal menyimpan data pembayaran: " . $conn->error);
        }
        
        $id_pembayaran = $conn->insert_id;

        // Update pesanan dengan id_pembayaran
        $conn->query("UPDATE pesanan SET id_pembayaran = $id_pembayaran WHERE id_pesanan = $id_pesanan");
        
        // Insert detail pesanan
        foreach($items as $item) {
            $subtotal = $item['harga_satuan'] * $item['jumlah'];
            
            $insert_detail = $conn->query("INSERT INTO detail_pesanan (id_pesanan, id_barang, jumlah, harga_satuan, subtotal) 
                                        VALUES ($id_pesanan, {$item['id_barang']}, {$item['jumlah']}, {$item['harga_satuan']}, $subtotal)");
            
            if(!$insert_detail) {
                throw new Exception("Gagal menyimpan detail pesanan: " . $conn->error);
            }
        }
        
        // Handle upload bukti pembayaran jika bukan COD
        if(!$is_cod && isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/bukti_pembayaran/';
            
            // Coba buat folder jika belum ada
            if(!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new Exception("Gagal membuat direktori upload");
            }
            
            // Validasi file
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if($_FILES['bukti_pembayaran']['size'] > $maxFileSize) {
                throw new Exception("Ukuran file terlalu besar. Maksimal 2MB");
            }
            
            $fileExt = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
            if(!in_array($fileExt, $allowedExtensions)) {
                throw new Exception("Format file tidak didukung. Gunakan JPG, PNG, atau PDF");
            }
            
            $fileName = 'bukti_' . $id_pembayaran . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if(move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $filePath)) {
                $update = $conn->query("UPDATE pembayaran SET bukti_pembayaran = '$filePath' WHERE id_pembayaran = $id_pembayaran");
                if(!$update) {
                    throw new Exception("Gagal menyimpan path bukti pembayaran: " . $conn->error);
                }
            } else {
                throw new Exception("Gagal mengupload file. Error: " . $_FILES['bukti_pembayaran']['error']);
            }
        }
                
        // Hapus keranjang setelah checkout
        $delete_cart = $conn->query("DELETE FROM keranjang WHERE id_pengguna = $user_id");
        
        if(!$delete_cart) {
            throw new Exception("Gagal menghapus keranjang: " . $conn->error);
        }
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Pesanan berhasil dibuat dengan kode $kode_pesanan. " . 
                                     ($is_cod ? "Pembayaran akan dilakukan saat barang diterima." : 
                                     "Silakan upload bukti pembayaran melalui halaman pesanan.");
        header("Location: pesanan.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}

// Di bagian awal file, ubah query untuk mengambil harga_grosir
$keranjang = $conn->query("SELECT k.*, b.nama_barang, b.harga_eceran, b.harga_grosir, b.gambar, b.satuan
                          FROM keranjang k
                          JOIN barang b ON k.id_barang = b.id_barang
                          WHERE k.id_pengguna = $user_id");

if($keranjang->num_rows == 0) {
    $_SESSION['error_message'] = "Keranjang kosong. Silakan tambahkan produk terlebih dahulu.";
    header("Location: lihat_produk.php");
    exit();
}

// Hitung total
$total_keranjang = 0;
$items_checkout = [];
while($item = $keranjang->fetch_assoc()) {
    // Gunakan harga grosir jika jumlah >= 50, selain itu harga eceran
    $harga_satuan = ($item['jumlah'] >= 50 && $item['harga_grosir'] > 0) ? $item['harga_grosir'] : $item['harga_eceran'];
    $subtotal = $harga_satuan * $item['jumlah'];
    $total_keranjang += $subtotal;
    
    // Tambahkan harga_satuan ke array item untuk ditampilkan
    $item['harga_satuan'] = $harga_satuan;
    $items_checkout[] = $item;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Pertanian</title>
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

        /* Checkout Card */
        .checkout-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .checkout-card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
        }

        .checkout-card-body {
            padding: 1.5rem;
        }

        /* Item Image */
        .item-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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

        .summary-section {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
        }

        /* Buttons */
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

        .btn-outline-secondary {
            border-radius: 12px;
        }

        /* Required Field */
        .required {
            color: #ef4444;
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
            .page-header {
                padding: 3rem 0 1.5rem;
                margin-top: 60px;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .item-img {
                width: 60px;
                height: 60px;
            }
            
            .summary-card {
                position: static;
                margin-top: 2rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 2rem 0 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
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
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user_data['nama']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-circle me-1"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
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
                <h1 class="page-title">Checkout Pesanan</h1>
                <p class="lead mb-0">Lengkapi informasi pengiriman dan pembayaran untuk menyelesaikan pesanan Anda</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5" style="margin-top: 100px !important;">
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="post" enctype="multipart/form-data">
            <div class="row">
                <!-- Form Checkout -->
                <div class="col-lg-8 order-lg-1 order-2">
                    <!-- Informasi Pengiriman -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Informasi Pengiriman</h5>
                        </div>
                        <div class="checkout-card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nama']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat Pengiriman <span class="required">*</span></label>
                                <textarea class="form-control" name="alamat_pengiriman" rows="3" required 
                                          placeholder="Masukkan alamat lengkap untuk pengiriman"><?php echo isset($user_data['alamat']) ? htmlspecialchars($user_data['alamat']) : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan Pesanan</label>
                                <textarea class="form-control" name="catatan" rows="2" 
                                          placeholder="Catatan khusus untuk pesanan (opsional)"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Metode Pembayaran</h5>
                        </div>
                        <div class="checkout-card-body">
                            <div class="mb-3">
                                <label class="form-label">Pilih Metode Pembayaran <span class="required">*</span></label>
                                <select class="form-select" name="metode_pembayaran" required>
                                    <option value="">-- Pilih Metode --</option>
                                    <?php
                                    $metodes = $conn->query("SELECT * FROM metode_pembayaran WHERE status='aktif'");
                                    while($metode = $metodes->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $metode['id_metode']; ?>">
                                        <?php echo htmlspecialchars($metode['nama_metode']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Form Upload Bukti Pembayaran (akan muncul jika bukan COD) -->
                            <div id="buktiPembayaranContainer" class="mb-3 d-none">
                                <label class="form-label">Upload Bukti Pembayaran <span class="required">*</span></label>
                                <input type="file" class="form-control" name="bukti_pembayaran" accept="image/*,.pdf">
                                <small class="text-muted">Format: JPG, PNG, atau PDF (maks. 2MB)</small>
                            </div>
                            
                            <div id="codInfo" class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Pembayaran COD</h6>
                                <p class="mb-0">Pembayaran akan dilakukan secara tunai saat barang diterima.</p>
                            </div>
                            
                            <div id="transferInfo" class="alert alert-info d-none">
                                <h6><i class="fas fa-info-circle me-2"></i>Pembayaran Transfer</h6>
                                <p class="mb-0">Silakan transfer ke rekening berikut dan upload bukti pembayaran:</p>
                                <ul class="mb-0">
                                    <li>Bank: BCA</li>
                                    <li>No. Rekening: 1234567890</li>
                                    <li>Atas Nama: Toko Hijau</li>
                                    <li>Jumlah: Rp <?php echo number_format($total_keranjang, 0, ',', '.'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Review Pesanan -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            <h5 class="mb-0"><i class="fas fa-box me-2"></i>Review Pesanan</h5>
                        </div>
                        <div class="checkout-card-body">
                            <?php foreach($items_checkout as $item): ?>
                            <!-- Di bagian review pesanan, ubah tampilan harga -->
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="<?php echo !empty($item['gambar']) ? htmlspecialchars($item['gambar']) : 'https://via.placeholder.com/80?text=Produk'; ?>" 
                                    class="item-img me-3" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_barang']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $item['jumlah']; ?> <?php echo htmlspecialchars($item['satuan']); ?> 
                                        × Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                                        <?php if($item['jumlah'] >= 50 && $item['harga_grosir'] > 0): ?>
                                            <span class="badge bg-success ms-2">Grosir</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-success">Rp <?php echo number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="col-lg-4 order-lg-2 order-1 mb-4 mb-lg-0">
                    <div class="summary-card">
                        <div class="checkout-card-header">
                            <h5 class="mb-0">Ringkasan Pesanan</h5>
                        </div>
                        <div class="checkout-card-body">
                            <div class="summary-section">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal (<?php echo count($items_checkout); ?> item)</span>
                                    <span>Rp <?php echo number_format($total_keranjang, 0, ',', '.'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Biaya Pengiriman</span>
                                    <span class="text-success">Gratis</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-4">
                                    <span class="fw-bold">Total Pembayaran</span>
                                    <span class="fw-bold text-success">Rp <?php echo number_format($total_keranjang, 0, ',', '.'); ?></span>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <small><i class="fas fa-info-circle me-2"></i>
                                    Pesanan akan diproses setelah mendapat persetujuan dari admin. Anda akan mendapat notifikasi melalui email.</small>
                                </div>
                                
                                <button type="submit" name="checkout" class="btn btn-success w-100 py-3">
                                    <i class="fas fa-shopping-cart me-2"></i>Buat Pesanan
                                </button>
                                
                                <a href="keranjang.php" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Keranjang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-modern');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const metodeSelect = document.querySelector('select[name="metode_pembayaran"]');
            const buktiContainer = document.getElementById('buktiPembayaranContainer');
            const codInfo = document.getElementById('codInfo');
            const transferInfo = document.getElementById('transferInfo');
            
            metodeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex].text.toLowerCase();
                
                if(selectedOption.includes('cod')) {
                    buktiContainer.classList.add('d-none');
                    transferInfo.classList.add('d-none');
                    codInfo.classList.remove('d-none');
                } else {
                    buktiContainer.classList.remove('d-none');
                    transferInfo.classList.remove('d-none');
                    codInfo.classList.add('d-none');
                }
            });
        });
    </script>
</body>
</html>