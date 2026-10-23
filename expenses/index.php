<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Expenses';
$activeMenu = 'expenses';

$categories = $pdo->query("SELECT * FROM expense_categories WHERE status='active' ORDER BY name")->fetchAll();

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $categoryId = post_int('category_id');
        $expenseDate = post_string('expense_date', date('Y-m-d'));
        $amount = post_float('amount');
        $description = post_string('description');
        $method = post_string('payment_method', 'cash');
        $userId = (int) $_SESSION['user_id'];

        if ($categoryId <= 0 || $amount <= 0) {
            flash('danger', 'Category and valid amount required.');
        } else {
            $pdo->prepare('INSERT INTO expenses (category_id, expense_date, amount, description, payment_method, created_by) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$categoryId, $expenseDate, $amount, $description, $method, $userId]);
            recalculate_daily_cash($expenseDate);
            log_activity('create', 'expenses', "Expense of {$amount} recorded");
            flash('success', 'Expense saved.');
        }
        redirect(url('expenses/index.php'));
    }

    if ($action === 'delete') {
        $delId = post_int('id');
        $stmt = $pdo->prepare('SELECT expense_date FROM expenses WHERE id = ?');
        $stmt->execute([$delId]);
        $date = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$delId]);
        if ($date) recalculate_daily_cash($date);
        flash('success', 'Expense deleted.');
        redirect(url('expenses/index.php'));
    }
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$sql = 'SELECT e.*, ec.name AS category_name, u.full_name FROM expenses e JOIN expense_categories ec ON ec.id = e.category_id LEFT JOIN users u ON u.id = e.created_by WHERE 1=1';
$params = [];
if ($from) { $sql .= ' AND e.expense_date >= ?'; $params[] = $from; }
if ($to) { $sql .= ' AND e.expense_date <= ?'; $params[] = $to; }
$sql .= ' ORDER BY e.expense_date DESC, e.id DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Expenses</h4></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Add Expense</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3"><label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <?php foreach (payment_methods() as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Expense</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="filter-bar">
            <form method="get" class="row g-2">
                <div class="col-md-4"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
                <div class="col-md-4"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
                <div class="col-md-4"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Method</th><th>Description</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($expenses as $ex): ?>
                    <tr>
                        <td><?= format_date($ex['expense_date']) ?></td>
                        <td><?= e($ex['category_name']) ?></td>
                        <td><?= format_money($ex['amount']) ?></td>
                        <td><?= e(payment_methods()[$ex['payment_method']] ?? $ex['payment_method']) ?></td>
                        <td><?= e($ex['description'] ?? '') ?></td>
                        <td>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete expense?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
