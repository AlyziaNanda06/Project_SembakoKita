<?php
session_start();
require_once "koneksi.php";

// Jika sudah login, kembali ke beranda
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = "Permintaan tidak valid. Muat ulang halaman.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($name === '' || strlen($name) > 100) {
            $error = "Nama wajib diisi (maksimal 100 karakter).";
        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150
        ) {
            $error = "Format email tidak valid.";
        } elseif (
            !is_string($password) ||
            strlen($password) < 8 ||
            strlen($password) > 72 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            $error = "Password minimal 8 karakter dan harus berisi huruf serta angka.";
        } elseif ($password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok.";
        } else {
            // Cek apakah email sudah terdaftar
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            if (!$stmt) {
                $error = "Terjadi kesalahan sistem.";
            } else {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = "Email sudah terdaftar.";
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);

                    // Hash password sebelum disimpan
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $insert = mysqli_prepare(
                        $conn,
                        "INSERT INTO users (name, email, password)
                         VALUES (?, ?, ?)"
                    );

                    if (!$insert) {
                        $error = "Gagal memproses pendaftaran.";
                    } else {
                        mysqli_stmt_bind_param(
                            $insert,
                            "sss",
                            $name,
                            $email,
                            $hashed_password
                        );

                        if (mysqli_stmt_execute($insert)) {
                            mysqli_stmt_close($insert);
                            header("Location: login.php?registered=1");
                            exit;
                        } else {
                            $error = "Pendaftaran gagal. Coba lagi.";
                            mysqli_stmt_close($insert);
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f8f7;
            color: #263b3b;
        }

        .register-container {
            width: 90%;
            max-width: 440px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #359fa0;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #687878;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd8d7;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #8ad6d1;
            border-color: #359fa0;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #359fa0;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #287f80;
        }

        .error {
            padding: 12px;
            margin-bottom: 17px;
            border-radius: 7px;
            background: #fff0f0;
            color: #b42318;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #287f80;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h1>Buat Akun</h1>
    <p class="subtitle">Gabung bersama Sembako-Kita!</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Masukkan nama lengkap"
                autocomplete="name"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Masukkan email"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                maxlength="72"
                placeholder="Minimal 8 karakter, huruf dan angka"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                maxlength="72"
                placeholder="Ulangi password"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit">Daftar Sekarang</button>
    </form>

    <div class="login-link">
        Sudah punya akun? <a href="login.php">Login</a>
    </div>
</div>

</body>
</html>