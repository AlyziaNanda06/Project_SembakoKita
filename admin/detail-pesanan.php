
<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Helper keamanan
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Format rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

// Format tanggal
function tanggalIndonesia($tanggal) {
    if (empty($tanggal)) {
        return "-";
    }

    return date("d-m-Y H:i", strtotime($tanggal));
}

// Ambil ID pesanan
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("ID pesanan tidak valid. <a href='pesanan.php'>Kembali</a>");
}

// Ambil informasi pesanan
$stmt = mysqli_prepare(
    $conn,
    "SELECT
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
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ?
     LIMIT 1"
);

if (!$stmt) {
    exit("Gagal mengambil data pesanan.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    exit("Pesanan tidak ditemukan. <a href='pesanan.php'>Kembali</a>");
}

// Ambil informasi pembayaran
$stmtPayment = mysqli_prepare(
    $conn,
    "SELECT method, amount, proof_image, status, paid_at
     FROM payments
     WHERE order_id = ?
     ORDER BY id DESC
     LIMIT 1"
);

if (!$stmtPayment) {
    exit("Gagal mengambil data pembayaran.");
}

mysqli_stmt_bind_param($stmtPayment, "i", $id);
mysqli_stmt_execute($stmtPayment);

$resultPayment = mysqli_stmt_get_result($stmtPayment);
$pembayaran = mysqli_fetch_assoc($resultPayment);

mysqli_stmt_close($stmtPayment);

// Ambil produk dalam pesanan
$stmtItem = mysqli_prepare(
    $conn,
    "SELECT
        oi.id,
        oi.product_id,
        oi.quantity,
        oi.price,
        oi.subtotal,
        p.name AS product_name,
        p.sku AS product_sku
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);

if (!$stmtItem) {
    exit("Gagal mengambil rincian produk.");
}

mysqli_stmt_bind_param($stmtItem, "i", $id);
mysqli_stmt_execute($stmtItem);

$resultItem = mysqli_stmt_get_result($stmtItem);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pesanan - SembakoKita</title>

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
            margin-bottom: 25px;
            flex-wrap: wrap;
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
            background: #187D79;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            transition: .2s;
        }

        .btn:hover {
            background: #126762;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 22px;
            box-shadow: 0 4px 18px rgba(24, 75, 70, .05);
            border: 1px solid #EAF1EF;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: #263B3B;
        }

        .section-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #E7F5F2;
            color: #187D79;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #F8FBFA;
            border: 1px solid #EDF3F1;
            border-radius: 10px;
            min-width: 0;
        }

        .label {
            display: block;
            color: #81918F;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .value {
            color: #263B3B;
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            background: #E7F5F2;
            color: #187D79;
            font-size: 12px;
            font-weight: 900;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th {
            background: #F3F8F6;
            color: #718181;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 15px 13px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid #EDF2F0;
            font-size: 13px;
            color: #455A58;
        }

        tbody tr:hover {
            background: #FAFCFB;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .product-name {
            color: #263B3B;
            font-weight: 800;
        }

        .muted {
            color: #81918F;
        }

        .total {
            max-width: 420px;
            margin: 22px 0 0 auto;
            padding: 18px;
            background: #F8FBFA;
            border-radius: 12px;
        }

        .total p {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin: 10px 0;
            font-size: 14px;
            color: #718181;
        }

        .total strong {
            color: #263B3B;
        }

        .grand {
            border-top: 1px dashed #CBD9D5;
            padding-top: 17px;
            margin-top: 17px !important;
            font-size: 17px !important;
            font-weight: 900;
            color: #187D79 !important;
        }

        .grand span:last-child {
            color: #187D79;
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

            .info {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .card {
                padding: 18px;
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

<!-- MAIN CONTENT -->
<main class="main">

    <div class="page-header">
        <div>
            <h1>Detail Pesanan</h1>
            <p>Informasi lengkap transaksi pelanggan SembakoKita.</p>
        </div>

        <a href="pesanan.php" class="btn">
            ← Kembali ke Pesanan
        </a>
    </div>

    <!-- INFORMASI PESANAN -->
    <section class="card">

        <div class="card-header">
            <h2>📋 Informasi Pesanan</h2>
        </div>

        <div class="info">

            <div class="info-item">
                <span class="label">Nomor Invoice</span>
                <div class="value">
                    <?= e($pesanan["invoice_number"] ?: "ORD-" . $pesanan["id"]) ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Tanggal Pesanan</span>
                <div class="value">
                    <?= e(tanggalIndonesia($pesanan["created_at"])) ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Nama Pelanggan</span>
                <div class="value">
                    <?= e($pesanan["customer_name"] ?: "Pelanggan #" . $pesanan["user_id"]) ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Email Pelanggan</span>
                <div class="value">
                    <?= e($pesanan["customer_email"] ?: "-") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Nama Penerima</span>
                <div class="value">
                    <?= e($pesanan["receiver_name"] ?: "-") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Nomor Telepon Penerima</span>
                <div class="value">
                    <?= e($pesanan["receiver_phone"] ?: "-") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Alamat Pengiriman</span>
                <div class="value">
                    <?= nl2br(e($pesanan["shipping_address"] ?: "-")) ?>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Status Pesanan</span>
                <div class="value">
                    <span class="status">
                        <?= e(ucfirst($pesanan["status"])) ?>
                    </span>
                </div>
            </div>

            <div class="info-item">
                <span class="label">Status Pembayaran</span>
                <div class="value">
                    <span class="status">
                        <?= e(ucfirst(str_replace("_", " ", $pesanan["payment_status"]))) ?>
                    </span>
                </div>
            </div>

            <div class="info-item">
    <span class="label">Metode Pembayaran</span>
    <div class="value">
        <?= e($pembayaran["method"] ?? "-") ?>
    </div>
</div>

<div class="info-item">
    <span class="label">Jumlah Pembayaran</span>
    <div class="value">
        <?= isset($pembayaran["amount"])
            ? rupiah($pembayaran["amount"])
            : "-" ?>
    </div>
</div>

<div class="info-item">
    <span class="label">Status Pembayaran Detail</span>
    <div class="value">
        <?= e(ucfirst($pembayaran["status"] ?? "-")) ?>
    </div>
</div>

<div class="info-item">
    <span class="label">Tanggal Pembayaran</span>
    <div class="value">
        <?= e(tanggalIndonesia($pembayaran["paid_at"] ?? null)) ?>
    </div>
</div>
        </div>
    </section>

    <!-- BARANG YANG DIPESAN -->
    <section class="card">

        <div class="card-header">
            <h2>📦 Barang yang Dipesan</h2>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kode Produk</th>
                        <th class="number">Harga</th>
                        <th class="number">Jumlah</th>
                        <th class="number">Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($resultItem) > 0): ?>

                    <?php while ($item = mysqli_fetch_assoc($resultItem)): ?>

                        <tr>
                            <td class="product-name">
                                <?= e($item["product_name"] ?: "Produk #" . $item["product_id"]) ?>
                            </td>

                            <td>
                                <?= e($item["product_sku"] ?: "-") ?>
                            </td>

                            <td class="number">
                                <?= rupiah($item["price"]) ?>
                            </td>

                            <td class="number">
                                <?= (int)$item["quantity"] ?>
                            </td>

                            <td class="number">
                                <strong><?= rupiah($item["subtotal"]) ?></strong>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="muted" style="text-align:center;">
                            Belum ada rincian barang pada pesanan ini.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- TOTAL PEMBAYARAN -->
        <div class="total">

            <p>
                <span>Total Produk</span>
                <strong><?= rupiah($pesanan["total_price"]) ?></strong>
            </p>

            <p>
                <span>Ongkos Kirim</span>
                <strong><?= rupiah($pesanan["shipping_cost"]) ?></strong>
            </p>

            <p class="grand">
                <span>Total Pembayaran</span>
                <span><?= rupiah($pesanan["grand_total"]) ?></span>
            </p>

        </div>

    </section>

    <a href="pesanan.php" class="btn">
        ← Kembali ke Daftar Pesanan
    </a>

</main>

</body>
</html>

<?php
mysqli_stmt_close($stmtItem);
?>