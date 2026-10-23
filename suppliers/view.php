<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    flash('danger', 'Supplier not found.');
    redirect(url('suppliers/index.php'));
}

$pageTitle = $supplier['name'];
$activeMenu = 'suppliers';

$stmt = $pdo->prepare('SELECT * FROM purchases WHERE supplier_id = ? ORDER BY purchase_date DESC');
$stmt->execute([$id]);
$purchases = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT sp.*, u.full_name FROM supplier_payments sp LEFT JOIN users u ON u.id = sp.created_by WHERE sp.supplier_id = ? ORDER BY sp.payment_date DESC');
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

$totalPurchases = array_sum(array_column($purchases, 'grand_total'));
$totalPaid = array_sum(array_column($payments, 'amount'));
$outstanding = get_supplier_outstanding($id);

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0"><?= e($supplier['name']) ?></h4>
    <a href="<?= url('reports/supplier_statement.php?supplier_id=' . $id) ?>" class="btn btn-outline-primary">Statement</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Total Purchases</small><div class="fs-5 fw-bold"><?= format_money($totalPurchases) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Total Paid</small><div class="fs-5 fw-bold"><?= format_money($totalPaid) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Outstanding</small><div class="fs-5 fw-bold text-danger"><?= format_money($outstanding) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Opening Balance</small><div class="fs-5 fw-bold"><?= format_money($supplier['opening_balance']) ?></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Purchase History</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>#</th><th>Date</th><th>Total</th><th>Remaining</th></tr></thead><tbody>
        <?php foreach ($purchases as $p): ?><tr><td><a href="<?= url('purchases/view.php?id='.$p['id']) ?>"><?= e($p['purchase_number']) ?></a></td><td><?= format_date($p['purchase_date']) ?></td><td><?= format_money($p['grand_total']) ?></td><td><?= format_money($p['remaining']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white"><h6 class="mb-0">Payment History</h6></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead><tbody>
        <?php foreach ($payments as $pay): ?><tr><td><?= format_date($pay['payment_date']) ?></td><td><?= format_money($pay['amount']) ?></td><td><?= e(payment_methods()[$pay['payment_method']] ?? $pay['payment_method']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
