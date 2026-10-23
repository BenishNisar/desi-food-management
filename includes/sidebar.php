<?php
declare(strict_types=1);

$menuItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => url('index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'pos', 'label' => 'POS / New Order', 'icon' => 'bi-cart-plus', 'url' => url('pos/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'orders', 'label' => 'Orders', 'icon' => 'bi-receipt', 'url' => url('orders/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'menu', 'label' => 'Menu', 'icon' => 'bi-menu-button-wide', 'url' => url('menu/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'purchases', 'label' => 'Purchases', 'icon' => 'bi-bag-check', 'url' => url('purchases/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'inventory', 'label' => 'Inventory', 'icon' => 'bi-boxes', 'url' => url('inventory/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'suppliers', 'label' => 'Suppliers', 'icon' => 'bi-truck', 'url' => url('suppliers/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'supplier_payments', 'label' => 'Supplier Payments', 'icon' => 'bi-cash-coin', 'url' => url('suppliers/payments.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'expenses', 'label' => 'Expenses', 'icon' => 'bi-wallet2', 'url' => url('expenses/index.php'), 'roles' => ['admin', 'staff']],
    ['id' => 'employees', 'label' => 'Employees', 'icon' => 'bi-people', 'url' => url('employees/index.php'), 'roles' => ['admin']],
    ['id' => 'salaries', 'label' => 'Salary Payments', 'icon' => 'bi-currency-dollar', 'url' => url('salaries/index.php'), 'roles' => ['admin']],
    ['id' => 'daily_cash', 'label' => 'Daily Cash', 'icon' => 'bi-safe', 'url' => url('daily_cash/index.php'), 'roles' => ['admin']],
    ['id' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'url' => url('reports/index.php'), 'roles' => ['admin']],
    ['id' => 'users', 'label' => 'Users', 'icon' => 'bi-person-gear', 'url' => url('users/index.php'), 'roles' => ['admin']],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'url' => url('settings/index.php'), 'roles' => ['admin']],
    ['id' => 'logs', 'label' => 'Activity Logs', 'icon' => 'bi-journal-text', 'url' => url('logs/index.php'), 'roles' => ['admin']],
];

function render_sidebar(string $activeMenu, array $user, bool $offcanvas = false): void
{
    global $menuItems;
    $id = $offcanvas ? 'sidebarOffcanvas' : 'sidebar';
    $class = $offcanvas ? 'offcanvas offcanvas-start sidebar-offcanvas' : 'sidebar d-none d-lg-flex flex-column';
    $attr = $offcanvas ? 'tabindex="-1" id="sidebarOffcanvas"' : 'id="sidebar"';

    echo '<aside class="' . $class . '" ' . $attr . '>';
    if ($offcanvas) {
        echo '<div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">' . e(get_setting('shop_name')) . '</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>';
        echo '<div class="offcanvas-body p-0 d-flex flex-column">';
    }

    if (!$offcanvas) {
        echo '<div class="sidebar-brand px-3 py-3 border-bottom"><h5 class="mb-0 text-white">' . e(get_setting('shop_name')) . '</h5><small class="text-white-50">Management System</small></div>';
    }

    echo '<nav class="sidebar-nav flex-grow-1 py-2">';
    foreach ($menuItems as $item) {
        if (!in_array($user['role'], $item['roles'], true)) {
            continue;
        }
        $active = $activeMenu === $item['id'] ? ' active' : '';
        echo '<a class="sidebar-link' . $active . '" href="' . e($item['url']) . '"><i class="bi ' . e($item['icon']) . ' me-2"></i>' . e($item['label']) . '</a>';
    }
    echo '</nav>';

    echo '<div class="sidebar-footer p-3 border-top mt-auto">';
    echo '<a class="sidebar-link text-danger" href="' . e(url('logout.php')) . '"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>';
    echo '</div>';

    if ($offcanvas) {
        echo '</div>';
    }
    echo '</aside>';
}
