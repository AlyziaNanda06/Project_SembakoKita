<?php
require_once "koneksi.php";

$cari = trim($_GET['cari'] ?? '');
$kategori_id = (int) ($_GET['kategori'] ?? 0);

$sqlKategori = "SELECT id, name
                FROM categories
                WHERE status = 1
                ORDER BY name ASC";
$resultKategori = mysqli_query($conn, $sqlKategori);

$kategoriList = [];
if ($resultKategori) {
    while ($kat = mysqli_fetch_assoc($resultKategori)) {
        $kategoriList[] = $kat;
    }
}

$where = "p.status = 'active'";

if ($kategori_id > 0) {
    $where .= " AND p.category_id = " . $kategori_id;
}

if ($cari !== '') {
    $kata = mysqli_real_escape_string($conn, $cari);
    $where .= " AND (p.name LIKE '%$kata%'
                OR p.description LIKE '%$kata%')";
}

$sqlProduk = "SELECT
                p.id, p.name, p.sku, p.price,
                p.discount_price, p.stock, p.description,
                c.name AS category_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE $where
              ORDER BY p.created_at DESC";

$resultProduk = mysqli_query($conn, $sqlProduk);

if (!$resultProduk) {
    die("Gagal mengambil produk: " . mysqli_error($conn));
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function ikonKategori($nama) {
    $nama = strtolower($nama);

    if (strpos($nama, 'pokok') !== false ||
        strpos($nama, 'beras') !== false) return '🌾';

    if (strpos($nama, 'bumbu') !== false) return '🧂';
    if (strpos($nama, 'snack') !== false ||
        strpos($nama, 'jajanan') !== false) return '🍪';

    if (strpos($nama, 'instan') !== false) return '🍜';
    if (strpos($nama, 'minum') !== false) return '🥤';

    if (strpos($nama, 'bayi') !== false) return '🍼';
    if (strpos($nama, 'rumah') !== false) return '🧹';

    return '🛍️';
}

function ikonProduk($nama) {
    $nama = strtolower($nama);

    if (strpos($nama, 'beras') !== false) return '🌾';
    if (strpos($nama, 'minyak') !== false) return '🫒';
    if (strpos($nama, 'gula') !== false) return '🍚';
    if (strpos($nama, 'indomie') !== false ||
        strpos($nama, 'mi ') !== false) return '🍜';

    if (strpos($nama, 'kopi') !== false) return '☕';
    if (strpos($nama, 'teh') !== false) return '🍵';
    if (strpos($nama, 'aqua') !== false ||
        strpos($nama, 'air mineral') !== false) return '💧';

    if (strpos($nama, 'popok') !== false) return '🍼';
    if (strpos($nama, 'deterjen') !== false) return '🧺';
    if (strpos($nama, 'pasta gigi') !== false) return '🪥';
    if (strpos($nama, 'biskuit') !== false ||
        strpos($nama, 'kerupuk') !== false) return '🍪';

    if (strpos($nama, 'kecap') !== false ||
        strpos($nama, 'saus') !== false) return '🧴';

    if (strpos($nama, 'royco') !== false ||
        strpos($nama, 'blue band') !== false) return '🥣';

    return '🛒';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#359FA0">
    <title>SembakoKita</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="nav-inner container">
        <a href="index.php" class="brand-logo">
            <span class="brand-icon">🛍️</span>
            <span>Sembako<span class="logo-accent">Ku</span>
                <small>Belanja Hemat Setiap Hari</small>
            </span>
        </a>

        <nav class="main-nav">
            <a href="index.php">Beranda</a>
            <a href="produk.php" class="active">Produk</a>
            <a href="keranjang.php">Keranjang 🛒</a>
        </nav>

        <a href="keranjang.php" class="nav-cart">
            🛒 <span>Keranjang</span>
        </a>
    </div>
</header>

<main class="container page-content">

    <section class="hero-banner">
        <div class="hero-text">
            <span class="hero-badge">🌿 TEMAN BELANJA KELUARGA</span>

            <h1>Kebutuhan Harian,<br>
                <span>Lebih Mudah & Hemat!</span>
            </h1>

            <p>
                Semua kebutuhan rumah tangga dalam satu tempat.
                Praktis, mudah, dan nyaman bersama SembakoKu.
            </p>

            <a href="#katalog" class="hero-button">
                Mulai Belanja <span>→</span>
            </a>

            <div class="hero-benefits">
                <span>✓ Produk pilihan</span>
                <span>✓ Harga bersahabat</span>
            </div>
        </div>

        <div class="hero-illustration" aria-hidden="true">
            <div class="hero-circle"></div>
            <span class="hero-basket">🧺</span>
            <span class="floating-item item-one">🥬</span>
            <span class="floating-item item-two">🥛</span>
            <span class="floating-item item-three">🍎</span>
            <span class="floating-item item-four">🥖</span>
        </div>
    </section>

    <section class="category-section">
        <div class="section-title">
            <div>
                <span class="eyebrow">PILIH SESUAI KEBUTUHAN</span>
                <h2>Belanja per Kategori</h2>
            </div>
            <span class="section-note">Temukan favoritmu ✨</span>
        </div>

        <div class="category-grid">
            <a href="produk.php" class="category-card">
                <span class="category-emoji">🛍️</span>
                <span>Semua Produk</span>
                <span class="category-arrow">↗</span>
            </a>

            <?php foreach (array_slice($kategoriList, 0, 7) as $kat): ?>
                <a
                    href="produk.php?kategori=<?= (int)$kat['id'] ?>"
                    class="category-card"
                >
                    <span class="category-emoji">
                        <?= ikonKategori($kat['name']) ?>
                    </span>
                    <span><?= e($kat['name']) ?></span>
                    <span class="category-arrow">↗</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="catalog-section" id="katalog">
        <div class="section-title">
            <div>
                <span class="eyebrow">PILIHAN UNTUKMU</span>
                <h2>Jelajahi Produk</h2>
                <p class="section-subtitle">
                    Lengkapi kebutuhan harianmu tanpa ribet.
                </p>
            </div>
        </div>

        <div class="catalog-layout">

            <aside class="filter-sidebar">
                <div class="filter-heading">
                    <span>☷</span>
                    <h3>Filter Produk</h3>
                </div>

                <form action="produk.php" method="GET" class="filter-form">
                    <label for="cari">Cari produk</label>
                    <div class="search-box">
                        <span>⌕</span>
                        <input
                            id="cari"
                            type="text"
                            name="cari"
                            placeholder="Nama produk..."
                            value="<?= e($cari) ?>"
                        >
                    </div>

                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori">
                        <option value="0">Semua kategori</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option
                                value="<?= (int)$kat['id'] ?>"
                                <?= $kategori_id === (int)$kat['id']
                                    ? 'selected' : '' ?>
                            >
                                <?= e($kat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="filter-button">
                        Terapkan Filter
                    </button>

                    <a href="produk.php" class="reset-button">
                        Reset Filter
                    </a>
                </form>

                <div class="sidebar-promo">
                    <span>💚</span>
                    <h4>Belanja lebih praktis!</h4>
                    <p>Temukan kebutuhan rumahmu di SembakoKu.</p>
                </div>
            </aside>

            <div class="catalog-main">
                <div class="catalog-toolbar">
                    <div>
                        <h3>
                            <?= $cari !== '' ? 'Hasil Pencarian' : 'Semua Produk' ?>
                        </h3>
                        <p>
                            Ditemukan
                            <strong><?= mysqli_num_rows($resultProduk) ?></strong>
                            produk
                        </p>
                    </div>

                    <?php if ($cari !== '' || $kategori_id > 0): ?>
                        <a href="produk.php" class="clear-link">
                            Hapus filter ✕
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-grid">

                    <?php if (mysqli_num_rows($resultProduk) > 0): ?>

                        <?php while ($produk = mysqli_fetch_assoc($resultProduk)): ?>
                            <?php
                            $harga = (float)($produk['price'] ?? 0);
                            $diskon = (float)($produk['discount_price'] ?? 0);

                            $adaDiskon = $diskon > 0 && $diskon < $harga;
                            $hargaFinal = $adaDiskon ? $diskon : $harga;

                            $stok = (int)$produk['stock'];

                            $persenDiskon = ($adaDiskon && $harga > 0)
                                ? round((($harga - $diskon) / $harga) * 100)
                                : 0;
                            ?>

                            <article class="product-card">
                                <div class="product-visual">
                                    <?php if ($adaDiskon): ?>
                                        <span class="discount-label">
                                            -<?= $persenDiskon ?>%
                                        </span>
                                    <?php endif; ?>

                                    <span class="product-emoji">
                                        <?= ikonProduk($produk['name']) ?>
                                    </span>

                                    <span class="visual-category">
                                        <?= e($produk['category_name'] ?? 'Produk') ?>
                                    </span>
                                </div>

                                <div class="product-details">
                                    <p class="product-category">
                                        <?= e($produk['category_name'] ?? 'Tanpa kategori') ?>
                                    </p>

                                    <h3 class="product-name">
                                        <?= e($produk['name']) ?>
                                    </h3>

                                    <div class="price-area">
                                        <span class="product-price">
                                            <?= rupiah($hargaFinal) ?>
                                        </span>

                                        <?php if ($adaDiskon): ?>
                                            <span class="old-price">
                                                <?= rupiah($harga) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="stock-info <?= $stok > 0 ? 'in-stock' : 'out-stock' ?>">
                                        <span>●</span>
                                        <?= $stok > 0
                                            ? 'Stok: ' . $stok
                                            : 'Stok habis' ?>
                                    </p>

                                    <a
                                        href="detail-produk.php?id=<?= (int)$produk['id'] ?>"
                                        class="detail-button"
                                    >
                                        Lihat Detail <span>→</span>
                                    </a>
                                </div>
                            </article>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            <span>🔎</span>
                            <h3>Yah, produk belum ditemukan</h3>
                            <p>Coba kata kunci atau kategori yang berbeda.</p>
                            <a href="produk.php" class="filter-button">
                                Lihat Semua Produk
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a href="index.php" class="footer-logo">
                Sembako<span>Ku</span>
            </a>
            <p>Belanja kebutuhan harian jadi lebih mudah.</p>
        </div>

        <div class="footer-copy">
            &copy; <?= date('Y') ?> SembakoKu. Semua hak dilindungi.
        </div>
    </div>
</footer>

</body>
</html>