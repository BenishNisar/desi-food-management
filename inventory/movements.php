<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Stock Movements';
$activeMenu = 'inventory';

$stmt = $pdo->query('SELECT sm.*, ii.name AS item_name, u.full_name AS user_name
    FROM stock_movements sm
    JOIN inventory_items ii ON ii.id = sm.inventory_item_id
    LEFT JOIN users u ON u.id = sm.created_by
    ORDER BY sm.created_at DESC LIMIT 300');
$movements = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Stock Movement History</h4>
    <a href="<?= url('inventory/index.php') ?>" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Price</th><th>Notes</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $m): ?>
            <tr>
                <td><?= format_datetime($m['created_at']) ?></td>
                <td><?= e($m['item_name']) ?></td>
                <td><span class="badge bg-secondary"><?= e(ucfirst($m['movement_type'])) ?></span></td>
                <td><?= e($m['quantity']) ?></td>
                <td><?= $m['unit_price'] !== null ? format_money($m['unit_price']) : '-' ?></td>
                <td><?= e($m['notes'] ?? '') ?></td>
                <td><?= e($m['user_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$movements): ?><tr><td colspan="7" class="text-center text-muted py-4">No movements yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
