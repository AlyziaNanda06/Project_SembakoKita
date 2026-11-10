<?php
session_start();
require_once __DIR__ . '/koneksi.php';

// Cek login pelanggan
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validasi ID pesanan
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id < 1) {
    header("Location: riwayat-pesanan.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fungsi keamanan output
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Format rupiah
function rupiah($angka) {
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

// Format status pesanan
function statusPesanan($status) {
    $status = strtolower((string) $status);

    $daftar = [
        'pending' => 'Menunggu Konfirmasi',
        'diproses' => 'Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan'
    ];

    return $daftar[$status] ?? ucfirst($status);
}

// Format status pembayaran
function statusPembayaran($status) {
    $status = strtolower((string) $status);

    $daftar = [
        'belum_bayar' => 'Belum Bayar',
        'sudah_bayar' => 'Sudah Bayar',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'gagal' => 'Gagal'
    ];

    return $daftar[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

// Ambil data pesanan milik pelanggan yang sedang login
$sql_order = "
    SELECT
        o.id,
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
        (
            SELECT p.method
            FROM payments p
            WHERE p.order_id = o.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS payment_method
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql_order);

if (!$stmt) {
    die("Terjadi kesalahan saat mengambil data pesanan.");
}

mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);

$result_order = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result_order);

mysqli_stmt_close($stmt);

// Pesanan tidak ditemukan atau bukan milik pelanggan
if (!$order) {
    http_response_code(404);
    $pesanan_tidak_ditemukan = true;
} else {
    $pesanan_tidak_ditemukan = false;
}

// Ambil detail produk pesanan
$items = [];

if (!$pesanan_tidak_ditemukan) {
    $sql_items = "
        SELECT
            oi.id,
            oi.product_id,
            oi.quantity,
            oi.price,
            oi.subtotal,
            p.name AS product_name,
            p.sku,
            (
                SELECT pi.image
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.is_primary DESC, pi.id ASC
                LIMIT 1
            ) AS product_image
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ";

    $stmt_items = mysqli_prepare($conn, $sql_items);

    if (!$stmt_items) {
        die("Terjadi kesalahan saat mengambil detail produk.");
    }

    mysqli_stmt_bind_param($stmt_items, "i", $order_id);
    mysqli_stmt_execute($stmt_items);

    $result_items = mysqli_stmt_get_result($stmt_items);

    while ($row = mysqli_fetch_assoc($result_items)) {
        $items[] = $row;
    }

    mysqli_stmt_close($stmt_items);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Sembako-Kita</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: #f3f8f7;
            font-family: 'Nunito Sans', Arial, sans-serif;
            color: #333;
        }

        .detail-container {
            width: 90%;
            max-width: 950px;
            margin: 40px auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #359fa0;
            text-decoration: none;
            font-weight: 700;
        }

        .detail-card {
            background: #fff;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .detail-card h2 {
            color: #287f80;
            font-size: 20px;
            margin: 0 0 20px;
        }

        .invoice {
            font-size: 18px;
            font-weight: 800;
            color: #359fa0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #777;
        }

        .info-value {
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e0f5f2;
            color: #287f80;
            font-size: 13px;
            font-weight: 700;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 75px;
            height: 75px;
            object-fit: contain;
            border-radius: 10px;
            background: #f3f8f7;
            flex-shrink: 0;
        }

        .product-placeholder {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f8f7;
            border-radius: 10px;
            font-size: 28px;
            flex-shrink: 0;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-info h3 {
            margin: 0 0 6px;
            font-size: 15px;
        }

        .product-info p {
            margin: 4px 0;
            font-size: 13px;
            color: #777;
        }

        .product-subtotal {
            font-weight: 800;
            color: #287f80;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .grand-total {
            border-top: 2px solid #eee;
            margin-top: 10px;
            padding-top: 18px;
            font-size: 19px;
            font-weight: 800;
            color: #287f80;
        }

        .empty-box {
            background: #fff;
            padding: 45px 25px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .btn-back {
            display: inline-block;
            margin-top: 15px;
            padding: 11px 22px;
            background: #359fa0;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
        }

        @media (max-width: 600px) {
            .detail-container {
                width: 94%;
                margin: 20px auto;
            }

            .detail-card {
                padding: 18px;
            }

            .product-item {
                flex-wrap: wrap;
            }

            .product-subtotal {
                width: 100%;
                text-align: left;
                padding-left: 90px;
            }

            .info-row {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="detail-container">

    <a href="riwayat-pesanan.php" class="back-link">
        &larr; Kembali ke Riwayat Pesanan
    </a>

    <?php if ($pesanan_tidak_ditemukan): ?>

        <div class="empty-box">
            <h2>Pesanan Tidak Ditemukan</h2>
            <p>Pesanan tidak tersedia atau kamu tidak memiliki akses ke pesanan ini.</p>
            <a href="riwayat-pesanan.php" class="btn-back">
                Kembali ke Riwayat
            </a>
        </div>

    <?php else: ?>

        <div class="detail-card">
            <h2>Detail Pesanan</h2>

            <div class="invoice">
                <?= e($order['invoice_number']) ?>
            </div>

            <p style="color:#777; margin-top:8px;">
                Tanggal Pesanan:
                <?= e(date('d-m-Y H:i', strtotime($order['created_at']))) ?>
            </p>

            <div class="info-row">
                <span class="info-label">Status Pesanan</span>
                <span class="info-value">
                    <span class="status">
                        <?= e(statusPesanan($order['status'])) ?>
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Status Pembayaran</span>
                <span class="info-value">
                    <span class="status">
                        <?= e(statusPembayaran($order['payment_status'])) ?>
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Metode Pembayaran</span>
                <span class="info-value">
                    <?= e($order['payment_method'] ?: '-') ?>
                </span>
            </div>
        </div>

        <div class="detail-card">
            <h2>Informasi Penerima</h2>

            <div class="info-row">
                <span class="info-label">Nama Penerima</span>
                <span class="info-value">
                    <?= e($order['receiver_name']) ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Nomor Telepon</span>
                <span class="info-value">
                    <?= e($order['receiver_phone']) ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Alamat Pengiriman</span>
                <span class="info-value">
                    <?= nl2br(e($order['shipping_address'])) ?>
                </span>
            </div>
        </div>

        <div class="detail-card">
            <h2>Produk yang Dipesan</h2>

            <?php if (empty($items)): ?>

                <p>Belum ada detail produk untuk pesanan ini.</p>

            <?php else: ?>

                <?php foreach ($items as $item): ?>

                    <div class="product-item">

                        <?php if (!empty($item['product_image'])): ?>
                            <img
                                src="<?= e($item['product_image']) ?>"
                                alt="<?= e($item['product_name'] ?? 'Produk') ?>"
                                class="product-image"
                            >
                        <?php else: ?>
                            <div class="product-placeholder">🛒</div>
                        <?php endif; ?>

                        <div class="product-info">
                            <h3>
                                <?= e($item['product_name'] ?? 'Produk tidak tersedia') ?>
                            </h3>

                            <p>
                                SKU: <?= e($item['sku'] ?? '-') ?>
                            </p>

                            <p>
                                <?= (int) $item['quantity'] ?> x
                                <?= rupiah($item['price']) ?>
                            </p>
                        </div>

                        <div class="product-subtotal">
                            <?= rupiah($item['subtotal']) ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <div class="detail-card">
            <h2>Rincian Pembayaran</h2>

            <div class="total-row">
                <span>Total Produk</span>
                <strong><?= rupiah($order['total_price']) ?></strong>
            </div>

            <div class="total-row">
                <span>Ongkos Kirim</span>
                <strong><?= rupiah($order['shipping_cost']) ?></strong>
            </div>

            <div class="total-row grand-total">
                <span>Total Pembayaran</span>
                <span><?= rupiah($order['grand_total']) ?></span>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>