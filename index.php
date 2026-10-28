<?php
session_start();
require_once "koneksi.php";

// Mengambil kategori yang aktif
$sqlKategori = "SELECT id, name, slug, image
                FROM categories
                WHERE status = 1
                ORDER BY name ASC";

$resultKategori = mysqli_query($conn, $sqlKategori);

// Mengambil produk aktif dan foto utamanya
$sqlProduk = "SELECT
                p.id,
                p.name,
                p.slug,
                p.price,
                p.discount_price,
                p.stock,
                p.description,
                c.name AS nama_kategori,
                pi.image AS foto
              FROM products p
              LEFT JOIN categories c
                ON p.category_id = c.id
              LEFT JOIN product_images pi
                ON p.id = pi.product_id
                AND pi.is_primary = 1
              WHERE p.status = 'active'
              ORDER BY p.created_at DESC
              LIMIT 8";

$resultProduk = mysqli_query($conn, $sqlProduk);

// Format harga ke Rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

// Menghindari HTML yang tidak aman
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SembakoKita | Belanja Kebutuhan Harian</title>

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
        <span class="login-link">
            Halo, <?= e($_SESSION['user_name'] ?? 'Pelanggan') ?>
        </span>
        <a href="logout.php" class="login-link">Keluar</a>
    <?php else: ?>
        <a href="login.php" class="login-link">Masuk</a>
    <?php endif; ?>
</nav>

</header>

<main>

    <!-- BANNER UTAMA -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-label">BELANJA LEBIH MUDAH</span>

            <h1>Kebutuhan Harianmu,<br>
                Ada di <span>Sembako-Kita!</span>
            </h1>

            <p>
                Belanja sembako dan kebutuhan rumah tangga
                dengan mudah, praktis, dan nyaman.
            </p>

            <a href="produk.php" class="btn-primary">
                Belanja Sekarang →
            </a>
        </div>

        <div class="hero-decoration">
            <div class="hero-circle">🛒</div>
            <span class="floating-item item-one">🌾</span>
            <span class="floating-item item-two">🥬</span>
            <span class="floating-item item-three">🥛</span>
        </div>
    </section>

    <!-- KATEGORI -->
    <section class="section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">PILIH SESUAI KEBUTUHAN</p>
                <h2>Kategori Produk</h2>
            </div>

            <a href="produk.php" class="see-all">Lihat semua →</a>
        </div>

        <div class="category-grid">
            <?php if ($resultKategori && mysqli_num_rows($resultKategori) > 0): ?>

                <?php while ($kategori = mysqli_fetch_assoc($resultKategori)): ?>
                    <a
                        href="kategori.php?slug=<?= urlencode($kategori['slug']) ?>"
                        class="category-card"
                    >
                        <div class="category-image">
                            <?php if (!empty($kategori['image'])): ?>
                                <img
                                    src="<?= e($kategori['image']) ?>"
                                    alt="<?= e($kategori['name']) ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                            <?php endif; ?>

                            <span
                                class="category-fallback"
                                <?= !empty($kategori['image']) ? 'style="display:none"' : '' ?>
                            >🛍️</span>
                        </div>

                        <h3><?= e($kategori['name']) ?></h3>
                    </a>
                <?php endwhile; ?>

            <?php else: ?>
                <p>Kategori belum tersedia.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- PRODUK TERBARU -->
    <section class="section products-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">PILIHAN UNTUKMU</p>
                <h2>Produk Terbaru</h2>
                <p class="section-description">
                    Temukan kebutuhan favoritmu di sini.
                </p>
            </div>

            <a href="produk.php" class="see-all">Lihat semua →</a>
        </div>

        <div class="product-grid">
            <?php if ($resultProduk && mysqli_num_rows($resultProduk) > 0): ?>

                <?php while ($produk = mysqli_fetch_assoc($resultProduk)): ?>
                    <?php
                    $punyaDiskon = $produk['discount_price'] !== null
                        && (float)$produk['discount_price'] > 0
                        && (float)$produk['discount_price'] < (float)$produk['price'];

                    $hargaTampil = $punyaDiskon
                        ? $produk['discount_price']
                        : $produk['price'];

                    $persenDiskon = $punyaDiskon
                        ? round(
                            (1 - ((float)$produk['discount_price'] /
                            (float)$produk['price'])) * 100
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
                                <?= e($produk['nama_kategori'] ?? 'Produk') ?>
                            </p>

                            <h3>
                                <a href="detail-produk.php?id=<?= (int)$produk['id'] ?>">
                                    <?= e($produk['name']) ?>
                                </a>
                            </h3>

                            <div class="product-prices">
                                <strong><?= rupiah($hargaTampil) ?></strong>

                                <?php if ($punyaDiskon): ?>
                                    <del><?= rupiah($produk['price']) ?></del>
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
                <p>Belum ada produk aktif yang dapat ditampilkan.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- INFORMASI KEUNGGULAN -->
    <section class="benefits">
        <div class="benefit-item">
            <span>🛒</span>
            <div>
                <h3>Belanja Praktis</h3>
                <p>Pilih kebutuhan dari rumah.</p>
            </div>
        </div>

        <div class="benefit-item">
            <span>📦</span>
            <div>
                <h3>Informasi Jelas</h3>
                <p>Lihat harga dan stok produk.</p>
            </div>
        </div>

        <div class="benefit-item">
            <span>🏠</span>
            <div>
                <h3>Kebutuhan Harian</h3>
                <p>Berbagai produk dalam satu tempat.</p>
            </div>
        </div>
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