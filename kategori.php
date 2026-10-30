
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

$slug = trim($_GET['slug'] ?? '');

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1"
);

if (!$stmt) {
    die("Query kategori gagal: " . e(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);

$hasilKategori = mysqli_stmt_get_result($stmt);
$kategori = mysqli_fetch_assoc($hasilKategori);

if (!$kategori) {
    http_response_code(404);
    $pesan = "Kategori tidak ditemukan.";
} else {
    $pesan = '';

    $stmtProduk = mysqli_prepare(
        $conn,
        "SELECT id, name, price, stock
         FROM products
         WHERE category_id = ?
         ORDER BY id DESC"
    );

    if (!$stmtProduk) {
        die("Query produk gagal: " . e(mysqli_error($conn)));
    }

    mysqli_stmt_bind_param($stmtProduk, "i", $kategori['id']);
    mysqli_stmt_execute($stmtProduk);
    $hasilProduk = mysqli_stmt_get_result($stmtProduk);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kategori Produk - Sembako-Kita</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h2><a href="index.php">Sembako-Kita</a></h2>
    <nav>
        <a href="index.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="login.php">Masuk</a>
    </nav>
</header>

<main>
    <h1>Kategori Produk</h1>

    <?php if ($pesan !== ''): ?>
        <p><?= e($pesan) ?></p>
        <p><a href="produk.php">Lihat semua produk</a></p>
    <?php else: ?>
        <h2><?= e($kategori['name']) ?></h2>

        <?php if (mysqli_num_rows($hasilProduk) === 0): ?>
            <p>Belum ada produk dalam kategori ini.</p>
        <?php else: ?>
            <section class="daftar-produk">
                <?php while ($produk = mysqli_fetch_assoc($hasilProduk)): ?>
                    <article class="kartu-produk">
                        <h3>
                            <a href="detail-produk.php?id=<?= (int)$produk['id'] ?>">
                                <?= e($produk['name']) ?>
                            </a>
                        </h3>

                        <p>Harga: Rp <?= number_format((float)$produk['price'], 0, ',', '.') ?></p>
                        <p>Stok: <?= (int)$produk['stock'] ?></p>

                        <a href="detail-produk.php?id=<?= (int)$produk['id'] ?>">
                            Lihat Produk
                        </a>
                    </article>
                <?php endwhile; ?>
            </section>
            
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 Sembako-Kita</p>
</footer>
</body>
</html>

