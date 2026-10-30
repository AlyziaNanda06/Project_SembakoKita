
<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil data kategori
$queryKategori = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

// Ambil data merek
$queryMerek = mysqli_query(
    $conn,
    "SELECT id, name FROM brands ORDER BY name ASC"
);

// Pesan dari proses tambah produk
$pesanError = $_SESSION['pesan_error'] ?? '';
unset($_SESSION['pesan_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk | SembakoKita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f4f7f7;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #238b8b;
        }

        .deskripsi {
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .tombol {
            display: inline-block;
            padding: 11px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .simpan {
            background: #238b8b;
            color: white;
        }

        .kembali {
            background: #e7eeee;
            color: #333;
            margin-right: 8px;
        }

        .error {
            background: #ffe4e4;
            color: #a00000;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Tambah Produk</h1>
    <p class="deskripsi">
        Isi formulir berikut untuk menambahkan produk baru
        ke katalog SembakoKita.
    </p>

    <?php if ($pesanError !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($pesanError) ?>
        </div>
    <?php endif; ?>

    <?php if (!$queryKategori || !$queryMerek): ?>
        <div class="error">
            Data kategori atau merek gagal dimuat.
            Periksa tabel categories dan brands di database.
        </div>
    <?php endif; ?>

    <form action="../proses/tambah-produk.php" method="POST">

        <div class="form-group">
            <label for="name">Nama Produk *</label>
            <input type="text" id="name" name="name"
                   placeholder="Contoh: Beras Premium 5 Kg"
                   maxlength="150" required>
        </div>

        <div class="form-group">
            <label for="sku">SKU / Kode Produk *</label>
            <input type="text" id="sku" name="sku"
                   placeholder="Contoh: BR001"
                   maxlength="50" required>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="category_id">Kategori *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>

                    <?php if ($queryKategori): ?>
                        <?php while ($kategori = mysqli_fetch_assoc($queryKategori)): ?>
                            <option value="<?= (int) $kategori['id'] ?>">
                                <?= htmlspecialchars($kategori['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand_id">Merek</label>
                <select id="brand_id" name="brand_id">
                    <option value="">-- Tanpa Merek --</option>

                    <?php if ($queryMerek): ?>
                        <?php while ($merek = mysqli_fetch_assoc($queryMerek)): ?>
                            <option value="<?= (int) $merek['id'] ?>">
                                <?= htmlspecialchars($merek['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="price">Harga (Rp) *</label>
                <input type="number" id="price" name="price"
                       min="0" step="0.01"
                       placeholder="75000" required>
            </div>

            <div class="form-group">
                <label for="discount_price">Harga Diskon (Rp)</label>
                <input type="number" id="discount_price"
                       name="discount_price"
                       min="0" step="0.01"
                       placeholder="Kosongkan jika tidak ada">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="weight">Berat (gram)</label>
                <input type="number" id="weight" name="weight"
                       min="0" placeholder="5000">
            </div>

            <div class="form-group">
                <label for="stock">Stok *</label>
                <input type="number" id="stock" name="stock"
                       min="0" value="0" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi Produk</label>
            <textarea id="description" name="description"
                      placeholder="Tuliskan deskripsi produk..."></textarea>
        </div>

        <div class="form-group">
            <label for="shipping_info">Informasi Pengiriman</label>
            <textarea id="shipping_info" name="shipping_info"
                      placeholder="Informasi pengemasan dan pengiriman..."></textarea>
        </div>

        <div class="form-group">
            <label for="return_policy">Kebijakan Pengembalian</label>
            <textarea id="return_policy" name="return_policy"
                      placeholder="Ketentuan pengembalian produk..."></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status Produk *</label>
            <select id="status" name="status" required>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        <a href="produk.php" class="tombol kembali">Kembali</a>
        <button type="submit" class="tombol simpan">
            Simpan Produk
        </button>

    </form>
</div>

</body>
</html>
