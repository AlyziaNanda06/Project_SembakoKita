
<?php
session_start();
require_once __DIR__ . "/../koneksi.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/kategori.php");
    exit;
}

// Validasi CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["admin_csrf_token"]) ||
    !hash_equals($_SESSION["admin_csrf_token"], $token)
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// Validasi ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: ../admin/kategori.php?hapus_gagal=1");
    exit;
}

try {
    mysqli_begin_transaction($conn);

    // Kunci kategori selama proses penghapusan
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM categories WHERE id = ? FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $kategori = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$kategori) {
        throw new Exception("Kategori tidak ditemukan.");
    }

    // Periksa apakah kategori masih memiliki produk
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS jumlah
         FROM products
         WHERE category_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ((int) $data["jumlah"] > 0) {
        mysqli_rollback($conn);
        header("Location: ../admin/kategori.php?kategori_dipakai=1");
        exit;
    }

    // Hapus kategori
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM categories WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("Kategori gagal dihapus.");
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    header("Location: ../admin/kategori.php?hapus_berhasil=1");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log("Hapus kategori: " . $e->getMessage());

    header("Location: ../admin/kategori.php?hapus_gagal=1");
    exit;
}