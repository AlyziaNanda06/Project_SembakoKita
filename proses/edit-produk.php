
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Pastikan admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Proses hanya boleh melalui formulir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/produk.php");
    exit;
}

// Ambil dan validasi ID produk
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("ID produk tidak valid.");
}

// Ambil data dari formulir
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$brand_id = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);

$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$price = $_POST['price'] ?? '';
$discount_price = $_POST['discount_price'] ?? '';
$weight = $_POST['weight'] ?? '';
$stock = $_POST['stock'] ?? '';
$description = trim($_POST['description'] ?? '');
$shipping_info = trim($_POST['shipping_info'] ?? '');
$return_policy = trim($_POST['return_policy'] ?? '');
$status = $_POST['status'] ?? '';

// Validasi kolom wajib
if (
    !$category_id ||
    !$brand_id ||
    $name === '' ||
    $sku === '' ||
    !is_numeric($price) ||
    !is_numeric($weight) ||
    !is_numeric($stock) ||
    (string)$price === ''
) {
    die("Data produk belum lengkap atau tidak valid. Silakan kembali dan periksa formulir.");
}

$price = (float)$price;
$weight = (float)$weight;
$stock = (int)$stock;

if ($price < 0 || $weight < 0 || $stock < 0) {
    die("Harga, berat, dan stok tidak boleh negatif.");
}

// Harga diskon boleh kosong
if ($discount_price === '' || $discount_price === null) {
    $discount_price = null;
} elseif (!is_numeric($discount_price) || (float)$discount_price < 0) {
    die("Harga diskon tidak valid.");
} else {
    $discount_price = (float)$discount_price;

    if ($discount_price >= $price) {
        die("Harga diskon harus lebih kecil daripada harga normal.");
    }
}

// Validasi status
if (!in_array($status, ['active', 'inactive'], true)) {
    die("Status produk tidak valid.");
}

// Pastikan kategori tersedia
$stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$kategori = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($kategori) === 0) {
    die("Kategori tidak ditemukan.");
}
mysqli_stmt_close($stmt);

// Pastikan merek tersedia
$stmt = mysqli_prepare($conn, "SELECT id FROM brands WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $brand_id);
mysqli_stmt_execute($stmt);
$merek = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($merek) === 0) {
    die("Merek tidak ditemukan.");
}
mysqli_stmt_close($stmt);

// Ambil stok lama
$stmt = mysqli_prepare(
    $conn,
    "SELECT stock FROM products WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$produkLama = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$produkLama) {
    die("Produk tidak ditemukan.");
}

// Cegah SKU dipakai produk lain
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "si", $sku, $id);
mysqli_stmt_execute($stmt);
$skuDuplikat = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($skuDuplikat) > 0) {
    die("SKU sudah digunakan produk lain. Gunakan SKU yang berbeda.");
}
mysqli_stmt_close($stmt);

// Simpan perubahan dengan transaksi
mysqli_begin_transaction($conn);

try {
    $sql = "UPDATE products SET
                category_id = ?,
                brand_id = ?,
                name = ?,
                sku = ?,
                price = ?,
                discount_price = ?,
                weight = ?,
                stock = ?,
                description = ?,
                shipping_info = ?,
                return_policy = ?,
                status = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iissddiissssi",
        $category_id,
        $brand_id,
        $name,
        $sku,
        $price,
        $discount_price,
        $weight,
        $stock,
        $description,
        $shipping_info,
        $return_policy,
        $status,
        $id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Catat perubahan stok jika stok berubah
    $stokLama = (int)$produkLama['stock'];
    $selisih = $stock - $stokLama;

    if ($selisih !== 0) {
        $tipe = $selisih > 0 ? 'IN' : 'OUT';
        $jumlah = abs($selisih);
        $keterangan = 'Penyesuaian stok melalui edit produk';

        $stmtStok = mysqli_prepare(
            $conn,
            "INSERT INTO stock_histories
                (product_id, type, quantity, description)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmtStok,
            "isis",
            $id,
            $tipe,
            $jumlah,
            $keterangan
        );

        mysqli_stmt_execute($stmtStok);
        mysqli_stmt_close($stmtStok);
    }

    mysqli_commit($conn);

    header("Location: ../admin/produk.php?pesan=edit-berhasil");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log("Gagal mengedit produk: " . $e->getMessage());
    die("Gagal menyimpan perubahan produk. Periksa struktur tabel dan data, lalu coba lagi.");
}
?>
