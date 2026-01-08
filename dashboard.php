<?php
include 'koneksi.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isAdmin()) {
    // Admin dashboard code
    $total_barang = $conn->query("SELECT COUNT(*) as total FROM barang")->fetch_assoc()['total'];
    $total_pengguna = $conn->query("SELECT COUNT(*) as total FROM pengguna")->fetch_assoc()['total'];
    $total_pesanan = $conn->query("SELECT COUNT(*) as total FROM pesanan")->fetch_assoc()['total'];
    
    // Hitung total penjualan dengan mempertimbangkan harga grosir/eceran
    $total_penjualan = $conn->query("
        SELECT SUM(
            CASE 
                WHEN dp.jumlah >= 50 AND b.harga_grosir > 0 THEN b.harga_grosir * dp.jumlah
                ELSE b.harga_eceran * dp.jumlah
            END
        ) as total 
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN barang b ON dp.id_barang = b.id_barang
        WHERE p.status_pesanan = 'dibeli'
    ")->fetch_assoc()['total'] ?? 0;
    
    // Query pesanan terbaru dengan perhitungan ulang total harga
    $pesanan_terbaru = $conn->query("
        SELECT p.*, u.nama as nama_pelanggan,
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
        GROUP BY p.id_pesanan
        ORDER BY p.tanggal_pesan DESC 
        LIMIT 5
    ");
    
    $barang_stok = $conn->query("SELECT * FROM barang ORDER BY stok ASC LIMIT 5");
} else {
    // User dashboard code
    $produk_populer = $conn->query("SELECT * FROM barang ORDER BY stok DESC LIMIT 4");
    $produk_terbaru = $conn->query("SELECT * FROM barang ORDER BY id_barang DESC LIMIT 4");
    $berita_pertanian = $conn->query("SELECT * FROM berita ORDER BY tanggal_posting DESC LIMIT 3");
    
    $user_data = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = $user_id")->fetch_assoc();
    $nama_user = $user_data['nama'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Hijau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Admin Hero Section */
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

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
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

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(155, 244, 188, 0.9) 0%, rgba(93, 133, 95, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1560493676-04071c5f467b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
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
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .hero-cta {
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; right: 15%; animation-delay: 2s; }
        .floating-element:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 4s; }

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
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
        }

        /* Admin specific styles */
        .admin-navbar {
            background: var(--gradient-1) !important;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
        }

        .admin-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .admin-navbar .nav-link:hover,
        .admin-navbar .nav-link.active {
            color: white !important;
            background: rgba(255, 255, 255, 0.2) !important;
        }

        .admin-navbar .navbar-brand {
            color: white !important;
            -webkit-text-fill-color: white !important;
        }

        /* Table styles */
        .table-modern {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .table-modern thead th {
            background: var(--light-green);
            color: var(--dark-green);
            font-weight: 600;
            border-bottom: none;
        }

        .table-modern tbody tr:hover {
            background: rgba(220, 252, 231, 0.3);
        }

        /* Stock badge */
        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.8rem;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-weight: 600;
        }

        .section-title {
            color: var(--dark-green);
            font-weight: 700;
            position: relative;
            margin-bottom: 2rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 2px;
        }

        /* Kategori Produk Styles */
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 280px;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-bg);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.15);
            border-color: var(--primary-green);
            color: inherit;
            text-decoration: none;
        }

        .category-card:hover::before {
            transform: scaleX(1);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            font-size: 2.5rem;
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(10deg);
        }

        .category-icon i {
            font-size: 2rem !important;
            color: white !important;
            display: inline-block !important;
        }

        .category-title {
            font-weight: 600;
            color: var(--dark-green);
            margin-bottom: 1rem;
        }

        .category-desc {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .category-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-yellow);
            color: #92400e;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Edukasi Styles */
        .education-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }

        .education-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(34, 197, 94, 0.1);
            border-color: var(--primary-green);
        }

        .education-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        .education-image i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.8);
            z-index: 1;
        }

        .education-content {
            padding: 1.5rem;
        }

        .education-title {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .education-desc {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .education-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .read-more-btn {
            background: var(--gradient-bg);
            color: var(--dark-green) !important;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .read-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Tips Section */
        .tips-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .tips-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-bg);
        }

        .tips-item {
            padding: 1rem;
            background: var(--light-green);
            border-radius: 15px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-green);
            transition: all 0.3s ease;
        }

        .tips-item:hover {
            transform: translateX(5px);
            background: #bbf7d0;
        }

        .tips-icon {
            color: var(--primary-green);
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .tips-text {
            color: var(--dark-green);
            font-weight: 500;
            margin: 0;
        }

        /* Stats Section */
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 2px solid var(--light-green);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            border-color: var(--primary-green);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.1);
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--dark-green);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stats-desc {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Promo Carousel Styles */
        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 5px;
            background-color: rgba(34, 197, 94, 0.5);
            border: none;
        }

        .carousel-indicators .active {
            background-color: var(--primary-green);
            transform: scale(1.2);
        }

        /* Promo Card Styles */
        .promo-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .promo-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.9rem;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            z-index: 2;
        }

        .promo-countdown {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            position: absolute;
            bottom: 15px;
            left: 15px;
            z-index: 2;
        }

        .promo-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .promo-card:hover .promo-image {
            transform: scale(1.05);
        }

        .promo-content {
            padding: 1.5rem;
        }

        .promo-title {
            font-weight: 700;
            color: var(--dark-green);
            margin-bottom: 0.5rem;
        }

        .promo-desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .promo-price {
            font-weight: 700;
            color: var(--primary-green);
            font-size: 1.2rem;
        }

        .promo-old-price {
            text-decoration: line-through;
            color: #9ca3af;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }

        .object-fit-cover {
            object-fit: cover;
        }

        /* Tambahkan ini ke bagian CSS dashboard.php */
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

        .modal-body {
            padding: 1.5rem;
        }

        /* Tambahkan ini ke bagian CSS */
        .modal-detail-pesanan .card {
            margin-bottom: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
        }

        .modal-detail-pesanan .card-header {
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .modal-detail-pesanan .card-body {
            padding: 1.5rem;
        }

        .modal-detail-pesanan .table {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modal-detail-pesanan .table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }

        .modal-detail-pesanan .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }

        .modal-detail-pesanan .table tbody tr:last-child td {
            border-bottom: 1px solid #dee2e6;
        }

        .modal-detail-pesanan .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }

        .modal-detail-pesanan .text-success {
            color: #22c55e !important;
        }

        .modal-detail-pesanan .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Style untuk tabel di modal */
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

        /* Timeline styles */
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .category-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .stats-number {
                font-size: 2rem;
            }

             .hero-cta .btn {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .hero-cta .btn:last-child {
                margin-bottom: 0;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php if(isAdmin()): ?>
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
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_barang.php"><i class="fas fa-box me-1"></i> Kelola Barang</a>
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
    <?php else: ?>
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
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-home me-1"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lihat_produk.php"><i class="fas fa-store me-1"></i> Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="keranjang.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesanan.php"><i class="fas fa-clipboard-list me-1"></i> Pesanan</a>
                    </li>
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars(isAdmin() ? 'admin' : (isset($nama_user) ? $nama_user : 'user')); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <?php if (isAdmin()): ?>
        <section class="admin-hero" style="margin-top: 80px;">
            <div class="container">
                <div class="admin-hero-content text-center">
                    <h1 class="admin-title">
                        <i class="fas fa-user-tie me-3"></i>Dashboard Admin
                    </h1>
                    <p class="admin-subtitle">Kelola toko pertanian dengan mudah dan efisien</p>
                </div>
            </div>
        </section>

    <?php else: ?>
        <section class="hero-section">
            <div class="container">
                <div class="hero-content text-center">
                    <h1 class="hero-title">
                        Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>!
                    </h1>
                    <p class="hero-subtitle">
                        Temukan produk pertanian berkualitas dan informasi bermanfaat untuk kegiatan bertani Anda
                    </p>
                    <div class="mt-4 d-flex flex-wrap justify-content-center gap-3">
                        <a href="lihat_produk.php" class="btn btn-success btn-lg">
                            <i class="fas fa-store me-2"></i> Belanja Sekarang
                        </a>
                        <a href="panduan.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-book-open me-2"></i> Pelajari Pertanian
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Floating elements -->
            <div class="floating-element">
                <i class="fas fa-leaf fa-3x text-white opacity-25"></i>
            </div>
            <div class="floating-element">
                <i class="fas fa-tractor fa-3x text-white opacity-25"></i>
            </div>
            <div class="floating-element">
                <i class="fas fa-seedling fa-3x text-white opacity-25"></i>
            </div>
        </section>
    <?php endif; ?>

    <section class="container mb-5" style="padding-top: 2rem;">
        <?php if(isAdmin()): ?>
        <!-- Admin Dashboard Content -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <h5 class="text-muted">Total Barang</h5>
                    <h3 class="mb-0"><?php echo number_format($total_barang); ?></h3>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="text-muted">Total Pengguna</h5>
                    <h3 class="mb-0"><?php echo number_format($total_pengguna); ?></h3>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h5 class="text-muted">Total Pesanan</h5>
                    <h3 class="mb-0"><?php echo number_format($total_pesanan); ?></h3>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card h-100">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h5 class="text-muted">Total Penjualan</h5>
                    <h3 class="mb-0">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Pesanan Terbaru</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($pesanan = $pesanan_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $pesanan['id_pesanan']; ?></td>
                                        <td><?php echo $pesanan['nama_pelanggan']; ?></td>
                                        <td>Rp <?php echo number_format($pesanan['total_harga_recalculated'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($pesanan['status_pesanan']) {
                                                    case 'dibeli': echo 'success'; break;
                                                    case 'pending': echo 'warning'; break;
                                                    case 'dibatalkan': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php 
                                                $statusText = [
                                                    'dibeli' => 'Selesai',
                                                    'pending' => 'Pending',
                                                    'dibatalkan' => 'Ditolak'
                                                ];
                                                echo $statusText[$pesanan['status_pesanan']] ?? $pesanan['status_pesanan']; 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="showOrderDetail(<?php echo $pesanan['id_pesanan']; ?>)" 
                                                    class="btn btn-sm btn-success">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="kelola_pesanan.php" class="btn btn-outline-success">Lihat Semua Pesanan</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Stok Barang</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($barang = $barang_stok->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $barang['nama_barang']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                if ($barang['stok'] > 25) {
                                                    echo 'success';
                                                } elseif ($barang['stok'] > 0) {
                                                    echo 'warning';
                                                } else {
                                                    echo 'danger';
                                                }
                                            ?>">
                                                <?php echo $barang['stok'].' '.$barang['satuan']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="kelola_barang.php?id=<?php echo $barang['id_barang']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="kelola_barang.php" class="btn btn-outline-success">Kelola Semua Barang</a>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Pesanan -->
            <div class="modal fade" id="detailPesananModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-info-circle me-2"></i>Detail Pesanan
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="detailPesananContent">
                            <div class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat detail pesanan...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <section class="container mb-5">
            <h2 class="section-title">
                <i class="fas fa-tags me-3"></i>Promo Spesial
            </h2>
            
            <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="2"></button>
                </div>
                
                <div class="carousel-inner rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <!-- Promo 1 -->
                    <div class="carousel-item active">
                        <div class="row g-0 align-items-center" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                            <div class="col-md-6 p-5">
                                <span class="badge bg-success mb-3">HOT PROMO</span>
                                <h3 class="fw-bold mb-3">Diskon 30% Pupuk Organik</h3>
                                <p class="mb-4">Rawat tanaman Anda dengan pupuk berkualitas tinggi dengan harga spesial. Promo hingga 30 Juni 2024.</p>
                            </div>
                            <div class="col-md-6">
                                <div style="height: 350px; overflow: hidden;">
                                    <img src="https://bing.com/th/id/OIP.E-DF4vWMlRTy7uVy_FZXcQHaHa?r=0&cb=thvnextc2&rs=1&pid=ImgDetMain" 
                                        class="w-100 h-100 object-fit-cover" 
                                        alt="Promo Pupuk Organik"
                                        style="object-position: center;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Promo 2 -->
                    <div class="carousel-item">
                        <div class="row g-0 align-items-center" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                            <div class="col-md-6 p-5">
                                <span class="badge bg-warning text-dark mb-3">NEW</span>
                                <h3 class="fw-bold mb-3">Paket Starter Kit Pertanian</h3>
                                <p class="mb-4">Dapatkan semua yang Anda butuhkan untuk memulai bertani dengan harga khusus. Hemat hingga 25%.</p>
                            </div>
                            <div class="col-md-6">
                                <div style="height: 350px; overflow: hidden;">
                                    <img src="https://cf.shopee.co.id/file/ebf8459a86db64b8e2d5ee1af2935905" 
                                        class="w-100 h-100 object-fit-cover" 
                                        alt="Starter Kit Pertanian"
                                        style="object-position: center;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Promo 3 -->
                    <div class="carousel-item">
                        <div class="row g-0 align-items-center" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                            <div class="col-md-6 p-5">
                                <span class="badge bg-danger mb-3">FLASH SALE</span>
                                <h3 class="fw-bold mb-3">Gratis Ongkir Minimal Belanja</h3>
                                <p class="mb-4">Gratis ongkir ke seluruh Indonesia untuk pembelian minimal Rp 200.000. Berlaku hingga 15 Juli 2024.</p>
                            </div>
                            <div class="col-md-6">
                                <div style="height: 350px; overflow: hidden;">
                                    <img src="https://down-id.img.susercontent.com/file/sg-11134201-23030-7mdxcj4t4tove4" 
                                        class="w-100 h-100 object-fit-cover" 
                                        alt="Gratis Ongkir"
                                        style="object-position: center;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-success rounded-circle p-3" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-success rounded-circle p-3" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </section>
            <div class="col-12">
                        <h4 class="section-title"><i class="fas fa-th-large me-3"></i>Produk Populer</h4>
                        <div class="row">
                            <?php while($barang = $produk_populer->fetch_assoc()): 
                            $gambar = isset($barang['gambar']) && !empty($barang['gambar']) ? $barang['gambar'] : 'https://via.placeholder.com/300?text=Produk+Tidak+Tersedia';
                            ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="product-card h-100 d-flex flex-column">
                                    <div class="position-relative" style="overflow: hidden; border-radius: 20px 20px 0 0;">
                                        <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>">
                                            <img src="<?php echo htmlspecialchars($gambar); ?>" 
                                                class="product-img" 
                                                alt="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                        </a>
                                        <span class="badge <?php 
                                            if ($barang['stok'] == 0) {
                                                echo 'bg-danger'; 
                                            } elseif ($barang['stok'] < 25) {
                                                echo 'bg-warning text-dark'; 
                                            } else {
                                                echo 'bg-success';
                                            }
                                        ?> stock-badge">
                                            <?php echo $barang['stok'] > 0 ? 'Stok: '.$barang['stok'] : 'Habis'; ?>
                                        </span>
                                    </div>

                                    <div class="p-3 d-flex flex-column h-100">
                                        <div class="mb-2">
                                            <span class="badge badge-category"><?php 
                                                $kategori = $conn->query("SELECT nama_kategori FROM kategori WHERE id_kategori = ".$barang['id_kategori'])->fetch_assoc();
                                                echo htmlspecialchars($kategori['nama_kategori']);
                                            ?></span>
                                        </div>
                                        <h5 class="fw-bold mb-2">
                                            <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($barang['nama_barang']); ?>
                                            </a>
                                        </h5>
                                        <p class="price mb-2">Rp <?php echo number_format($barang['harga_eceran'], 0, ',', '.'); ?></p>
                                        <p class="text-muted small mb-3 flex-grow-1"><?php 
                                            echo strlen($barang['deskripsi']) > 100 ? 
                                                substr(htmlspecialchars($barang['deskripsi']), 0, 100).'...' : 
                                                htmlspecialchars($barang['deskripsi']);
                                        ?></p>

                                        <div class="d-grid gap-2 mt-auto">
                                            <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>" class="btn btn-success">
                                                <i class="fas fa-eye me-2"></i>Lihat Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="text-end">
                            <a href="lihat_produk.php" class="btn btn-outline-success">Lihat Semua Produk</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edukasi Section -->
        <section class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-graduation-cap me-3"></i>Edukasi Pertanian
            </h2>
            
            <?php 
            if ($berita_pertanian->num_rows > 0) {
                // Jika lebih dari 3 berita, tampilkan carousel
                if ($berita_pertanian->num_rows > 3): 
            ?>
            <div id="edukasiCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php 
                    $counter = 0;
                    $berita_pertanian->data_seek(0); // Reset pointer
                    while($berita = $berita_pertanian->fetch_assoc()): 
                        if ($counter % 3 == 0) {
                            echo '<div class="carousel-item'.($counter == 0 ? ' active' : '').'"><div class="row g-4">';
                        }
                        
                        $gambar_berita = isset($berita['gambar']) && !empty($berita['gambar']) ? $berita['gambar'] : 'https://via.placeholder.com/300x200?text=Berita+Pertanian';
                        $tanggal = date('d M Y', strtotime($berita['tanggal_posting']));
                        
                        // Get writer name if available
                        $penulis = "Admin";
                        if ($berita['id_penulis']) {
                            $query_penulis = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = ".$berita['id_penulis']);
                            if ($query_penulis && $query_penulis->num_rows > 0) {
                                $data_penulis = $query_penulis->fetch_assoc();
                                $penulis = $data_penulis['nama'];
                            }
                        }
                        
                        $deskripsi_singkat = strlen($berita['deskripsi']) > 150 ? 
                            substr(htmlspecialchars($berita['deskripsi']), 0, 150).'...' : 
                            htmlspecialchars($berita['deskripsi']);
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="education-card h-100 d-flex flex-column">
                            <div class="education-image" style="background-image: url('<?php echo $gambar_berita; ?>'); background-size: cover; background-position: center;">
                                <?php if(empty($berita['gambar'])): ?>
                                    <i class="fas fa-newspaper"></i>
                                <?php endif; ?>
                            </div>
                            <div class="education-content d-flex flex-column flex-grow-1">
                                <div class="education-meta">
                                    <span><i class="fas fa-calendar me-1"></i><?php echo $tanggal; ?></span>
                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($penulis); ?></span>
                                </div>
                                <h5 class="education-title"><?php echo htmlspecialchars($berita['judul']); ?></h5>
                                <p class="education-desc flex-grow-1"><?php echo $deskripsi_singkat; ?></p>
                                <div class="mt-auto pt-3">
                                    <a href="detail_berita.php?id=<?php echo $berita['id_berita']; ?>" class="read-more-btn w-100 text-center">
                                        <i class="fas fa-arrow-right me-2"></i>Baca Selengkapnya
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        $counter++;
                        if ($counter % 3 == 0 || $counter == $berita_pertanian->num_rows) {
                            echo '</div></div>';
                        }
                    endwhile;
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#edukasiCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-success rounded-circle p-3" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#edukasiCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-success rounded-circle p-3" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            <?php else: // Jika 3 atau kurang, tampilkan biasa ?>
            <div class="row g-4">
                <?php 
                $berita_pertanian->data_seek(0); // Reset pointer
                while($berita = $berita_pertanian->fetch_assoc()): 
                    $gambar_berita = isset($berita['gambar']) && !empty($berita['gambar']) ? $berita['gambar'] : 'https://via.placeholder.com/300x200?text=Berita+Pertanian';
                    $tanggal = date('d M Y', strtotime($berita['tanggal_posting']));
                    
                    // Get writer name if available
                    $penulis = "Admin";
                    if ($berita['id_penulis']) {
                        $query_penulis = $conn->query("SELECT nama FROM pengguna WHERE id_pengguna = ".$berita['id_penulis']);
                        if ($query_penulis && $query_penulis->num_rows > 0) {
                            $data_penulis = $query_penulis->fetch_assoc();
                            $penulis = $data_penulis['nama'];
                        }
                    }
                    
                    $deskripsi_singkat = strlen($berita['deskripsi']) > 150 ? 
                        substr(htmlspecialchars($berita['deskripsi']), 0, 150).'...' : 
                        htmlspecialchars($berita['deskripsi']);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="education-card h-100 d-flex flex-column">
                        <div class="education-image" style="background-image: url('<?php echo $gambar_berita; ?>'); background-size: cover; background-position: center;">
                            <?php if(empty($berita['gambar'])): ?>
                                <i class="fas fa-newspaper"></i>
                            <?php endif; ?>
                        </div>
                        <div class="education-content d-flex flex-column flex-grow-1">
                            <div class="education-meta">
                                <span><i class="fas fa-calendar me-1"></i><?php echo $tanggal; ?></span>
                                <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($penulis); ?></span>
                            </div>
                            <h5 class="education-title"><?php echo htmlspecialchars($berita['judul']); ?></h5>
                            <p class="education-desc flex-grow-1"><?php echo $deskripsi_singkat; ?></p>
                            <div class="mt-auto pt-3">
                                <a href="detail_berita.php?id=<?php echo $berita['id_berita']; ?>" class="read-more-btn w-100 text-center">
                                    <i class="fas fa-arrow-right me-2"></i>Baca Selengkapnya
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php 
                endif;
            } else {
                echo '<div class="col-12 text-center py-4"><p>Tidak ada berita edukasi saat ini.</p></div>';
            }
            ?>
        </section>

        <!-- Tips Pertanian Section -->
        <section class="mb-5">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title">
                        <i class="fas fa-lightbulb me-3"></i>Tips Pertanian Hari Ini
                    </h2>
                    <div class="tips-container">
                        <div class="tips-item">
                            <i class="fas fa-droplet tips-icon"></i>
                            <p class="tips-text">Siram tanaman pada pagi atau sore hari untuk menghindari penguapan berlebihan</p>
                        </div>
                        <div class="tips-item">
                            <i class="fas fa-thermometer-half tips-icon"></i>
                            <p class="tips-text">Periksa suhu tanah sebelum menanam benih untuk hasil optimal</p>
                        </div>
                        <div class="tips-item">
                            <i class="fas fa-recycle tips-icon"></i>
                            <p class="tips-text">Manfaatkan kompos dari sisa organik untuk pupuk alami yang berkualitas</p>
                        </div>
                        <div class="tips-item">
                            <i class="fas fa-sun tips-icon"></i>
                            <p class="tips-text">Pastikan tanaman mendapat sinar matahari minimal 6 jam per hari</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt me-3"></i>Kalender Tanam
                    </h2>
                    <div class="tips-container text-center">
                        <div class="mb-3">
                            <div class="stats-number" style="font-size: 2rem;">Juni</div>
                            <div class="stats-label">Musim Tanam Optimal</div>
                        </div>
                        <div class="tips-item">
                            <i class="fas fa-carrot tips-icon"></i>
                            <p class="tips-text">Waktu ideal menanam wortel dan kentang</p>
                        </div>
                        <div class="tips-item">
                            <i class="fas fa-pepper-hot tips-icon"></i>
                            <p class="tips-text">Mulai tanam cabai untuk panen September</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="detail_kalender.php" class="read-more-btn">
                                <i class="fas fa-calendar me-2"></i>Lihat Kalender Lengkap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row mb-4">
            <div class="col-12">
                        <h4 class="section-title"><i class="fas fa-star me-3"></i>Produk Terbaru</h4>
                        <div class="row">
                            <?php while($barang = $produk_terbaru->fetch_assoc()): 
                            $gambar = isset($barang['gambar']) && !empty($barang['gambar']) ? $barang['gambar'] : 'https://via.placeholder.com/300?text=Produk+Tidak+Tersedia';
                            ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="product-card h-100 d-flex flex-column">
                                    <div class="position-relative" style="overflow: hidden; border-radius: 20px 20px 0 0;">
                                        <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>">
                                            <img src="<?php echo htmlspecialchars($gambar); ?>" 
                                                class="product-img" 
                                                alt="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                        </a>
                                        <span class="badge <?php 
                                            if ($barang['stok'] == 0) {
                                                echo 'bg-danger'; 
                                            } elseif ($barang['stok'] < 25) {
                                                echo 'bg-warning text-dark'; 
                                            } else {
                                                echo 'bg-success';
                                            }
                                        ?> stock-badge">
                                            <?php echo $barang['stok'] > 0 ? 'Stok: '.$barang['stok'] : 'Habis'; ?>
                                        </span>
                                    </div>

                                    <div class="p-3 d-flex flex-column h-100">
                                        <div class="mb-2">
                                            <span class="badge badge-category"><?php 
                                                $kategori = $conn->query("SELECT nama_kategori FROM kategori WHERE id_kategori = ".$barang['id_kategori'])->fetch_assoc();
                                                echo htmlspecialchars($kategori['nama_kategori']);
                                            ?></span>
                                        </div>
                                        <h5 class="fw-bold mb-2">
                                            <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($barang['nama_barang']); ?>
                                            </a>
                                        </h5>
                                        <p class="price mb-2">Rp <?php echo number_format($barang['harga_eceran'], 0, ',', '.'); ?></p>
                                        <p class="text-muted small mb-3 flex-grow-1"><?php 
                                            echo strlen($barang['deskripsi']) > 100 ? 
                                                substr(htmlspecialchars($barang['deskripsi']), 0, 100).'...' : 
                                                htmlspecialchars($barang['deskripsi']);
                                        ?></p>

                                        <div class="d-grid gap-2 mt-auto">
                                            <a href="detail_barang.php?id=<?php echo $barang['id_barang']; ?>" class="btn btn-success">
                                                <i class="fas fa-eye me-2"></i>Lihat Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </section>

    <?php if(!isAdmin()): ?>
    <div class="modal fade" id="beritaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body p-4">
                    <!-- Konten akan diisi via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
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
                    <a href="#" class="footer-link">Beranda</a>
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
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi untuk elemen floating
            const floatingElements = document.querySelectorAll('.floating-element');
            floatingElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 2}s`;
            });
        });

        $(document).ready(function() {
            // Handle click on news items
            $('body').on('click', '[data-news-id]', function(e) {
                e.preventDefault();
                var newsId = $(this).data('news-id');
                
                // Load news content via AJAX
                $.get('detail_berita_content.php?id=' + newsId, function(data) {
                    $('#beritaModal .modal-body').html(data);
                    $('#beritaModal').modal('show');
                });
            });
        });

        $(document).ready(function() {
            // Handle click on detail pesanan
            $('body').on('click', '.btn-detail-pesanan', function(e) {
                e.preventDefault();
                var idPesanan = $(this).data('id');
                
                // Load detail pesanan via AJAX
                $.get('detail_pesanan.php?id=' + idPesanan, function(data) {
                    $('#detailPesananContent').html(data);
                    $('#detailPesananModal').modal('show');
                });
            });
        });

        function showOrderDetail(orderId) {
            // Tampilkan spinner loading
            $('#detailPesananContent').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail pesanan...</p>
                </div>
            `);
            
            // Tampilkan modal
            const modal = new bootstrap.Modal(document.getElementById('detailPesananModal'));
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
                    $('#detailPesananContent').html(data);
                })
                .catch(error => {
                    $('#detailPesananContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${error.message}
                        </div>
                    `);
                });
        }
    </script>
</body>
</html>