<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(url('index.php'));
}

$error = '';

if (is_post()) {
    require_csrf();
    $username = post_string('username');
    $password = post_string('password');

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } elseif (login_user($username, $password)) {
        redirect(url('index.php'));
    } else {
        $error = 'Invalid username or password.';
    }
}

$shopName = get_setting('shop_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e($shopName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card card mx-3">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-icon bg-primary-custom text-white mx-auto mb-3"><i class="bi bi-shop"></i></div>
                <h3 class="fw-bold"><?= e($shopName) ?></h3>
                <p class="text-muted mb-0">Internal Management System</p>
            </div>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" required autofocus value="<?= e(post_string('username')) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary-custom btn-lg w-100">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
