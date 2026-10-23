<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Daily Cash';
$activeMenu = 'daily_cash';

$date = $_GET['date'] ?? date('Y-m-d');

if (is_post()) {
    require_csrf();
    $cashDate = post_string('cash_date', date('Y-m-d'));
    $opening = post_float('opening_cash');
    $otherIncome = post_float('other_income');
    $actualClosing = post_string('actual_closing') !== '' ? post_float('actual_closing') : null;
    $notes = post_string('notes');
    $userId = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_date = ? AND status = 'completed' AND payment_method = 'cash'");
    $stmt->execute([$cashDate]);
    $cashSales = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$cashDate]);
    $supplierPayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM salary_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$cashDate]);
    $employeePayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = ? AND payment_method = 'cash'");
    $stmt->execute([$cashDate]);
    $expensesTotal = (float) $stmt->fetchColumn();

    $expected = $opening + $cashSales + $otherIncome - $supplierPayments - $employeePayments - $expensesTotal;
    $difference = $actualClosing !== null ? $actualClosing - $expected : null;

    $stmt = $pdo->prepare('SELECT id FROM daily_cash WHERE cash_date = ?');
    $stmt->execute([$cashDate]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare('UPDATE daily_cash SET opening_cash=?, cash_sales=?, other_income=?, supplier_payments=?, employee_payments=?, expenses_total=?, expected_closing=?, actual_closing=?, difference=?, notes=?, updated_at=NOW() WHERE id=?')
            ->execute([$opening, $cashSales, $otherIncome, $supplierPayments, $employeePayments, $expensesTotal, $expected, $actualClosing, $difference, $notes, $existing]);
    } else {
        $pdo->prepare('INSERT INTO daily_cash (cash_date, opening_cash, cash_sales, other_income, supplier_payments, employee_payments, expenses_total, expected_closing, actual_closing, difference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$cashDate, $opening, $cashSales, $otherIncome, $supplierPayments, $employeePayments, $expensesTotal, $expected, $actualClosing, $difference, $notes, $userId]);
    }

    log_activity('save', 'daily_cash', "Daily cash saved for {$cashDate}");
    flash('success', 'Daily cash record saved.');
    redirect(url('daily_cash/index.php?date=' . $cashDate));
}

$stmt = $pdo->prepare('SELECT * FROM daily_cash WHERE cash_date = ?');
$stmt->execute([$date]);
$record = $stmt->fetch();

if (!$record) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_date = ? AND status = 'completed' AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $cashSales = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $supplierPayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM salary_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $employeePayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $expensesTotal = (float) $stmt->fetchColumn();

    $record = [
        'cash_date' => $date,
        'opening_cash' => 0,
        'cash_sales' => $cashSales,
        'other_income' => 0,
        'supplier_payments' => $supplierPayments,
        'employee_payments' => $employeePayments,
        'expenses_total' => $expensesTotal,
        'expected_closing' => 0 + $cashSales - $supplierPayments - $employeePayments - $expensesTotal,
        'actual_closing' => null,
        'difference' => null,
        'notes' => '',
    ];
}

$history = $pdo->query('SELECT * FROM daily_cash ORDER BY cash_date DESC LIMIT 30')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Daily Cash</h4></div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Cash Register - <?= format_date($date) ?></h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cash_date" value="<?= e($date) ?>">
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" value="<?= e($date) ?>" onchange="location.href='?date='+this.value"></div>
                    <div class="mb-3"><label class="form-label">Opening Cash</label><input type="number" step="0.01" name="opening_cash" class="form-control" value="<?= e($record['opening_cash']) ?>"></div>
                    <div class="mb-2 d-flex justify-content-between"><span>Cash Sales (auto)</span><strong><?= format_money($record['cash_sales']) ?></strong></div>
                    <div class="mb-3"><label class="form-label">Other Income</label><input type="number" step="0.01" name="other_income" class="form-control" value="<?= e($record['other_income']) ?>"></div>
                    <div class="mb-2 d-flex justify-content-between text-danger"><span>Supplier Payments (cash)</span><strong><?= format_money($record['supplier_payments']) ?></strong></div>
                    <div class="mb-2 d-flex justify-content-between text-danger"><span>Employee Payments (cash)</span><strong><?= format_money($record['employee_payments']) ?></strong></div>
                    <div class="mb-2 d-flex justify-content-between text-danger"><span>Expenses (cash)</span><strong><?= format_money($record['expenses_total']) ?></strong></div>
                    <hr>
                    <?php
                    $expected = (float)$record['opening_cash'] + (float)$record['cash_sales'] + (float)$record['other_income'] - (float)$record['supplier_payments'] - (float)$record['employee_payments'] - (float)$record['expenses_total'];
                    ?>
                    <div class="mb-3 d-flex justify-content-between"><span>Expected Closing</span><strong class="fs-5"><?= format_money($expected) ?></strong></div>
                    <div class="mb-3"><label class="form-label">Actual Closing Cash</label><input type="number" step="0.01" name="actual_closing" class="form-control" value="<?= e($record['actual_closing'] ?? '') ?>"></div>
                    <div class="mb-3"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"><?= e($record['notes'] ?? '') ?></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Daily Cash</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Recent Daily Cash Records</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Expected</th><th>Actual</th><th>Difference</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><a href="?date=<?= e($h['cash_date']) ?>"><?= format_date($h['cash_date']) ?></a></td>
                        <td><?= format_money($h['expected_closing']) ?></td>
                        <td><?= $h['actual_closing'] !== null ? format_money($h['actual_closing']) : '-' ?></td>
                        <td class="<?= ($h['difference'] ?? 0) != 0 ? 'text-danger fw-bold' : '' ?>"><?= $h['difference'] !== null ? format_money($h['difference']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
