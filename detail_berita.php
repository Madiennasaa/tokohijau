<?php
include 'koneksi.php';

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id_berita = (int)$_GET['id'];
$query = $conn->prepare("SELECT b.*, p.nama as nama_penulis 
                        FROM berita b 
                        LEFT JOIN pengguna p ON b.id_penulis = p.id_pengguna 
                        WHERE b.id_berita = ?");
$query->bind_param("i", $id_berita);
$query->execute();
$result = $query->get_result();

if($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$berita = $result->fetch_assoc();
$tanggal = date('d F Y', strtotime($berita['tanggal_posting']));
$gambar_berita = !empty($berita['gambar']) ? $berita['gambar'] : 'https://via.placeholder.com/800x400?text=Berita+Pertanian';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Berita - <?php echo htmlspecialchars($berita['judul']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .berita-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .berita-image {
            width: 100%;
            height: auto; /* Biarkan tinggi menyesuaikan proporsi */
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .berita-title {
            color: #22c55e;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .berita-meta {
            color: #6c757d;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .berita-content {
            line-height: 1.8;
            color: #495057;
        }
        .back-btn {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="berita-container">
            <a href="dashboard.php" class="btn btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            
            <img src="<?php echo htmlspecialchars($gambar_berita); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="berita-image">
            
            <div class="berita-meta">
                <span><i class="fas fa-calendar-alt me-1"></i> <?php echo $tanggal; ?></span>
                <span class="float-end"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($berita['nama_penulis'] ?? 'Admin'); ?></span>
            </div>
            
            <h1 class="berita-title"><?php echo htmlspecialchars($berita['judul']); ?></h1>
            
            <div class="berita-content">
                <?php echo nl2br(htmlspecialchars($berita['deskripsi'])); ?>
            </div>
            
            <a href="dashboard.php" class="btn btn-success back-btn">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>