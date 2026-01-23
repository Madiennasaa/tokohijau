<?php
include 'koneksi.php';

// Periksa apakah user adalah admin
if(!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Proses persetujuan/penolakan pesanan
if(isset($_POST['action']) && isset($_POST['id_pesanan'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $action = $_POST['action'];
    
    $conn->begin_transaction();
    
    try {
        if($action == 'approve') {
            // Setujui pesanan
            $conn->query("UPDATE pesanan SET status_pesanan = 'dibeli' WHERE id_pesanan = $id_pesanan");
            $_SESSION['success_message'] = "Pesanan berhasil disetujui";
        } elseif($action == 'reject') {
        // Tolak pesanan dan kembalikan stok
        
        // Ambil detail pesanan
        $detail_query = $conn->query("SELECT id_barang, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
        
        // Kembalikan stok untuk setiap item
        while($item = $detail_query->fetch_assoc()) {
            $conn->query("UPDATE barang SET stok = stok + {$item['jumlah']} WHERE id_barang = {$item['id_barang']}");
        }
        
        // Update status pesanan
        $conn->query("UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = $id_pesanan");
        $_SESSION['success_message'] = "Pesanan berhasil ditolak dan stok dikembalikan";
    }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    }
    
    header("Location: kelola_pesanan.php");
    exit();
}

// Proses penghapusan pesanan
if(isset($_POST['delete_order']) && isset($_POST['id_pesanan'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    
    $conn->begin_transaction();
    
    try {
        // Hapus detail pesanan terlebih dahulu (karena constraint foreign key)
        $conn->query("DELETE FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
        
        // Kemudian hapus pesanan
        $delete_order = $conn->query("DELETE FROM pesanan WHERE id_pesanan = $id_pesanan");
        
        if(!$delete_order) {
            throw new Exception("Gagal menghapus pesanan: " . $conn->error);
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Pesanan berhasil dihapus";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Gagal menghapus pesanan: " . $e->getMessage();
    }
    
    header("Location: kelola_pesanan.php");
    exit();
}

// Pagination setup
$items_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Filter pesanan
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "";

switch($filter) {
    case 'pending':
        $where_clause = "WHERE p.status_pesanan = 'pending'";
        break;
    case 'approved':
        $where_clause = "WHERE p.status_pesanan = 'dibeli'";
        break;
    case 'rejected':
        $where_clause = "WHERE p.status_pesanan = 'dibatalkan'";
        break;
    default:
        $where_clause = "";
}

// Hitung total pesanan berdasarkan filter
$total_query = $conn->query("SELECT COUNT(*) as total FROM pesanan p JOIN pengguna u ON p.id_pengguna = u.id_pengguna $where_clause");
$total_orders = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

$pesanan_query = $conn->query("
    SELECT p.*, u.nama as nama_pelanggan, u.email, 
           GROUP_CONCAT(b.nama_barang SEPARATOR ', ') AS nama_barang,
           GROUP_CONCAT(b.gambar SEPARATOR '|') AS gambar_barang,
           SUM(
               CASE 
                   WHEN dp.jumlah >= 50 AND b.harga_grosir > 0 THEN b.harga_grosir * dp.jumlah
                   ELSE b.harga_eceran * dp.jumlah
               END
           ) AS total_harga_recalculated
    FROM pesanan p
    JOIN pengguna u ON p.id_pengguna = u.id_pengguna
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN barang b ON dp.id_barang = b.id_barang
    $where_clause
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_pesan DESC
    LIMIT $offset, $items_per_page
");

// Statistik pesanan
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM pesanan")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM pesanan WHERE status_pesanan = 'pending'")->fetch_assoc()['count'],
    'approved' => $conn->query("SELECT COUNT(*) as count FROM pesanan WHERE status_pesanan = 'dibeli'")->fetch_assoc()['count'],
    'rejected' => $conn->query("SELECT COUNT(*) as count FROM pesanan WHERE status_pesanan = 'dibatalkan'")->fetch_assoc()['count']
];

// Helper function untuk membuat URL pagination
function getPaginationUrl($page, $filter) {
    return "?page=$page&filter=$filter";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Toko Hijau</title>
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
        .stat-card.stat-pending::before { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.stat-approved::before { background: var(--gradient-1); }
        .stat-card.stat-rejected::before { background: linear-gradient(135deg, #ef4444, #dc2626); }

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
        .stat-icon.icon-pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.icon-approved { background: var(--gradient-1); }
        .stat-icon.icon-rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
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

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        /* Order Cards */
        .order-card {
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
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .order-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .customer-info h6 {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .customer-info .email {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .order-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .order-total {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1f2937;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-approved {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: var(--dark-green);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-rejected {
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

        .btn-detail {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-detail:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-approve {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-approve:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #374151, #1f2937);
            color: white;
            box-shadow: 0 4px 15px rgba(55, 65, 81, 0.3);
        }

        .btn-delete:hover {
            color: white;
            box-shadow: 0 8px 25px rgba(55, 65, 81, 0.4);
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

        .pagination .page-item.disabled .page-link {
            color: #9ca3af;
            background: #f3f4f6;
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

        /* Responsive */
        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .stat-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .order-card {
                padding: 1rem;
            }
            
            .action-btn {
                width: 100%;
                margin: 0.25rem 0;
            }
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            color: #1f2937;
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
                        <a class="nav-link active" href="kelola_pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Kelola Pesanan</a>
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
                    <i class="fas fa-clipboard-list me-3"></i>Kelola Pesanan
                </h1>
                <p class="admin-subtitle">Kelola dan pantau semua pesanan pelanggan dengan mudah</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistik Pesanan -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-total fade-in-up">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-pending fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Menunggu Persetujuan</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-approved fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-icon icon-approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number" style="color: var(--primary-green);"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Disetujui</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card stat-rejected fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-icon icon-rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number text-danger"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Ditolak</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section fade-in-up">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0"><i class="fas fa-filter me-2 text-success"></i>Filter Pesanan</h5>
                
                <div class="btn-group flex-wrap" role="group">
                    <a href="<?php echo getPaginationUrl(1, 'all'); ?>" 
                       class="btn filter-btn <?php echo $filter == 'all' ? 'btn-success active' : 'btn-outline-success'; ?>">
                        <i class="fas fa-list me-2"></i>Semua
                    </a>
                    <a href="<?php echo getPaginationUrl(1, 'pending'); ?>" 
                       class="btn filter-btn <?php echo $filter == 'pending' ? 'btn-warning text-white active' : 'btn-outline-warning'; ?>">
                        <i class="fas fa-clock me-2"></i>Pending
                    </a>
                    <a href="<?php echo getPaginationUrl(1, 'approved'); ?>" 
                       class="btn filter-btn <?php echo $filter == 'approved' ? 'btn-success active' : 'btn-outline-success'; ?>">
                        <i class="fas fa-check me-2"></i>Disetujui
                    </a>
                    <a href="<?php echo getPaginationUrl(1, 'rejected'); ?>" 
                       class="btn filter-btn <?php echo $filter == 'rejected' ? 'btn-danger active' : 'btn-outline-danger'; ?>">
                        <i class="fas fa-times me-2"></i>Ditolak
                    </a>
                </div>
            </div>
        </div>

        <!-- Daftar Pesanan -->
        <?php if($pesanan_query->num_rows > 0): ?>
            <?php $animation_delay = 0; ?>
            <?php while($pesanan = $pesanan_query->fetch_assoc()): ?>
                <div class="order-card fade-in-up" style="animation-delay: <?php echo $animation_delay; ?>s;">
                    <div class="row align-items-center">
                        <!-- Kolom Gambar Produk (Paling Kiri) -->
                        <div class="col-lg-1 col-md-2 mb-3 mb-md-0 text-center">
                            <?php 
                            $gambar_barang = explode('|', $pesanan['gambar_barang'])[0] ?? '';
                            if($gambar_barang): ?>
                                <img src="<?= htmlspecialchars($gambar_barang) ?>" alt="Gambar Barang" class="product-image img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-box-open text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Kolom Informasi Pesanan -->
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0">
                            <div class="order-id">#<?php echo htmlspecialchars($pesanan['id_pesanan']); ?></div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('d M Y', strtotime($pesanan['tanggal_pesan'])); ?>
                            </div>
                        </div>
                        
                        <!-- Kolom Nama Barang -->
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0">
                            <div class="order-code" title="<?php echo htmlspecialchars($pesanan['nama_barang']); ?>">
                                <?php 
                                $nama_barang = $pesanan['nama_barang'] ?? 'Tidak ada barang';
                                echo htmlspecialchars(substr($nama_barang, 0, 20));
                                echo strlen($nama_barang) > 20 ? '...' : '';
                                ?>
                            </div>
                        </div>
                        
                        <!-- Kolom Informasi Pelanggan -->
                        <div class="col-lg-2 col-md-4 mb-3 mb-md-0">
                            <div class="customer-info">
                                <h6><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></h6>
                                <div class="email"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($pesanan['email']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Kolom Total Harga -->
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0">
                            <div class="order-total">
                                <i class="fas fa-money-bill-wave me-1"></i>Rp <?php echo number_format($pesanan['total_harga_recalculated'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        
                        <!-- Kolom Status -->
                        <div class="col-lg-1 col-md-2 mb-3 mb-md-0">
                            <?php if($pesanan['status_pesanan'] == 'pending'): ?>
                                <span class="status-badge status-pending">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </span>
                            <?php elseif($pesanan['status_pesanan'] == 'dibeli'): ?>
                                <span class="status-badge status-approved">
                                    <i class="fas fa-check me-1"></i>Disetujui
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-rejected">
                                    <i class="fas fa-times me-1"></i>Ditolak
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Kolom Aksi -->
                        <div class="col-lg-2 col-md-12">
                            <div class="d-flex flex-wrap justify-content-end">
                                <!-- Tambahkan ini di samping tombol modal: -->
                                <button 
                                    type="button" 
                                    class="action-btn btn-detail" 
                                    onclick="showOrderDetail(<?php echo $pesanan['id_pesanan']; ?>)"
                                >
                                    <i class="fas fa-eye me-1"></i>Detail
                                </button>
                                
                                <?php if($pesanan['status_pesanan'] == 'pending'): ?>
                                    <!-- Approve Button -->
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="id_pesanan" value="<?php echo $pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="action-btn btn-approve" onclick="return confirm('Setujui pesanan ini?')">
                                            <i class="fas fa-check me-1"></i>Setujui
                                        </button>
                                    </form>
                                    
                                    <!-- Reject Button -->
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="id_pesanan" value="<?php echo $pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-btn btn-reject" onclick="return confirm('Tolak pesanan ini dan kembalikan stok?')">
                                            <i class="fas fa-times me-1"></i>Tolak
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <!-- Delete Button -->
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="id_pesanan" value="<?php echo $pesanan['id_pesanan']; ?>">
                                    <input type="hidden" name="delete_order" value="1">
                                    <button type="submit" class="action-btn btn-delete" onclick="return confirm('Hapus pesanan ini secara permanen?')">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php $animation_delay += 0.1; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state fade-in-up">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="empty-title">Tidak Ada Pesanan</h3>
                <p class="empty-text">Tidak ada pesanan yang ditemukan dengan filter yang dipilih.</p>
                <a href="?filter=all" class="btn btn-success">
                    <i class="fas fa-list me-2"></i>Lihat Semua Pesanan
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <div class="pagination-wrapper fade-in-up">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo getPaginationUrl($current_page - 1, $filter); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo getPaginationUrl($i, $filter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo getPaginationUrl($current_page + 1, $filter); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Detail Pesanan -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Pesanan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail pesanan...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Fungsi untuk menampilkan detail pesanan
        function showOrderDetail(orderId) {
        // Tampilkan spinner loading
        document.getElementById('orderDetailContent').innerHTML = `
            <div class="text-center py-4">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat detail pesanan...</p>
            </div>
        `;
        
        // Tampilkan modal
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        modal.show();

        // Fetch data pesanan
        fetch(`detail_pesanan.php?id=${orderId}`)
            .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memuat data');
            }
            return response.text();
            })
            .then(data => {
            document.getElementById('orderDetailContent').innerHTML = data;
            })
            .catch(error => {
            document.getElementById('orderDetailContent').innerHTML = `
                <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${error.message}
                </div>
            `;
            });
        }

        // Inisialisasi tooltip
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>