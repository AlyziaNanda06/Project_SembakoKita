
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: kategori.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, description
     FROM categories
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$kategori = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$kategori) {
    header("Location: kategori.php");
    exit;
}

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f4f8f8;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        h2 {
            color: #359fa0;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button, .back {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 18px;
            border: none;
            border-radius: 7px;
            background: #359fa0;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .back {
            background: #777;
            margin-left: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Edit Kategori</h2>

        <form action="../proses/edit-kategori.php" method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["admin_csrf_token"]) ?>">

            <input type="hidden" name="id"
                   value="<?= e($kategori["id"]) ?>">

            <label>Nama Kategori</label>
            <input type="text" name="name" maxlength="100"
                   value="<?= e($kategori["name"]) ?>" required>

            <label>Deskripsi</label>
            <textarea name="description"><?= e($kategori["description"]) ?></textarea>

            <button type="submit">Simpan Perubahan</button>
            <a href="kategori.php" class="back">Kembali</a>
        </form>
    </div>
</body>
</html>