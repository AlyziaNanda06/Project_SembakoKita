
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
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    exit("Pesanan tidak ditemukan. <a href='pesanan.php'>Kembali</a>");
}

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

mysqli_stmt_bind_param($stmtItem, "i", $id);
mysqli_stmt_execute($stmtItem);
$resultItem = mysqli_stmt_get_result($stmtItem);

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f7;
            color: #263b3b;
        }

        header {
            background: #359fa0;
            color: white;
            padding: 18px 5%;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        main {
            max-width: 1050px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px #00000008;
        }

        h1 {
            margin-top: 0;
        }

        h2 {
            font-size: 19px;
            margin-top: 0;
        }

        .info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .info p {
            margin: 8px 0;
            overflow-wrap: anywhere;
        }

        .label {
            color: #718181;
            font-size: 13px;
        }

        .value {
            font-weight: bold;
            margin-top: 5px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid #edf0f0;
        }

        th {
            background: #f8fbfb;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .total {
            max-width: 400px;
            margin: 20px 0 0 auto;
        }

        .total p {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .grand {
            font-size: 20px;
            color: #258b8c;
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 7px;
            background: #359fa0;
            color: white;
            text-decoration: none;
        }

        .muted {
            color: #718181;
        }

        @media (max-width: 600px) {
            .info {
                grid-template-columns: 1fr;
            }

            main {
                padding: 0 12px;
            }

            .card {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>Admin Sembako-Kita</strong>
    <span style="float:right">
        <a href="pesanan.php">Kembali ke Pesanan</a>
    </span>
</header>

<main>
    <h1>Detail Pesanan</h1>
    <p class="muted">Informasi lengkap transaksi pelanggan.</p>

    <section class="card">
        <h2>Informasi Pesanan</h2>

        <div class="info">
            <div>
                <p class="label">Nomor Invoice</p>
                <div class="value">
                    <?= e($pesanan["invoice_number"] ?: "ORD-" . $pesanan["id"]) ?>
                </div>
            </div>

            <div>
                <p class="label">Tanggal Pesanan</p>
                <div class="value"><?= e($pesanan["created_at"]) ?></div>
            </div>

            <div>
                <p class="label">Nama Pelanggan</p>
                <div class="value">
                    <?= e($pesanan["customer_name"] ?: "Pelanggan #" . $pesanan["user_id"]) ?>
                </div>
            </div>

            <div>
                <p class="label">Email Pelanggan</p>
                <div class="value">
                    <?= e($pesanan["customer_email"] ?: "-") ?>
                </div>
            </div>

            <div>
                <p class="label">Status Pesanan</p>
                <div class="value"><?= e(ucfirst($pesanan["status"])) ?></div>
            </div>

            <div>
                <p class="label">Status Pembayaran</p>
                <div class="value">
                    <?= e(ucfirst(str_replace("_", " ", $pesanan["payment_status"]))) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Barang yang Dipesan</h2>

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
                            <td>
                                <?= e($item["product_name"] ?: "Produk #" . $item["product_id"]) ?>
                            </td>
                            <td><?= e($item["product_sku"] ?: "-") ?></td>
                            <td class="number"><?= rupiah($item["price"]) ?></td>
                            <td class="number"><?= (int)$item["quantity"] ?></td>
                            <td class="number"><?= rupiah($item["subtotal"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="muted">
                            Belum ada rincian barang pada pesanan ini.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

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

    <a href="pesanan.php" class="btn">← Kembali ke Daftar Pesanan</a>
</main>

</body>
</html>

<?php
mysqli_stmt_close($stmtItem);
?>
