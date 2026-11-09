
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

// Mengambil slug kategori
$slug = trim($_GET['slug'] ?? '');

// Mengambil data kategori
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, slug
     FROM categories
     WHERE slug = ? AND status = 1
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);

$hasilKategori = mysqli_stmt_get_result($stmt);
$kategori = mysqli_fetch_assoc($hasilKategori);

$hasilProduk = false;

if ($kategori) {
    // Mengambil produk aktif beserta foto utama
    $stmtProduk = mysqli_prepare(
        $conn,
        "SELECT
            p.id,
            p.name,
            p.price,
            p.discount_price,
            p.stock,
            c.name AS nama_kategori,
            pi.image AS foto
         FROM products p
         LEFT JOIN categories c
            ON p.category_id = c.id
         LEFT JOIN product_images pi
            ON p.id = pi.product_id
            AND pi.is_primary = 1
         WHERE p.category_id = ?
            AND p.status = 'active'
         ORDER BY p.id DESC"
    );

    mysqli_stmt_bind_param($stmtProduk, "i", $kategori['id']);
    mysqli_stmt_execute($stmtProduk);

    $hasilProduk = mysqli_stmt_get_result($stmtProduk);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $kategori ? e($kategori['name']) : 'Kategori Tidak Ditemukan' ?>
        - Sembako-Kita
    </title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">

    <a href="index.php" class="logo">
        <span class="logo-icon">S</span>
        Sembako<span>-Kita</span>
    </a>

    <form action="produk.php" method="GET" class="search-form">
        <input
            type="search"
            name="cari"
            placeholder="Cari beras, minyak, gula..."
            aria-label="Cari produk"
        >
        <button type="submit">Cari</button>
    </form>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <span class="user-greeting">
                Halo, <?= e($_SESSION['user_name'] ?? 'Pengguna') ?>
            </span>

            <form action="logout.php" method="POST" class="logout-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token'] ?? '') ?>"
                >
                <button type="submit" class="logout-link">
                    Logout
                </button>
            </form>

        <?php else: ?>

            <a href="login.php" class="login-link">Masuk</a>
            <a href="register.php" class="register-link">Daftar</a>

        <?php endif; ?>
    </nav>

</header>

<main>

    <!-- DAFTAR PRODUK KATEGORI -->
    <section class="section products-section">

        <?php if (!$kategori): ?>

            <div class="section-heading">
                <div>
                    <p class="eyebrow">SEMBako-KITA</p>
                    <h2>Kategori tidak ditemukan</h2>
                    <p class="section-description">
                        Kategori yang kamu cari tidak tersedia.
                    </p>
                </div>

                <a href="produk.php" class="see-all">
                    Lihat semua produk →
                </a>
            </div>

        <?php else: ?>

            <div class="section-heading">
                <div>
                    <p class="eyebrow">PILIH SESUAI KEBUTUHAN</p>
                    <h2><?= e($kategori['name']) ?></h2>
                    <p class="section-description">
                        Temukan kebutuhan harianmu di kategori ini.
                    </p>
                </div>

                <a href="produk.php" class="see-all">
                    Semua Produk →
                </a>
            </div>

            <div class="product-grid">

                <?php if ($hasilProduk && mysqli_num_rows($hasilProduk) > 0): ?>

                    <?php while ($produk = mysqli_fetch_assoc($hasilProduk)): ?>

                        <?php
                        $punyaDiskon =
                            $produk['discount_price'] !== null &&
                            (float)$produk['discount_price'] > 0 &&
                            (float)$produk['discount_price'] < (float)$produk['price'];

                        $hargaTampil = $punyaDiskon
                            ? $produk['discount_price']
                            : $produk['price'];

                        $persenDiskon = $punyaDiskon
                            ? round(
                                (1 - (
                                    (float)$produk['discount_price'] /
                                    (float)$produk['price']
                                )) * 100
                            )
                            : 0;
                        ?>

                        <article class="product-card">

                            <a
                                href="detail-produk.php?id=<?= (int)$produk['id'] ?>"
                                class="product-image"
                            >

                                <?php if (!empty($produk['foto'])): ?>

                                    <img
                                        src="<?= e($produk['foto']) ?>"
                                        alt="<?= e($produk['name']) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                <?php endif; ?>

                                <span
                                    class="product-fallback"
                                    <?= !empty($produk['foto']) ? 'style="display:none"' : '' ?>
                                >🛍️</span>

                                <?php if ($punyaDiskon): ?>
                                    <span class="discount-badge">
                                        -<?= $persenDiskon ?>%
                                    </span>
                                <?php endif; ?>

                            </a>

                            <div class="product-info">

                                <p class="product-category">
                                    <?= e($produk['nama_kategori']) ?>
                                </p>

                                <h3>
                                    <a href="detail-produk.php?id=<?= (int)$produk['id'] ?>">
                                        <?= e($produk['name']) ?>
                                    </a>
                                </h3>

                                <div class="product-prices">
                                    <strong>
                                        <?= rupiah($hargaTampil) ?>
                                    </strong>

                                    <?php if ($punyaDiskon): ?>
                                        <del>
                                            <?= rupiah($produk['price']) ?>
                                        </del>
                                    <?php endif; ?>
                                </div>

                                <p class="stock-info">
                                    <?php if ((int)$produk['stock'] > 0): ?>
                                        Stok: <?= (int)$produk['stock'] ?>
                                    <?php else: ?>
                                        Stok habis
                                    <?php endif; ?>
                                </p>

                                <a
                                    href="detail-produk.php?id=<?= (int)$produk['id'] ?>"
                                    class="btn-product"
                                >
                                    Lihat Produk
                                </a>

                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p>Belum ada produk aktif dalam kategori ini.</p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<!-- FOOTER -->
<footer class="footer">

    <div class="footer-content">

        <div>
            <a href="index.php" class="logo footer-logo">
                Sembako<span>-Kita</span>
            </a>

            <p>
                Teman belanja kebutuhan harian
                untuk rumah tangga Indonesia.
            </p>
        </div>

        <div>
            <h3>Navigasi</h3>
            <a href="index.php">Beranda</a>
            <a href="produk.php">Produk</a>
            <a href="login.php">Masuk</a>
        </div>

        <div>
            <h3>Bantuan</h3>
            <p>Informasi pengiriman</p>
            <p>Kebijakan pengembalian</p>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Sembako-Kita. Semua hak dilindungi.
    </div>

</footer>

<script src="js/script.js"></script>

</body>
</html>