<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Purchases / Rashan';
$activeMenu = 'purchases';

$stmt = $pdo->query('SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.purchase_date DESC, p.id DESC LIMIT 200');
$purchases = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Purchases</h4>
    <a href="<?= url('purchases/add.php') ?>" class="btn btn-primary-custom"><i class="bi bi-plus-lg"></i> New Purchase</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Purchase #</th><th>Date</th><th>Supplier</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
            <tr>
                <td><?= e($p['purchase_number']) ?></td>
                <td><?= format_date($p['purchase_date']) ?></td>
                <td><?= e($p['supplier_name']) ?></td>
                <td><?= format_money($p['grand_total']) ?></td>
                <td><?= format_money($p['amount_paid']) ?></td>
                <td><?= format_money($p['remaining']) ?></td>
                <td><span class="badge bg-<?= $p['payment_status'] === 'paid' ? 'success' : ($p['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= e(ucfirst($p['payment_status'])) ?></span></td>
                <td><a href="<?= url('purchases/view.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$purchases): ?><tr><td colspan="8" class="text-center text-muted py-4">No purchases yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
