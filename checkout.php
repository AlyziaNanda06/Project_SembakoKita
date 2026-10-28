
<?php
session_start();
require_once __DIR__ . '/koneksi.php';

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pastikan keranjang tidak kosong
if (empty($_SESSION['keranjang'])) {
    header("Location: keranjang.php");
    exit;
}

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

$items = [];
$total = 0;

// Ambil data produk terbaru dari database
foreach ($_SESSION['keranjang'] as $item) {
    $id = (int)($item['id'] ?? 0);
    $jumlah = (int)($item['jumlah'] ?? 0);

    if ($id <= 0 || $jumlah <= 0) {
        continue;
    }

    $stmt = mysqli_prepare(
        $conn,
       "SELECT id, name, price, discount_price, stock
 FROM products
 WHERE id = ? AND status = 'active'
 LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    if (!$produk || $jumlah > (int)$produk['stock']) {
        continue;
    }

    $hargaNormal = (float)$produk['price'];
$hargaDiskon = $produk['discount_price'];

$harga = (
    $hargaDiskon !== null &&
    (float)$hargaDiskon > 0 &&
    (float)$hargaDiskon < $hargaNormal
) ? (float)$hargaDiskon : $hargaNormal;

$produk['harga_checkout'] = $harga;
$subtotal = $harga * $jumlah;
    $produk['jumlah'] = $jumlah;
    $produk['subtotal'] = $subtotal;

    $items[] = $produk;
    $total += $subtotal;
}

if (empty($items)) {
    header("Location: keranjang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7faf9;
            color: #333;
        }

        nav {
            background: #359FA0;
            padding: 16px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 14px;
            font-weight: bold;
        }

        .container {
            max-width: 900px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 12px;
            box-shadow: 0 2px 10px #0000000d;
        }

        h1, h2 {
            color: #287f80;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #eaf7f5;
        }

        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            border: none;
            border-radius: 7px;
            background: #359FA0;
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }

        .button:hover {
            background: #287f80;
        }

        .secondary {
            background: #777;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #666;
        }
    </style>
</head>
<body>

<nav>
    <div>
        <a href="index.php">Sembako-Kita</a>
        <a href="index.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>
    </div>

    <div>
        Halo, <?= e($_SESSION['user_name'] ?? 'Pelanggan') ?>
        <a href="logout.php">Keluar</a>
    </div>
</nav>

<main class="container">
    <h1>Checkout Pesanan</h1>

    <form action="proses-checkout.php" method="POST">
        <section class="card">
            <h2>Data Pengiriman</h2>

            <label for="nama">Nama Penerima</label>
            <input
                type="text"
                id="nama"
                name="nama"
                value="<?= e($_SESSION['user_name'] ?? '') ?>"
                required
            >

            <label for="telepon">Nomor Telepon</label>
            <input
                type="tel"
                id="telepon"
                name="telepon"
                placeholder="Masukkan nomor telepon"
                required
            >

            <label for="alamat">Alamat Lengkap</label>
            <textarea
                id="alamat"
                name="alamat"
                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, dan kota"
                required
            ></textarea>

            <label for="metode">Metode Pembayaran</label>
            <select id="metode" name="metode_pembayaran" required>
                <option value="">-- Pilih Pembayaran --</option>
                <option value="COD">Bayar di Tempat (COD)</option>
                <option value="Transfer Bank">Transfer Bank</option>
            </select>
        </section>

        <section class="card">
            <h2>Ringkasan Belanja</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $produk): ?>
                            <tr>
                                <td><?= e($produk['name']) ?></td>
                                <td><?= rupiah($produk['harga_checkout']) ?></td>
                                <td><?= (int)$produk['jumlah'] ?></td>
                                <td><?= rupiah($produk['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="total">
                Total Belanja: <?= rupiah($total) ?>
            </p>

            <input
                type="hidden"
                name="total"
                value="<?= e($total) ?>"
            >

            <div class="actions">
                <a href="keranjang.php" class="button secondary">
                    Kembali ke Keranjang
                </a>

                <button type="submit" class="button">
                    Buat Pesanan
                </button>
            </div>
        </section>
    </form>
</main>

<footer>
    © 2026 Sembako-Kita. Semua hak dilindungi.
</footer>

</body>
</html>
