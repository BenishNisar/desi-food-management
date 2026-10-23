<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Employee Report';
$activeMenu = 'reports';

$period = $_GET['period'] ?? 'month';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
[$start, $end] = get_date_range($period, $from, $to);

$totalSalaries = get_period_salaries($start, $end);

$stmt = $pdo->prepare('SELECT sp.*, e.name FROM salary_payments sp JOIN employees e ON e.id = sp.employee_id WHERE sp.payment_date BETWEEN ? AND ? ORDER BY sp.payment_date DESC');
$stmt->execute([$start, $end]);
$salaries = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT ea.*, e.name FROM employee_advances ea JOIN employees e ON e.id = ea.employee_id WHERE ea.advance_date BETWEEN ? AND ? ORDER BY ea.advance_date DESC');
$stmt->execute([$start, $end]);
$advances = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
include BASE_PATH . '/reports/_filter.php';
?>

<div class="page-header">
    <h4 class="mb-0">Employee Report</h4>
    <span class="badge bg-primary fs-6">Salaries Paid: <?= format_money($totalSalaries) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Salary Payments</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Employee</th><th>Month</th><th>Paid</th></tr></thead><tbody>
        <?php foreach ($salaries as $s): ?><tr><td><?= format_date($s['payment_date']) ?></td><td><?= e($s['name']) ?></td><td><?= e($s['salary_month']) ?></td><td><?= format_money($s['amount_paid']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Advances</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Employee</th><th>Amount</th></tr></thead><tbody>
        <?php foreach ($advances as $a): ?><tr><td><?= format_date($a['advance_date']) ?></td><td><?= e($a['name']) ?></td><td><?= format_money($a['amount']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
