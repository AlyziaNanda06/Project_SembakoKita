
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
require_once __DIR__ . '/koneksi.php';

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Pastikan keranjang tersedia
if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if (!isset($_SESSION['pesan_keranjang'])) {
    $_SESSION['pesan_keranjang'] = '';
}

// Proses tambah, ubah jumlah, dan hapus produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        $_SESSION['pesan_keranjang'] = 'Produk tidak valid.';
        header('Location: keranjang.php');
        exit;
    }

    // Hapus produk
    if ($aksi === 'hapus') {
        unset($_SESSION['keranjang'][$id]);

        $_SESSION['pesan_keranjang'] =
            'Produk berhasil dihapus dari keranjang.';

        header('Location: keranjang.php');
        exit;
    }

    // Validasi jumlah untuk tambah dan ubah
    if (!in_array($aksi, ['tambah', 'ubah'], true)) {
        $_SESSION['pesan_keranjang'] = 'Aksi tidak valid.';
        header('Location: keranjang.php');
        exit;
    }

    if ($jumlah === false || $jumlah === null || $jumlah < 1) {
        $_SESSION['pesan_keranjang'] =
            'Jumlah minimal pembelian adalah 1.';

        header('Location: keranjang.php');
        exit;
    }

    // Ambil data produk aktif dan stok terbaru
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, stock
         FROM products
         WHERE id = ? AND status = 'active'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$produk) {
        unset($_SESSION['keranjang'][$id]);

        $_SESSION['pesan_keranjang'] =
            'Produk tidak ditemukan atau sudah tidak tersedia.';

        header('Location: keranjang.php');
        exit;
    }

    $stok = (int)$produk['stock'];

    if ($aksi === 'tambah') {
        $jumlahLama = (int)(
            $_SESSION['keranjang'][$id]['jumlah'] ?? 0
        );

        $jumlahBaru = $jumlahLama + $jumlah;
    } else {
        $jumlahBaru = $jumlah;
    }

    if ($stok < 1 || $jumlahBaru > $stok) {
        $_SESSION['pesan_keranjang'] =
            'Jumlah melebihi stok yang tersedia: ' . $stok . '.';

        header('Location: keranjang.php');
        exit;
    }

    // Simpan jumlah produk ke session
    $_SESSION['keranjang'][$id] = [
        'id' => $id,
        'jumlah' => $jumlahBaru
    ];

    if ($aksi === 'tambah') {
        $_SESSION['pesan_keranjang'] =
            $produk['name'] . ' berhasil ditambahkan ke keranjang.';
    } else {
        $_SESSION['pesan_keranjang'] =
            'Jumlah produk berhasil diperbarui.';
    }

    header('Location: keranjang.php');
    exit;
}

// Ambil pesan notifikasi
$pesan = $_SESSION['pesan_keranjang'];
unset($_SESSION['pesan_keranjang']);

// Ambil seluruh produk di keranjang
$produkKeranjang = [];
$total = 0;

foreach ($_SESSION['keranjang'] as $key => $item) {
    $id = is_array($item)
        ? (int)($item['id'] ?? $key)
        : (int)$key;

    $jumlah = is_array($item)
        ? (int)($item['jumlah'] ?? $item['qty'] ?? 1)
        : (int)$item;

    if ($id < 1 || $jumlah < 1) {
        unset($_SESSION['keranjang'][$key]);
        continue;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, stock
         FROM products
         WHERE id = ? AND status = 'active'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $dataProduk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$dataProduk) {
        unset($_SESSION['keranjang'][$key]);
        continue;
    }

    // Sesuaikan jumlah jika stok berubah
    $stok = (int)$dataProduk['stock'];

    if ($stok < 1) {
        unset($_SESSION['keranjang'][$key]);
        continue;
    }

    if ($jumlah > $stok) {
        $jumlah = $stok;

        $_SESSION['keranjang'][$id] = [
            'id' => $id,
            'jumlah' => $jumlah
        ];
    }

    $dataProduk['jumlah'] = $jumlah;
    $dataProduk['subtotal'] =
        (float)$dataProduk['price'] * $jumlah;

    $produkKeranjang[] = $dataProduk;
    $total += $dataProduk['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Keranjang - Sembako-Kita</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .cart-container {
            max-width: 1000px;
            margin: 35px auto;
            padding: 20px;
        }

        .cart-table-wrapper {
            overflow-x: auto;
        }

        .cart-table-wrapper table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .cart-table-wrapper th,
        .cart-table-wrapper td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .cart-quantity-input {
            width: 75px;
            min-width: 65px;
            box-sizing: border-box;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            color: #222;
            font-size: 16px;
        }

        .cart-action-form {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 3px;
        }

        .cart-button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .cart-update {
            background: #359FA0;
            color: white;
        }

        .cart-delete {
            background: #ff8c52;
            color: white;
        }

        .cart-message {
            padding: 12px;
            margin: 15px 0;
            background: #fff0c5;
            border-radius: 8px;
        }

        .cart-total {
            margin-top: 20px;
        }

        .cart-link {
            display: inline-block;
            margin-top: 12px;
            margin-right: 10px;
        }

        .cart-empty {
            margin: 25px 0;
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
            <span class="login-link">
                Halo, <?= e($_SESSION['user_name'] ?? 'Pelanggan') ?>
            </span>
            <a href="logout.php" class="login-link">Keluar</a>
        <?php else: ?>
            <a href="login.php" class="login-link">Masuk</a>
        <?php endif; ?>
    </nav>
</header>

<main class="cart-container">

    <h1>Keranjang Belanja</h1>

    <?php if ($pesan !== ''): ?>
        <p class="cart-message"><?= e($pesan) ?></p>
    <?php endif; ?>

    <?php if (empty($produkKeranjang)): ?>

        <div class="cart-empty">
            <p>Keranjang kamu masih kosong.</p>
            <a href="produk.php" class="cart-link">Mulai Belanja</a>
        </div>

    <?php else: ?>

        <div class="cart-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($produkKeranjang as $produk): ?>
                        <tr>
                            <td>
                                <?= e($produk['name']) ?>
                            </td>

                            <td>
                                <?= rupiah($produk['price']) ?>
                            </td>

                            <td>
                                <form
                                    action="keranjang.php"
                                    method="POST"
                                    class="cart-action-form"
                                >
                                    <input
                                        type="hidden"
                                        name="aksi"
                                        value="ubah"
                                    >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)$produk['id'] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="jumlah"
                                        class="cart-quantity-input"
                                        value="<?= (int)$produk['jumlah'] ?>"
                                        min="1"
                                        max="<?= (int)$produk['stock'] ?>"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="cart-button cart-update"
                                    >
                                        Ubah
                                    </button>
                                </form>
                            </td>

                            <td>
                                <?= rupiah($produk['subtotal']) ?>
                            </td>

                            <td>
                                <form
                                    action="keranjang.php"
                                    method="POST"
                                    class="cart-action-form"
                                    onsubmit="return confirm('Hapus produk ini dari keranjang?')"
                                >
                                    <input
                                        type="hidden"
                                        name="aksi"
                                        value="hapus"
                                    >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)$produk['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="cart-button cart-delete"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 class="cart-total">
            Total Belanja: <?= rupiah($total) ?>
        </h2>

        <a href="produk.php" class="cart-link">
            Lanjut Belanja
        </a>

    <?php endif; ?>

</main>

<footer class="footer">
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Sembako-Kita. Semua hak dilindungi.
    </div>
</footer>

</body>
</html>
