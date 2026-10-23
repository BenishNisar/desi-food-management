<?php
declare(strict_types=1);

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect(url('login.php'));
    }
}

function current_user(): ?array
{
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user === null) {
        $stmt = $pdo->prepare('SELECT id, username, full_name, role, status FROM users WHERE id = ? AND status = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id'], 'active']);
        $user = $stmt->fetch() ?: null;

        if (!$user) {
            logout_user();
            redirect(url('login.php'));
        }
    }

    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

function login_user(string $username, string $password): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND status = ? LIMIT 1');
    $stmt->execute([$username, 'active']);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        log_activity('login', 'auth', 'User logged in');
        return true;
    }

    return false;
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        log_activity('logout', 'auth', 'User logged out');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_init(): void
{
    require_once BASE_PATH . '/includes/functions.php';
    require_once BASE_PATH . '/includes/auth.php';
}
