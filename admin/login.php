<?php
session_start();

require_once __DIR__ . '/../koneksi.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password
             FROM users
             WHERE email = ? AND role = 'admin'
             LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($admin && password_verify($password, $admin["password"])) {
                session_regenerate_id(true);

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["name"];
                $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));

                header("Location: dashboard.php");
                exit;
            }
        }

        $error = "Email atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Sembako-Kita</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f4f8f7;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .brand {
            color: #359FA0;
            text-align: center;
            font-size: 27px;
            font-weight: bold;
        }

        h2 {
            margin-bottom: 8px;
            text-align: center;
            color: #263b3b;
        }

        .subtitle {
            margin-bottom: 25px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5dfdd;
            border-radius: 8px;
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
            border: none;
            border-radius: 8px;
            background: #359FA0;
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
            border-radius: 8px;
            background: #fff0ed;
            color: #b53a2b;
            font-size: 14px;
        }

        .back {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #359FA0;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="brand">Sembako-Kita</div>
        <h2>Login Admin</h2>
        <p class="subtitle">Masuk untuk mengelola toko</p>

        <?php if ($error !== ""): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Email Admin</label>
            <input
                type="email"
                id="email"
                name="email"
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

            <button type="submit">Masuk ke Dashboard</button>
        </form>

        <a href="../index.php" class="back">
            &larr; Kembali ke halaman toko
        </a>
    </div>
</body>
</html>
