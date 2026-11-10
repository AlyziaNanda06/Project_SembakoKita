
<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Ambil data kategori
$queryKategori = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

// Ambil data merek
$queryMerek = mysqli_query(
    $conn,
    "SELECT id, name FROM brands ORDER BY name ASC"
);

// Pesan error dari proses tambah produk
$pesanError = $_SESSION["pesan_error"] ?? "";
unset($_SESSION["pesan_error"]);

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Produk - SembakoKita</title>

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

        .form-group {
            margin-bottom: 20px;
            min-width: 0;
        }

        .form-group label {
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

        input::placeholder,
        textarea::placeholder {
            color: #A5B2AF;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
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

        .form-note {
            color: #81918F;
            font-size: 11px;
            margin-top: 6px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #EDF2F0;
            padding-top: 22px;
            margin-top: 10px;
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

            .row {
                grid-template-columns: 1fr;
                gap: 0;
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
            <h1>Tambah Produk</h1>
            <p>Tambahkan produk baru ke katalog SembakoKita.</p>
        </div>

        <a href="produk.php" class="btn btn-back">
            ← Kembali ke Produk
        </a>
    </div>

    <section class="card">

        <div class="card-header">
            <div class="section-icon">📦</div>
            <div>
                <h2>Formulir Produk</h2>
                <p>Lengkapi informasi produk yang akan ditambahkan.</p>
            </div>
        </div>

        <?php if ($pesanError !== ''): ?>
            <div class="error">
                <?= e($pesanError) ?>
            </div>
        <?php endif; ?>

        <?php if (!$queryKategori || !$queryMerek): ?>
            <div class="error">
                Data kategori atau merek gagal dimuat.
                Periksa tabel categories dan brands di database.
            </div>
        <?php endif; ?>

        <form action="../proses/tambah-produk.php" method="POST">

            <div class="form-group">
                <label for="name">Nama Produk <span class="required">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Contoh: Beras Premium 5 Kg"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="sku">SKU / Kode Produk <span class="required">*</span></label>
                <input
                    type="text"
                    id="sku"
                    name="sku"
                    placeholder="Contoh: BR001"
                    maxlength="50"
                    required
                >
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="category_id">Kategori <span class="required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>

                        <?php if ($queryKategori): ?>
                            <?php while ($kategori = mysqli_fetch_assoc($queryKategori)): ?>
                                <option value="<?= (int)$kategori["id"] ?>">
                                    <?= e($kategori["name"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="brand_id">Merek</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">-- Tanpa Merek --</option>

                        <?php if ($queryMerek): ?>
                            <?php while ($merek = mysqli_fetch_assoc($queryMerek)): ?>
                                <option value="<?= (int)$merek["id"] ?>">
                                    <?= e($merek["name"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="price">Harga (Rp) <span class="required">*</span></label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="75000"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="discount_price">Harga Diskon (Rp)</label>
                    <input
                        type="number"
                        id="discount_price"
                        name="discount_price"
                        min="0"
                        step="0.01"
                        placeholder="Kosongkan jika tidak ada"
                    >
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="weight">Berat (gram)</label>
                    <input
                        type="number"
                        id="weight"
                        name="weight"
                        min="0"
                        placeholder="5000"
                    >
                </div>

                <div class="form-group">
                    <label for="stock">Stok <span class="required">*</span></label>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        value="0"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi Produk</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Tuliskan deskripsi produk..."
                ></textarea>
            </div>

            <div class="form-group">
                <label for="shipping_info">Informasi Pengiriman</label>
                <textarea
                    id="shipping_info"
                    name="shipping_info"
                    placeholder="Informasi pengemasan dan pengiriman..."
                ></textarea>
            </div>

            <div class="form-group">
                <label for="return_policy">Kebijakan Pengembalian</label>
                <textarea
                    id="return_policy"
                    name="return_policy"
                    placeholder="Ketentuan pengembalian produk..."
                ></textarea>
            </div>

            <div class="form-group">
                <label for="status">Status Produk <span class="required">*</span></label>
                <select id="status" name="status" required>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="produk.php" class="btn btn-back">
                    Batal
                </a>

                <button type="submit" class="btn btn-save">
                    ✓ Simpan Produk
                </button>
            </div>

        </form>
    </section>

</main>

</body>
</html>