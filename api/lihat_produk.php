<?php
session_start();
require_once 'koneksi.php'; // HARUS define $pdo (PDO)

// ==========================
// PAGINATION
// ==========================
$items_per_page = 6;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Total produk
$total_stmt = $pdo->query("SELECT COUNT(*) FROM barang");
$total_items = (int)$total_stmt->fetchColumn();
$total_pages = max(1, ceil($total_items / $items_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// ==========================
// FILTER
// ==========================
$where = [];
$params = [];

// Filter kategori
if (!empty($_GET['kategori'])) {
    $ids = array_map('intval', explode(',', $_GET['kategori']));
    $where[] = "id_kategori IN (" . implode(',', $ids) . ")";
}

// Filter harga
if (!empty($_GET['max_price'])) {
    $where[] = "harga_eceran <= ?";
    $params[] = (int)$_GET['max_price'];
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ==========================
// PRODUK
// ==========================
$sql = "
    SELECT *
    FROM barang
    $where_sql
    ORDER BY id_barang DESC
    LIMIT $offset, $items_per_page
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// KATEGORI
// ==========================
$kategori_stmt = $pdo->query("SELECT * FROM kategori");
$kategori_list = $kategori_stmt->fetchAll(PDO::FETCH_ASSOC);

// map id => nama
$kategori_map = [];
foreach ($kategori_list as $k) {
    $kategori_map[$k['id_kategori']] = $k['nama_kategori'];
}

// ==========================
// LOGIN
// ==========================
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk | Toko Hijau</title>
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
            overflow: hidden
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

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-seedling me-2"></i>Toko Hijau
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="lihat_produk.php">Produk</a></li>
                <?php if($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container" style="margin-top:100px">

<?php if(!$is_logged_in): ?>
<div class="alert alert-warning">
    <i class="fas fa-info-circle"></i>
    Silakan <a href="login.php" class="fw-bold">login</a> untuk melakukan pemesanan.
</div>
<?php endif; ?>

<div class="row">

<!-- FILTER -->
<div class="col-md-3">
    <div class="card mb-4">
        <div class="card-body">
            <h5>Filter Produk</h5>

            <h6>Kategori</h6>
            <?php foreach ($kategori_list as $cat): ?>
                <div class="form-check">
                    <input class="form-check-input cat-check"
                        type="checkbox"
                        value="<?= $cat['id_kategori'] ?>"
                        <?= (isset($_GET['kategori']) && in_array($cat['id_kategori'], explode(',', $_GET['kategori']))) ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </label>
                </div>
            <?php endforeach; ?>

            <hr>

            <h6>Harga Maks</h6>
            <input type="number" id="maxPrice" class="form-control"
                   value="<?= $_GET['max_price'] ?? '' ?>">

            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-success" onclick="applyFilter()">Terapkan</button>
                <a href="lihat_produk.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </div>
</div>

<!-- PRODUK -->
<div class="col-md-9">

<p class="text-muted">
    Menampilkan <b><?= count($products) ?></b> dari <b><?= $total_items ?></b> produk
</p>

<?php if(empty($products)): ?>
<div class="alert alert-info">Produk tidak ditemukan.</div>
<?php else: ?>
<div class="row">
<?php foreach ($products as $p): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="<?= htmlspecialchars($p['gambar'] ?? 'https://via.placeholder.com/300') ?>"
                 class="card-img-top">

            <div class="card-body d-flex flex-column">
                <span class="badge bg-secondary mb-2">
                    <?= htmlspecialchars($kategori_map[$p['id_kategori']] ?? '-') ?>
                </span>

                <h5><?= htmlspecialchars($p['nama_barang']) ?></h5>
                <p class="text-success fw-bold">
                    Rp <?= number_format($p['harga_eceran'],0,',','.') ?>
                </p>
                <p class="small text-muted flex-grow-1">
                    <?= htmlspecialchars(substr($p['deskripsi'],0,100)) ?>...
                </p>

                <a href="detail_barang.php?id=<?= $p['id_barang'] ?>"
                   class="btn btn-success mt-auto">
                    Lihat Detail
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- PAGINATION -->
<nav>
<ul class="pagination justify-content-center">
<?php for($i=1;$i<=$total_pages;$i++): ?>
<li class="page-item <?= $i==$current_page?'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>
<?= isset($_GET['kategori'])?'&kategori='.$_GET['kategori']:'' ?>
<?= isset($_GET['max_price'])?'&max_price='.$_GET['max_price']:'' ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
</nav>

</div>
</div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const priceRange   = document.getElementById('priceRange');
            const priceValue   = document.getElementById('priceValue');
            const applyBtn     = document.getElementById('applyFilter');
            const resetBtn     = document.getElementById('resetFilter');

            // ❗ Kalau elemen penting nggak ada → stop
            if (!priceRange || !priceValue || !applyBtn || !resetBtn) return;

            const MAX_PRICE = priceRange.max || 1000000;

            // Update tampilan harga
            const updatePriceText = () => {
                priceValue.textContent =
                    'Rp ' + parseInt(priceRange.value).toLocaleString('id-ID');
            };

            updatePriceText();

            priceRange.addEventListener('input', updatePriceText);

            // APPLY FILTER
            applyBtn.addEventListener('click', function () {

                const selectedCategories = [];
                document
                    .querySelectorAll('.form-check-input:checked')
                    .forEach(cb => selectedCategories.push(cb.value));

                const params = new URLSearchParams();

                if (selectedCategories.length > 0) {
                    params.set('kategori', selectedCategories.join(','));
                }

                if (parseInt(priceRange.value) < MAX_PRICE) {
                    params.set('max_price', priceRange.value);
                }

                window.location.href =
                    'lihat_produk.php' + (params.toString() ? '?' + params.toString() : '');
            });

            // RESET FILTER
            resetBtn.addEventListener('click', function () {
                document
                    .querySelectorAll('.form-check-input')
                    .forEach(cb => cb.checked = false);

                priceRange.value = MAX_PRICE;
                updatePriceText();

                window.location.href = 'lihat_produk.php';
            });
        });
    </script>


</body>
</html>
