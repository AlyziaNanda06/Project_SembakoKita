
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

// Validasi CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['admin_csrf_token']) ||
    !hash_equals($_SESSION['admin_csrf_token'], $token)
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// Fungsi untuk mengembalikan pesan error
function gagal($pesan, $id = 0) {
    $_SESSION['pesan_error'] = $pesan;

    if ($id > 0) {
        header("Location: ../admin/edit-produk.php?id=" . $id);
    } else {
        header("Location: ../admin/produk.php");
    }
    exit;
}

// Ambil ID produk
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    gagal("ID produk tidak valid.");
}

// Ambil data formulir
$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$categoryRaw = trim($_POST['category_id'] ?? '');
$brandRaw = trim($_POST['brand_id'] ?? '');

$priceRaw = $_POST['price'] ?? '';
$discountRaw = $_POST['discount_price'] ?? '';
$weightRaw = $_POST['weight'] ?? '';
$stockRaw = $_POST['stock'] ?? '';

$description = trim($_POST['description'] ?? '');
$shipping_info = trim($_POST['shipping_info'] ?? '');
$return_policy = trim($_POST['return_policy'] ?? '');
$status = $_POST['status'] ?? '';

// Validasi nama
if ($name === '' || mb_strlen($name) > 150) {
    gagal("Nama produk wajib diisi maksimal 150 karakter.", $id);
}

// Validasi SKU (boleh kosong)
if ($sku === '') {
    $sku = null;
} elseif (mb_strlen($sku) > 50) {
    gagal("SKU maksimal 50 karakter.", $id);
}

// Validasi kategori (boleh kosong)
if ($categoryRaw === '') {
    $category_id = null;
} elseif (ctype_digit($categoryRaw) && (int)$categoryRaw > 0) {
    $category_id = (int)$categoryRaw;
} else {
    gagal("Kategori tidak valid.", $id);
}

// Validasi merek (boleh kosong)
if ($brandRaw === '') {
    $brand_id = null;
} elseif (ctype_digit($brandRaw) && (int)$brandRaw > 0) {
    $brand_id = (int)$brandRaw;
} else {
    gagal("Merek tidak valid.", $id);
}

// Validasi harga
if ($priceRaw === '' || !is_numeric($priceRaw)) {
    gagal("Harga normal wajib diisi dengan angka.", $id);
}

$price = (float)$priceRaw;

if (!is_finite($price) || $price < 0 || $price > 9999999999.99) {
    gagal("Harga normal tidak valid.", $id);
}

// Validasi harga diskon
if ($discountRaw === '') {
    $discount_price = null;
} elseif (is_numeric($discountRaw)) {
    $discount_price = (float)$discountRaw;

    if (
        !is_finite($discount_price) ||
        $discount_price < 0 ||
        $discount_price >= $price ||
        $discount_price > 9999999999.99
    ) {
        gagal("Harga diskon harus lebih kecil daripada harga normal.", $id);
    }
} else {
    gagal("Harga diskon tidak valid.", $id);
}

// Validasi berat (boleh kosong, harus bilangan bulat)
if ($weightRaw === '') {
    $weight = null;
} elseif (
    ctype_digit((string)$weightRaw) &&
    (int)$weightRaw >= 0
) {
    $weight = (int)$weightRaw;
} else {
    gagal("Berat harus berupa bilangan bulat dan tidak boleh negatif.", $id);
}

// Validasi stok
if (
    $stockRaw === '' ||
    !ctype_digit((string)$stockRaw)
) {
    gagal("Stok wajib berupa bilangan bulat dan tidak boleh negatif.", $id);
}

$stock = (int)$stockRaw;

// Validasi status
if (!in_array($status, ['active', 'inactive'], true)) {
    gagal("Status produk tidak valid.", $id);
}

// Pastikan kategori tersedia jika dipilih
if ($category_id !== null) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM categories WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ada = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if (!$ada) {
        gagal("Kategori tidak ditemukan.", $id);
    }
}

// Pastikan merek tersedia jika dipilih
if ($brand_id !== null) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM brands WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $brand_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ada = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if (!$ada) {
        gagal("Merek tidak ditemukan.", $id);
    }
}

// Cegah SKU duplikat jika SKU diisi
if ($sku !== null) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "si", $sku, $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $duplikat = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if ($duplikat) {
        gagal("SKU sudah digunakan produk lain.", $id);
    }
}

// Buat slug berdasarkan nama dan ID produk
$slug = strtolower($name);
$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');
$slug = ($slug !== '' ? $slug : 'produk') . '-' . $id;

// Mulai transaksi
try {
    mysqli_begin_transaction($conn);

    // Ambil stok lama dan kunci baris produk
    $stmt = mysqli_prepare(
        $conn,
        "SELECT stock FROM products WHERE id = ? FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $produkLama = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$produkLama) {
        throw new Exception("Produk tidak ditemukan.");
    }

    // Update produk
    $sql = "UPDATE products SET
                category_id = ?,
                brand_id = ?,
                name = ?,
                slug = ?,
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
    "iisssddiissssi",
    $category_id,
    $brand_id,
    $name,
    $slug,
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

    // Catat perubahan stok
    $stokLama = (int)$produkLama['stock'];
    $selisih = $stock - $stokLama;

    if ($selisih !== 0) {
        $tipe = $selisih > 0 ? 'IN' : 'OUT';
        $jumlah = abs($selisih);
        $keterangan = 'Penyesuaian stok melalui edit produk';

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO stock_histories
                (product_id, type, quantity, description)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isis",
            $id,
            $tipe,
            $jumlah,
            $keterangan
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

    header("Location: ../admin/produk.php?pesan=edit-berhasil");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);

    error_log("Gagal mengedit produk: " . $e->getMessage());

    gagal("Gagal menyimpan perubahan produk. Silakan periksa kembali data.", $id);
}