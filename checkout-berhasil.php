
<?php
session_start();
require_once __DIR__ . '/koneksi.php';

function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, invoice_number, total_price, shipping_cost,
            grand_total, status, payment_status, created_at
     FROM orders
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT oi.quantity, oi.price, oi.subtotal, p.name
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - SembakoKita</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f4f8f7;
            color: #263238;
        }

        .container {
            max-width: 720px;
            margin: 30px auto;
            padding: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .07);
        }

        .success {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .check {
            font-size: 52px;
            color: #359fa0;
        }

        h1 {
            margin: 8px 0;
            color: #359fa0;
            font-size: 27px;
        }

        .invoice {
            margin-top: 18px;
            padding: 14px;
            background: #eef9f8;
            border-radius: 8px;
        }

        h2 {
            font-size: 18px;
            margin-top: 25px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .muted {
            color: #657477;
            font-size: 14px;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: bold;
        }

        .button {
            display: block;
            margin-top: 24px;
            padding: 13px;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            background: #359fa0;
            color: white;
            font-weight: bold;
        }

        .button:hover {
            background: #287f80;
        }

        @media (max-width: 500px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 18px;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="success">
        <div class="check">✓</div>
        <h1>Pesanan Berhasil Dibuat!</h1>
        <p>Terima kasih sudah berbelanja di SembakoKita.</p>

        <div class="invoice">
            <div class="muted">Nomor invoice</div>
            <strong><?= e($order['invoice_number']) ?></strong>
        </div>
    </div>

    <h2>Rincian Pesanan</h2>

    <?php if (empty($items)): ?>
        <p>Rincian produk tidak ditemukan.</p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="item">
                <div>
                    <strong><?= e($item['name'] ?? 'Produk') ?></strong>
                    <div class="muted">
                        <?= (int) $item['quantity'] ?> ×
                        <?= rupiah($item['price']) ?>
                    </div>
                </div>
                <strong><?= rupiah($item['subtotal']) ?></strong>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Pembayaran</h2>

    <div class="item">
        <span>Total produk</span>
        <span><?= rupiah($order['total_price']) ?></span>
    </div>

    <div class="item">
        <span>Ongkos kirim</span>
        <span><?= rupiah($order['shipping_cost']) ?></span>
    </div>

    <div class="total">
        <span>Total pembayaran</span>
        <span><?= rupiah($order['grand_total']) ?></span>
    </div>

    <p class="muted">
        Status pesanan: <?= e($order['status']) ?><br>
        Status pembayaran: <?= e($order['payment_status']) ?>
    </p>

    <a class="button" href="produk.php">Lanjut Belanja</a>
    <a class="button" href="index.php">Kembali ke Beranda</a>
</div>
</body>
</html>
