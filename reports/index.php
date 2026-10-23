<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Reports';
$activeMenu = 'reports';

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Reports</h4></div>

<div class="row g-3">
    <?php
    $reports = [
        ['Sales Report', 'reports/sales.php', 'bi-graph-up', 'Daily/weekly/monthly sales'],
        ['Purchase Report', 'reports/purchases.php', 'bi-bag', 'Purchase summary'],
        ['Expense Report', 'reports/expenses.php', 'bi-wallet2', 'Expense breakdown'],
        ['Employee Report', 'reports/employees.php', 'bi-people', 'Salaries and advances'],
        ['Profit & Loss', 'reports/profit_loss.php', 'bi-calculator', 'P&L statement'],
        ['Supplier Statement', 'reports/supplier_statement.php', 'bi-file-text', 'Supplier ledger'],
    ];
    foreach ($reports as [$title, $path, $icon, $desc]):
    ?>
    <div class="col-md-6 col-lg-4">
        <a href="<?= url($path) ?>" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi <?= e($icon) ?>"></i></div>
                        <div>
                            <h6 class="mb-1 text-dark"><?= e($title) ?></h6>
                            <small class="text-muted"><?= e($desc) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
