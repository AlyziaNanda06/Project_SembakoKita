
<?php
session_start();

require_once __DIR__ . '/../koneksi.php';

// Pastikan hanya admin yang dapat membuka dashboard
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Menghitung jumlah data
function hitungData($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);
    return (int) ($data['total'] ?? 0);
}

$totalProduk = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

$totalKategori = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM categories"
);

$totalPesanan = hitungData(
    $conn,
    "SELECT COUNT(*) AS total FROM orders"
);

$pesananPending = hitungData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'pending'"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SembakoKita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --teal: #25AFA5;
            --teal-dark: #168D86;
            --orange: #FF875C;
            --yellow: #FFD166;
            --purple: #8B83E8;
            --background: #F1F8F6;
            --dark: #263D3A;
            --muted: #829490;
            --white: #FFFFFF;
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

        /* LAYOUT UTAMA */

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
            background: #FFFFFF;
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
            letter-spacing: -0.5px;
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

        /* AREA KONTEN */

        .content {
            flex: 1;
            min-width: 0;
            padding: 30px 38px;
        }

        /* HEADER */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
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

        /* WELCOME BANNER */

        .welcome {
            background: linear-gradient(115deg, #25AFA5, #47C2B2);
            padding: 30px 32px;
            border-radius: 22px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
            margin-bottom: 32px;
            box-shadow: 0 10px 25px rgba(37,175,165,.17);
        }

        .welcome::after {
            content: "🛒";
            position: absolute;
            right: 55px;
            top: 50%;
            transform: translateY(-50%) rotate(-10deg);
            font-size: 85px;
            opacity: .25;
        }

        .welcome-text {
            position: relative;
            z-index: 1;
        }

        .welcome small {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #D9FFFA;
        }

        .welcome h2 {
            font-size: 25px;
            font-weight: 900;
            margin: 8px 0;
        }

        .welcome p {
            font-size: 13px;
            color: #E8FFFC;
        }

        /* SECTION TITLE */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .section-heading h2 {
            font-size: 19px;
            font-weight: 900;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 12px;
        }

        /* STATISTIK */

        .ringkasan {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .kartu {
            padding: 23px;
            border-radius: 19px;
            position: relative;
            overflow: hidden;
            transition: .25s;
            min-width: 0;
            box-shadow: 0 7px 20px rgba(40,80,70,.06);
        }

        .kartu:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(40,80,70,.12);
        }

        .kartu.produk {
            background: #DDF5EF;
            border-bottom: 4px solid #25AFA5;
        }

        .kartu.kategori {
            background: #FFF0DF;
            border-bottom: 4px solid #FF875C;
        }

        .kartu.pesanan {
            background: #EAE8FF;
            border-bottom: 4px solid #8B83E8;
        }

        .kartu.pending {
            background: #FFF4D5;
            border-bottom: 4px solid #F0BB42;
        }

        .kartu-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 8px;
        }

        .kartu-icon {
            width: 47px;
            height: 47px;
            background: rgba(255,255,255,.7);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .kartu-label {
            font-size: 12px;
            font-weight: 800;
            color: #647B75;
        }

        .angka {
            font-size: 34px;
            line-height: 1.2;
            font-weight: 900;
            color: var(--dark);
        }

        .kartu-desc {
            font-size: 11px;
            color: #728781;
            margin-top: 6px;
        }

        /* MENU CEPAT */

        .quick-section {
            margin-bottom: 30px;
        }

        .quick-menu {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .quick-card {
            background: white;
            padding: 22px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 18px rgba(40,80,70,.05);
            transition: .25s;
            border: 1px solid #EAF1EF;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            border-color: #B9E6DE;
            box-shadow: 0 9px 22px rgba(40,80,70,.09);
        }

        .quick-icon {
            width: 49px;
            height: 49px;
            flex-shrink: 0;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .quick-icon.teal {
            background: #DDF5EF;
        }

        .quick-icon.orange {
            background: #FFF0DF;
        }

        .quick-icon.purple {
            background: #EAE8FF;
        }

        .quick-text strong {
            display: block;
            color: var(--dark);
            font-size: 13px;
            margin-bottom: 4px;
        }

        .quick-text span {
            color: var(--muted);
            font-size: 11px;
        }

        .arrow {
            margin-left: auto;
            color: #A4B6B1;
            font-size: 20px;
        }

        /* INFORMASI */

        .info-box {
            background: white;
            border-radius: 18px;
            padding: 23px;
            border: 1px solid #EAF1EF;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .info-icon {
            width: 43px;
            height: 43px;
            background: #FFF0C5;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .info-box h3 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-box p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        /* FOOTER */

        footer {
            text-align: center;
            color: #91A49E;
            font-size: 11px;
            padding: 25px 0 5px;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .ringkasan {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .quick-menu {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
                padding: 22px 17px;
            }

            .topbar {
                align-items: flex-start;
                margin-bottom: 23px;
            }

            .topbar h1 {
                font-size: 21px;
            }

            .admin-profile {
                padding: 7px;
            }

            .profile-info {
                display: none;
            }

            .welcome {
                padding: 24px 21px;
            }

            .welcome h2 {
                font-size: 20px;
            }

            .welcome::after {
                right: 10px;
                font-size: 60px;
            }

            .ringkasan {
                gap: 12px;
            }

            .kartu {
                padding: 17px;
            }

            .angka {
                font-size: 28px;
            }

            .quick-menu {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 380px) {
            .ringkasan {
                grid-template-columns: 1fr;
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
            <a href="dashboard.php" class="active">
                <span class="menu-icon">▦</span>
                Dashboard
            </a>

            <a href="produk.php">
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


    <!-- KONTEN UTAMA -->
    <main class="content">

        <!-- HEADER -->
        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Kelola dan pantau toko SembakoKita di sini.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">👤</div>
                <div class="profile-info">
                    <strong>Administrator</strong>
                    <span>Admin SembakoKita</span>
                </div>
            </div>
        </header>


        <!-- WELCOME -->
        <section class="welcome">
            <div class="welcome-text">
                <small>WELCOME BACK, ADMIN!</small>
                <h2>Halo, Selamat Datang! 👋</h2>
                <p>Yuk, pantau aktivitas dan kelola toko SembakoKita hari ini.</p>
            </div>
        </section>


        <!-- STATISTIK -->
        <section>

            <div class="section-heading">
                <div>
                    <h2>Ringkasan Toko</h2>
                    <p>Informasi data toko secara keseluruhan</p>
                </div>
            </div>

            <div class="ringkasan">

                <div class="kartu produk">
                    <div class="kartu-top">
                        <span class="kartu-label">TOTAL PRODUK</span>
                        <div class="kartu-icon">📦</div>
                    </div>

                    <div class="angka"><?= $totalProduk ?></div>
                    <div class="kartu-desc">Produk dalam database</div>
                </div>


                <div class="kartu kategori">
                    <div class="kartu-top">
                        <span class="kartu-label">TOTAL KATEGORI</span>
                        <div class="kartu-icon">🏷️</div>
                    </div>

                    <div class="angka"><?= $totalKategori ?></div>
                    <div class="kartu-desc">Kategori produk tersedia</div>
                </div>


                <div class="kartu pesanan">
                    <div class="kartu-top">
                        <span class="kartu-label">TOTAL PESANAN</span>
                        <div class="kartu-icon">🛍️</div>
                    </div>

                    <div class="angka"><?= $totalPesanan ?></div>
                    <div class="kartu-desc">Seluruh pesanan pelanggan</div>
                </div>


                <div class="kartu pending">
                    <div class="kartu-top">
                        <span class="kartu-label">PESANAN PENDING</span>
                        <div class="kartu-icon">⏳</div>
                    </div>

                    <div class="angka"><?= $pesananPending ?></div>
                    <div class="kartu-desc">Pesanan menunggu diproses</div>
                </div>

            </div>

        </section>


        <!-- MENU CEPAT -->
        <section class="quick-section">

            <div class="section-heading">
                <div>
                    <h2>Akses Cepat</h2>
                    <p>Pilih menu untuk mulai mengelola toko</p>
                </div>
            </div>

            <div class="quick-menu">

                <a href="produk.php" class="quick-card">
                    <div class="quick-icon teal">📦</div>
                    <div class="quick-text">
                        <strong>Kelola Produk</strong>
                        <span>Tambah dan edit produk</span>
                    </div>
                    <span class="arrow">›</span>
                </a>


                <a href="kategori.php" class="quick-card">
                    <div class="quick-icon orange">🏷️</div>
                    <div class="quick-text">
                        <strong>Kelola Kategori</strong>
                        <span>Atur kategori produk</span>
                    </div>
                    <span class="arrow">›</span>
                </a>


                <a href="pesanan.php" class="quick-card">
                    <div class="quick-icon purple">🛍️</div>
                    <div class="quick-text">
                        <strong>Kelola Pesanan</strong>
                        <span>Pantau pesanan pelanggan</span>
                    </div>
                    <span class="arrow">›</span>
                </a>

            </div>

        </section>


        <!-- INFORMASI -->
        <section class="info-box">

            <div class="info-icon">💡</div>

            <div>
                <h3>Informasi Admin</h3>
                <p>
                    Selamat mengelola SembakoKita! Pastikan informasi
                    produk selalu diperbarui, stok terpantau, dan
                    pesanan pelanggan diproses dengan baik.
                </p>
            </div>

        </section>


        <footer>
            © <?= date('Y') ?> SembakoKita. All Rights Reserved.
        </footer>

    </main>

</div>

</body>
</html>