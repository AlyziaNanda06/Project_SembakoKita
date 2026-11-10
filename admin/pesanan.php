
<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Token keamanan form
if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

// Mengambil pilihan ENUM dari database
function ambilPilihanEnum($conn, $kolom) {
    $kolomDiizinkan = ["status", "payment_status"];

    if (!in_array($kolom, $kolomDiizinkan, true)) {
        return [];
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$kolom'");

    if (!$result || mysqli_num_rows($result) === 0) {
        return [];
    }

    $data = mysqli_fetch_assoc($result);
    $tipe = $data["Type"] ?? "";

    if (preg_match("/^enum\((.*)\)$/i", $tipe, $matches)) {
        return str_getcsv($matches[1], ",", "'");
    }

    return [];
}

$statusPilihan = ambilPilihanEnum($conn, "status");
$pembayaranPilihan = ambilPilihanEnum($conn, "payment_status");

// Pesan notifikasi
$pesan = $_SESSION["pesanan_pesan"] ?? "";
$error = $_SESSION["pesanan_error"] ?? "";

unset($_SESSION["pesanan_pesan"], $_SESSION["pesanan_error"]);

// Pencarian pesanan
$cari = trim($_GET["cari"] ?? "");

// Statistik pesanan
$statistik = [
    "total" => 0,
    "pending" => 0,
    "diproses" => 0,
    "selesai" => 0
];

$sqlStatistik = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'diproses') AS diproses,
        SUM(status = 'selesai') AS selesai
    FROM orders
";

$resultStatistik = mysqli_query($conn, $sqlStatistik);

if ($resultStatistik) {
    $statistik = array_merge(
        $statistik,
        mysqli_fetch_assoc($resultStatistik) ?: []
    );
}

// Mengambil data pesanan
$sql = "
    SELECT
        o.id,
        o.user_id,
        o.invoice_number,
        o.total_price,
        o.shipping_cost,
        o.grand_total,
        o.status,
        o.payment_status,
        o.receiver_name,
        o.receiver_phone,
        o.shipping_address,
        o.created_at
    FROM orders o
";

if ($cari !== "") {
    $sql .= "
        WHERE o.invoice_number LIKE ?
        OR CAST(o.id AS CHAR) LIKE ?
    ";
}

$sql .= " ORDER BY o.created_at DESC, o.id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Gagal menyiapkan pencarian pesanan.");
}

if ($cari !== "") {
    $kataCari = "%" . $cari . "%";
    mysqli_stmt_bind_param($stmt, "ss", $kataCari, $kataCari);
}

mysqli_stmt_execute($stmt);
$resultPesanan = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Pesanan | SembakoKita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Nunito Sans", Arial, sans-serif;
            background: #F1F8F6;
            color: #263D3A;
        }

        a {
            text-decoration: none;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #187D79;
            color: white;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 5px 25px;
            border-bottom: 1px solid rgba(255,255,255,.18);
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.16);
            border-radius: 13px;
            font-size: 23px;
            flex-shrink: 0;
        }

        .brand h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        }

        .brand span {
            display: block;
            margin-top: 3px;
            color: #C5EAE4;
            font-size: 10px;
            letter-spacing: 1.5px;
            font-weight: 800;
        }

        .menu-label {
            margin: 28px 10px 13px;
            color: #B7E0DB;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.3px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            color: #E5F5F2;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,.13);
            transform: translateX(3px);
        }

        .menu a.active {
            background: white;
            color: #187D79;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
        }

        .menu-icon {
            width: 23px;
            text-align: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 18px 10px 0;
            border-top: 1px solid rgba(255,255,255,.18);
            color: #C5EAE4;
            font-size: 12px;
        }

        .sidebar-bottom strong {
            color: white;
            font-size: 13px;
        }

        .sidebar-bottom p {
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* KONTEN UTAMA */
        .main {
            margin-left: 260px;
            padding: 30px 35px;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
        }

        .topbar small {
            color: #7D9691;
            font-size: 13px;
        }

        .admin-label {
            background: white;
            padding: 10px 16px;
            border-radius: 30px;
            color: #187D79;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 3px 12px rgba(35,90,80,.05);
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0 0 7px;
            font-size: 29px;
            font-weight: 900;
        }

        .page-heading p {
            margin: 0;
            color: #81938F;
            font-size: 14px;
        }

        /* STATISTIK */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(35,90,80,.05);
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .stat-icon {
            width: 49px;
            height: 49px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 22px;
            flex-shrink: 0;
        }

        .icon-purple {
            background: #EFEDFF;
        }

        .icon-orange {
            background: #FFF0E8;
        }

        .icon-blue {
            background: #E7F2FF;
        }

        .icon-green {
            background: #E2F7ED;
        }

        .stat-card p {
            margin: 0 0 4px;
            color: #8A9B97;
            font-size: 12px;
            font-weight: 700;
        }

        .stat-card strong {
            font-size: 25px;
            font-weight: 900;
        }

        /* PANEL */
        .card {
            background: white;
            padding: 24px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(35,90,80,.05);
            margin-bottom: 22px;
        }

        .card-heading {
            margin-bottom: 18px;
        }

        .card-heading h2 {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 900;
        }

        .card-heading p {
            margin: 0;
            color: #8A9B97;
            font-size: 12px;
        }

        .muted {
            color: #81938F;
        }

        /* PENCARIAN */
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 12px 14px;
            border: 1px solid #DCE9E5;
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            background: #FBFDFC;
        }

        input[type="text"]:focus {
            border-color: #25AFA5;
            box-shadow: 0 0 0 3px rgba(37,175,165,.12);
        }

        /* TOMBOL */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            padding: 10px 14px;
            border-radius: 9px;
            background: #25AFA5;
            color: white;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(.96);
        }

        .btn-search {
            background: #25AFA5;
        }

        .btn-secondary {
            background: #EDF1F0;
            color: #60716D;
        }

        .btn-detail {
            background: #8B83E8;
        }

        .btn-save {
            background: #25AFA5;
            padding: 8px 12px;
        }

        /* NOTIFIKASI */
        .alert {
            padding: 13px 17px;
            border-radius: 11px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 700;
            background: #E3F7EB;
            color: #23734B;
            border: 1px solid #C7EBD6;
        }

        .alert-error {
            background: #FFF0ED;
            color: #A63329;
            border: 1px solid #F7D5CE;
        }

        /* TABEL */
        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .table-heading h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
        }

        .total-label {
            background: #E5F7F3;
            color: #187D79;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1300px;
            border-collapse: collapse;
        }

        th {
            padding: 13px 12px;
            background: #F1F8F6;
            color: #69817B;
            font-size: 11px;
            font-weight: 900;
            text-align: left;
            white-space: nowrap;
        }

        th:first-child {
            border-radius: 9px 0 0 9px;
        }

        th:last-child {
            border-radius: 0 9px 9px 0;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #EDF2F0;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #FAFCFB;
        }

        .invoice {
            color: #187D79;
            font-weight: 900;
        }

        .receiver-name {
            font-weight: 900;
            color: #304943;
        }

        .receiver-phone {
            display: inline-block;
            margin-top: 4px;
            color: #94A39F;
            font-size: 11px;
        }

        .total-payment {
            color: #187D79;
            font-weight: 900;
        }

        .status-form {
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
            align-items: flex-start;
        }

        select {
            width: 145px;
            padding: 9px 10px;
            border: 1px solid #DCE9E5;
            border-radius: 9px;
            background: #FBFDFC;
            color: #425A55;
            font-family: inherit;
            font-size: 11px;
            outline: none;
        }

        select:focus {
            border-color: #25AFA5;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: #91A19D;
        }

        .empty-icon {
            display: block;
            font-size: 30px;
            margin-bottom: 8px;
        }

        /* RESPONSIVE */
        @media (max-width: 1150px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 850px) {
            .sidebar {
                width: 75px;
                padding: 20px 10px;
            }

            .brand {
                justify-content: center;
                padding: 0 0 20px;
            }

            .brand h2,
            .brand span,
            .menu-label,
            .sidebar-bottom {
                display: none;
            }

            .menu a {
                justify-content: center;
                padding: 13px 5px;
                font-size: 0;
            }

            .menu-icon {
                font-size: 20px;
            }

            .main {
                margin-left: 75px;
                padding: 25px 20px;
            }
        }

        @media (max-width: 600px) {
            .main {
                padding: 20px 13px;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 13px;
                gap: 9px;
            }

            .stat-icon {
                width: 39px;
                height: 39px;
                font-size: 18px;
            }

            .stat-card strong {
                font-size: 21px;
            }

            .stat-card p {
                font-size: 10px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .card {
                padding: 17px;
            }

            .topbar {
                align-items: flex-start;
            }

            .admin-label {
                font-size: 11px;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form input,
            .search-form .btn {
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

        <a href="produk.php">
            <span class="menu-icon">📦</span>
            Kelola Produk
        </a>

        <a href="kategori.php">
            <span class="menu-icon">🏷️</span>
            Kelola Kategori
        </a>

        <a href="pesanan.php" class="active">
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
<main class="main">

    <div class="topbar">
        <small>Admin Panel / Kelola Pesanan</small>
        <div class="admin-label">⚙️ Administrator</div>
    </div>

    <div class="page-heading">
        <h1>Kelola Pesanan 🛍️</h1>
        <p>Lihat rincian transaksi dan kelola status pesanan pelanggan.</p>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert">
            ✓ <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error">
            ⚠ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon icon-purple">🛍️</div>
            <div>
                <p>Total Pesanan</p>
                <strong><?= (int)$statistik["total"] ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orange">⏳</div>
            <div>
                <p>Menunggu</p>
                <strong><?= (int)$statistik["pending"] ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-blue">📦</div>
            <div>
                <p>Diproses</p>
                <strong><?= (int)$statistik["diproses"] ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-green">✓</div>
            <div>
                <p>Selesai</p>
                <strong><?= (int)$statistik["selesai"] ?></strong>
            </div>
        </div>

    </section>

    <!-- PENCARIAN -->
    <section class="card">

        <div class="card-heading">
            <h2>🔎 Cari Pesanan</h2>
            <p>Temukan pesanan berdasarkan nomor invoice atau ID pesanan.</p>
        </div>

        <form method="GET" class="search-form">

            <input
                type="text"
                name="cari"
                placeholder="Masukkan nomor invoice atau ID pesanan..."
                value="<?= e($cari) ?>"
            >

            <button type="submit" class="btn btn-search">
                Cari Pesanan
            </button>

            <a href="pesanan.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

    </section>

    <!-- DAFTAR PESANAN -->
    <section class="card">

        <div class="table-heading">
            <h2>Daftar Pesanan</h2>

            <span class="total-label">
                <?= mysqli_num_rows($resultPesanan) ?> Data Ditemukan
            </span>
        </div>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Penerima</th>
                        <th>ID Pelanggan</th>
                        <th>Total Produk</th>
                        <th>Ongkir</th>
                        <th>Total Bayar</th>
                        <th>Tanggal</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($resultPesanan) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($resultPesanan)): ?>

                        <tr>

                            <td>
                                #<?= (int)$row["id"] ?>
                            </td>

                            <td>
                                <span class="invoice">
                                    <?= e($row["invoice_number"] ?: "ORD-" . $row["id"]) ?>
                                </span>
                            </td>

                            <td>
                                <span class="receiver-name">
                                    <?= e($row["receiver_name"] ?: "-") ?>
                                </span>
                                <br>
                                <span class="receiver-phone">
                                    <?= e($row["receiver_phone"] ?: "-") ?>
                                </span>
                            </td>

                            <td>
                                <?= $row["user_id"] !== null
                                    ? (int)$row["user_id"]
                                    : "-" ?>
                            </td>

                            <td>
                                <?= rupiah($row["total_price"]) ?>
                            </td>

                            <td>
                                <?= rupiah($row["shipping_cost"]) ?>
                            </td>

                            <td>
                                <span class="total-payment">
                                    <?= rupiah($row["grand_total"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($row["created_at"]) ?>
                            </td>

                            <td>
                                <form
                                    action="../proses/update-pesanan.php"
                                    method="POST"
                                    class="status-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="jenis"
                                        value="status"
                                    >

                                    <select name="nilai" aria-label="Status pesanan">
                                        <?php foreach ($statusPilihan as $pilihan): ?>
                                            <option
                                                value="<?= e($pilihan) ?>"
                                                <?= $row["status"] === $pilihan ? "selected" : "" ?>
                                            >
                                                <?= e(ucfirst(str_replace("_", " ", $pilihan))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn btn-save">
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td>
                                <form
                                    action="../proses/update-pesanan.php"
                                    method="POST"
                                    class="status-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="jenis"
                                        value="payment_status"
                                    >

                                    <select name="nilai" aria-label="Status pembayaran">
                                        <?php foreach ($pembayaranPilihan as $pilihan): ?>
                                            <option
                                                value="<?= e($pilihan) ?>"
                                                <?= $row["payment_status"] === $pilihan ? "selected" : "" ?>
                                            >
                                                <?= e(ucfirst(str_replace("_", " ", $pilihan))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn btn-save">
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td>
                                <a
                                    class="btn btn-detail"
                                    href="detail-pesanan.php?id=<?= (int)$row["id"] ?>"
                                >
                                    Detail
                                </a>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="11">
                            <div class="empty">
                                <span class="empty-icon">🛍️</span>
                                Belum ada pesanan atau pesanan tidak ditemukan.
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>