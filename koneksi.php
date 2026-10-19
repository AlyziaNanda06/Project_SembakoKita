<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "db_sembako_store";

// Menghubungkan PHP dengan MySQL
$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

// Memeriksa koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mengatur karakter agar mendukung teks UTF-8
mysqli_set_charset($conn, "utf8mb4");
?>