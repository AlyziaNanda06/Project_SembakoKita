
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/tambah-produk.php");
    exit;
}

function gagal($pesan) {
    $_SESSION['pesan_error'] = $pesan;
    header("Location: ../admin/tambah-produk.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$skuInput = trim($_POST['sku'] ?? '');
$sku = $skuInput === '' ? null : $skuInput;

$categoryInput = $_POST['category_id'] ?? '';
$brandInput = $_POST['brand_id'] ?? '';

$categoryId = $categoryInput === '' ? null : filter_var(
    $categoryInput, FILTER_VALIDATE_INT
);
$brandId = $brandInput === '' ? null : filter_var(
    $brandInput, FILTER_VALIDATE_INT
);

$priceInput = $_POST['price'] ?? '';
$discountInput = $_POST['discount_price'] ?? '';
$weightInput = $_POST['weight'] ?? '';
$stockInput = $_POST['stock'] ?? '';

$description = trim($_POST['description'] ?? '');
$shippingInfo = trim($_POST['shipping_info'] ?? '');
$returnPolicy = trim($_POST['return_policy'] ?? '');
$status = $_POST['status'] ?? 'active';

if ($name === '' || mb_strlen($name) > 150) {
    gagal("Nama produk wajib diisi dan maksimal 150 karakter.");
}

if ($sku !== null && mb_strlen($sku) > 50) {
    gagal("SKU maksimal 50 karakter.");
}

if ($categoryId === false || $brandId === false) {
    gagal("Kategori atau merek tidak valid.");
}

if ($categoryId !== null && $categoryId < 1) {
    gagal("Kategori tidak valid.");
}

if ($brandId !== null && $brandId < 1) {
    gagal("Merek tidak valid.");
}

if (
    $priceInput === '' ||
    !is_numeric($priceInput) ||
    (float)$priceInput < 0 ||
    (float)$priceInput > 9999999999.99
) {
    gagal("Harga normal tidak valid.");
}

$price = (float)$priceInput;
$discountPrice = null;

if ($discountInput !== '') {
    if (
        !is_numeric($discountInput) ||
        (float)$discountInput < 0 ||
        (float)$discountInput > 9999999999.99 ||
        (float)$discountInput >= $price
    ) {
        gagal("Harga diskon harus valid dan lebih rendah dari harga normal.");
    }

    $discountPrice = (float)$discountInput;
}

if (
    $stockInput === '' ||
    filter_var($stockInput, FILTER_VALIDATE_INT) === false ||
    (int)$stockInput < 0
) {
    gagal("Stok harus berupa bilangan bulat nol atau lebih.");
}

$stock = (int)$stockInput;

$weight = null;
if ($weightInput !== '') {
    if (
        filter_var($weightInput, FILTER_VALIDATE_INT) === false ||
        (int)$weightInput < 0
    ) {
        gagal("Berat harus berupa bilangan bulat nol atau lebih.");
    }
    $weight = (int)$weightInput;
}

if (!in_array($status, ['active', 'inactive'], true)) {
    gagal("Status produk tidak valid.");
}

// Pastikan kategori dan merek benar-benar ada.
if ($categoryId !== null) {
    $stmt = mysqli_prepare(
        $conn, "SELECT id FROM categories WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
    mysqli_stmt_execute($stmt);
    $cek = mysqli_stmt_get_result($stmt);
    $ada = mysqli_num_rows($cek) > 0;
    mysqli_stmt_close($stmt);

    if (!$ada) gagal("Kategori tidak ditemukan.");
}

if ($brandId !== null) {
    $stmt = mysqli_prepare(
        $conn, "SELECT id FROM brands WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $brandId);
    mysqli_stmt_execute($stmt);
    $cek = mysqli_stmt_get_result($stmt);
    $ada = mysqli_num_rows($cek) > 0;
    mysqli_stmt_close($stmt);

    if (!$ada) gagal("Merek tidak ditemukan.");
}

// Membuat slug dari nama produk.
$slugDasar = strtolower(trim($name));
$slugDasar = preg_replace('/[^a-z0-9]+/', '-', $slugDasar);
$slugDasar = trim($slugDasar, '-');

if ($slugDasar === '') {
    $slugDasar = 'produk';
}

$slug = $slugDasar . '-' . bin2hex(random_bytes(4));

mysqli_begin_transaction($conn);

try {
    $sql = "INSERT INTO products
        (category_id, brand_id, name, slug, sku, price,
         discount_price, weight, stock, description,
         shipping_info, return_policy, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iisssddiissss",
        $categoryId,
        $brandId,
        $name,
        $slug,
        $sku,
        $price,
        $discountPrice,
        $weight,
        $stock,
        $description,
        $shippingInfo,
        $returnPolicy,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menyimpan produk.");
    }

    $productId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Catat stok awal jika produk langsung memiliki stok.
    if ($stock > 0) {
        $jenis = 'IN';
        $keterangan = 'Stok awal saat produk ditambahkan';

        $stmtStok = mysqli_prepare(
            $conn,
            "INSERT INTO stock_histories
             (product_id, type, quantity, description)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmtStok,
            "isis",
            $productId,
            $jenis,
            $stock,
            $keterangan
        );

        if (!mysqli_stmt_execute($stmtStok)) {
            throw new Exception("Gagal menyimpan riwayat stok.");
        }

        mysqli_stmt_close($stmtStok);
    }

    mysqli_commit($conn);

    $_SESSION['pesan_sukses'] = "Produk berhasil ditambahkan.";
    header("Location: ../admin/produk.php");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    gagal("Produk gagal disimpan. Periksa data dan struktur database.");
}
