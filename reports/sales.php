<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Sales Report';
$activeMenu = 'reports';

$period = $_GET['period'] ?? 'today';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
[$start, $end] = get_date_range($period, $from, $to);

$totalSales = get_period_sales($start, $end);
$orderCount = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_date BETWEEN ? AND ? AND status = 'completed'");
$stmt->execute([$start, $end]);
$orderCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT o.order_date, COUNT(*) AS orders, COALESCE(SUM(o.grand_total),0) AS total
    FROM orders o WHERE o.order_date BETWEEN ? AND ? AND o.status = 'completed'
    GROUP BY o.order_date ORDER BY o.order_date DESC");
$stmt->execute([$start, $end]);
$daily = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT oi.item_name, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS total
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.order_date BETWEEN ? AND ? AND o.status = 'completed'
    GROUP BY oi.item_name ORDER BY total DESC");
$stmt->execute([$start, $end]);
$items = $stmt->fetchAll();

$print = isset($_GET['print']);
if ($print) {
    include BASE_PATH . '/reports/print_layout.php';
    exit;
}

require BASE_PATH . '/includes/header.php';
include BASE_PATH . '/reports/_filter.php';
?>

<div class="page-header">
    <h4 class="mb-0">Sales Report</h4>
    <a href="?period=<?= e($period) ?>&from=<?= e($from) ?>&to=<?= e($to) ?>&print=1" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i> Print</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Total Sales</small><div class="fs-4 fw-bold"><?= format_money($totalSales) ?></div></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Completed Orders</small><div class="fs-4 fw-bold"><?= $orderCount ?></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Daily Breakdown</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Orders</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($daily as $d): ?><tr><td><?= format_date($d['order_date']) ?></td><td><?= (int)$d['orders'] ?></td><td><?= format_money($d['total']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Item-wise Sales</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($items as $it): ?><tr><td><?= e($it['item_name']) ?></td><td><?= (int)$it['qty'] ?></td><td><?= format_money($it['total']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
