<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?');
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    flash('danger', 'Purchase not found.');
    redirect(url('purchases/index.php'));
}

$pageTitle = $purchase['purchase_number'];
$activeMenu = 'purchases';

$stmt = $pdo->prepare('SELECT * FROM purchase_items WHERE purchase_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0"><?= e($purchase['purchase_number']) ?></h4>
    <a href="<?= url('purchases/index.php') ?>" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <p><strong>Date:</strong> <?= format_date($purchase['purchase_date']) ?></p>
            <p><strong>Supplier:</strong> <?= e($purchase['supplier_name']) ?></p>
            <p><strong>Grand Total:</strong> <?= format_money($purchase['grand_total']) ?></p>
            <p><strong>Amount Paid:</strong> <?= format_money($purchase['amount_paid']) ?></p>
            <p><strong>Remaining:</strong> <?= format_money($purchase['remaining']) ?></p>
            <p><strong>Status:</strong> <span class="badge bg-<?= $purchase['payment_status'] === 'paid' ? 'success' : ($purchase['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= e(ucfirst($purchase['payment_status'])) ?></span></p>
            <?php if ($purchase['notes']): ?><p><strong>Notes:</strong> <?= e($purchase['notes']) ?></p><?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['item_name']) ?></td>
                        <td><?= e($item['quantity']) ?></td>
                        <td><?= e($item['unit']) ?></td>
                        <td><?= format_money($item['rate']) ?></td>
                        <td><?= format_money($item['line_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
