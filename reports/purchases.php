<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Purchase Report';
$activeMenu = 'reports';

$period = $_GET['period'] ?? 'month';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
[$start, $end] = get_date_range($period, $from, $to);

$total = get_period_purchases($start, $end);

$stmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id WHERE p.purchase_date BETWEEN ? AND ? ORDER BY p.purchase_date DESC');
$stmt->execute([$start, $end]);
$purchases = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
include BASE_PATH . '/reports/_filter.php';
?>

<div class="page-header">
    <h4 class="mb-0">Purchase Report</h4>
    <span class="badge bg-primary fs-6">Total: <?= format_money($total) ?></span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Purchase #</th><th>Date</th><th>Supplier</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
            <tr>
                <td><?= e($p['purchase_number']) ?></td>
                <td><?= format_date($p['purchase_date']) ?></td>
                <td><?= e($p['supplier_name']) ?></td>
                <td><?= format_money($p['grand_total']) ?></td>
                <td><?= format_money($p['amount_paid']) ?></td>
                <td><?= format_money($p['remaining']) ?></td>
                <td><?= e(ucfirst($p['payment_status'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
