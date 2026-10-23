<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Salary Payments';
$activeMenu = 'salaries';

$employees = $pdo->query("SELECT id, name, monthly_salary FROM employees WHERE status='active' ORDER BY name")->fetchAll();

if (is_post()) {
    require_csrf();
    $employeeId = post_int('employee_id');
    $salaryMonth = post_string('salary_month');
    $basicSalary = post_float('basic_salary');
    $advance = post_float('advance');
    $bonus = post_float('bonus');
    $deduction = post_float('deduction');
    $amountPaid = post_float('amount_paid');
    $paymentDate = post_string('payment_date') ?: null;
    $method = post_string('payment_method') ?: null;
    $notes = post_string('notes');
    $userId = (int) $_SESSION['user_id'];

    $netPayable = $basicSalary + $bonus - $deduction - $advance;
    if ($netPayable < 0) $netPayable = 0;
    $remaining = max(0, $netPayable - $amountPaid);
    $status = salary_payment_status($netPayable, $amountPaid);

    if ($employeeId <= 0 || $salaryMonth === '') {
        flash('danger', 'Employee and month required.');
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM salary_payments WHERE employee_id = ? AND salary_month = ?');
            $stmt->execute([$employeeId, $salaryMonth]);
            if ($stmt->fetch()) {
                flash('danger', 'Salary for this employee and month already exists.');
            } else {
                $pdo->prepare('INSERT INTO salary_payments (employee_id, salary_month, basic_salary, advance, bonus, deduction, net_payable, amount_paid, remaining, payment_date, payment_method, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$employeeId, $salaryMonth, $basicSalary, $advance, $bonus, $deduction, $netPayable, $amountPaid, $remaining, $paymentDate, $method, $status, $notes, $userId]);
                if ($paymentDate) recalculate_daily_cash($paymentDate);
                log_activity('salary', 'employees', "Salary payment for employee #{$employeeId} month {$salaryMonth}");
                flash('success', 'Salary payment saved.');
            }
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    }
    redirect(url('salaries/index.php'));
}

$salaries = $pdo->query('SELECT sp.*, e.name AS employee_name FROM salary_payments sp JOIN employees e ON e.id = sp.employee_id ORDER BY sp.salary_month DESC, sp.id DESC LIMIT 200')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Salary Payments</h4></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Record Salary</h6></div>
            <div class="card-body">
                <form method="post" id="salaryForm">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Employee</label>
                        <select name="employee_id" id="employeeSelect" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>" data-salary="<?= e($e['monthly_salary']) ?>"><?= e($e['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Month</label><input type="month" name="salary_month" class="form-control" value="<?= date('Y-m') ?>" required></div>
                    <div class="mb-3"><label class="form-label">Basic Salary</label><input type="number" step="0.01" name="basic_salary" id="basicSalary" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Advance</label><input type="number" step="0.01" name="advance" id="advance" class="form-control" value="0"></div>
                    <div class="mb-3"><label class="form-label">Bonus</label><input type="number" step="0.01" name="bonus" id="bonus" class="form-control" value="0"></div>
                    <div class="mb-3"><label class="form-label">Deduction</label><input type="number" step="0.01" name="deduction" id="deduction" class="form-control" value="0"></div>
                    <div class="mb-3"><strong>Net Payable: </strong><span id="netPayable">Rs.0.00</span></div>
                    <div class="mb-3"><label class="form-label">Amount Paid</label><input type="number" step="0.01" name="amount_paid" id="amountPaid" class="form-control" value="0"></div>
                    <div class="mb-3"><label class="form-label">Payment Date</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="mb-3"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <?php foreach (payment_methods() as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Salary</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Month</th><th>Employee</th><th>Net Payable</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($salaries as $s): ?>
                    <tr>
                        <td><?= e($s['salary_month']) ?></td>
                        <td><?= e($s['employee_name']) ?></td>
                        <td><?= format_money($s['net_payable']) ?></td>
                        <td><?= format_money($s['amount_paid']) ?></td>
                        <td><?= format_money($s['remaining']) ?></td>
                        <td><span class="badge bg-<?= $s['status'] === 'paid' ? 'success' : ($s['status'] === 'partial' ? 'warning' : 'danger') ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function calcNet() {
    const basic = parseFloat(document.getElementById('basicSalary').value) || 0;
    const advance = parseFloat(document.getElementById('advance').value) || 0;
    const bonus = parseFloat(document.getElementById('bonus').value) || 0;
    const deduction = parseFloat(document.getElementById('deduction').value) || 0;
    const net = Math.max(0, basic + bonus - deduction - advance);
    document.getElementById('netPayable').textContent = formatMoney(net);
}
document.getElementById('employeeSelect').addEventListener('change', function() {
    const salary = this.selectedOptions[0]?.dataset.salary || 0;
    document.getElementById('basicSalary').value = salary;
    calcNet();
});
['basicSalary','advance','bonus','deduction'].forEach(id => document.getElementById(id).addEventListener('input', calcNet));
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
