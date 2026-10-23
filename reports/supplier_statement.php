<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Supplier Statement';
$activeMenu = 'reports';

$suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE status='active' ORDER BY name")->fetchAll();
$supplierId = (int) ($_GET['supplier_id'] ?? 0);
$supplier = null;
$purchases = [];
$payments = [];
$outstanding = 0;

if ($supplierId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$supplierId]);
    $supplier = $stmt->fetch();

    if ($supplier) {
        $outstanding = get_supplier_outstanding($supplierId);
        $stmt = $pdo->prepare('SELECT * FROM purchases WHERE supplier_id = ? ORDER BY purchase_date ASC');
        $stmt->execute([$supplierId]);
        $purchases = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT * FROM supplier_payments WHERE supplier_id = ? ORDER BY payment_date ASC');
        $stmt->execute([$supplierId]);
        $payments = $stmt->fetchAll();
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Supplier Statement</h4>
    <?php if ($supplier): ?><button onclick="window.print()" class="btn btn-outline-secondary no-print">Print</button><?php endif; ?>
</div>

<div class="filter-bar no-print">
    <form method="get" class="row g-2">
        <div class="col-md-6">
            <select name="supplier_id" class="form-select" required onchange="this.form.submit()">
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= $supplierId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($supplier): ?>
<div class="card print-page">
    <div class="card-body">
        <h4><?= e($supplier['name']) ?></h4>
        <p><?= e($supplier['phone'] ?? '') ?> | <?= e($supplier['address'] ?? '') ?></p>
        <p><strong>Opening Balance:</strong> <?= format_money($supplier['opening_balance']) ?> |
        <strong>Outstanding:</strong> <span class="text-danger"><?= format_money($outstanding) ?></span></p>

        <h6 class="mt-4">Purchases</h6>
        <table class="table table-sm"><thead><tr><th>Date</th><th>#</th><th>Total</th><th>Paid</th><th>Remaining</th></tr></thead><tbody>
        <?php foreach ($purchases as $p): ?><tr><td><?= format_date($p['purchase_date']) ?></td><td><?= e($p['purchase_number']) ?></td><td><?= format_money($p['grand_total']) ?></td><td><?= format_money($p['amount_paid']) ?></td><td><?= format_money($p['remaining']) ?></td></tr><?php endforeach; ?>
        </tbody></table>

        <h6 class="mt-4">Payments</h6>
        <table class="table table-sm"><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead><tbody>
        <?php foreach ($payments as $pay): ?><tr><td><?= format_date($pay['payment_date']) ?></td><td><?= format_money($pay['amount']) ?></td><td><?= e($pay['payment_method']) ?></td><td><?= e($pay['reference'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/includes/footer.php'; ?>
