<?php
require_once "koneksi.php";

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

// Mengambil ID produk dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit("Produk tidak ditemukan.");
}

// Mengambil informasi produk
$sql = "SELECT
            p.id,
            p.name,
            p.sku,
            p.price,
            p.discount_price,
            p.weight,
            p.stock,
            p.description,
            p.shipping_info,
            p.return_policy,
            c.name AS nama_kategori
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
          AND p.status = 'active'
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    http_response_code(404);
    exit("Produk tidak ditemukan atau tidak tersedia.");
}

// Mengambil foto produk
$sqlFoto = "SELECT image, is_primary
            FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, id ASC
            LIMIT 1";

$stmtFoto = mysqli_prepare($conn, $sqlFoto);
mysqli_stmt_bind_param($stmtFoto, "i", $id);
mysqli_stmt_execute($stmtFoto);

$resultFoto = mysqli_stmt_get_result($stmtFoto);
$dataFoto = mysqli_fetch_assoc($resultFoto);

$foto = $dataFoto['image'] ?? '';

// Menghitung harga setelah diskon
$adaDiskon = $produk['discount_price'] !== null
    && (float)$produk['discount_price'] > 0
    && (float)$produk['discount_price'] < (float)$produk['price'];

$harga = $adaDiskon
    ? $produk['discount_price']
    : $produk['price'];

$persenDiskon = $adaDiskon
    ? round((1 - (float)$produk['discount_price'] /
        (float)$produk['price']) * 100)
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($produk['name']) ?> | Sembako-Kita</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <span class="logo-icon">S</span>
        Sembako<span>-Kita</span>
    </a>

    <form action="produk.php" method="GET" class="search-form">
        <input type="search" name="cari" placeholder="Cari produk...">
        <button type="submit">Cari</button>
    </form>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="login.php">Masuk</a>
    </nav>
</header>

<main class="detail-page">

    <p class="breadcrumb">
        <a href="index.php">Beranda</a> /
        <a href="produk.php">Produk</a> /
        <?= e($produk['name']) ?>
    </p>

    <section class="detail-card">

        <div class="detail-image">
            <?php if ($foto !== ''): ?>
                <img
                    src="<?= e($foto) ?>"
                    alt="<?= e($produk['name']) ?>"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >
            <?php endif; ?>

            <span
                class="detail-fallback"
                <?= $foto !== '' ? 'style="display:none"' : '' ?>
            >🛍️</span>

            <?php if ($adaDiskon): ?>
                <span class="discount-badge">
                    Hemat <?= $persenDiskon ?>%
                </span>
            <?php endif; ?>
        </div>

        <div class="detail-info">

            <p class="product-category">
                <?= e($produk['nama_kategori'] ?? 'Produk') ?>
            </p>

            <h1><?= e($produk['name']) ?></h1>

            <?php if (!empty($produk['sku'])): ?>
                <p class="detail-sku">
                    Kode produk: <?= e($produk['sku']) ?>
                </p>
            <?php endif; ?>

            <div class="detail-price">
                <strong><?= rupiah($harga) ?></strong>

                <?php if ($adaDiskon): ?>
                    <del><?= rupiah($produk['price']) ?></del>
                <?php endif; ?>
            </div>

            <p class="detail-stock">
                <?php if ((int)$produk['stock'] > 0): ?>
                    <span class="stock-dot"></span>
                    Tersedia — stok <?= (int)$produk['stock'] ?>
                <?php else: ?>
                    Stok sedang habis
                <?php endif; ?>
            </p>

            <hr>

            <h3>Deskripsi Produk</h3>

            <p class="detail-description">
                <?= nl2br(e($produk['description'] ?: 'Belum ada deskripsi produk.')) ?>
            </p>

            <div class="detail-extra">
                <h3>Informasi Pengiriman</h3>
                <p>
                    <?= nl2br(e($produk['shipping_info'] ?: 'Informasi pengiriman belum tersedia.')) ?>
                </p>
            </div>

            <div class="detail-extra">
                <h3>Kebijakan Pengembalian</h3>
                <p>
                    <?= nl2br(e($produk['return_policy'] ?: 'Informasi pengembalian belum tersedia.')) ?>
                </p>
            </div>

            <form action="keranjang.php" method="POST" class="add-cart-form">
                <input type="hidden" name="product_id" value="<?= (int)$produk['id'] ?>">

                <label for="jumlah">Jumlah pembelian</label>

                <div class="quantity-row">
                    <input
                        type="number"
                        id="jumlah"
                        name="quantity"
                        value="1"
                        min="1"
                        max="<?= (int)$produk['stock'] ?>"
                        required
                        <?= (int)$produk['stock'] < 1 ? 'disabled' : '' ?>
                    >

                    <button
                        type="submit"
                        class="btn-primary"
                        <?= (int)$produk['stock'] < 1 ? 'disabled' : '' ?>
                    >
                        Tambah ke Keranjang
                    </button>
                </div>

                <?php if ((int)$produk['stock'] < 1): ?>
                    <p class="stock-warning">Produk ini belum bisa dibeli.</p>
                <?php endif; ?>
            </form>

        </div>
    </section>

    <a href="produk.php" class="back-link">← Kembali ke Katalog</a>

</main>

<footer class="footer">
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Sembako-Kita. Semua hak dilindungi.
    </div>
</footer>

</body>
</html>