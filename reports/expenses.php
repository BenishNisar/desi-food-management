<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Expense Report';
$activeMenu = 'reports';

$period = $_GET['period'] ?? 'month';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
[$start, $end] = get_date_range($period, $from, $to);

$total = get_period_expenses($start, $end);

$stmt = $pdo->prepare('SELECT ec.name, COALESCE(SUM(e.amount),0) AS total FROM expenses e JOIN expense_categories ec ON ec.id = e.category_id WHERE e.expense_date BETWEEN ? AND ? GROUP BY ec.id, ec.name ORDER BY total DESC');
$stmt->execute([$start, $end]);
$byCategory = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT e.*, ec.name AS category_name FROM expenses e JOIN expense_categories ec ON ec.id = e.category_id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC');
$stmt->execute([$start, $end]);
$expenses = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
include BASE_PATH . '/reports/_filter.php';
?>

<div class="page-header">
    <h4 class="mb-0">Expense Report</h4>
    <span class="badge bg-danger fs-6">Total: <?= format_money($total) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">By Category</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Category</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($byCategory as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= format_money($c['total']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Description</th></tr></thead><tbody>
        <?php foreach ($expenses as $ex): ?><tr><td><?= format_date($ex['expense_date']) ?></td><td><?= e($ex['category_name']) ?></td><td><?= format_money($ex['amount']) ?></td><td><?= e($ex['description'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
