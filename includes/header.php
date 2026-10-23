<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e($shopName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <?php if (!empty($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
<div class="app-wrapper">
    <?php require BASE_PATH . '/includes/sidebar.php'; render_sidebar($activeMenu, $user); ?>

    <div class="offcanvas offcanvas-start sidebar-offcanvas d-lg-none" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title"><?= e($shopName) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <nav class="sidebar-nav flex-grow-1 py-2">
                <?php
                require_once BASE_PATH . '/includes/sidebar.php';
                foreach ($menuItems as $item):
                    if (!in_array($user['role'], $item['roles'], true)) continue;
                    $active = $activeMenu === $item['id'] ? ' active' : '';
                ?>
                <a class="sidebar-link<?= $active ?>" href="<?= e($item['url']) ?>"><i class="bi <?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer p-3 border-top">
                <a class="sidebar-link text-danger" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <nav class="top-navbar navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0 h1 fs-5"><?= e($pageTitle) ?></span>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary"><?= e(ucfirst($user['role'])) ?></span>
                    <span class="text-muted small d-none d-sm-inline"><?= e($user['full_name']) ?></span>
                </div>
            </div>
        </nav>

        <main class="content-area p-3 p-md-4">
            <?php $flash = get_flash(); if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
