
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// Pastikan hanya admin yang sudah login dapat membuka halaman ini.
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function rupiah($nilai)
{
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
}

// Ambil semua pesanan terbaru.
$sql = "SELECT
            id,
            user_id,
            invoice_number,
            total_price,
            shipping_cost,
            grand_total,
            status,
            payment_status,
            receiver_name,
            receiver_phone,
            created_at
        FROM orders
        ORDER BY created_at DESC";

$result = $conn->query($sql);

$jumlahPesanan = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f5f7f7;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            color: #247f80;
        }

        .muted {
            color: #666;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 6px;
            background: #359fa0;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px #0000000d;
        }

        .table-wrapper {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th, td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        th {
            background: #e8f6f4;
            color: #246f70;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #fff0c5;
            font-size: 12px;
            white-space: nowrap;
        }

        .empty {
            padding: 30px;
            text-align: center;
            color: #666;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div>
            <h1>Kelola Pesanan</h1>
            <div class="muted">
                Daftar pesanan pelanggan Sembako-Kita
            </div>
        </div>

        <a class="button" href="dashboard.php">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="summary">
        <strong>Total Pesanan:</strong>
        <?= $jumlahPesanan ?> pesanan
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Invoice</th>
                    <th>Penerima</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status Pesanan</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($jumlahPesanan > 0): ?>
                <?php $no = 1; ?>

                <?php while ($pesanan = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <?= e($pesanan['invoice_number']) ?>
                        </td>

                        <td>
                            <strong>
                                <?= e($pesanan['receiver_name'] ?? '-') ?>
                            </strong>
                            <br>
                            <span class="muted">
                                <?= e($pesanan['receiver_phone'] ?? '-') ?>
                            </span>
                        </td>

                        <td>
                            <?= e($pesanan['created_at']) ?>
                        </td>

                        <td>
                            <strong>
                                <?= rupiah($pesanan['grand_total']) ?>
                            </strong>
                        </td>

                        <td>
                            <span class="status">
                                <?= e($pesanan['status']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="status">
                                <?= e($pesanan['payment_status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty">
                        Belum ada pesanan.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
