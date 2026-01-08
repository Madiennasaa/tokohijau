<?php
include 'koneksi.php';

// Periksa apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Silakan login terlebih dahulu</div>';
    exit();
}

if(!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID pesanan tidak ditemukan</div>';
    exit();
}

$id_pesanan = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Query pesanan utama
if(isAdmin()) {
    $pesanan_query = $conn->query("SELECT p.*, u.nama as nama_pelanggan, u.email, u.no_telepon, u.alamat 
                              FROM pesanan p 
                              JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
                              WHERE p.id_pesanan = $id_pesanan");
} else {
    // Tambahkan join ke tabel pembayaran dan metode_pembayaran
    $pesanan_query = $conn->query("SELECT p.*, u.nama as nama_pelanggan, u.email, u.no_telepon, u.alamat,
                                pm.status as status_pembayaran, pm.bukti_pembayaran, 
                                mp.nama_metode
                            FROM pesanan p 
                            JOIN pengguna u ON p.id_pengguna = u.id_pengguna
                            LEFT JOIN pembayaran pm ON p.id_pembayaran = pm.id_pembayaran
                            LEFT JOIN metode_pembayaran mp ON pm.id_metode = mp.id_metode
                            WHERE p.id_pesanan = $id_pesanan");
}

$pesanan = $pesanan_query->fetch_assoc();

// Query detail pesanan - SIMPAN DALAM ARRAY untuk digunakan berulang kali
$detail_query = $conn->query("
    SELECT dp.*, b.nama_barang, b.gambar, b.satuan, b.stok,
           b.harga_eceran, b.harga_grosir,
           CASE 
               WHEN dp.jumlah >= 50 AND b.harga_grosir > 0 THEN b.harga_grosir
               ELSE b.harga_eceran
           END AS harga_satuan,
           (CASE 
               WHEN dp.jumlah >= 50 AND b.harga_grosir > 0 THEN b.harga_grosir
               ELSE b.harga_eceran
           END * dp.jumlah) AS subtotal
    FROM detail_pesanan dp
    JOIN barang b ON dp.id_barang = b.id_barang
    WHERE dp.id_pesanan = $id_pesanan
");

// Simpan hasil query dalam array
$detail_items = [];
$total_recalculated = 0;

while($detail = $detail_query->fetch_assoc()) {
    $detail_items[] = $detail;
    $total_recalculated += $detail['subtotal'];
}

// Update total di data pesanan
$pesanan['total_harga'] = $total_recalculated;

$status_info = [
    'pending' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Sedang diproses'],
    'dibeli' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Selesai'],
    'dibatalkan' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Ditolak']
];

$status = $status_info[$pesanan['status_pesanan']] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => 'Unknown'];
?>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 20px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 0;
    width: 30px;
    height: 30px;
    background: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-item.completed .timeline-marker {
    background: #22c55e;
}

.timeline-item.current .timeline-marker {
    background: #f59e0b;
    color: #000;
}

.timeline-content h6 {
    margin-bottom: 5px;
    color: #1f2937;
    font-weight: 600;
}

.timeline-content p {
    font-size: 14px;
    color: #6b7280;
}

.table-modern {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
}

.table-modern thead th {
    background: #dcfce7;
    color: #16a34a;
    font-weight: 600;
    border-bottom: none;
}

.table-modern tbody tr:hover {
    background: rgba(220, 252, 231, 0.3);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.alert-info {
    background-color: #f0f9ff;
    border-color: #bae6fd;
    color: #0369a1;
}

.alert-warning {
    background-color: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
</style>

<div class="container-fluid py-3">
    <!-- Informasi Pesanan dan Pelanggan -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Pesanan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="40%"><strong>ID Pesanan:</strong></td>
                            <td>#<?php echo $pesanan['id_pesanan']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Pesan:</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pesanan['tanggal_pesan'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge bg-<?php echo $status['class']; ?>">
                                    <i class="fas fa-<?php echo $status['icon']; ?> me-1"></i>
                                    <?php echo $status['text']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total Harga:</strong></td>
                            <td class="text-success fw-bold">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Metode Pembayaran:</strong></td>
                            <td>
                                <?php if(!empty($pesanan['nama_metode'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <p class="mb-1"><strong>Metode:</strong> <?php echo htmlspecialchars($pesanan['nama_metode']); ?></p>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($pesanan['status_pembayaran']); ?></p>
                                            
                                            <?php if(!empty($pesanan['bukti_pembayaran'])): ?>
                                                <p class="mb-1"><strong>Bukti Pembayaran:</strong></p>
                                                <?php if(strpos($pesanan['bukti_pembayaran'], '.pdf') !== false): ?>
                                                    <a href="<?php echo $pesanan['bukti_pembayaran']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-file-pdf me-1"></i>Lihat Bukti PDF
                                                    </a>
                                                <?php else: ?>
                                                    <img src="<?php echo $pesanan['bukti_pembayaran']; ?>" class="img-fluid mt-2" style="max-height: 200px;">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    
        <!-- Informasi Pelanggan -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="40%"><strong>Nama:</strong></td>
                            <td><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($pesanan['email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Telepon:</strong></td>
                            <td><?php echo htmlspecialchars($pesanan['no_telepon']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Alamat User:</strong></td>
                            <td><?php echo htmlspecialchars($pesanan['alamat']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alamat Pengiriman -->
    <?php if(!empty($pesanan['alamat_pengiriman'])): ?>
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Alamat Pengiriman</h6>
            <div class="alert alert-info">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Catatan -->
    <?php if(!empty($pesanan['catatan'])): ?>
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="mb-2"><i class="fas fa-sticky-note me-2"></i>Catatan Pesanan</h6>
            <div class="alert alert-warning">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($pesanan['catatan'])); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detail Produk -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Detail Produk</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th>Stok Tersedia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detail_items as $detail): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($detail['gambar']) ? htmlspecialchars($detail['gambar']) : 'https://via.placeholder.com/40?text=Produk'; ?>" 
                                                class="me-2" width="40" height="40" style="object-fit: contain;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($detail['nama_barang']); ?></strong>
                                                <br>
                                                <small class="text-muted">Per <?php echo htmlspecialchars($detail['satuan']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">Rp <?php echo number_format($detail['harga_satuan'], 0, ',', '.'); ?></div>
                                        <?php if($detail['jumlah'] >= 50 && $detail['harga_grosir'] > 0 && $detail['harga_satuan'] == $detail['harga_grosir']): ?>
                                            <small><span class="badge bg-success">Harga Grosir</span></small>
                                        <?php else: ?>
                                            <small><span class="badge bg-info">Harga Eceran</span></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $detail['jumlah']; ?> <?php echo htmlspecialchars($detail['satuan']); ?></span>
                                    </td>
                                    <td class="fw-bold text-success">Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                        $stok_color = $detail['stok'] >= $detail['jumlah'] ? 'success' : 'danger';
                                        $stok_icon = $detail['stok'] >= $detail['jumlah'] ? 'check' : 'exclamation-triangle';
                                        $stok_message = $detail['stok'] >= $detail['jumlah'] ? 'Stok mencukupi' : 'Stok tidak mencukupi';
                                        
                                        if($detail['jumlah'] >= 50) {
                                            $stok_message .= ' (Pembelian Grosir)';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $stok_color; ?>" title="<?php echo $stok_message; ?>">
                                            <i class="fas fa-<?php echo $stok_icon; ?> me-1"></i>
                                            <?php echo $detail['stok']; ?> <?php echo htmlspecialchars($detail['satuan']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="3" class="text-end">Total Pesanan:</th>
                                    <th class="text-success">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions untuk Admin -->
    <?php if(isAdmin() && $pesanan['status_pesanan'] == 'pending'): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi Admin</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="updateStatusPesanan(<?php echo $id_pesanan; ?>, 'dibeli')">
                            <i class="fas fa-check me-1"></i>Selesai
                        </button>
                        <button type="button" class="btn btn-danger" onclick="updateStatusPesanan(<?php echo $id_pesanan; ?>, 'dibatalkan')">
                            <i class="fas fa-times me-1"></i>Tolak
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>
                        Setelah pesanan selesai, status akan berubah menjadi "Selesai" dan stok barang akan dikurangi.
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <a href="struk.php?id=<?php echo $id_pesanan; ?>" class="btn btn-primary mt-3" target="_blank">
        <i class="fas fa-receipt me-1"></i> Lihat Struk
    </a>

    <!-- Timeline Status -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Status</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item <?php echo $pesanan['status_pesanan'] != 'pending' ? 'completed' : 'current'; ?>">
                            <div class="timeline-marker">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Pesanan Dibuat</h6>
                                <p class="text-muted mb-0"><?php echo date('d/m/Y H:i', strtotime($pesanan['tanggal_pesan'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if($pesanan['status_pesanan'] == 'dibeli'): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Pesanan Disetujui</h6>
                                <p class="text-muted mb-0">Status diubah menjadi selesai</p>
                            </div>
                        </div>
                        <?php elseif($pesanan['status_pesanan'] == 'dibatalkan'): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Pesanan Ditolak</h6>
                                <p class="text-muted mb-0">Status diubah menjadi ditolak</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatusPesanan(id_pesanan, status) {
    const statusText = status === 'dibeli' ? 'menyetujui' : 'menolak';
    
    if(confirm(`Apakah Anda yakin ingin ${statusText} pesanan ini?`)) {
        const formData = new FormData();
        formData.append('id_pesanan', id_pesanan);
        formData.append('status', status);
        
        fetch('proses_update_status_pesanan.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan sistem'
            });
        });
    }
}
</script>