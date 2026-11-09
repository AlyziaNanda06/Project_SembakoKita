
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
} elseif (isset($_GET["edit_berhasil"])) {
    $pesan = "Kategori berhasil diperbarui.";
} elseif (isset($_GET["hapus_berhasil"])) {
    $pesan = "Kategori berhasil dihapus.";
} elseif (isset($_GET["kategori_dipakai"])) {
    $error = "Kategori tidak bisa dihapus karena masih memiliki produk.";
} elseif (isset($_GET["hapus_gagal"])) {
    $error = "Kategori gagal dihapus atau tidak ditemukan.";
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

$totalKategori = mysqli_num_rows($resultKategori);
$kategoriAktif = 0;
$kategoriNonaktif = 0;
$totalProdukKategori = 0;

$dataKategori = [];

while ($kategori = mysqli_fetch_assoc($resultKategori)) {
    $dataKategori[] = $kategori;

    if ((int)$kategori["status"] === 1) {
        $kategoriAktif++;
    } else {
        $kategoriNonaktif++;
    }

    $totalProdukKategori += (int)$kategori["jumlah_produk"];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Kategori | Sembako-Kita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Nunito Sans", Arial, sans-serif;
            background: #F1F8F6;
            color: #263D3A;
        }

        a {
            text-decoration: none;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #187D79;
            color: white;
            padding: 27px 18px;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .brand {
            padding: 0 12px 30px;
            border-bottom: 1px solid rgba(255,255,255,.18);
        }

        .brand h2 {
            margin: 0;
            font-size: 23px;
            font-weight: 900;
        }

        .brand span {
            display: block;
            margin-top: 5px;
            color: #C5EAE4;
            font-size: 12px;
        }

        .menu-title {
            margin: 27px 12px 12px;
            color: #B7E0DB;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.3px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            margin-bottom: 7px;
            color: #E5F5F2;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,.13);
            transform: translateX(3px);
        }

        .menu a.active {
            background: #FFFFFF;
            color: #187D79;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
        }

        .menu-icon {
            width: 23px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px 12px 0;
            border-top: 1px solid rgba(255,255,255,.18);
            font-size: 12px;
            color: #C5EAE4;
        }

        .main {
            margin-left: 250px;
            padding: 30px 35px;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
        }

        .topbar small {
            color: #7D9691;
            font-size: 13px;
        }

        .admin-label {
            background: white;
            padding: 10px 16px;
            border-radius: 30px;
            color: #187D79;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 3px 12px rgba(35,90,80,.05);
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0 0 7px;
            font-size: 29px;
            font-weight: 900;
            color: #263D3A;
        }

        .page-heading p {
            margin: 0;
            color: #81938F;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 21px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(35,90,80,.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 24px;
            flex-shrink: 0;
        }

        .icon-purple {
            background: #EFEDFF;
        }

        .icon-green {
            background: #E2F7ED;
        }

        .icon-orange {
            background: #FFF0E8;
        }

        .stat-card small {
            color: #8A9B97;
            font-size: 12px;
            font-weight: 700;
        }

        .stat-card h2 {
            margin: 3px 0 0;
            font-size: 25px;
            font-weight: 900;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(35,90,80,.05);
            min-width: 0;
        }

        .panel-heading {
            margin-bottom: 20px;
        }

        .panel-heading h2 {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 900;
        }

        .panel-heading p {
            margin: 0;
            color: #8A9B97;
            font-size: 12px;
        }

        label {
            display: block;
            margin: 17px 0 8px;
            font-size: 13px;
            font-weight: 800;
            color: #425A55;
        }

        input, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #DCE9E5;
            border-radius: 10px;
            outline: none;
            font: inherit;
            font-size: 13px;
            color: #263D3A;
            background: #FBFDFC;
            transition: .2s;
        }

        input:focus, textarea:focus {
            border-color: #25AFA5;
            box-shadow: 0 0 0 3px rgba(37,175,165,.12);
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 14px;
            border: none;
            border-radius: 9px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(.96);
        }

        .btn-primary {
            background: #25AFA5;
            color: white;
        }

        .btn-orange {
            background: #FF875C;
            color: white;
        }

        .btn-purple {
            background: #8B83E8;
            color: white;
        }

        .btn-gray {
            background: #EDF1F0;
            color: #60716D;
        }

        .btn-submit {
            width: 100%;
            margin-top: 18px;
            padding: 13px;
            font-size: 13px;
        }

        .notice {
            padding: 13px 17px;
            border-radius: 11px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 700;
        }

        .success {
            background: #E3F7EB;
            color: #23734B;
            border: 1px solid #C7EBD6;
        }

        .failure {
            background: #FFF0ED;
            color: #A63329;
            border: 1px solid #F7D5CE;
        }

        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .table-heading h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
        }

        .total-label {
            background: #E5F7F3;
            color: #187D79;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        th {
            padding: 13px 12px;
            background: #F1F8F6;
            color: #69817B;
            font-size: 11px;
            font-weight: 900;
            text-align: left;
            white-space: nowrap;
        }

        th:first-child {
            border-radius: 9px 0 0 9px;
        }

        th:last-child {
            border-radius: 0 9px 9px 0;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #EDF2F0;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #FAFCFB;
        }

        .category-name {
            font-weight: 900;
            color: #304943;
            font-size: 13px;
        }

        .description {
            display: block;
            margin-top: 4px;
            max-width: 210px;
            color: #94A39F;
            line-height: 1.5;
        }

        .slug {
            color: #718681;
            background: #F2F6F5;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
        }

        .product-count {
            display: inline-block;
            min-width: 28px;
            padding: 5px 9px;
            text-align: center;
            border-radius: 8px;
            background: #F0EDFF;
            color: #7168CC;
            font-weight: 900;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .status-active {
            background: #E3F7EB;
            color: #25804E;
        }

        .status-inactive {
            background: #FFF0ED;
            color: #C45B4F;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: #91A19D;
        }

        .empty-icon {
            display: block;
            font-size: 30px;
            margin-bottom: 8px;
        }

        @media (max-width: 1100px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 850px) {
            .sidebar {
                width: 75px;
                padding: 20px 10px;
            }

            .brand {
                padding: 0 0 20px;
                text-align: center;
            }

            .brand h2 {
                font-size: 12px;
            }

            .brand span,
            .menu-title,
            .menu-text,
            .sidebar-bottom {
                display: none;
            }

            .menu a {
                justify-content: center;
                padding: 13px 5px;
            }

            .menu-icon {
                font-size: 20px;
            }

            .main {
                margin-left: 75px;
                padding: 25px 20px;
            }
        }

        @media (max-width: 600px) {
            .main {
                padding: 20px 13px;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .panel {
                padding: 17px;
            }

            .topbar {
                align-items: flex-start;
            }

            .admin-label {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <h2>🛒 Sembako-Kita</h2>
        <span>Admin Management Panel</span>
    </div>

    <div class="menu-title">MENU UTAMA</div>

    <nav class="menu">
        <a href="dashboard.php">
            <span class="menu-icon">▦</span>
            <span class="menu-text">Dashboard</span>
        </a>

        <a href="produk.php">
            <span class="menu-icon">📦</span>
            <span class="menu-text">Kelola Produk</span>
        </a>

        <a href="kategori.php" class="active">
            <span class="menu-icon">🏷️</span>
            <span class="menu-text">Kelola Kategori</span>
        </a>

        <a href="pesanan.php">
            <span class="menu-icon">🧾</span>
            <span class="menu-text">Kelola Pesanan</span>
        </a>

        <a href="../index.php" target="_blank">
            <span class="menu-icon">🌐</span>
            <span class="menu-text">Lihat Website</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        Sembako-Kita Admin<br>
        Kelola toko dengan mudah.
    </div>
</aside>

<main class="main">

    <div class="topbar">
        <small>Admin Panel / Kelola Kategori</small>
        <div class="admin-label">⚙️ Administrator</div>
    </div>

    <div class="page-heading">
        <h1>Kelola Kategori 🏷️</h1>
        <p>Atur dan kelompokkan produk berdasarkan kategori toko.</p>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="notice success">
            ✓ <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice failure">
            ⚠ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-icon icon-purple">🏷️</div>
            <div>
                <small>Total Kategori</small>
                <h2><?= $totalKategori ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-green">✓</div>
            <div>
                <small>Kategori Aktif</small>
                <h2><?= $kategoriAktif ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orange">📦</div>
            <div>
                <small>Total Produk dalam Kategori</small>
                <h2><?= $totalProdukKategori ?></h2>
            </div>
        </div>
    </section>

    <section class="content-grid">

        <div class="panel">
            <div class="panel-heading">
                <h2>✨ Tambah Kategori</h2>
                <p>Tambahkan kelompok produk baru ke toko.</p>
            </div>

            <form method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                >

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

                <button type="submit" class="btn btn-primary btn-submit">
                    + Tambah Kategori
                </button>
            </form>
        </div>

        <div class="panel">
            <div class="table-heading">
                <h2>Daftar Kategori</h2>
                <span class="total-label">
                    <?= $totalKategori ?> Kategori
                </span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kategori</th>
                            <th>Slug</th>
                            <th>Produk</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($totalKategori > 0): ?>
                            <?php foreach ($dataKategori as $kategori): ?>
                                <?php
                                    $aktif = (int)$kategori["status"] === 1;
                                ?>

                                <tr>
                                    <td>#<?= (int)$kategori["id"] ?></td>

                                    <td>
                                        <div class="category-name">
                                            <?= e($kategori["name"]) ?>
                                        </div>

                                        <?php if (!empty($kategori["description"])): ?>
                                            <small class="description">
                                                <?= e($kategori["description"]) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="slug">
                                            <?= e($kategori["slug"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="product-count">
                                            <?= (int)$kategori["jumlah_produk"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status <?= $aktif ? "status-active" : "status-inactive" ?>">
                                            <?= $aktif ? "● Aktif" : "● Tidak Aktif" ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <a
                                                href="edit-kategori.php?id=<?= (int)$kategori["id"] ?>"
                                                class="btn btn-purple"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                action="../proses/hapus-kategori.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus kategori ini?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$kategori["id"] ?>"
                                                >

                                                <button type="submit" class="btn btn-gray">
                                                    Hapus
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Ubah status kategori ini?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                                >

                                                <input type="hidden" name="aksi" value="status">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$kategori["id"] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="<?= $aktif ? 0 : 1 ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn <?= $aktif ? "btn-orange" : "btn-primary" ?>"
                                                >
                                                    <?= $aktif ? "Nonaktifkan" : "Aktifkan" ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty">
                                        <span class="empty-icon">🏷️</span>
                                        Belum ada kategori yang ditambahkan.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

</main>

</body>
</html>