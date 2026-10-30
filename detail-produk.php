
<?php
session_start();
require_once __DIR__ . '/koneksi.php';

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Ambil ID produk dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$produk = null;

// Ambil data produk
if ($id && $id > 0) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            p.id,
            p.name,
            p.sku,
            p.price,
            p.discount_price,
            p.stock,
            p.description,
            c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ? AND p.status = 'active'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);
}

// Hitung harga
$harga = 0;
$hargaNormal = 0;
$adaDiskon = false;

if ($produk) {
    $hargaNormal = (float)$produk['price'];
    $hargaDiskon = (float)($produk['discount_price'] ?? 0);

    $adaDiskon = (
        $hargaDiskon > 0 &&
        $hargaDiskon < $hargaNormal
    );

    $harga = $adaDiskon ? $hargaDiskon : $hargaNormal;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Produk - Sembako-Kita</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .detail-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .detail-card {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            padding: 25px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .detail-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            background: #FFF0C5;
            border-radius: 12px;
            font-size: 100px;
        }

        .detail-info h1 {
            margin-top: 0;
        }

        .detail-price {
            margin: 18px 0;
            color: #359FA0;
            font-size: 26px;
            font-weight: bold;
        }

        .detail-price del {
            display: block;
            margin-bottom: 5px;
            color: #888;
            font-size: 15px;
            font-weight: normal;
        }

        .detail-description {
            line-height: 1.7;
            white-space: pre-line;
        }

        .detail-quantity {
            display: block;
            margin: 20px 0 8px;
            font-weight: bold;
        }

        .detail-quantity-input {
            width: 100%;
            max-width: 150px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .detail-back {
            display: inline-block;
            margin-top: 20px;
        }

        .detail-empty {
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            text-align: center;
        }

        @media (max-width: 700px) {
            .detail-card {
                grid-template-columns: 1fr;
            }

            .detail-icon {
                min-height: 200px;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <span class="logo-icon">S</span>
        Sembako<span>-Kita</span>
    </a>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Produk</a>
        <a href="keranjang.php">Keranjang</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <span>
                Halo, <?= e($_SESSION['user_name'] ?? 'Pelanggan') ?>
            </span>
            <a href="logout.php">Keluar</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
        <?php endif; ?>
    </nav>
</header>

<main class="detail-container">

    <?php if (!$produk): ?>

        <div class="detail-empty">
            <h1>Produk Tidak Ditemukan</h1>
            <p>Produk tidak tersedia atau sudah tidak aktif.</p>
            <a href="produk.php" class="btn-primary">
                Kembali ke Produk
            </a>
        </div>

    <?php else: ?>

        <div class="detail-card">

            <div class="detail-icon" aria-hidden="true">
                🛒
            </div>

            <div class="detail-info">

                <p>
                    <?= e($produk['category_name'] ?? 'Produk') ?>
                </p>

                <h1><?= e($produk['name']) ?></h1>

                <?php if (!empty($produk['sku'])): ?>
                    <p>
                        Kode Produk: <?= e($produk['sku']) ?>
                    </p>
                <?php endif; ?>

                <div class="detail-price">
                    <?php if ($adaDiskon): ?>
                        <del><?= rupiah($hargaNormal) ?></del>
                    <?php endif; ?>

                    <?= rupiah($harga) ?>
                </div>

                <p>
                    Stok tersedia:
                    <strong><?= (int)$produk['stock'] ?></strong>
                </p>

                <h3>Deskripsi Produk</h3>

                <p class="detail-description"><?=
                    !empty($produk['description'])
                        ? e($produk['description'])
                        : 'Belum ada deskripsi untuk produk ini.'
                ?></p>

                <?php if ((int)$produk['stock'] > 0): ?>

                    <form
                        action="keranjang.php"
                        method="POST"
                        class="add-cart-form"
                    >
                        <input
                            type="hidden"
                            name="aksi"
                            value="tambah"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int)$produk['id'] ?>"
                        >

                        <label for="jumlah" class="detail-quantity">
                            Jumlah Pembelian
                        </label>

                        <input
                            type="number"
                            id="jumlah"
                            name="jumlah"
                            class="detail-quantity-input"
                            value="1"
                            min="1"
                            max="<?= (int)$produk['stock'] ?>"
                            required
                        >

                        <br>

                        <button type="submit" class="btn-primary">
                            Tambah ke Keranjang
                        </button>
                    </form>

                <?php else: ?>

                    <p>Maaf, produk ini sedang habis.</p>

                <?php endif; ?>

                <a href="produk.php" class="detail-back">
                    &larr; Kembali ke Daftar Produk
                </a>

            </div>
        </div>

    <?php endif; ?>

</main>

<footer class="footer">
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Sembako-Kita. Semua hak dilindungi.
    </div>
</footer>

</body>
</html>
