
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Keamanan login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Token CSRF
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Mengambil data produk
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

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$totalProduk = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - SembakoKita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --teal: #25AFA5;
            --teal-dark: #187D79;
            --orange: #FF875C;
            --yellow: #FFD166;
            --purple: #8B83E8;
            --background: #F1F8F6;
            --dark: #263D3A;
            --muted: #829490;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Nunito Sans', sans-serif;
            background: var(--background);
            color: var(--dark);
        }

        a {
            text-decoration: none;
        }

        /* LAYOUT */

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 255px;
            min-height: 100vh;
            background: linear-gradient(180deg, #219F97, #187D79);
            color: white;
            padding: 28px 18px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px;
            margin-bottom: 42px;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: white;
            color: var(--teal-dark);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .brand h2 {
            font-size: 20px;
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
            color: #C3E9E4;
            padding: 0 13px;
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
            padding: 13px 15px;
            color: #E0F5F1;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            transition: .25s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,.19);
            color: white;
            transform: translateX(3px);
        }

        .menu-icon {
            width: 25px;
            font-size: 19px;
            text-align: center;
        }

        .sidebar-bottom {
            margin-top: auto;
            background: rgba(255,255,255,.13);
            padding: 16px;
            border-radius: 15px;
        }

        .sidebar-bottom strong {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .sidebar-bottom p {
            font-size: 11px;
            color: #D6EFEB;
            line-height: 1.6;
        }

        /* CONTENT */

        .content {
            flex: 1;
            min-width: 0;
            padding: 30px 38px;
        }

        /* TOPBAR */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 25px;
            font-weight: 900;
        }

        .topbar p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 9px 15px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(36,80,70,.05);
        }

        .profile-icon {
            width: 39px;
            height: 39px;
            background: #DDF5EF;
            color: var(--teal-dark);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
        }

        .profile-info span {
            color: var(--muted);
            font-size: 11px;
        }

        /* BREADCRUMB */

        .breadcrumb {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 15px;
        }

        .breadcrumb a {
            color: var(--teal-dark);
            font-weight: 800;
        }

        /* PAGE BANNER */

        .page-banner {
            background: linear-gradient(115deg, #25AFA5, #47C2B2);
            border-radius: 20px;
            padding: 25px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            box-shadow: 0 9px 22px rgba(37,175,165,.15);
        }

        .page-banner h2 {
            font-size: 23px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .page-banner p {
            font-size: 13px;
            color: #E8FFFC;
        }

        .banner-icon {
            font-size: 65px;
            opacity: .8;
        }

        /* TOOLBAR */

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 18px;
        }

        .toolbar h3 {
            font-size: 18px;
            font-weight: 900;
        }

        .toolbar p {
            font-size: 12px;
            color: var(--muted);
            margin-top: 3px;
        }

        .btn-tambah {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--orange);
            color: white;
            padding: 12px 19px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 800;
            border: none;
            transition: .25s;
        }

        .btn-tambah:hover {
            background: #F36E43;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255,135,92,.25);
        }

        /* TABLE */

        .table-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(40,80,70,.05);
            border: 1px solid #EAF1EF;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 17px;
        }

        .table-header strong {
            font-size: 14px;
        }

        .total-badge {
            background: #DDF5EF;
            color: #168D86;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1050px;
        }

        th {
            background: #EAF8F5;
            color: #347D76;
            font-size: 12px;
            font-weight: 900;
            padding: 15px 13px;
            text-align: left;
            white-space: nowrap;
        }

        th:first-child {
            border-radius: 10px 0 0 0;
        }

        th:last-child {
            border-radius: 0 10px 0 0;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid #EDF2F0;
            font-size: 12px;
            color: #536B65;
            vertical-align: middle;
        }

        tbody tr {
            transition: .2s;
        }

        tbody tr:hover {
            background: #F8FCFB;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .nomor {
            color: #96A8A3;
            font-weight: 800;
        }

        .nama-produk {
            color: var(--dark);
            font-weight: 800;
            min-width: 140px;
        }

        .sku {
            background: #F1F5F4;
            color: #71837E;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .harga {
            color: var(--dark);
            font-weight: 800;
            white-space: nowrap;
        }

        .harga-diskon {
            color: #E87950;
            font-weight: 800;
            white-space: nowrap;
        }

        .stok {
            display: inline-block;
            padding: 5px 10px;
            background: #EAF8F5;
            color: #168D86;
            border-radius: 7px;
            font-weight: 800;
        }

        .stok.habis {
            background: #FFE7E3;
            color: #DC5A4B;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 900;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status.aktif {
            background: #DDF5E8;
            color: #248454;
        }

        .status.nonaktif {
            background: #FFE7E3;
            color: #D65A4D;
        }

        .status.lain {
            background: #FFF3D5;
            color: #A87917;
        }

        /* ACTION BUTTON */

        .aksi {
            display: flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .btn-edit,
        .btn-hapus {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
        }

        .btn-edit {
            background: #EAE8FF;
            color: #7168CF;
        }

        .btn-edit:hover {
            background: #D8D4FF;
        }

        .btn-hapus {
            background: #FFE7E3;
            color: #DC5A4B;
        }

        .btn-hapus:hover {
            background: #FFD4CE;
        }

        .kosong {
            text-align: center;
            padding: 50px 20px;
        }

        .kosong-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .kosong strong {
            display: block;
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 5px;
        }

        .kosong p {
            color: var(--muted);
            font-size: 12px;
        }

        footer {
            text-align: center;
            color: #91A49E;
            font-size: 11px;
            padding: 25px 0 5px;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .content {
                padding: 25px;
            }
        }

        @media (max-width: 700px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
                padding: 17px;
            }

            .brand {
                margin-bottom: 20px;
            }

            .menu-label,
            .sidebar-bottom {
                display: none;
            }

            .menu {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 7px;
            }

            .menu a {
                flex-direction: column;
                justify-content: center;
                text-align: center;
                gap: 5px;
                padding: 11px 4px;
                font-size: 11px;
            }

            .menu a:hover,
            .menu a.active {
                transform: none;
            }

            .content {
                padding: 20px 15px;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 21px;
            }

            .profile-info {
                display: none;
            }

            .admin-profile {
                padding: 7px;
            }

            .page-banner {
                padding: 22px 18px;
            }

            .page-banner h2 {
                font-size: 19px;
            }

            .banner-icon {
                font-size: 45px;
            }

            .table-card {
                padding: 13px;
            }

            .toolbar {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="layout">

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


    <!-- KONTEN -->
    <main class="content">

        <!-- HEADER -->
        <header class="topbar">
            <div>
                <h1>Kelola Produk</h1>
                <p>Atur dan pantau seluruh produk toko kamu.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">👤</div>
                <div class="profile-info">
                    <strong>Administrator</strong>
                    <span>Admin SembakoKita</span>
                </div>
            </div>
        </header>


        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            &nbsp; / &nbsp; Kelola Produk
        </div>


        <!-- BANNER -->
        <section class="page-banner">
            <div>
                <h2>Manajemen Produk 📦</h2>
                <p>Kelola produk, harga, stok, dan informasi barang toko kamu.</p>
            </div>

            <div class="banner-icon">🛍️</div>
        </section>


        <!-- TOOLBAR -->
        <section class="toolbar">
            <div>
                <h3>Daftar Produk</h3>
                <p>Seluruh produk yang tersimpan di database.</p>
            </div>

            <a href="tambah-produk.php" class="btn-tambah">
                <span>＋</span> Tambah Produk
            </a>
        </section>


        <!-- TABEL PRODUK -->
        <section class="table-card">

            <div class="table-header">
                <strong>📋 Data Produk SembakoKita</strong>

                <span class="total-badge">
                    <?= $totalProduk ?> Produk
                </span>
            </div>

            <div class="table-wrapper">

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

                    <?php if ($totalProduk > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($p = mysqli_fetch_assoc($result)): ?>

                            <?php
                                $statusProduk = strtolower(trim($p['status'] ?? ''));
                                $classStatus = 'lain';

                                if ($statusProduk === 'active' || $statusProduk === 'aktif') {
                                    $classStatus = 'aktif';
                                } elseif ($statusProduk === 'inactive' || $statusProduk === 'nonaktif') {
                                    $classStatus = 'nonaktif';
                                }

                                $stokHabis = (int)$p['stock'] <= 0;
                            ?>

                            <tr>
                                <td class="nomor"><?= $no++ ?></td>

                                <td class="nama-produk">
                                    <?= e($p['name']) ?>
                                </td>

                                <td>
                                    <span class="sku">
                                        <?= e($p['sku'] ?: '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($p['category_name'] ?? 'Tanpa kategori') ?>
                                </td>

                                <td class="harga">
                                    <?= rupiah($p['price']) ?>
                                </td>

                                <td class="harga-diskon">
                                    <?= $p['discount_price'] !== null
                                        ? rupiah($p['discount_price'])
                                        : '-' ?>
                                </td>

                                <td>
                                    <span class="stok <?= $stokHabis ? 'habis' : '' ?>">
                                        <?= (int)$p['stock'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status <?= $classStatus ?>">
                                        <?= e($p['status'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="aksi">

                                        <a class="btn-edit"
                                           href="edit-produk.php?id=<?= (int)$p['id'] ?>">
                                            ✏️ Edit
                                        </a>

                                        <form action="../proses/hapus-produk.php"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus produk ini?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION['admin_csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="id"
                                                   value="<?= (int)$p['id'] ?>">

                                            <button type="submit" class="btn-hapus">
                                                🗑️ Hapus
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="kosong">
                                <div class="kosong-icon">📦</div>
                                <strong>Belum Ada Produk</strong>
                                <p>Silakan tambahkan produk pertama toko kamu.</p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>


        <footer>
            © <?= date('Y') ?> SembakoKita. All Rights Reserved.
        </footer>

    </main>

</div>

</body>
</html>