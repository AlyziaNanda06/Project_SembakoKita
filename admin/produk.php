
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Sesuaikan dengan session login admin di project kamu.
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$sql = "SELECT
            p.id,
            p.name,
            p.sku,
            p.price,
            p.discount_price,
            p.stock,
            p.status,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Gagal mengambil data produk.");
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - SembakoKita</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f7f8;
            color: #263238;
        }
        .container {
            max-width: 1200px;
            margin: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        h1 { color: #267e80; }
        .tombol {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 7px;
            text-decoration: none;
            background: #359fa0;
            color: white;
            border: none;
            cursor: pointer;
        }
        .kembali {
            display: inline-block;
            margin-bottom: 15px;
            color: #267e80;
            text-decoration: none;
        }
        .tabel-wrapper {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            padding: 15px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 850px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th { background: #e4f5f2; }
        .status {
            padding: 5px 9px;
            border-radius: 5px;
            background: #e4f5f2;
        }
        .kosong { text-align: center; padding: 25px; }
    </style>
</head>
<body>
<div class="container">

    <a class="kembali" href="dashboard.php">← Kembali ke Dashboard</a>

    <div class="header">
        <h1>Kelola Produk</h1>
        <a class="tombol" href="tambah-produk.php">+ Tambah Produk</a>
    </div>

    <div class="tabel-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Produk</th>
                    <th>SKU</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Harga Diskon</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php $no = 1; ?>
                <?php while ($p = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['category_name'] ?? 'Tanpa kategori') ?></td>
                        <td><?= rupiah($p['price'] ?? 0) ?></td>
                        <td>
                            <?= $p['discount_price'] !== null
                                ? rupiah($p['discount_price'])
                                : '-' ?>
                        </td>
                        <td><?= (int)$p['stock'] ?></td>
                        <td>
                            <span class="status">
                                <?= htmlspecialchars($p['status'] ?? '-') ?>
                            </span>
                        </td>
                        <td>
                            <a class="tombol"
                               href="edit-produk.php?id=<?= (int)$p['id'] ?>">
                                Edit
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="kosong">
                        Belum ada produk.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
