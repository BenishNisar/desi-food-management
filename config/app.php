<?php
declare(strict_types=1);

define('APP_NAME', 'Desi Food Management');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/desi-food-management');

define('SESSION_LIFETIME', 3600 * 8);
define('TIMEZONE', 'Asia/Karachi');

date_default_timezone_set(TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}
