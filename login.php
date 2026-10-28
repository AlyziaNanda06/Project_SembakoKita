
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
require_once __DIR__ . '/koneksi.php';

$error = '';
$email = '';

if (isset($_GET['daftar']) && $_GET['daftar'] === 'berhasil') {
    $pesan = 'Pendaftaran berhasil! Silakan login dengan akun kamu.';
} else {
    $pesan = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Terjadi kesalahan saat menyiapkan login.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result(
                    $stmt,
                    $user_id,
                    $user_name,
                    $user_email,
                    $password_hash,
                    $role
                );

                mysqli_stmt_fetch($stmt);

                if (password_verify($password, $password_hash)) {
                    if ($role !== 'customer') {
                        $error = 'Gunakan halaman login admin untuk akun admin.';
                    } else {
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = $user_name;
                        $_SESSION['user_email'] = $user_email;

                        mysqli_stmt_close($stmt);

                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $error = 'Email atau password salah.';
                }
            } else {
                $error = 'Email atau password salah.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SembakoKita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4fbfa;
            color: #333;
        }

        header {
            background: #359FA0;
            color: white;
            padding: 20px 8%;
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            width: 90%;
            max-width: 420px;
            margin: 45px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: #359FA0;
            margin-top: 0;
        }

        .deskripsi {
            text-align: center;
            color: #666;
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #8AD6D1;
            border-color: #359FA0;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            background: #359FA0;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #287f80;
        }

        .pesan {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .sukses {
            background: #e0f5ef;
            color: #176b50;
        }

        .error {
            background: #ffe5e5;
            color: #a32121;
        }

        .link {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #359FA0;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header>SembakoKita</header>

    <main class="container">
        <h2>Login Akun</h2>

        <p class="deskripsi">
            Masuk untuk melanjutkan belanja.
        </p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan sukses">
                <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="pesan error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Login</button>
        </form>

        <p class="link">
            Belum punya akun?
            <a href="register.php">Daftar di sini</a>
        </p>

        <p class="link">
            <a href="index.php">Kembali ke Beranda</a>
        </p>
    </main>
</body>
</html>
