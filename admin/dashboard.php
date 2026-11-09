```php
<?php
session_start();
require_once "../koneksi.php";

// Pastikan admin sudah login
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Fungsi untuk menghitung jumlah data
function jumlahData($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);

    return (int) ($data["total"] ?? 0);
}

// Ringkasan dashboard
$totalProduk = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$totalKategori = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM categories"
);

// Periksa tabel orders
$cekOrders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");

$totalPesanan = 0;

if ($cekOrders && mysqli_num_rows($cekOrders) > 0) {
    $totalPesanan = jumlahData(
        $conn,
        "SELECT COUNT(*) AS total FROM orders"
    );
}

// Nama admin
$username = htmlspecialchars(
    $_SESSION["admin_username"] ?? "Admin",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Admin | Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f8f7;
            color: #263b3b;
        }

        .navbar {
            background: #359FA0;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .admin-info form {
            margin: 0;
        }

        .logout-button {
            padding: 9px 15px;
            background: #FF8C52;
            border: none;
            border-radius: 7px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        .welcome h1 {
            margin-bottom: 8px;
            font-size: 28px;
        }

        .welcome p {
            margin: 0;
            color: #6d7d7c;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(210px, 1fr)
            );
            gap: 20px;
        }

        .stat-card {
            padding: 25px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border-left: 5px solid #359FA0;
        }

        .stat-card h3 {
            margin: 0 0 14px;
            color: #657574;
            font-size: 15px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #263b3b;
        }

        .section-title {
            margin: 35px 0 18px;
            font-size: 21px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(200px, 1fr)
            );
            gap: 18px;
        }

        .menu-card {
            display: block;
            padding: 23px;
            background: white;
            border-radius: 12px;
            color: #263b3b;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-3px);
        }

        .menu-card .icon {
            font-size: 27px;
            margin-bottom: 12px;
        }

        .menu-card h3 {
            margin: 0 0 8px;
            color: #287f80;
        }

        .menu-card p {
            margin: 0;
            color: #6d7d7c;
            font-size: 14px;
            line-height: 1.5;
        }

        .notice {
            margin-top: 25px;
            padding: 15px;
            background: #e4f5f1;
            border-radius: 9px;
            color: #286b63;
            font-size: 14px;
            line-height: 1.6;
        }

        footer {
            margin-top: 50px;
            padding: 22px;
            text-align: center;
            color: #7a8988;
            font-size: 13px;
        }

        @media (max-width: 500px) {
            .welcome h1 {
                font-size: 23px;
            }

            .navbar {
                padding: 16px 20px;
            }

            .container {
                padding: 0 14px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="brand">Sembako-Kita | Admin</div>

    <div class="admin-info">
        <span>Halo, <?= $username ?></span>

        <form action="logout.php" method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["admin_csrf_token"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <button type="submit" class="logout-button">
                Logout
            </button>
        </form>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h1>Dashboard Admin</h1>
        <p>
            Selamat datang di panel pengelolaan toko Sembako-Kita.
        </p>
    </section>

    <!-- Ringkasan data -->
    <section class="stats">

        <div class="stat-card">
            <h3>Total Produk</h3>
            <div class="number"><?= $totalProduk ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Kategori</h3>
            <div class="number"><?= $totalKategori ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Pesanan</h3>
            <div class="number"><?= $totalPesanan ?></div>
        </div>

    </section>

    <h2 class="section-title">Menu Pengelolaan</h2>

    <section class="menu-grid">

        <!-- Kelola produk -->
        <a href="produk.php" class="menu-card">
            <div class="icon">📦</div>
            <h3>Kelola Produk</h3>
            <p>
                Kelola informasi produk, harga, dan stok barang.
            </p>
        </a>

        <!-- Kelola kategori -->
        <a href="kategori.php" class="menu-card">
            <div class="icon">🏷️</div>
            <h3>Kelola Kategori</h3>
            <p>
                Kelola kategori barang yang tersedia di toko.
            </p>
        </a>

        <!-- Kelola pesanan -->
        <a href="pesanan.php" class="menu-card">
            <div class="icon">🛒</div>
            <h3>Kelola Pesanan</h3>
            <p>
                Periksa transaksi dan perbarui status pesanan pelanggan.
            </p>
        </a>

        <!-- Data pelanggan -->
        <a href="pelanggan.php" class="menu-card">
            <div class="icon">👥</div>
            <h3>Data Pelanggan</h3>
            <p>
                Lihat akun, email, nomor telepon, dan alamat pelanggan.
            </p>
        </a>

        <!-- LAPORAN PENJUALAN: MENU BARU -->
        <a href="laporan.php" class="menu-card">
            <div class="icon">📊</div>
            <h3>Laporan Penjualan</h3>
            <p>
                Lihat ringkasan transaksi, total pendapatan,
                dan rincian penjualan berdasarkan periode.
            </p>
        </a>

    </section>

    <div class="notice">
        <strong>Informasi:</strong>
        Gunakan menu di atas untuk mengelola data toko.
        Laporan penjualan dihitung berdasarkan transaksi
        yang tersimpan di database.
    </div>

</main>

<footer>
    &copy; 2026 Sembako-Kita. Panel Administrasi.
</footer>

</body>
</html>
```