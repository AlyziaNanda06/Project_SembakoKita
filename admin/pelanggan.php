```php
<?php
session_start();
require_once "../koneksi.php";

// Pastikan admin sudah login
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Pencarian pelanggan
$cari = trim($_GET["cari"] ?? "");

// Filter role
$role = $_GET["role"] ?? "customer";

if (!in_array($role, ["customer", "admin", "semua"], true)) {
    $role = "customer";
}

// Statistik jumlah akun
$sqlStatistik = "
    SELECT
        COUNT(*) AS total,
        SUM(role = 'customer') AS pelanggan,
        SUM(role = 'admin') AS admin
    FROM users
";

$resultStatistik = mysqli_query($conn, $sqlStatistik);

$statistik = [
    "total" => 0,
    "pelanggan" => 0,
    "admin" => 0
];

if ($resultStatistik) {
    $statistik = array_merge(
        $statistik,
        mysqli_fetch_assoc($resultStatistik) ?: []
    );
}

// Query daftar pengguna
$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        address,
        city,
        province,
        postal_code,
        role,
        created_at
    FROM users
    WHERE 1 = 1
";

$types = "";
$params = [];

if ($role !== "semua") {
    $sql .= " AND role = ?";
    $types .= "s";
    $params[] = $role;
}

if ($cari !== "") {
    $sql .= "
        AND (
            name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $types .= "sss";

    $kataCari = "%" . $cari . "%";

    $params[] = $kataCari;
    $params[] = $kataCari;
    $params[] = $kataCari;
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    exit("Query pelanggan gagal disiapkan.");
}

if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$resultPelanggan = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Kelola Pelanggan - Sembako-Kita</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .stat-card p {
            margin: 0 0 12px;
            color: #718181;
        }

        .stat-card strong {
            font-size: 27px;
            color: #258b8c;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
            margin-bottom: 22px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        input,
        select {
            padding: 11px 12px;
            border: 1px solid #d5dddd;
            border-radius: 7px;
            font-size: 14px;
        }

        input {
            flex: 1;
            min-width: 220px;
        }

        button,
        .btn {
            display: inline-block;
            border: none;
            padding: 11px 15px;
            border-radius: 7px;
            background: #359fa0;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary {
            background: #718181;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 11px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #edf0f0;
        }

        th {
            background: #f8fbfb;
            white-space: nowrap;
        }

        .name {
            font-weight: bold;
        }

        .email {
            color: #718181;
            font-size: 13px;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .role {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #e0f4f1;
            color: #267a72;
            font-size: 12px;
        }

        .role-admin {
            background: #fff0c5;
            color: #805b13;
        }

        .empty {
            text-align: center;
            padding: 28px;
            color: #718181;
        }

        .address {
            max-width: 260px;
            white-space: normal;
            line-height: 1.5;
        }

        @media (max-width: 700px) {
            main {
                padding: 0 12px;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .card {
                padding: 15px;
            }

            header {
                padding: 17px 12px;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>Admin Sembako-Kita</strong>

    <a href="dashboard.php">← Dashboard</a>
</header>

<main>
    <h1>Kelola Pelanggan</h1>

    <p class="muted">
        Lihat informasi akun dan data pelanggan yang terdaftar.
    </p>

    <section class="stats">
        <div class="stat-card">
            <p>Total Semua Akun</p>
            <strong><?= (int)$statistik["total"] ?></strong>
        </div>

        <div class="stat-card">
            <p>Total Pelanggan</p>
            <strong><?= (int)$statistik["pelanggan"] ?></strong>
        </div>

        <div class="stat-card">
            <p>Total Admin</p>
            <strong><?= (int)$statistik["admin"] ?></strong>
        </div>
    </section>

    <section class="card">
        <h2>Cari dan Filter Pelanggan</h2>

        <form method="GET" class="filter-form">
            <input
                type="text"
                name="cari"
                placeholder="Cari nama, email, atau nomor HP"
                value="<?= e($cari) ?>"
            >

            <select name="role">
                <option
                    value="customer"
                    <?= $role === "customer" ? "selected" : "" ?>
                >
                    Pelanggan
                </option>

                <option
                    value="admin"
                    <?= $role === "admin" ? "selected" : "" ?>
                >
                    Admin
                </option>

                <option
                    value="semua"
                    <?= $role === "semua" ? "selected" : "" ?>
                >
                    Semua Akun
                </option>
            </select>

            <button type="submit">Cari</button>

            <a href="pelanggan.php" class="btn btn-secondary">
                Reset
            </a>
        </form>
    </section>

    <section class="card">
        <h2>Daftar Akun</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama dan Email</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($resultPelanggan) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($resultPelanggan)): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <div class="name">
                                    <?= e($row["name"]) ?>
                                </div>

                                <div class="email">
                                    <?= e($row["email"]) ?>
                                </div>
                            </td>

                            <td>
                                <?= e($row["phone"] ?: "-") ?>
                            </td>

                            <td class="address">
                                <?php
                                $bagianAlamat = array_filter([
                                    $row["address"],
                                    $row["city"],
                                    $row["province"],
                                    $row["postal_code"]
                                ], function ($item) {
                                    return trim((string)$item) !== "";
                                });

                                echo $bagianAlamat
                                    ? e(implode(", ", $bagianAlamat))
                                    : "-";
                                ?>
                            </td>

                            <td>
                                <span class="role <?= $row["role"] === "admin" ? "role-admin" : "" ?>">
                                    <?= e(ucfirst($row["role"] ?? "customer")) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($row["created_at"]) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">
                            Tidak ada akun yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <a href="dashboard.php" class="btn btn-secondary">
        ← Kembali ke Dashboard
    </a>
</main>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
```