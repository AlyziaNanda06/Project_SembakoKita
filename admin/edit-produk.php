
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Token keamanan form
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Helper keamanan
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Ambil ID produk
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['pesan_error'] = "ID produk tidak valid.";
    header("Location: produk.php");
    exit;
}

// Ambil data produk
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, category_id, brand_id, name, sku, price,
            discount_price, weight, stock, description,
            shipping_info, return_policy, status
     FROM products
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$produk) {
    $_SESSION['pesan_error'] = "Produk tidak ditemukan.";
    header("Location: produk.php");
    exit;
}

// Ambil daftar kategori
$kategoriResult = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (!$kategoriResult) {
    die("Gagal mengambil data kategori.");
}

// Ambil daftar merek
$merekResult = mysqli_query(
    $conn,
    "SELECT id, name FROM brands ORDER BY name ASC"
);

if (!$merekResult) {
    die("Gagal mengambil data merek.");
}

// Pesan error
$pesanError = $_SESSION['pesan_error'] ?? '';
unset($_SESSION['pesan_error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Produk - SembakoKita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Nunito Sans', sans-serif;
            background: #F1F8F6;
            color: #263B3B;
        }

        /* SIDEBAR */

        .sidebar {
            width: 260px;
            height: 100vh;
            background: #187D79;
            position: fixed;
            left: 0;
            top: 0;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 38px;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: white;
            color: #187D79;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .brand h2 {
            font-size: 19px;
            margin: 0;
            font-weight: 900;
        }

        .brand span {
            font-size: 10px;
            letter-spacing: 2px;
            opacity: .8;
        }

        .menu-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            opacity: .7;
            margin-bottom: 13px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,.13);
        }

        .menu a.active {
            background: white;
            color: #187D79;
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px;
            background: rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .sidebar-bottom strong {
            font-size: 13px;
        }

        .sidebar-bottom p {
            font-size: 11px;
            line-height: 1.6;
            margin-bottom: 0;
            opacity: .8;
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 260px;
            padding: 35px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 900;
            color: #263B3B;
        }

        .page-header p {
            margin: 5px 0 0;
            color: #81918F;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 17px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            transition: .2s;
        }

        .btn-back {
            background: #E5EFEC;
            color: #45615C;
        }

        .btn-back:hover {
            background: #D6E7E2;
        }

        .btn-save {
            background: #187D79;
            color: white;
        }

        .btn-save:hover {
            background: #126762;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 4px 18px rgba(24, 75, 70, .05);
            border: 1px solid #EAF1EF;
            max-width: 1000px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 1px solid #EDF2F0;
        }

        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #E7F5F2;
            color: #187D79;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
        }

        .card-header p {
            margin: 3px 0 0;
            color: #81918F;
            font-size: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 20px;
        }

        .field {
            margin-bottom: 20px;
            min-width: 0;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 800;
            color: #405653;
        }

        .required {
            color: #F07858;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #DCE7E3;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            color: #263B3B;
            background: #FCFEFD;
            outline: none;
            transition: .2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #25AFA5;
            box-shadow: 0 0 0 3px rgba(37,175,165,.10);
            background: white;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .error {
            background: #FFF0EE;
            border: 1px solid #FFD4CD;
            color: #B83D30;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 13px;
            font-weight: 700;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #EDF2F0;
            padding-top: 22px;
            margin-top: 5px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                padding: 25px;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 18px;
            }

            .brand {
                margin-bottom: 20px;
            }

            .menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .menu a {
                font-size: 12px;
                padding: 11px 9px;
            }

            .sidebar-bottom {
                display: none;
            }

            .main {
                margin-left: 0;
                padding: 20px 14px;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .card {
                padding: 19px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">
        <div class="brand-icon">🛒</div>
        <div>
            <h2>SembakoKita</h2>
            <span>ADMIN PANEL</span>
        </div>
    </div>

    <div class="menu-label">MENU UTAMA</div>

    <nav class="menu">
        <a href="dashboard.php">
            <span class="menu-icon">▦</span>
            Dashboard
        </a>

        <a href="produk.php" class="active">
            <span class="menu-icon">📦</span>
            Kelola Produk
        </a>

        <a href="kategori.php">
            <span class="menu-icon">🏷️</span>
            Kelola Kategori
        </a>

        <a href="pesanan.php">
            <span class="menu-icon">🛍️</span>
            Kelola Pesanan
        </a>

        <a href="../index.php">
            <span class="menu-icon">🌐</span>
            Lihat Website
        </a>
    </nav>

    <div class="sidebar-bottom">
        <strong>🌱 SembakoKita</strong>
        <p>Belanja kebutuhan sehari-hari jadi lebih mudah dan hemat.</p>
    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <div class="page-header">
        <div>
            <h1>Edit Produk</h1>
            <p>Perbarui informasi produk SembakoKita.</p>
        </div>

        <a href="produk.php" class="btn btn-back">
            ← Kembali ke Produk
        </a>
    </div>

    <section class="card">

        <div class="card-header">
            <div class="section-icon">✏️</div>
            <div>
                <h2>Formulir Edit Produk</h2>
                <p>Ubah informasi produk, lalu simpan perubahan.</p>
            </div>
        </div>

        <?php if ($pesanError !== ''): ?>
            <div class="error"><?= e($pesanError) ?></div>
        <?php endif; ?>

        <form action="../proses/edit-produk.php" method="POST">

            <input type="hidden" name="id" value="<?= (int)$produk['id'] ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['admin_csrf_token']) ?>"
            >

            <div class="grid">

                <div class="field full">
                    <label for="name">Nama Produk <span class="required">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="150"
                        required
                        value="<?= e($produk['name']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="sku">SKU / Kode Produk</label>
                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        maxlength="50"
                        value="<?= e($produk['sku']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id">
                        <option value="">Tanpa kategori</option>

                        <?php while ($kategori = mysqli_fetch_assoc($kategoriResult)): ?>
                            <option
                                value="<?= (int)$kategori['id'] ?>"
                                <?= (string)$produk['category_id'] === (string)$kategori['id'] ? 'selected' : '' ?>
                            >
                                <?= e($kategori['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="brand_id">Merek</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">Tanpa merek</option>

                        <?php while ($merek = mysqli_fetch_assoc($merekResult)): ?>
                            <option
                                value="<?= (int)$merek['id'] ?>"
                                <?= (string)$produk['brand_id'] === (string)$merek['id'] ? 'selected' : '' ?>
                            >
                                <?= e($merek['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="price">Harga Normal (Rp) <span class="required">*</span></label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        max="9999999999.99"
                        step="0.01"
                        required
                        value="<?= e($produk['price']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="discount_price">Harga Diskon (Rp)</label>
                    <input
                        type="number"
                        id="discount_price"
                        name="discount_price"
                        min="0"
                        max="9999999999.99"
                        step="0.01"
                        value="<?= e($produk['discount_price']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="weight">Berat (gram)</label>
                    <input
                        type="number"
                        id="weight"
                        name="weight"
                        min="0"
                        step="1"
                        value="<?= e($produk['weight']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="stock">Stok <span class="required">*</span></label>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        step="1"
                        required
                        value="<?= e($produk['stock']) ?>"
                    >
                </div>

                <div class="field full">
                    <label for="description">Deskripsi Produk</label>
                    <textarea
                        id="description"
                        name="description"
                    ><?= e($produk['description']) ?></textarea>
                </div>

                <div class="field full">
                    <label for="shipping_info">Informasi Pengiriman</label>
                    <textarea
                        id="shipping_info"
                        name="shipping_info"
                    ><?= e($produk['shipping_info']) ?></textarea>
                </div>

                <div class="field full">
                    <label for="return_policy">Kebijakan Pengembalian</label>
                    <textarea
                        id="return_policy"
                        name="return_policy"
                    ><?= e($produk['return_policy']) ?></textarea>
                </div>

                <div class="field full">
                    <label for="status">Status Produk <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option
                            value="active"
                            <?= $produk['status'] === 'active' ? 'selected' : '' ?>
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            <?= $produk['status'] === 'inactive' ? 'selected' : '' ?>
                        >
                            Nonaktif
                        </option>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <a href="produk.php" class="btn btn-back">
                    Batal
                </a>

                <button type="submit" class="btn btn-save">
                    ✓ Simpan Perubahan
                </button>
            </div>

        </form>
    </section>

</main>

</body>
</html>