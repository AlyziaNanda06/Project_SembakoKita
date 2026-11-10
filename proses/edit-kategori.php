
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
$nama = trim($_POST["name"] ?? "");
$deskripsi = trim($_POST["description"] ?? "");

if ($id <= 0 || $nama === "" || mb_strlen($nama) > 100) {
    die("Data kategori tidak valid. Nama wajib diisi maksimal 100 karakter.");
}

// Buat slug otomatis dari nama kategori
$slug = strtolower($nama);
$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');
if ($slug === "") {
    die("Nama kategori harus mengandung huruf atau angka.");
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE categories
     SET name = ?, slug = ?, description = ?
     WHERE id = ?"
);

if (!$stmt) {
    die("Query gagal disiapkan: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "sssi", $nama, $slug, $deskripsi, $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: ../admin/kategori.php?edit_berhasil=1");
    exit;
}

mysqli_stmt_close($stmt);
die("Kategori gagal diperbarui. Periksa apakah slug sudah digunakan.");