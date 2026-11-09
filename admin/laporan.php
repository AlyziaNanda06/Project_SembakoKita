
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

function tanggalValid($tanggal) {
    $date = DateTime::createFromFormat("!Y-m-d", $tanggal);
    return $date && $date->format("Y-m-d") === $tanggal;
}

function tanggalIndonesia($tanggal) {
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April",
        "Mei", "Juni", "Juli", "Agustus",
        "September", "Oktober", "November", "Desember"
    ];

    $timestamp = strtotime($tanggal);

    return date("j", $timestamp) . " " .
        $bulan[(int)date("n", $timestamp)] . " " .
        date("Y", $timestamp);
}

$tanggalMulai = $_GET["mulai"] ?? date("Y-m-01");
$tanggalAkhir = $_GET["akhir"] ?? date("Y-m-d");

if (!tanggalValid($tanggalMulai) || !tanggalValid($tanggalAkhir)) {
    exit("Format tanggal tidak valid.");
}

if ($tanggalMulai > $tanggalAkhir) {
    exit("Tanggal mulai tidak boleh melewati tanggal akhir.");
}

$sqlRingkasan = "
    SELECT
        COUNT(*) AS total_pesanan,
        COALESCE(SUM(status = 'selesai'), 0) AS pesanan_selesai,
        COALESCE(SUM(status = 'pending'), 0) AS pesanan_pending,
        COALESCE(SUM(
            CASE WHEN status = 'selesai'
            THEN COALESCE(grand_total, 0)
            ELSE 0 END
        ), 0) AS total_pendapatan
    FROM orders
    WHERE created_at >= ?
      AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
";

$stmtRingkasan = mysqli_prepare($conn, $sqlRingkasan);
mysqli_stmt_bind_param($stmtRingkasan, "ss", $tanggalMulai, $tanggalAkhir);
mysqli_stmt_execute($stmtRingkasan);
$resultRingkasan = mysqli_stmt_get_result($stmtRingkasan);
$ringkasan = mysqli_fetch_assoc($resultRingkasan);
mysqli_stmt_close($stmtRingkasan);

$sqlPesanan = "
    SELECT
        id, invoice_number, user_id, total_price,
        shipping_cost, grand_total, status,
        payment_status, created_at
    FROM orders
    WHERE created_at >= ?
      AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY created_at DESC, id DESC
";

$stmtPesanan = mysqli_prepare($conn, $sqlPesanan);
mysqli_stmt_bind_param($stmtPesanan, "ss", $tanggalMulai, $tanggalAkhir);
mysqli_stmt_execute($stmtPesanan);
$resultPesanan = mysqli_stmt_get_result($stmtPesanan);

$totalBaris = mysqli_num_rows($resultPesanan);
$tanggalCetak = date("d-m-Y H:i");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan Penjualan - Sembako-Kita</title>

<style>
    :root {
        --primary: #258b8c;
        --dark: #243b3b;
        --muted: #647474;
        --line: #dce5e5;
        --light: #f2f8f7;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        background: #edf2f2;
        color: var(--dark);
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
        line-height: 1.5;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 4%;
        background: var(--primary);
        color: white;
    }

    .topbar a {
        color: white;
        text-decoration: none;
    }

    .page {
        max-width: 1200px;
        margin: 28px auto;
        padding: 0 20px;
    }

    .filter-card,
    .report {
        background: white;
        border-radius: 10px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 3px 14px rgba(0,0,0,.05);
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    input[type="date"] {
        min-height: 39px;
        padding: 8px;
        border: 1px solid #cbd7d7;
        border-radius: 5px;
        font: inherit;
    }

    .btn {
        display: inline-block;
        padding: 10px 15px;
        border: 0;
        border-radius: 5px;
        background: var(--primary);
        color: white;
        text-decoration: none;
        cursor: pointer;
        font: inherit;
    }

    .btn-secondary {
        background: #657474;
    }

    .report {
        padding: 30px;
    }

    .report-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding-bottom: 20px;
        border-bottom: 3px solid var(--primary);
    }

    .brand-mark {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 62px;
        height: 62px;
        border-radius: 12px;
        background: var(--light);
        color: var(--primary);
        font-size: 30px;
        font-weight: bold;
    }

    .brand-name {
        margin: 0;
        color: var(--primary);
        font-size: 24px;
        letter-spacing: 1px;
    }

    .brand-subtitle {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 12px;
    }

    .report-title {
        margin: 24px 0 4px;
        text-align: center;
        font-size: 20px;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .report-period {
        margin: 0 0 22px;
        text-align: center;
        color: var(--muted);
        font-size: 12px;
    }

    .summary-heading,
    .table-heading {
        margin: 22px 0 10px;
        font-size: 14px;
        font-weight: bold;
    }

    .summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .summary-item {
        padding: 14px;
        border: 1px solid var(--line);
        border-top: 3px solid var(--primary);
        border-radius: 5px;
        min-width: 0;
    }

    .summary-label {
        margin: 0 0 8px;
        color: var(--muted);
        font-size: 11px;
    }

    .summary-value {
        display: block;
        overflow-wrap: anywhere;
        font-size: 19px;
        font-weight: bold;
    }

    .summary-item.revenue {
        background: var(--light);
    }

    .summary-item.revenue .summary-value {
        color: var(--primary);
        font-size: 16px;
    }

    .table-wrap {
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        font-size: 11px;
    }

    thead {
        display: table-header-group;
    }

    tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    th {
        padding: 10px 7px;
        background: var(--primary);
        color: white;
        border: 1px solid var(--primary);
        text-align: left;
        font-weight: bold;
    }

    td {
        padding: 9px 7px;
        border: 1px solid var(--line);
        vertical-align: top;
        overflow-wrap: anywhere;
    }

    tbody tr:nth-child(even) {
        background: #f7fafa;
    }

    .center {
        text-align: center;
    }

    .money {
        text-align: right;
        white-space: nowrap;
    }

    .status {
        display: inline-block;
        padding: 3px 6px;
        border: 1px solid #d5e5e2;
        border-radius: 4px;
        font-size: 10px;
        white-space: nowrap;
    }

    .status-pending {
        background: #fff5d9;
        border-color: #ead9a8;
    }

    .status-batal {
        background: #fff0f0;
        border-color: #e9caca;
    }

    .empty {
        padding: 24px;
        text-align: center;
        color: var(--muted);
    }

    .report-footer {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid var(--line);
        color: var(--muted);
        font-size: 10px;
    }

    .signature {
        display: flex;
        justify-content: flex-end;
        margin-top: 28px;
    }

    .signature-box {
        width: 210px;
        text-align: center;
        font-size: 12px;
    }

    .signature-space {
        height: 62px;
    }

    .print-only {
        display: none;
    }

    @media (max-width: 750px) {
        .page {
            padding: 0 10px;
        }

        .report {
            padding: 16px;
        }

        .summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .brand-name {
            font-size: 20px;
        }

        .report-header {
            gap: 10px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
        }
    }

    @page {
        size: A4 landscape;
        margin: 12mm;
    }

    @media print {
        html,
        body {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            color: #172727 !important;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print,
        .topbar,
        .filter-card {
            display: none !important;
        }

        .page {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
        }

        .report {
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .report-header {
            padding-bottom: 10px;
        }

        .brand-mark {
            width: 45px;
            height: 45px;
            border: 1px solid #b9d9d6;
            font-size: 22px;
        }

        .brand-name {
            font-size: 20px;
        }

        .report-title {
            margin-top: 15px;
            font-size: 16px;
        }

        .report-period {
            margin-bottom: 12px;
            font-size: 10px;
        }

        .summary-heading,
        .table-heading {
            margin: 13px 0 7px;
            font-size: 11px;
        }

        .summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 7px;
        }

        .summary-item {
            padding: 8px;
            border-radius: 0;
        }

        .summary-label {
            margin-bottom: 4px;
            font-size: 9px;
        }

        .summary-value {
            font-size: 15px;
        }

        .summary-item.revenue .summary-value {
            font-size: 13px;
        }

        table {
            width: 100%;
            font-size: 9px;
        }

        th {
            padding: 7px 5px;
            background: #e8f2f1 !important;
            color: #172727 !important;
            border: 1px solid #9aabab;
        }

        td {
            padding: 6px 5px;
            border: 1px solid #c8d1d1;
        }

        tbody tr:nth-child(even) {
            background: #f5f8f8 !important;
        }

        .status {
            padding: 2px 4px;
            font-size: 8px;
        }

        .status-pending,
        .status-batal {
            background: white !important;
            color: black !important;
            border-color: #aaa;
        }

        .money {
            white-space: nowrap;
        }

        .report-footer {
            margin-top: 12px;
            padding-top: 7px;
            font-size: 9px;
        }

        .signature {
            margin-top: 18px;
            break-inside: avoid;
        }

        .signature-space {
            height: 48px;
        }

        .print-only {
            display: block;
        }

        a {
            color: inherit;
            text-decoration: none;
        }
    }
</style>
</head>

<body>

<header class="topbar no-print">
    <strong>Admin Sembako-Kita</strong>
    <a href="dashboard.php">← Kembali ke Dashboard</a>
</header>

<main class="page">

    <section class="filter-card no-print">
        <h2 style="margin-top:0">Filter Laporan Penjualan</h2>

        <form method="GET" class="filter-form">
            <div class="field">
                <label for="mulai">Tanggal Mulai</label>
                <input type="date" id="mulai" name="mulai"
                    value="<?= e($tanggalMulai) ?>" required>
            </div>

            <div class="field">
                <label for="akhir">Tanggal Akhir</label>
                <input type="date" id="akhir" name="akhir"
                    value="<?= e($tanggalAkhir) ?>" required>
            </div>

            <button class="btn" type="submit">Tampilkan Laporan</button>
            <a class="btn btn-secondary" href="laporan.php">Reset</a>
            <button class="btn" type="button" onclick="window.print()">
                Cetak / Simpan PDF
            </button>
        </form>
    </section>

    <section class="report">

        <div class="report-header">
            <div class="brand-mark">SK</div>
            <div>
                <h1 class="brand-name">SEMBAKO-KITA</h1>
                <p class="brand-subtitle">
                    Laporan administrasi penjualan dan transaksi
                </p>
            </div>
        </div>

        <h2 class="report-title">Laporan Penjualan</h2>

        <p class="report-period">
            Periode
            <strong><?= e(tanggalIndonesia($tanggalMulai)) ?></strong>
            sampai
            <strong><?= e(tanggalIndonesia($tanggalAkhir)) ?></strong>
        </p>

        <h3 class="summary-heading">A. Ringkasan Penjualan</h3>

        <div class="summary">
            <div class="summary-item">
                <p class="summary-label">Total Pesanan</p>
                <strong class="summary-value">
                    <?= number_format((int)$ringkasan["total_pesanan"], 0, ",", ".") ?>
                </strong>
            </div>

            <div class="summary-item">
                <p class="summary-label">Pesanan Selesai</p>
                <strong class="summary-value">
                    <?= number_format((int)$ringkasan["pesanan_selesai"], 0, ",", ".") ?>
                </strong>
            </div>

            <div class="summary-item">
                <p class="summary-label">Pesanan Pending</p>
                <strong class="summary-value">
                    <?= number_format((int)$ringkasan["pesanan_pending"], 0, ",", ".") ?>
                </strong>
            </div>

            <div class="summary-item revenue">
                <p class="summary-label">Pendapatan Pesanan Selesai</p>
                <strong class="summary-value">
                    <?= rupiah($ringkasan["total_pendapatan"]) ?>
                </strong>
            </div>
        </div>

        <h3 class="table-heading">B. Rincian Transaksi</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="center">No.</th>
                        <th>Nomor Invoice</th>
                        <th class="center">ID Pelanggan</th>
                        <th>Tanggal Pesanan</th>
                        <th class="money">Total Produk</th>
                        <th class="money">Ongkir</th>
                        <th class="money">Total Bayar</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($totalBaris > 0): ?>
                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($resultPesanan)): ?>
                        <?php
                        $status = (string)($row["status"] ?? "");
                        $pembayaran = (string)($row["payment_status"] ?? "");

                        $kelasStatus = "";

                        if ($status === "pending") {
                            $kelasStatus = "status-pending";
                        } elseif (
                            in_array($status, ["dibatalkan", "batal"], true)
                        ) {
                            $kelasStatus = "status-batal";
                        }
                        ?>

                        <tr>
                            <td class="center"><?= $no++ ?></td>
                            <td><?= e($row["invoice_number"] ?: "ORD-" . $row["id"]) ?></td>
                            <td class="center">
                                <?= $row["user_id"] !== null ? (int)$row["user_id"] : "-" ?>
                            </td>
                            <td>
                                <?= e(date("d-m-Y H:i", strtotime($row["created_at"]))) ?>
                            </td>
                            <td class="money"><?= rupiah($row["total_price"]) ?></td>
                            <td class="money"><?= rupiah($row["shipping_cost"]) ?></td>
                            <td class="money"><strong><?= rupiah($row["grand_total"]) ?></strong></td>
                            <td>
                                <span class="status <?= e($kelasStatus) ?>">
                                    <?= e(ucfirst(str_replace("_", " ", $status))) ?>
                                </span>
                            </td>
                            <td>
                                <?= e(ucfirst(str_replace("_", " ", $pembayaran))) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty">
                            Tidak ada transaksi pada periode laporan ini.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            <span>Dokumen laporan internal Sembako-Kita</span>
            <span>
                Dicetak pada <?= e($tanggalCetak) ?> |
                <?= $totalBaris ?> transaksi
            </span>
        </div>

        <div class="signature">
            <div class="signature-box">
                <p>
                    Mengetahui,<br>
                    Admin Sembako-Kita
                </p>
                <div class="signature-space"></div>
                <strong>( ____________________ )</strong>
            </div>
        </div>

    </section>

    <div class="no-print" style="margin:16px 0">
        <a href="dashboard.php" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>

</main>

<?php
mysqli_stmt_close($stmtPesanan);
?>

</body>
</html>
