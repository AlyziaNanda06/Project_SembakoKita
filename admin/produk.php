
<?php
session_start();
require_once "../koneksi.php";

// Hanya admin yang sudah login boleh mengakses halaman ini.
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function buatSlug($teks) {
    $teks = strtolower(trim($teks));
    $teks = preg_replace('/[^a-z0-9]+/', '-', $teks);
    return trim($teks, '-');
}

function rupiah($angka) {
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

$pesan = "";
$error = "";
$editProduk = null;

// Ambil kategori aktif untuk pilihan dropdown.
$sqlKategori = "SELECT id, name FROM categories
                WHERE status = 1 ORDER BY name ASC";
$resultKategori = mysqli_query($conn, $sqlKategori);
$daftarKategori = [];

if ($resultKategori) {
    while ($row = mysqli_fetch_assoc($resultKategori)) {
        $daftarKategori[] = $row;
    }
}

// Proses tambah, edit, dan nonaktifkan produk.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["admin_csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $aksi = $_POST["aksi"] ?? "";

        if ($aksi === "simpan") {
            $id = (int) ($_POST["id"] ?? 0);
            $nama = trim($_POST["name"] ?? "");
            $kategoriId = (int) ($_POST["category_id"] ?? 0);
            $sku = trim($_POST["sku"] ?? "");
            $harga = $_POST["price"] ?? "";
            $diskon = trim($_POST["discount_price"] ?? "");
            $stok = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);
            $berat = trim($_POST["weight"] ?? "");
            $deskripsi = trim($_POST["description"] ?? "");
            $pengiriman = trim($_POST["shipping_info"] ?? "");
            $retur = trim($_POST["return_policy"] ?? "");
            $status = $_POST["status"] ?? "active";

            if (
                $nama === "" ||
                $kategoriId < 1 ||
                $harga === "" ||
                !is_numeric($harga) ||
                (float) $harga < 0 ||
                $stok === false ||
                $stok === null ||
                $stok < 0 ||
                !in_array($status, ["active", "inactive"], true)
            ) {
                $error = "Periksa nama, kategori, harga, stok, dan status produk.";
            } elseif ($diskon !== "" && (
                !is_numeric($diskon) ||
                (float) $diskon < 0 ||
                (float) $diskon > (float) $harga
            )) {
                $error = "Harga diskon harus valid dan tidak boleh melebihi harga normal.";
            } elseif ($berat !== "" && (
                !ctype_digit($berat) ||
                (int) $berat < 0
            )) {
                $error = "Berat harus berupa angka bulat nol atau lebih.";
            } else {
                // Pastikan kategori yang dipilih memang aktif.
                $cekKategori = mysqli_prepare(
                    $conn,
                    "SELECT id FROM categories WHERE id = ? AND status = 1"
                );
                mysqli_stmt_bind_param($cekKategori, "i", $kategoriId);
                mysqli_stmt_execute($cekKategori);
                $hasilKategori = mysqli_stmt_get_result($cekKategori);
                $kategoriValid = mysqli_fetch_assoc($hasilKategori);
                mysqli_stmt_close($cekKategori);

                if (!$kategoriValid) {
                    $error = "Kategori tidak ditemukan atau sudah tidak aktif.";
                } else {
                    $slug = buatSlug($nama);
                    $harga = (float) $harga;
                    $diskonDb = $diskon === "" ? null : (float) $diskon;
                    $beratDb = $berat === "" ? null : (int) $berat;

                    if ($id > 0) {
                        $sql = "UPDATE products SET
                                    category_id = ?,
                                    name = ?,
                                    slug = ?,
                                    sku = NULLIF(?, ''),
                                    price = ?,
                                    discount_price = ?,
                                    weight = ?,
                                    stock = ?,
                                    description = ?,
                                    shipping_info = ?,
                                    return_policy = ?,
                                    status = ?
                                WHERE id = ?";

                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param(
                            $stmt,
                            "isssddiiisssi",
                            $kategoriId,
                            $nama,
                            $slug,
                            $sku,
                            $harga,
                            $diskonDb,
                            $beratDb,
                            $stok,
                            $deskripsi,
                            $pengiriman,
                            $retur,
                            $status,
                            $id
                        );
                    } else {
                        $sql = "INSERT INTO products (
                                    category_id, name, slug, sku,
                                    price, discount_price, weight, stock,
                                    description, shipping_info,
                                    return_policy, status
                                ) VALUES (
                                    ?, ?, ?, NULLIF(?, ''),
                                    ?, ?, ?, ?, ?, ?, ?, ?
                                )";

                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param(
                            $stmt,
                            "isssddiissss",
                            $kategoriId,
                            $nama,
                            $slug,
                            $sku,
                            $harga,
                            $diskonDb,
                            $beratDb,
                            $stok,
                            $deskripsi,
                            $pengiriman,
                            $retur,
                            $status
                        );
                    }

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header("Location: produk.php?berhasil=1");
                        exit;
                    }

                    if (mysqli_stmt_errno($stmt) === 1062) {
                        $error = "Kode SKU atau slug sudah digunakan. Periksa data produk.";
                    } else {
                        $error = "Produk gagal disimpan. Periksa kembali data database.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        } elseif ($aksi === "nonaktifkan") {
            $id = (int) ($_POST["id"] ?? 0);

            if ($id > 0) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE products SET status = 'inactive' WHERE id = ?"
                );
                mysqli_stmt_bind_param($stmt, "i", $id);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: produk.php?dinonaktifkan=1");
                    exit;
                }

                mysqli_stmt_close($stmt);
                $error = "Produk gagal dinonaktifkan.";
            } else {
                $error = "ID produk tidak valid.";
            }
        }
    }
}

if (isset($_GET["berhasil"])) {
    $pesan = "Data produk berhasil disimpan.";
} elseif (isset($_GET["dinonaktifkan"])) {
    $pesan = "Produk berhasil dinonaktifkan.";
}

// Ambil data produk untuk formulir edit.
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM products WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $resultEdit = mysqli_stmt_get_result($stmt);
    $editProduk = mysqli_fetch_assoc($resultEdit);
    mysqli_stmt_close($stmt);

    if (!$editProduk) {
        $error = "Produk yang ingin diedit tidak ditemukan.";
    }
}

// Ambil semua produk beserta nama kategorinya.
$sqlProduk = "SELECT p.id, p.name, p.sku, p.price,
                     p.discount_price, p.stock, p.status,
                     c.name AS nama_kategori
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              ORDER BY p.id DESC";

$resultProduk = mysqli_query($conn, $sqlProduk);
if (!$resultProduk) {
    die("Data produk tidak dapat ditampilkan. Periksa struktur tabel products.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Produk | Sembako-Kita</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f8f7;
            color: #263b3b;
        }

        header {
            padding: 18px 5%;
            background: #359FA0;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
        }

        h1 { margin-top: 0; font-size: 27px; }
        h2 { font-size: 21px; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field { margin-bottom: 4px; }
        .full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d5dfdd;
            border-radius: 7px;
            font: inherit;
            background: white;
        }

        textarea { min-height: 85px; resize: vertical; }

        button, .button {
            display: inline-block;
            padding: 10px 14px;
            border: none;
            border-radius: 7px;
            background: #359FA0;
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .orange { background: #FF8C52; }
        .gray { background: #687776; }
        .danger { background: #b9473c; }

        .notice {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .success { background: #e3f6ed; color: #216b48; }
        .failure { background: #fff0ed; color: #a63329; }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e7eeec;
            text-align: left;
            font-size: 14px;
        }

        th { background: #f1f8f7; }

        .status {
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 12px;
            background: #e3f6ed;
        }

        .inactive { background: #eee; color: #666; }
        .actions { display: flex; gap: 7px; align-items: center; }
        .actions form { margin: 0; }

        @media (max-width: 650px) {
            .form-grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
            .panel { padding: 17px; }
        }
    </style>
</head>
<body>

<header>
    <strong>Sembako-Kita | Kelola Produk</strong>
    <a href="dashboard.php">← Kembali ke Dashboard</a>
</header>

<main>
    <h1>Kelola Produk</h1>
    <p>Tambah, ubah, dan kelola produk toko Sembako-Kita.</p>

    <?php if ($pesan !== ""): ?>
        <div class="notice success"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice failure"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $editProduk ? "Edit Produk" : "Tambah Produk Baru" ?></h2>

        <?php if (count($daftarKategori) === 0): ?>
            <div class="notice failure">
                Belum ada kategori aktif. Tambahkan atau aktifkan kategori terlebih dahulu.
            </div>
        <?php else: ?>
            <form method="POST" action="produk.php">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION["admin_csrf_token"]) ?>">
                <input type="hidden" name="aksi" value="simpan">
                <input type="hidden" name="id"
                       value="<?= e($editProduk["id"] ?? 0) ?>">

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Nama Produk *</label>
                        <input id="name" name="name" required maxlength="150"
                               value="<?= e($editProduk["name"] ?? "") ?>">
                    </div>

                    <div class="field">
                        <label for="category_id">Kategori *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($daftarKategori as $kategori): ?>
                                <option value="<?= e($kategori["id"]) ?>"
                                    <?= (string)($editProduk["category_id"] ?? "") ===
                                        (string)$kategori["id"] ? "selected" : "" ?>>
                                    <?= e($kategori["name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="sku">Kode SKU</label>
                        <input id="sku" name="sku" maxlength="50"
                               value="<?= e($editProduk["sku"] ?? "") ?>">
                    </div>

                    <div class="field">
                        <label for="price">Harga Normal (Rp) *</label>
                        <input id="price" name="price" type="number"
                               min="0" step="0.01" required
                               value="<?= e($editProduk["price"] ?? "") ?>">
                    </div>

                    <div class="field">
                        <label for="discount_price">Harga Diskon (Rp)</label>
                        <input id="discount_price" name="discount_price"
                               type="number" min="0" step="0.01"
                               value="<?= e($editProduk["discount_price"] ?? "") ?>">
                    </div>

                    <div class="field">
                        <label for="stock">Stok *</label>
                        <input id="stock" name="stock" type="number"
                               min="0" step="1" required
                               value="<?= e($editProduk["stock"] ?? 0) ?>">
                    </div>

                    <div class="field">
                        <label for="weight">Berat (gram)</label>
                        <input id="weight" name="weight" type="number"
                               min="0" step="1"
                               value="<?= e($editProduk["weight"] ?? "") ?>">
                    </div>

                    <div class="field">
                        <label for="status">Status Produk</label>
                        <select id="status" name="status">
                            <option value="active"
                                <?= ($editProduk["status"] ?? "active") === "active"
                                    ? "selected" : "" ?>>
                                Aktif
                            </option>
                            <option value="inactive"
                                <?= ($editProduk["status"] ?? "") === "inactive"
                                    ? "selected" : "" ?>>
                                Tidak Aktif
                            </option>
                        </select>
                    </div>

                    <div class="field full">
                        <label for="description">Deskripsi Produk</label>
                        <textarea id="description" name="description"><?= e($editProduk["description"] ?? "") ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="shipping_info">Informasi Pengiriman</label>
                        <textarea id="shipping_info" name="shipping_info"><?= e($editProduk["shipping_info"] ?? "") ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="return_policy">Kebijakan Pengembalian</label>
                        <textarea id="return_policy" name="return_policy"><?= e($editProduk["return_policy"] ?? "") ?></textarea>
                    </div>
                </div>

                <p>
                    <button type="submit">
                        <?= $editProduk ? "Simpan Perubahan" : "Tambah Produk" ?>
                    </button>

                    <?php if ($editProduk): ?>
                        <a class="button gray" href="produk.php">Batal Edit</a>
                    <?php endif; ?>
                </p>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Daftar Produk</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>SKU</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($resultProduk) > 0): ?>
                    <?php while ($produk = mysqli_fetch_assoc($resultProduk)): ?>
                        <tr>
                            <td><?= e($produk["id"]) ?></td>
                            <td><?= e($produk["name"]) ?></td>
                            <td><?= e($produk["nama_kategori"] ?? "Tanpa kategori") ?></td>
                            <td><?= e($produk["sku"] ?? "-") ?></td>
                            <td>
                                <?= rupiah($produk["price"] ?? 0) ?>
                                <?php if ($produk["discount_price"] !== null): ?>
                                    <br><small>Diskon: <?= rupiah($produk["discount_price"]) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($produk["stock"]) ?></td>
                            <td>
                                <span class="status <?= $produk["status"] === "inactive" ? "inactive" : "" ?>">
                                    <?= $produk["status"] === "active" ? "Aktif" : "Tidak Aktif" ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button orange"
                                       href="produk.php?edit=<?= e($produk["id"]) ?>">
                                        Edit
                                    </a>

                                    <?php if ($produk["status"] === "active"): ?>
                                        <form method="POST" action="produk.php"
                                              onsubmit="return confirm('Nonaktifkan produk ini? Produk tidak akan dihapus permanen.');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= e($_SESSION["admin_csrf_token"]) ?>">
                                            <input type="hidden" name="aksi" value="nonaktifkan">
                                            <input type="hidden" name="id"
                                                   value="<?= e($produk["id"]) ?>">
                                            <button type="submit" class="danger">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Belum ada data produk.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>
