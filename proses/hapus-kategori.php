
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/kategori.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["admin_csrf_token"]) ||
    !hash_equals($_SESSION["admin_csrf_token"], $token)
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: ../admin/kategori.php?hapus_gagal=1");
    exit;
}

// Periksa apakah kategori masih memiliki produk
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah FROM products WHERE category_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ((int) $data["jumlah"] > 0) {
    header("Location: ../admin/kategori.php?kategori_dipakai=1");
    exit;
}

// Hapus kategori jika tidak memiliki produk
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM categories WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("Location: ../admin/kategori.php?hapus_berhasil=1");
    exit;
}

mysqli_stmt_close($stmt);
header("Location: ../admin/kategori.php?hapus_gagal=1");
exit;