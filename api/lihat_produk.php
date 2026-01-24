<?php
session_start();
include 'koneksi.php';

/* ======================
   ERROR DEBUG (hapus kalau sudah production)
====================== */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ======================
   LOGIN STATUS
====================== */
$is_logged_in = isset($_SESSION['user_id']);
$user_role    = $is_logged_in ? ($_SESSION['role'] ?? 'user') : 'guest';

/* ======================
   PAGINATION
====================== */
$items_per_page = 6;
$current_page   = max(1, (int)($_GET['page'] ?? 1));
$offset         = ($current_page - 1) * $items_per_page;

/* ======================
   FILTER
====================== */
$where  = [];
$params = [];

/* Filter kategori */
if (!empty($_GET['kategori'])) {
    $ids = array_filter(array_map('intval', explode(',', $_GET['kategori'])));
    if (!empty($ids)) {
        $where[] = "b.id_kategori IN (" . implode(',', $ids) . ")";
    }
}

/* Filter harga */
if (!empty($_GET['max_price'])) {
    $where[]  = "b.harga_eceran <= ?";
    $params[] = (int)$_GET['max_price'];
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ======================
   TOTAL PRODUK
====================== */
$total_stmt  = $pdo->prepare("SELECT COUNT(*) FROM barang b $where_sql");
$total_stmt->execute($params);
$total_items = (int)$total_stmt->fetchColumn();
$total_pages = max(1, ceil($total_items / $items_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $items_per_page;
}

/* ======================
   AMBIL PRODUK (JOIN KATEGORI)
====================== */
$sql = "
    SELECT 
        b.*,
        k.nama_kategori
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    $where_sql
    LIMIT $offset, $items_per_page
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================
   LIST KATEGORI (FILTER)
====================== */
$catStmt    = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Toko Hijau - Produk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $is_logged_in ? 'dashboard.php' : 'index.php' ?>">
            🌱 Toko Hijau
        </a>
    </div>
</nav>

<div class="container" style="padding-top:100px">

<?php if(!$is_logged_in): ?>
<div class="alert alert-warning">
    ⚠️ Silakan <a href="login.php">login</a> untuk belanja
</div>
<?php endif; ?>

<div class="row">
    <!-- FILTER -->
    <div class="col-md-3 mb-4">
        <div class="card p-3 shadow-sm">
            <h5>Filter</h5>

            <h6 class="mt-3">Kategori</h6>
            <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           value="<?= $cat['id_kategori'] ?>"
                           <?= (isset($_GET['kategori']) && in_array($cat['id_kategori'], explode(',', $_GET['kategori']))) ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </label>
                </div>
            <?php endforeach; ?>

            <h6 class="mt-3">Harga Maks</h6>
            <input type="range" id="priceRange" min="0" max="1000000" step="10000"
                   value="<?= (int)($_GET['max_price'] ?? 1000000) ?>">
            <small id="priceValue"></small>

            <button class="btn btn-success w-100 mt-3" id="applyFilter">Terapkan</button>
            <a href="lihat_produk.php" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
        </div>
    </div>

    <!-- PRODUK -->
    <div class="col-md-9">
        <h6 class="mb-3">
            Menampilkan <?= count($products) ?> dari <?= $total_items ?> produk
        </h6>

        <?php if(empty($products)): ?>
            <div class="alert alert-info">Produk tidak ditemukan</div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($products as $p): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= htmlspecialchars($p['gambar'] ?? 'https://via.placeholder.com/300') ?>"
                         class="card-img-top" style="height:200px;object-fit:cover">
                    <div class="card-body">
                        <span class="badge bg-success mb-2"><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></span>
                        <h6><?= htmlspecialchars($p['nama_barang']) ?></h6>
                        <p class="fw-bold text-success">
                            Rp <?= number_format($p['harga_eceran'],0,',','.') ?>
                        </p>
                        <a href="detail_barang.php?id=<?= $p['id_barang'] ?>" class="btn btn-success w-100">
                            Detail
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- PAGINATION -->
        <?php if($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for($i=1;$i<=$total_pages;$i++): ?>
                    <li class="page-item <?= $i==$current_page?'active':'' ?>">
                        <a class="page-link"
                           href="?page=<?= $i ?><?= isset($_GET['kategori'])?'&kategori='.$_GET['kategori']:'' ?><?= isset($_GET['max_price'])?'&max_price='.$_GET['max_price']:'' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
const priceRange = document.getElementById('priceRange');
const priceValue = document.getElementById('priceValue');

function updatePrice() {
    priceValue.textContent = 'Rp ' + parseInt(priceRange.value).toLocaleString('id-ID');
}
updatePrice();
priceRange.addEventListener('input', updatePrice);

document.getElementById('applyFilter').onclick = () => {
    const cats = [];
    document.querySelectorAll('.form-check-input:checked').forEach(c => cats.push(c.value));
    const params = [];
    if (cats.length) params.push('kategori=' + cats.join(','));
    if (priceRange.value < 1000000) params.push('max_price=' + priceRange.value);
    location.href = 'lihat_produk.php' + (params.length ? '?' + params.join('&') : '');
};
</script>

</body>
</html>
