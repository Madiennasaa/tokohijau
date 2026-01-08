<?php
include 'koneksi.php';

// Periksa login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data pesanan
if(!isset($_GET['id'])) {
    header("Location: pesanan.php");
    exit();
}

$id_pesanan = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data pesanan
$pesanan = $conn->query("SELECT p.*, pm.*, mp.nama_metode 
                        FROM pesanan p
                        JOIN pembayaran pm ON p.id_pembayaran = pm.id_pembayaran
                        JOIN metode_pembayaran mp ON pm.id_metode = mp.id_metode
                        WHERE p.id_pesanan = $id_pesanan AND p.id_pengguna = $user_id")->fetch_assoc();

if(!$pesanan) {
    $_SESSION['error_message'] = "Pesanan tidak ditemukan";
    header("Location: pesanan.php");
    exit();
}

// Ambil detail pesanan
$detail_pesanan = $conn->query("SELECT dp.*, b.nama_barang, b.satuan
                              FROM detail_pesanan dp
                              JOIN barang b ON dp.id_barang = b.id_barang
                              WHERE dp.id_pesanan = $id_pesanan");

// Data untuk QR Code
$qrData = "Order ID: ".$pesanan['kode_pesanan']."\n";
$qrData .= "Tanggal: ".date('d/m/Y H:i', strtotime($pesanan['tanggal_pesan']))."\n";
$qrData .= "Total: Rp ".number_format($pesanan['total_harga'], 0, ',', '.')."\n";
$qrData .= "Status: ".ucfirst($pesanan['status_pesanan'])."\n";
$qrData .= "Toko Hijau";
$qrDataEncoded = urlencode($qrData);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #<?php echo $pesanan['kode_pesanan']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }

        body {
            background: #f8f9fa;
        }
        .struk-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .header-struk {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #22c55e;
            margin-bottom: 0.5rem;
        }
        .divider {
            border-top: 2px dashed #dee2e6;
            margin: 1.5rem 0;
        }
        .table-detail td {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .table-detail tr:last-child td {
            border-bottom: none;
        }
        .bukti-pembayaran {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-top: 1rem;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            display: block;
            border: 1px solid #dee2e6;
            padding: 5px;
            background: white;
        }
        .qr-container {
            text-align: center;
            margin: 1rem 0;
        }
        .qr-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <div class="header-struk">
            <div class="logo">Toko Hijau</div>
            <small class="text-muted">Jl. Pertanian No. 123, Jakarta</small>
            <h4 class="mt-3">Struk Pesanan</h4>
            <p class="text-muted mb-0">#<?php echo $pesanan['kode_pesanan']; ?></p>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted">Tanggal</small>
                <p><?php echo date('d/m/Y H:i', strtotime($pesanan['tanggal_pesan'])); ?></p>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted">Status</small>
                <p>
                    <span class="badge bg-<?php 
                        echo $pesanan['status_pesanan'] == 'selesai' ? 'success' : 
                             ($pesanan['status_pesanan'] == 'dibatalkan' ? 'danger' : 'warning'); ?>">
                        <?php echo ucfirst($pesanan['status_pesanan']); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <h6>Detail Produk</h6>
        <table class="table-detail w-100">
            <?php while($item = $detail_pesanan->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                <td class="text-end"><?php echo $item['jumlah'] . ' ' . htmlspecialchars($item['satuan']); ?></td>
                <td class="text-end">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                <td class="text-end">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        
        <div class="divider"></div>
        
        <table class="w-100">
            <tr>
                <td class="text-end fw-bold">Subtotal</td>
                <td class="text-end" width="150">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="text-end fw-bold">Biaya Pengiriman</td>
                <td class="text-end">Rp 0</td>
            </tr>
            <tr>
                <td class="text-end fw-bold">Total</td>
                <td class="text-end">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <div class="qr-container">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo $qrDataEncoded; ?>" 
                 alt="QR Code Pesanan" class="qr-code">
            <div class="qr-label">Scan QR Code untuk verifikasi pesanan</div>
        </div>
        
        <div class="divider"></div>
        
        <h6>Metode Pembayaran</h6>
        <p>
            <?php 
            if(isset($pesanan['nama_metode'])) {
                if(stripos($pesanan['nama_metode'], 'cod') !== false) {
                    echo '<i class="fas fa-money-bill-wave me-1"></i>';
                } elseif(stripos($pesanan['nama_metode'], 'transfer') !== false) {
                    echo '<i class="fas fa-university me-1"></i>';
                }
                echo htmlspecialchars($pesanan['nama_metode']);
            } else {
                echo 'Belum dipilih';
            }
            ?>
        </p>
        
        <?php if($pesanan['status'] == 'dibayar' && !empty($pesanan['bukti_pembayaran'])): ?>
        <h6 class="mt-3">Bukti Pembayaran</h6>
        <?php if(strpos($pesanan['bukti_pembayaran'], '.pdf') !== false): ?>
            <a href="<?php echo $pesanan['bukti_pembayaran']; ?>" class="btn btn-sm btn-success" target="_blank">
                Lihat PDF
            </a>
        <?php else: ?>
            <img src="<?php echo $pesanan['bukti_pembayaran']; ?>" class="bukti-pembayaran" alt="Bukti Pembayaran">
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="divider"></div>
        
        <div class="text-center mt-4">
            <p class="text-muted">Terima kasih telah berbelanja di Toko Hijau</p>
            <button onclick="window.print()" class="btn btn-success no-print">
                <i class="fas fa-print me-2"></i>Cetak Struk
            </button>
            <a href="pesanan.php" class="btn btn-outline-secondary ms-2 no-print">
                Kembali ke Pesanan
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>