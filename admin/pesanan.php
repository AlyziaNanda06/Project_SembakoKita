```php
<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Token keamanan form
if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

// Mengambil pilihan ENUM langsung dari database
function ambilPilihanEnum($conn, $kolom) {
    $kolomDiizinkan = ["status", "payment_status"];

    if (!in_array($kolom, $kolomDiizinkan, true)) {
        return [];
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$kolom'");

    if (!$result || mysqli_num_rows($result) === 0) {
        return [];
    }

    $data = mysqli_fetch_assoc($result);
    $tipe = $data["Type"] ?? "";

    if (preg_match("/^enum\((.*)\)$/i", $tipe, $matches)) {
        return str_getcsv($matches[1], ",", "'");
    }

    return [];
}

$statusPilihan = ambilPilihanEnum($conn, "status");
$pembayaranPilihan = ambilPilihanEnum($conn, "payment_status");

$pesan = "";
$error = "";

// Proses perubahan status
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["admin_csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $jenis = $_POST["jenis"] ?? "";
        $nilai = $_POST["nilai"] ?? "";

        if (!$id || $id < 1) {
            $error = "ID pesanan tidak valid.";
        } elseif ($jenis === "status" && in_array($nilai, $statusPilihan, true)) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE orders SET status = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $nilai, $id);

            if (mysqli_stmt_execute($stmt)) {
                $pesan = "Status pesanan berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui status pesanan.";
            }

            mysqli_stmt_close($stmt);
        } elseif (
            $jenis === "payment_status" &&
            in_array($nilai, $pembayaranPilihan, true)
        ) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE orders SET payment_status = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $nilai, $id);

            if (mysqli_stmt_execute($stmt)) {
                $pesan = "Status pembayaran berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui status pembayaran.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Data status yang dipilih tidak valid.";
        }
    }
}

// Pencarian pesanan
$cari = trim($_GET["cari"] ?? "");

// Statistik pesanan
$statistik = [
    "total" => 0,
    "pending" => 0,
    "diproses" => 0,
    "selesai" => 0
];

$sqlStatistik = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'diproses') AS diproses,
        SUM(status = 'selesai') AS selesai
    FROM orders
";

$resultStatistik = mysqli_query($conn, $sqlStatistik);

if ($resultStatistik) {
    $statistik = array_merge(
        $statistik,
        mysqli_fetch_assoc($resultStatistik) ?: []
    );
}

// Ambil data pesanan
$sql = "
    SELECT
        id,
        user_id,
        invoice_number,
        total_price,
        shipping_cost,
        grand_total,
        status,
        payment_status,
        created_at
    FROM orders
";

if ($cari !== "") {
    $sql .= "
        WHERE invoice_number LIKE ?
        OR CAST(id AS CHAR) LIKE ?
    ";
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($cari !== "") {
    $kataCari = "%" . $cari . "%";
    mysqli_stmt_bind_param($stmt, "ss", $kataCari, $kataCari);
}

mysqli_stmt_execute($stmt);
$resultPesanan = mysqli_stmt_get_result($stmt);
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
            font-family: Arial, sans-serif;
            background: #f4f7f7;
            color: #263b3b;
        }

        header {
            background: #359fa0;
            color: white;
            padding: 20px 5%;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        main {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h1 {
            margin-top: 0;
        }

        .muted {
            color: #718181;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .stat-card p {
            margin: 0 0 10px;
            color: #718181;
        }

        .stat-card strong {
            font-size: 26px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input[type="text"] {
            padding: 11px 12px;
            border: 1px solid #d5dddd;
            border-radius: 7px;
            flex: 1;
            min-width: 180px;
        }

        button, .btn {
            display: inline-block;
            border: none;
            padding: 10px 13px;
            border-radius: 7px;
            background: #359fa0;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-detail {
            background: #287c7d;
            white-space: nowrap;
        }

        .btn-secondary {
            background: #718181;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            background: #e1f5e8;
            color: #24653b;
        }

        .alert-error {
            background: #fde8e7;
            color: #9b2929;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid #edf0f0;
            vertical-align: top;
        }

        th {
            background: #f8fbfb;
            white-space: nowrap;
        }

        select {
            width: 145px;
            padding: 8px;
            margin-bottom: 7px;
            border: 1px solid #d5dddd;
            border-radius: 6px;
            background: white;
        }

        .status-form {
            margin: 0 0 10px;
        }

        .status-form button {
            display: block;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #718181;
        }

        @media (max-width: 800px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            main {
                padding: 0 12px;
            }

            .card {
                padding: 15px;
            }
        }

        @media (max-width: 420px) {
            .stats {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .stat-card {
                padding: 13px;
            }

            .stat-card strong {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>Admin Sembako-Kita</strong>

    <span style="float: right;">
        <a href="dashboard.php">Kembali ke Dashboard</a>
    </span>
</header>

<main>
    <h1>Kelola Pesanan</h1>
    <p class="muted">Lihat rincian transaksi dan kelola status pesanan pelanggan.</p>

    <?php if ($pesan !== ""): ?>
        <div class="alert"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <p>Total Pesanan</p>
            <strong><?= (int)$statistik["total"] ?></strong>
        </div>

        <div class="stat-card">
            <p>Menunggu</p>
            <strong><?= (int)$statistik["pending"] ?></strong>
        </div>

        <div class="stat-card">
            <p>Diproses</p>
            <strong><?= (int)$statistik["diproses"] ?></strong>
        </div>

        <div class="stat-card">
            <p>Selesai</p>
            <strong><?= (int)$statistik["selesai"] ?></strong>
        </div>
    </section>

    <section class="card">
        <h2>Cari Pesanan</h2>

        <form method="GET" class="search-form">
            <input
                type="text"
                name="cari"
                placeholder="Masukkan nomor invoice atau ID pesanan"
                value="<?= e($cari) ?>"
            >

            <button type="submit">Cari</button>

            <a href="pesanan.php" class="btn btn-secondary">Reset</a>
        </form>
    </section>

    <section class="card">
        <h2>Daftar Pesanan</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>ID Pelanggan</th>
                        <th>Total Produk</th>
                        <th>Ongkir</th>
                        <th>Total Bayar</th>
                        <th>Tanggal</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($resultPesanan) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($resultPesanan)): ?>
                        <tr>
                            <td><?= (int)$row["id"] ?></td>

                            <td>
                                <?= e($row["invoice_number"] ?: "ORD-" . $row["id"]) ?>
                            </td>

                            <td>
                                <?= $row["user_id"] !== null
                                    ? (int)$row["user_id"]
                                    : "-" ?>
                            </td>

                            <td><?= rupiah($row["total_price"]) ?></td>

                            <td><?= rupiah($row["shipping_cost"]) ?></td>

                            <td><strong><?= rupiah($row["grand_total"]) ?></strong></td>

                            <td><?= e($row["created_at"]) ?></td>

                            <td>
                                <form method="POST" class="status-form">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="jenis"
                                        value="status"
                                    >

                                    <select name="nilai" aria-label="Status pesanan">
                                        <?php foreach ($statusPilihan as $pilihan): ?>
                                            <option
                                                value="<?= e($pilihan) ?>"
                                                <?= $row["status"] === $pilihan ? "selected" : "" ?>
                                            >
                                                <?= e(ucfirst(str_replace("_", " ", $pilihan))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit">Simpan</button>
                                </form>
                            </td>

                            <td>
                                <form method="POST" class="status-form">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["admin_csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="jenis"
                                        value="payment_status"
                                    >

                                    <select name="nilai" aria-label="Status pembayaran">
                                        <?php foreach ($pembayaranPilihan as $pilihan): ?>
                                            <option
                                                value="<?= e($pilihan) ?>"
                                                <?= $row["payment_status"] === $pilihan ? "selected" : "" ?>
                                            >
                                                <?= e(ucfirst(str_replace("_", " ", $pilihan))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit">Simpan</button>
                                </form>
                            </td>

                            <td>
                                <a
                                    class="btn btn-detail"
                                    href="detail-pesanan.php?id=<?= (int)$row["id"] ?>"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty">
                            Belum ada pesanan atau pesanan tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <a href="dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
</main>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
```