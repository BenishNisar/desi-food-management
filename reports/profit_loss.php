<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Profit & Loss';
$activeMenu = 'reports';

$period = $_GET['period'] ?? 'month';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
[$start, $end] = get_date_range($period, $from, $to);

$sales = get_period_sales($start, $end);
$purchases = get_period_purchases($start, $end);
$expenses = get_period_expenses($start, $end);
$salaries = get_period_salaries($start, $end);

$grossProfit = $sales - $purchases;
$netProfit = $sales - $purchases - $expenses - $salaries;

$print = isset($_GET['print']);

require BASE_PATH . '/includes/header.php';
include BASE_PATH . '/reports/_filter.php';
?>

<div class="page-header">
    <h4 class="mb-0">Profit & Loss Statement</h4>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="card print-page">
    <div class="card-body">
        <div class="text-center mb-4">
            <h4><?= e(get_setting('shop_name')) ?></h4>
            <p class="text-muted mb-0">Profit & Loss: <?= format_date($start) ?> to <?= format_date($end) ?></p>
        </div>
        <table class="table">
            <tbody>
            <tr><td><strong>Total Sales (Completed Orders)</strong></td><td class="text-end text-success"><?= format_money($sales) ?></td></tr>
            <tr><td>Cost of Purchases (Rashan)</td><td class="text-end text-danger">(<?= format_money($purchases) ?>)</td></tr>
            <tr class="table-light"><td><strong>Gross Profit</strong></td><td class="text-end fw-bold"><?= format_money($grossProfit) ?></td></tr>
            <tr><td>Operating Expenses</td><td class="text-end text-danger">(<?= format_money($expenses) ?>)</td></tr>
            <tr><td>Salary Payments</td><td class="text-end text-danger">(<?= format_money($salaries) ?>)</td></tr>
            <tr class="table-primary"><td><strong>Net Profit / Loss</strong></td><td class="text-end fw-bold fs-5"><?= format_money($netProfit) ?></td></tr>
            </tbody>
        </table>
        <p class="text-muted small mt-3">Note: Cancelled orders are excluded from sales. P&L uses cash-basis totals by transaction date.</p>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
