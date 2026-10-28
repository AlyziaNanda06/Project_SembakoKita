
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
require_once __DIR__ . '/koneksi.php';

$pesan = '';
$berhasil = false;
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' ||
        $password === '' || $konfirmasi === '') {
        $pesan = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $pesan = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $pesan = 'Konfirmasi password tidak sama.';
    } else {
        // Periksa apakah email sudah terdaftar.
        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if (!$cek) {
            $pesan = 'Gagal memeriksa email: '
                . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($cek, 's', $email);
            mysqli_stmt_execute($cek);
            mysqli_stmt_store_result($cek);

            if (mysqli_stmt_num_rows($cek) > 0) {
                $pesan = 'Email sudah terdaftar. Gunakan email lain.';
            }

            mysqli_stmt_close($cek);
        }

        // Simpan akun baru jika email belum digunakan.
        if ($pesan === '') {
            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'customer')"
            );

            if (!$stmt) {
                $pesan = 'Gagal menyiapkan pendaftaran: '
                    . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    'sss',
                    $nama,
                    $email,
                    $hash
                );

                if (mysqli_stmt_execute($stmt)) {
                    $berhasil = true;
                    $pesan = 'Pendaftaran berhasil! Akun kamu sudah tersimpan.';
                    $nama = '';
                    $email = '';
                } else {
                    $pesan = 'Pendaftaran gagal: '
                        . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
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
    <title>Daftar Akun - SembakoKita</title>

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
            max-width: 440px;
            margin: 40px auto;
            padding: 28px;
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
            overflow-wrap: anywhere;
        }

        .sukses {
            background: #e0f5ef;
            color: #176b50;
        }

        .error {
            background: #ffe5e5;
            color: #a32121;
        }

        .login {
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
        <h2>Buat Akun Baru</h2>

        <p class="deskripsi">
            Daftar untuk mulai berbelanja di SembakoKita.
        </p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan <?= $berhasil ? 'sukses' : 'error' ?>">
                <?= htmlspecialchars(
                    $pesan,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <?php if ($berhasil): ?>
                <p style="text-align:center">
                    <a href="login.php">Lanjut ke halaman login</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="nama">Nama Lengkap</label>
            <input
                type="text"
                id="nama"
                name="nama"
                value="<?= htmlspecialchars(
                    $nama,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                maxlength="100"
                autocomplete="name"
                required
            >

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
                maxlength="100"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="konfirmasi_password">
                Konfirmasi Password
            </label>
            <input
                type="password"
                id="konfirmasi_password"
                name="konfirmasi_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Daftar</button>
        </form>

        <p class="login">
            Sudah punya akun?
            <a href="login.php">Login di sini</a>
        </p>

        <p class="login">
            <a href="index.php">Kembali ke Beranda</a>
        </p>
    </main>
</body>
</html>
