
<?php
session_start();
require_once "../koneksi.php";

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

function buatSlugKategori($teks) {
    $teks = strtolower(trim($teks));
    $teks = preg_replace('/[^a-z0-9]+/', '-', $teks);
    return trim($teks, '-');
}

$pesan = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["admin_csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Muat ulang halaman.";
    } else {
        $aksi = $_POST["aksi"] ?? "";

        if ($aksi === "tambah") {
            $nama = trim($_POST["name"] ?? "");
            $deskripsi = trim($_POST["description"] ?? "");

            if ($nama === "" || mb_strlen($nama) > 100) {
                $error = "Nama kategori wajib diisi, maksimal 100 karakter.";
            } else {
                $slug = buatSlugKategori($nama);

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO categories (name, slug, description, status)
                     VALUES (?, ?, ?, 1)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sss",
                    $nama,
                    $slug,
                    $deskripsi
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: kategori.php?berhasil=1");
                    exit;
                }

                $error = "Kategori gagal ditambahkan. Periksa apakah nama atau slug sudah digunakan.";
                mysqli_stmt_close($stmt);
            }
        } elseif ($aksi === "status") {
            $id = (int) ($_POST["id"] ?? 0);
            $status = (int) ($_POST["status"] ?? -1);

            if ($id > 0 && in_array($status, [0, 1], true)) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE categories SET status = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param($stmt, "ii", $status, $id);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: kategori.php?status_diubah=1");
                    exit;
                }

                $error = "Status kategori gagal diubah.";
                mysqli_stmt_close($stmt);
            } else {
                $error = "Data kategori tidak valid.";
            }
        }
    }
}

if (isset($_GET["berhasil"])) {
    $pesan = "Kategori berhasil ditambahkan.";
} elseif (isset($_GET["status_diubah"])) {
    $pesan = "Status kategori berhasil diperbarui.";
}

$resultKategori = mysqli_query(
    $conn,
    "SELECT c.id, c.name, c.slug, c.description, c.status,
            COUNT(p.id) AS jumlah_produk
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.slug, c.description, c.status
     ORDER BY c.id DESC"
);

if (!$resultKategori) {
    die("Data kategori gagal ditampilkan. Periksa tabel categories.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Kategori | Sembako-Kita</title>

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
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 24px;
            margin-bottom: 24px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
        }

        h1 { margin-top: 0; }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5dfdd;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 85px;
            resize: vertical;
        }

        button, .button {
            display: inline-block;
            padding: 10px 14px;
            border: none;
            border-radius: 7px;
            background: #359FA0;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
        }

        .orange { background: #FF8C52; }
        .gray { background: #687776; }

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
            min-width: 700px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #e7eeec;
            text-align: left;
            font-size: 14px;
        }

        th { background: #f1f8f7; }

        .status {
            padding: 5px 8px;
            border-radius: 5px;
            background: #e3f6ed;
            color: #216b48;
            font-size: 12px;
        }

        .inactive {
            background: #eee;
            color: #666;
        }

        .inline-form { margin: 0; }

        @media (max-width: 600px) {
            .panel { padding: 17px; }
        }
    </style>
</head>
<body>

<header>
    <strong>Sembako-Kita | Kelola Kategori</strong>
    <a href="dashboard.php">← Kembali ke Dashboard</a>
</header>

<main>
    <h1>Kelola Kategori</h1>
    <p>Atur kategori untuk mengelompokkan produk toko.</p>

    <?php if ($pesan !== ""): ?>
        <div class="notice success"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice failure"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Tambah Kategori Baru</h2>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["admin_csrf_token"]) ?>">
            <input type="hidden" name="aksi" value="tambah">

            <label for="name">Nama Kategori *</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                placeholder="Contoh: Bahan Pokok Utama"
                required
            >

            <label for="description">Deskripsi Kategori</label>
            <textarea
                id="description"
                name="description"
                placeholder="Jelaskan kategori produk ini..."
            ></textarea>

            <p>
                <button type="submit">Tambah Kategori</button>
            </p>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Kategori</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Slug</th>
                        <th>Jumlah Produk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($resultKategori) > 0): ?>
                        <?php while ($kategori = mysqli_fetch_assoc($resultKategori)): ?>
                            <tr>
                                <td><?= e($kategori["id"]) ?></td>
                                <td>
                                    <strong><?= e($kategori["name"]) ?></strong>
                                    <?php if (!empty($kategori["description"])): ?>
                                        <br>
                                        <small><?= e($kategori["description"]) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($kategori["slug"] ?? "-") ?></td>
                                <td><?= e($kategori["jumlah_produk"]) ?></td>
                                <td>
                                    <span class="status <?= (int)$kategori["status"] === 1 ? "" : "inactive" ?>">
                                        <?= (int)$kategori["status"] === 1 ? "Aktif" : "Tidak Aktif" ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="inline-form"
                                          onsubmit="return confirm('Ubah status kategori ini?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION["admin_csrf_token"]) ?>">
                                        <input type="hidden" name="aksi" value="status">
                                        <input type="hidden" name="id"
                                               value="<?= e($kategori["id"]) ?>">
                                        <input type="hidden" name="status"
                                               value="<?= (int)$kategori["status"] === 1 ? 0 : 1 ?>">

                                        <button type="submit"
                                                class="<?= (int)$kategori["status"] === 1 ? "orange" : "gray" ?>">
                                            <?= (int)$kategori["status"] === 1 ? "Nonaktifkan" : "Aktifkan" ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Belum ada kategori.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>
