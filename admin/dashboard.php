
<?php
session_start();

require_once __DIR__ . '/../koneksi.php';

// Pastikan hanya admin yang dapat membuka dashboard
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Menghitung jumlah data
function hitungData($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);
    return (int) ($data['total'] ?? 0);
}

$totalProduk = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$totalKategori = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM categories"
);

$totalPesanan = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM orders"
);

$pesananPending = hitungData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'pending'"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SembakoKita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f8;
            color: #263238;
        }

        header {
            background: #359fa0;
            color: white;
            padding: 22px 30px;
        }

        header h1 {
            margin: 0 0 6px;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 16px 30px;
            background: white;
            box-shadow: 0 2px 5px #ddd;
        }

        nav a {
            color: #267e80;
            text-decoration: none;
            font-weight: bold;
        }

        main {
            padding: 30px;
        }

        .ringkasan {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
        }

        .kartu {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 10px #0000000d;
        }

        .kartu h3 {
            margin-top: 0;
            font-size: 16px;
            color: #607d8b;
        }

        .angka {
            font-size: 32px;
            font-weight: bold;
            color: #359fa0;
        }

        .keterangan {
            margin-top: 28px;
            background: #fff0c5;
            padding: 18px;
            border-radius: 10px;
        }

        @media (max-width: 600px) {
            main {
                padding: 18px;
            }

            header, nav {
                padding: 18px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Dashboard Admin</h1>
    <p>SembakoKita — Kelola toko dengan mudah</p>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="produk.php">Kelola Produk</a>
    <a href="kategori.php">Kelola Kategori</a>
    <a href="pesanan.php">Kelola Pesanan</a>
    <a href="../index.php">Lihat Website</a>
</nav>

<main>
    <h2>Ringkasan Toko</h2>

    <section class="ringkasan">
        <div class="kartu">
            <h3>Total Produk</h3>
            <div class="angka"><?= $totalProduk ?></div>
        </div>

        <div class="kartu">
            <h3>Total Kategori</h3>
            <div class="angka"><?= $totalKategori ?></div>
        </div>

        <div class="kartu">
            <h3>Total Pesanan</h3>
            <div class="angka"><?= $totalPesanan ?></div>
        </div>

        <div class="kartu">
            <h3>Pesanan Menunggu</h3>
            <div class="angka"><?= $pesananPending ?></div>
        </div>
    </section>

    <section class="keterangan">
        <h3>Selamat datang di halaman admin!</h3>
        <p>
            Gunakan menu di atas untuk mengelola produk,
            kategori, dan pesanan pelanggan.
        </p>
    </section>
</main>

</body>
</html>
