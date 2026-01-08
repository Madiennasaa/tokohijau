<?php
session_start(); // Pastikan session_start() ada di awal
include 'koneksi.php';

// Periksa login
if(!isset($_SESSION['user_id'])) {
    // Simpan URL referer untuk redirect setelah login
    $_SESSION['redirect_url'] = $_SERVER['HTTP_REFERER'] ?? 'detail_barang.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['add_to_cart'])) {
    // Validasi input
    if(!isset($_POST['id_barang']) || !isset($_POST['quantity'])) {
        $_SESSION['error_message'] = "Data tidak lengkap";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'detail_barang.php'));
        exit();
    }

    $id_barang = (int)$_POST['id_barang'];
    $quantity = (int)$_POST['quantity'];

    // Validasi jumlah
    if($quantity < 1) {
        $_SESSION['error_message'] = "Jumlah tidak valid";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'detail_barang.php'));
        exit();
    }

    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // 1. Periksa stok tersedia dan dapatkan info produk
        $check_stock = $conn->prepare("SELECT stok, nama_barang FROM barang WHERE id_barang = ? FOR UPDATE");
        $check_stock->bind_param("i", $id_barang);
        $check_stock->execute();
        $stock_result = $check_stock->get_result();
        
        if($stock_result->num_rows === 0) {
            throw new Exception("Produk tidak ditemukan");
        }
        
        $product = $stock_result->fetch_assoc();
        $current_stock = $product['stok'];
        $product_name = $product['nama_barang'];
        
        if($current_stock < $quantity) {
            throw new Exception("Stok $product_name tidak mencukupi. Stok tersedia: $current_stock");
        }
        
        // 2. Periksa apakah produk sudah ada di keranjang
        $check_cart = $conn->prepare("SELECT id_keranjang, jumlah FROM keranjang WHERE id_pengguna = ? AND id_barang = ?");
        $check_cart->bind_param("ii", $user_id, $id_barang);
        $check_cart->execute();
        $cart_result = $check_cart->get_result();
        
        if($cart_result->num_rows > 0) {
            // Update jumlah jika sudah ada
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['jumlah'] + $quantity;
            
            // Validasi stok setelah penambahan
            if($new_quantity > $current_stock) {
                throw new Exception("Jumlah melebihi stok tersedia untuk $product_name");
            }
            
            $update_cart = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?");
            $update_cart->bind_param("ii", $new_quantity, $cart_item['id_keranjang']);
            $update_cart->execute();
        } else {
            // Tambahkan baru jika belum ada
            $insert_cart = $conn->prepare("INSERT INTO keranjang (id_pengguna, id_barang, jumlah) VALUES (?, ?, ?)");
            $insert_cart->bind_param("iii", $user_id, $id_barang, $quantity);
            $insert_cart->execute();
        }
        
        // 3. Kurangi stok
        $new_stock = $current_stock - $quantity;
        $update_stock = $conn->prepare("UPDATE barang SET stok = ? WHERE id_barang = ?");
        $update_stock->bind_param("ii", $new_stock, $id_barang);
        $update_stock->execute();
        
        // Commit transaksi jika semua berhasil
        $conn->commit();
        
        $_SESSION['success_message'] = "$product_name berhasil ditambahkan ke keranjang";
        header("Location: keranjang.php"); // Arahkan langsung ke keranjang setelah berhasil
        exit();
        
    } catch (Exception $e) {
        // Rollback jika ada error
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'detail_barang.php'));
        exit();
    }
} else {
    // Jika akses langsung ke file ini tanpa submit form
    header("Location: detail_barang.php");
    exit();
}