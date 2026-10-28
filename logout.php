
<?php
session_start();

// Hapus sesi login pelanggan
unset(
    $_SESSION['user_id'],
    $_SESSION['user_name'],
    $_SESSION['user_email']
);

// Hapus sesi agar pelanggan keluar sepenuhnya
session_destroy();

// Kembali ke halaman utama
header("Location: index.php");
exit;
