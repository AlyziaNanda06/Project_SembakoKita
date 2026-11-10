
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
    header("Location: ../admin/pesanan.php");
    exit;
}

// Validasi CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['admin_csrf_token']) ||
    !hash_equals($_SESSION['admin_csrf_token'], $token)
) {
    $_SESSION['pesanan_error'] = 'Permintaan tidak valid. Silakan coba lagi.';
    header("Location: ../admin/pesanan.php");
    exit;
}

// Validasi ID pesanan
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$jenis = $_POST['jenis'] ?? '';
$nilai = $_POST['nilai'] ?? '';

if (!$id || $id < 1) {
    $_SESSION['pesanan_error'] = 'ID pesanan tidak valid.';
    header("Location: ../admin/pesanan.php");
    exit;
}

// Tentukan kolom yang boleh diperbarui
$kolomDiizinkan = [
    'status' => 'status',
    'payment_status' => 'payment_status'
];

if (!isset($kolomDiizinkan[$jenis])) {
    $_SESSION['pesanan_error'] = 'Jenis perubahan tidak valid.';
    header("Location: ../admin/pesanan.php");
    exit;
}

$kolom = $kolomDiizinkan[$jenis];

// Ambil pilihan ENUM dari database
$resultEnum = mysqli_query(
    $conn,
    "SHOW COLUMNS FROM orders LIKE '$kolom'"
);

$dataEnum = $resultEnum
    ? mysqli_fetch_assoc($resultEnum)
    : null;

$pilihan = [];

if (
    $dataEnum &&
    preg_match("/^enum\((.*)\)$/i", $dataEnum['Type'], $matches)
) {
    $pilihan = str_getcsv($matches[1], ",", "'");
}

if (!in_array($nilai, $pilihan, true)) {
    $_SESSION['pesanan_error'] = 'Status yang dipilih tidak tersedia.';
    header("Location: ../admin/pesanan.php");
    exit;
}

// Pastikan pesanan tersedia
$stmtCek = mysqli_prepare(
    $conn,
    "SELECT id FROM orders WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmtCek, "i", $id);
mysqli_stmt_execute($stmtCek);

$resultCek = mysqli_stmt_get_result($stmtCek);
$pesananAda = mysqli_fetch_assoc($resultCek);

mysqli_stmt_close($stmtCek);

if (!$pesananAda) {
    $_SESSION['pesanan_error'] = 'Pesanan tidak ditemukan.';
    header("Location: ../admin/pesanan.php");
    exit;
}

// Update status
$sql = "UPDATE orders SET `$kolom` = ? WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['pesanan_error'] = 'Gagal menyiapkan proses pembaruan.';
    header("Location: ../admin/pesanan.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $nilai, $id);

if (mysqli_stmt_execute($stmt)) {

    if ($jenis === 'status') {
        $_SESSION['pesanan_pesan'] = 'Status pesanan berhasil diperbarui.';
    } else {
        $_SESSION['pesanan_pesan'] = 'Status pembayaran berhasil diperbarui.';
    }

} else {
    $_SESSION['pesanan_error'] = 'Gagal memperbarui data pesanan.';
}

mysqli_stmt_close($stmt);

header("Location: ../admin/pesanan.php");
exit;