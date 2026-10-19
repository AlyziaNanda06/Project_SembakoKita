
<?php
session_start();
require_once "koneksi.php";

// Jika pengguna sudah login, arahkan ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Membuat token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";

// Memproses form login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validasi token keamanan
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } elseif (!is_string($password) || $password === '') {
            $error = "Password wajib diisi.";
        } else {
            // Cari akun berdasarkan email
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, name, email, password
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            if (!$stmt) {
                $error = "Terjadi kesalahan sistem. Coba lagi nanti.";
            } else {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result(
                    $stmt,
                    $id,
                    $name,
                    $db_email,
                    $hashed_password
                );

                // Verifikasi password
                if (
                    mysqli_stmt_fetch($stmt) &&
                    password_verify($password, $hashed_password)
                ) {
                    mysqli_stmt_close($stmt);

                    // Mencegah session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $db_email;

                    // Perbarui token setelah login
                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Email atau password salah.";
                    mysqli_stmt_close($stmt);
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
    <title>Login - Sembako-Kita</title>
    <link rel="stylesheet" href="assets/css/style.css">

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

        .login-container {
            width: 90%;
            max-width: 420px;
            margin: 65px auto;
            padding: 30px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .login-container h1 {
            margin-bottom: 8px;
            text-align: center;
            color: #359fa0;
        }

        .login-container p {
            text-align: center;
            color: #687878;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd8d7;
            border-radius: 7px;
            font-size: 15px;
        }

        .form-group input:focus {
            outline: 2px solid #8ad6d1;
            border-color: #359fa0;
        }

        .btn-login {
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

        .btn-login:hover {
            background: #287f80;
        }

        .error {
            padding: 12px;
            margin-bottom: 16px;
            background: #fff0f0;
            color: #b42318;
            border-radius: 7px;
            font-size: 14px;
        }

        .success {
            padding: 12px;
            margin-bottom: 16px;
            background: #eaf8ef;
            color: #187443;
            border-radius: 7px;
            font-size: 14px;
        }

        .register-link {
            margin-top: 20px;
            text-align: center;
        }

        .register-link a {
            color: #287f80;
            font-weight: bold;
            text-decoration: none;
        }

        .back-link {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #687878;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Login</h1>
        <p>Selamat datang kembali di Sembako-Kita!</p>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success">
                Registrasi berhasil. Silakan login dengan akun kamu.
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
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
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Masukkan email"
                    maxlength="150"
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
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                Login
            </button>
        </form>

        <div class="register-link">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </div>

        <a href="index.php" class="back-link">
            &larr; Kembali ke Beranda
        </a>
    </div>

</body>
</html>
