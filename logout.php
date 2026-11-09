
<?php
session_start();

// Logout hanya boleh dilakukan melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Validasi token keamanan
if (
    !isset($_SESSION["csrf_token"], $_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Permintaan tidak valid. Silakan login kembali.");
}

// Hapus semua data sesi pengguna
$_SESSION = [];

// Hapus cookie sesi jika digunakan
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Kembali ke halaman login
header("Location: login.php?logout=1");
exit;
