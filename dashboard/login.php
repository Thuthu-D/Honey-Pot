<?php
require_once __DIR__ . '/../config.php';
session_name(HP_SESSION_NAME);
session_start();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (hash_equals(HP_DASHBOARD_USER, $user) && password_verify($pass, HP_DASHBOARD_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['hp_authenticated'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Honeypot Dashboard - Sign In</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <h1>Honeypot Dashboard</h1>
        <p class="subtitle">Sign in to view captured activity</p>
        <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
