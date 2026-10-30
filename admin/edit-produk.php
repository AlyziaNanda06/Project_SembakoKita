
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['pesan_error'] = "ID produk tidak valid.";
    header("Location: produk.php");
    exit;
}

// Ambil data produk berdasarkan ID.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, category_id, brand_id, name, sku, price,
            discount_price, weight, stock, description,
            shipping_info, return_policy, status
     FROM products
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$produk) {
    $_SESSION['pesan_error'] = "Produk tidak ditemukan.";
    header("Location: produk.php");
    exit;
}

// Ambil daftar kategori.
$kategoriResult = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (!$kategoriResult) {
    die("Gagal mengambil data kategori.");
}

// Ambil daftar merek.
$merekResult = mysqli_query(
    $conn,
    "SELECT id, name FROM brands ORDER BY name ASC"
);

if (!$merekResult) {
    die("Gagal mengambil data merek.");
}

$pesanError = $_SESSION['pesan_error'] ?? '';
unset($_SESSION['pesan_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - SembakoKita</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f5f8f8;
            color: #263238;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            padding: 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .08);
        }

        h1 {
            margin-top: 0;
            color: #278f90;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field {
            margin-bottom: 16px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5d5;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .error {
            padding: 12px;
            margin-bottom: 18px;
            color: #842029;
            background: #f8d7da;
            border-radius: 7px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border: 0;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .save {
            color: white;
            background: #278f90;
        }

        .cancel {
            color: #263238;
            background: #e7eeee;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 18px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Produk</h1>
    <p>Ubah informasi produk, lalu tekan Simpan Perubahan.</p>

    <?php if ($pesanError !== ''): ?>
        <div class="error"><?= e($pesanError) ?></div>
    <?php endif; ?>

    <form action="../proses/edit-produk.php" method="POST">
        <input type="hidden" name="id" value="<?= (int)$produk['id'] ?>">

        <div class="grid">
            <div class="field full">
                <label for="name">Nama Produk *</label>
                <input type="text" id="name" name="name"
                       maxlength="150" required
                       value="<?= e($produk['name']) ?>">
            </div>

            <div class="field">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku"
                       maxlength="50" value="<?= e($produk['sku']) ?>">
            </div>

            <div class="field">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id">
                    <option value="">Tanpa kategori</option>
                    <?php while ($kategori = mysqli_fetch_assoc($kategoriResult)): ?>
                        <option value="<?= (int)$kategori['id'] ?>"
                            <?= (string)$produk['category_id'] === (string)$kategori['id'] ? 'selected' : '' ?>>
                            <?= e($kategori['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field">
                <label for="brand_id">Merek</label>
                <select id="brand_id" name="brand_id">
                    <option value="">Tanpa merek</option>
                    <?php while ($merek = mysqli_fetch_assoc($merekResult)): ?>
                        <option value="<?= (int)$merek['id'] ?>"
                            <?= (string)$produk['brand_id'] === (string)$merek['id'] ? 'selected' : '' ?>>
                            <?= e($merek['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field">
                <label for="price">Harga Normal (Rp) *</label>
                <input type="number" id="price" name="price"
                       min="0" max="9999999999.99" step="0.01" required
                       value="<?= e($produk['price']) ?>">
            </div>

            <div class="field">
                <label for="discount_price">Harga Diskon (Rp)</label>
                <input type="number" id="discount_price" name="discount_price"
                       min="0" max="9999999999.99" step="0.01"
                       value="<?= e($produk['discount_price']) ?>">
            </div>

            <div class="field">
                <label for="weight">Berat (gram)</label>
                <input type="number" id="weight" name="weight"
                       min="0" step="1" value="<?= e($produk['weight']) ?>">
            </div>

            <div class="field">
                <label for="stock">Stok *</label>
                <input type="number" id="stock" name="stock"
                       min="0" step="1" required
                       value="<?= e($produk['stock']) ?>">
            </div>

            <div class="field full">
                <label for="description">Deskripsi Produk</label>
                <textarea id="description" name="description"><?= e($produk['description']) ?></textarea>
            </div>

            <div class="field full">
                <label for="shipping_info">Informasi Pengiriman</label>
                <textarea id="shipping_info" name="shipping_info"><?= e($produk['shipping_info']) ?></textarea>
            </div>

            <div class="field full">
                <label for="return_policy">Kebijakan Pengembalian</label>
                <textarea id="return_policy" name="return_policy"><?= e($produk['return_policy']) ?></textarea>
            </div>

            <div class="field full">
                <label for="status">Status Produk</label>
                <select id="status" name="status" required>
                    <option value="active"
                        <?= $produk['status'] === 'active' ? 'selected' : '' ?>>
                        Aktif
                    </option>
                    <option value="inactive"
                        <?= $produk['status'] === 'inactive' ? 'selected' : '' ?>>
                        Nonaktif
                    </option>
                </select>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn save">Simpan Perubahan</button>
            <a href="produk.php" class="btn cancel">Batal</a>
        </div>
    </form>
</div>
</body>
</html>
