<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();
$user = current_user();

$pageTitle = $pageTitle ?? 'Dashboard';
$activeMenu = $activeMenu ?? '';

$shopName = get_setting('shop_name', APP_NAME);
