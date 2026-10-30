
<?php
session_start();
require_once __DIR__ . '/koneksi.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function kembali($pesan, $halaman = 'checkout.php') {
    $_SESSION['checkout_pesan'] = $pesan;
    header("Location: " . $halaman);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    kembali('Silakan login terlebih dahulu.', 'login.php');
}

$keranjang = $_SESSION['keranjang'] ?? [];

if (empty($keranjang)) {
    kembali('Keranjang kamu masih kosong.', 'keranjang.php');
}

$nama = trim($_POST['nama'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$metode = trim($_POST['metode_pembayaran'] ?? '');

if ($nama === '' || $telepon === '' || $alamat === '') {
    kembali('Nama penerima, nomor telepon, dan alamat wajib diisi.');
}

if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $telepon)) {
    kembali('Format nomor telepon belum valid.');
}

$metodeDiizinkan = ['COD', 'Transfer Bank'];

if (!in_array($metode, $metodeDiizinkan, true)) {
    kembali('Metode pembayaran tidak valid.');
}

$userId = (int) $_SESSION['user_id'];
$shippingCost = 0;
$daftarProduk = [];
$totalHarga = 0;

try {
    $conn->begin_transaction();

    // Kunci data produk selama pengecekan agar stok tidak terjual
    // melebihi jumlah yang tersedia dalam transaksi bersamaan.
    foreach ($keranjang as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $jumlah = (int) ($item['jumlah'] ?? 0);

        if ($productId <= 0 || $jumlah <= 0) {
            throw new Exception('Data keranjang tidak valid.');
        }

        $stmt = $conn->prepare(
            "SELECT id, name, price, discount_price, stock
             FROM products
             WHERE id = ? AND status = 'active'
             FOR UPDATE"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $hasil = $stmt->get_result();
        $produk = $hasil->fetch_assoc();
        $stmt->close();

        if (!$produk) {
            throw new Exception('Ada produk yang sudah tidak tersedia.');
        }

        if ($jumlah > (int) $produk['stock']) {
            throw new Exception(
                'Stok ' . $produk['name'] . ' tidak mencukupi.'
            );
        }

        $hargaNormal = (float) $produk['price'];
        $hargaDiskon = $produk['discount_price'];

        $harga = (
            $hargaDiskon !== null &&
            (float) $hargaDiskon > 0 &&
            (float) $hargaDiskon < $hargaNormal
        ) ? (float) $hargaDiskon : $hargaNormal;

        if ($harga <= 0) {
            throw new Exception('Harga produk tidak valid.');
        }

        $subtotal = $harga * $jumlah;
        $totalHarga += $subtotal;

        $daftarProduk[] = [
            'id' => $productId,
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => $subtotal
        ];
    }

    $grandTotal = $totalHarga + $shippingCost;
    $invoice = 'SK-' . date('YmdHis') . '-' . random_int(100, 999);

    // Simpan pesanan utama beserta alamat penerima.
    $stmt = $conn->prepare(
        "INSERT INTO orders
        (user_id, invoice_number, total_price, shipping_cost,
         grand_total, status, payment_status,
         receiver_name, receiver_phone, shipping_address)
         VALUES (?, ?, ?, ?, ?, 'pending', 'belum_bayar', ?, ?, ?)"
    );

    $stmt->bind_param(
        'isdddsss',
        $userId,
        $invoice,
        $totalHarga,
        $shippingCost,
        $grandTotal,
        $nama,
        $telepon,
        $alamat
    );

    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();

    // Simpan detail produk dan kurangi stok.
    foreach ($daftarProduk as $produk) {
        $productId = $produk['id'];
        $jumlah = $produk['jumlah'];
        $harga = $produk['harga'];
        $subtotal = $produk['subtotal'];

        $stmt = $conn->prepare(
            "INSERT INTO order_items
             (order_id, product_id, quantity, price, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iiidd',
            $orderId,
            $productId,
            $jumlah,
            $harga,
            $subtotal
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE products SET stock = stock - ?
             WHERE id = ? AND stock >= ?"
        );
        $stmt->bind_param('iii', $jumlah, $productId, $jumlah);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('Stok berubah. Silakan coba checkout lagi.');
        }

        $stmt->close();
    }

    // Catat metode pembayaran. Pembayaran belum dianggap lunas.
    $statusPembayaran = 'pending';

    $stmt = $conn->prepare(
        "INSERT INTO payments (order_id, method, amount, status)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'isds',
        $orderId,
        $metode,
        $grandTotal,
        $statusPembayaran
    );
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Keranjang dikosongkan hanya setelah transaksi berhasil.
    unset($_SESSION['keranjang']);

    $_SESSION['checkout_pesan'] =
        'Pesanan berhasil dibuat. Nomor invoice: ' . $invoice;

    header('Location: checkout-berhasil.php?id=' . $orderId);
    exit;

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Abaikan jika transaksi sudah tidak aktif.
    }

    error_log('Checkout gagal: ' . $e->getMessage());

    kembali(
        'Pesanan belum berhasil dibuat. ' .
        'Periksa data dan coba lagi.'
    );
}
