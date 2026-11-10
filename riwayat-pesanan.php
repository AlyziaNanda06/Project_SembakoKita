
<?php
session_start();
require_once "koneksi.php";

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ambil riwayat pesanan milik pelanggan yang sedang login
$sql = "
    SELECT
        o.id,
        o.invoice_number,
        o.grand_total,
        o.status,
        o.payment_status,
        o.created_at,
        COUNT(oi.id) AS jumlah_item
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY
        o.id,
        o.invoice_number,
        o.grand_total,
        o.status,
        o.payment_status,
        o.created_at
    ORDER BY o.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    return "Rp" . number_format((float) $angka, 0, ',', '.');
}

function statusPesanan($status) {
    $daftar = [
        'pending' => 'Menunggu Konfirmasi',
        'diproses' => 'Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan'
    ];

    return $daftar[$status] ?? ucfirst($status);
}

function statusPembayaran($status) {
    $daftar = [
        'belum_bayar' => 'Belum Bayar',
        'sudah_bayar' => 'Sudah Bayar'
    ];

    return $daftar[$status] ?? ucfirst($status);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Sembako-Kita</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #f3f8f7;
            color: #263b3b;
            font-family: Arial, sans-serif;
        }

        .history-container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .history-header {
            margin-bottom: 25px;
        }

        .history-header h1 {
            color: #287f80;
            margin-bottom: 8px;
        }

        .history-header p {
            color: #687878;
        }

        .order-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .invoice {
            font-weight: bold;
            color: #287f80;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin: 20px 0;
        }

        .info-label {
            color: #777;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .info-value {
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #fff0c5;
            color: #805b00;
            font-size: 12px;
            font-weight: bold;
        }

        .payment-status {
            background: #dff5ee;
            color: #187443;
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .detail-button {
            display: inline-block;
            padding: 10px 18px;
            background: #359fa0;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }

        .detail-button:hover {
            background: #287f80;
        }

        .empty-order {
            background: white;
            padding: 45px 20px;
            border-radius: 12px;
            text-align: center;
        }

        .empty-order a {
            color: #287f80;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #287f80;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .order-card {
                padding: 16px;
            }

            .order-actions {
                justify-content: stretch;
            }

            .detail-button {
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="history-container">

    <a href="index.php" class="back-link">
        &larr; Kembali ke Beranda
    </a>

    <div class="history-header">
        <h1>Riwayat Pesanan</h1>
        <p>
            Halo, <?= e($_SESSION['user_name'] ?? 'Pelanggan') ?>!
            Berikut daftar pesanan kamu.
        </p>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <?php while ($pesanan = mysqli_fetch_assoc($result)): ?>

            <div class="order-card">

                <div class="order-top">
                    <div>
                        <div class="invoice">
                            <?= e($pesanan['invoice_number']) ?>
                        </div>

                        <small>
                            <?= e(date(
                                'd-m-Y H:i',
                                strtotime($pesanan['created_at'])
                            )) ?>
                        </small>
                    </div>

                    <span class="status">
                        <?= e(statusPesanan($pesanan['status'])) ?>
                    </span>
                </div>

                <div class="order-info">

                    <div>
                        <div class="info-label">Jumlah Item</div>
                        <div class="info-value">
                            <?= (int) $pesanan['jumlah_item'] ?> jenis produk
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Total Pembayaran</div>
                        <div class="info-value">
                            <?= formatRupiah($pesanan['grand_total']) ?>
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Status Pembayaran</div>
                        <div>
                            <span class="status payment-status">
                                <?= e(statusPembayaran($pesanan['payment_status'])) ?>
                            </span>
                        </div>
                    </div>

                </div>

                <div class="order-actions">
                    <a
                        href="detail-riwayat-pesanan.php?id=<?= (int) $pesanan['id'] ?>"
                        class="detail-button"
                    >
                        Lihat Detail
                    </a>
                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-order">
            <h2>Belum Ada Pesanan</h2>
            <p>Kamu belum memiliki riwayat pesanan.</p>
            <a href="produk.php">Mulai Belanja</a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>