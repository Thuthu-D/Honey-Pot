<?php
require_once __DIR__ . '/includes/logger.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? substr((string)$_POST['username'], 0, 255) : '';
    $password = isset($_POST['password']) ? substr((string)$_POST['password'], 0, 255) : '';

    hp_log_event('login_attempt', [
        'username_attempt' => $username,
        'password_attempt' => $password,
    ]);

    // Small artificial delay so it feels like a real auth check ran.
    usleep(random_int(300000, 900000));

    $error = 'Invalid username or password.';
} else {
    hp_log_event('page_view');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars(HP_SITE_NAME) ?> - Sign In</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <h1><?= htmlspecialchars(HP_SITE_NAME) ?></h1>
        <p class="subtitle">Sign in to continue</p>
        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Sign In</button>
        </form>
        <p class="footer">v3.2.1 &middot; Internal use only</p>
    </div>
</div>
</body>
</html>
