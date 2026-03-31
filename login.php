<?php
require_once 'config.php';

// nyimpen sesion admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    redirect('admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = sanitize($conn, $_POST['password'] ?? '');

    $result = $conn->query("SELECT * FROM admin WHERE Username = '$username' AND Password = '$password'");

    if ($result && $result->num_rows > 0) {
        $_SESSION['role']     = 'admin';
        $_SESSION['username'] = $username;
        redirect('admin/dashboard.php');
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="./style/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <div class="login-logo">
            <h1>AspiraSi Pelaporan</h1>
            <p>Panel Admin — Pengaduan Sarana Sekolah</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Username admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <p style="text-align:center; margin-top:1.25rem; font-size:0.85rem;">
            <a href="index.php" style="color:var(--text-muted); text-decoration:none;">← Kembali ke Form Aspirasi</a>
        </p>
    </div>
</div>
</body>
</html>