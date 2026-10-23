<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Employee Advances';
$activeMenu = 'employees';

$employees = $pdo->query("SELECT id, name FROM employees WHERE status='active' ORDER BY name")->fetchAll();

if (is_post()) {
    require_csrf();
    $employeeId = post_int('employee_id');
    $advanceDate = post_string('advance_date', date('Y-m-d'));
    $amount = post_float('amount');
    $notes = post_string('notes');
    $userId = (int) $_SESSION['user_id'];

    if ($employeeId <= 0 || $amount <= 0) {
        flash('danger', 'Employee and valid amount required.');
    } else {
        $pdo->prepare('INSERT INTO employee_advances (employee_id, advance_date, amount, notes, created_by) VALUES (?, ?, ?, ?, ?)')
            ->execute([$employeeId, $advanceDate, $amount, $notes, $userId]);
        log_activity('advance', 'employees', "Advance {$amount} for employee #{$employeeId}");
        flash('success', 'Advance recorded.');
    }
    redirect(url('employees/advances.php'));
}

$advances = $pdo->query('SELECT ea.*, e.name AS employee_name, u.full_name FROM employee_advances ea JOIN employees e ON e.id = ea.employee_id LEFT JOIN users u ON u.id = ea.created_by ORDER BY ea.advance_date DESC LIMIT 200')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Employee Advances</h4>
    <a href="<?= url('employees/index.php') ?>" class="btn btn-outline-secondary">Employees</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="advance_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Advance</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Employee</th><th>Amount</th><th>Notes</th><th>By</th></tr></thead>
                    <tbody>
                    <?php foreach ($advances as $a): ?>
                    <tr>
                        <td><?= format_date($a['advance_date']) ?></td>
                        <td><?= e($a['employee_name']) ?></td>
                        <td><?= format_money($a['amount']) ?></td>
                        <td><?= e($a['notes'] ?? '') ?></td>
                        <td><?= e($a['full_name'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
