
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Pastikan admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/produk.php");
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['admin_csrf_token']) ||
    !hash_equals($_SESSION['admin_csrf_token'], $token)
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: ../admin/produk.php?hapus_gagal=1");
    exit;
}

try {
    mysqli_begin_transaction($conn);

    // Pastikan produk tersedia
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM products WHERE id = ? FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$produk) {
        throw new Exception("Produk tidak ditemukan.");
    }

    // Cek apakah produk masih digunakan tabel lain
    $tabelRelasi = [
        'order_items',
        'cart_items',
        'reviews',
        'wishlists'
    ];

    foreach ($tabelRelasi as $tabel) {
        $sql = "SELECT COUNT(*) AS jumlah FROM `$tabel` WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ((int)$data['jumlah'] > 0) {
            throw new Exception("Produk masih memiliki data terkait.");
        }
    }

   // Hapus data gambar produk dari database
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM product_images WHERE product_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Hapus riwayat stok produk
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM stock_histories WHERE product_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

    // Hapus produk
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM products WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) < 1) {
        throw new Exception("Produk gagal dihapus.");
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    header("Location: ../admin/produk.php?hapus_berhasil=1");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log("Hapus produk: " . $e->getMessage());

    header("Location: ../admin/produk.php?hapus_gagal=1");
    exit;
}