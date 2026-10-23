<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Inventory / Stock';
$activeMenu = 'inventory';

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save_item') {
        $id = post_int('id');
        $name = post_string('name');
        $category = post_string('category');
        $unit = post_string('unit', 'kg');
        $minimum = post_float('minimum_stock');
        $status = post_string('status', 'active');

        if ($name === '') {
            flash('danger', 'Item name is required.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE inventory_items SET name=?, category=?, unit=?, minimum_stock=?, status=?, updated_at=NOW() WHERE id=?')
                    ->execute([$name, $category, $unit, $minimum, $status, $id]);
                flash('success', 'Inventory item updated.');
            } else {
                $pdo->prepare('INSERT INTO inventory_items (name, category, unit, minimum_stock, status) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$name, $category, $unit, $minimum, $status]);
                flash('success', 'Inventory item created.');
            }
            log_activity('save', 'inventory', "Saved inventory item {$name}");
        }
        redirect(url('inventory/index.php'));
    }

    if ($action === 'movement') {
        $itemId = post_int('inventory_item_id');
        $type = post_string('movement_type');
        $quantity = post_float('quantity');
        $notes = post_string('notes');
        $userId = (int) $_SESSION['user_id'];

        $validTypes = ['usage', 'wastage', 'adjustment', 'correction'];
        if (!in_array($type, $validTypes, true) || $itemId <= 0) {
            flash('danger', 'Invalid movement data.');
        } else {
            try {
                $pdo->beginTransaction();
                add_stock_movement($itemId, $type, $quantity, null, $notes, $userId);
                log_activity('movement', 'inventory', "Stock {$type} for item #{$itemId}");
                $pdo->commit();
                flash('success', 'Stock movement recorded.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('danger', $e->getMessage());
            }
        }
        redirect(url('inventory/index.php'));
    }
}

$filter = $_GET['filter'] ?? '';
$sql = "SELECT * FROM inventory_items WHERE status = 'active'";
if ($filter === 'low') {
    $sql .= ' AND current_quantity <= minimum_stock';
}
$sql .= ' ORDER BY name';
$items = $pdo->query($sql)->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Inventory</h4>
    <div>
        <a href="<?= url('inventory/index.php?filter=low') ?>" class="btn btn-outline-danger btn-sm">Low Stock</a>
        <a href="<?= url('inventory/movements.php') ?>" class="btn btn-outline-primary btn-sm">Movement History</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Add Inventory Item</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_item">
                    <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Item name" required></div>
                    <div class="mb-2"><input type="text" name="category" class="form-control" placeholder="Category" required></div>
                    <div class="mb-2"><input type="text" name="unit" class="form-control" value="kg" required></div>
                    <div class="mb-2"><input type="number" step="0.001" name="minimum_stock" class="form-control" placeholder="Minimum stock" value="0"></div>
                    <button class="btn btn-primary-custom w-100">Add Item</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Record Stock Movement</h6></div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="movement">
                    <div class="col-md-4">
                        <select name="inventory_item_id" class="form-select" required>
                            <option value="">Select Item</option>
                            <?php foreach ($items as $it): ?><option value="<?= (int)$it['id'] ?>"><?= e($it['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="movement_type" class="form-select" required>
                            <option value="usage">Usage</option>
                            <option value="wastage">Wastage</option>
                            <option value="adjustment">Adjustment (set qty)</option>
                            <option value="correction">Correction (set qty)</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" step="0.001" name="quantity" class="form-control" placeholder="Qty" required></div>
                    <div class="col-md-3"><input type="text" name="notes" class="form-control" placeholder="Notes"></div>
                    <div class="col-12"><button class="btn btn-outline-primary">Record Movement</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Item</th><th>Category</th><th>Current Qty</th><th>Unit</th><th>Min Stock</th><th>Avg Price</th><th>Last Price</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
            <tr class="<?= $it['current_quantity'] <= $it['minimum_stock'] ? 'table-warning' : '' ?>">
                <td><?= e($it['name']) ?></td>
                <td><?= e($it['category']) ?></td>
                <td><?= e($it['current_quantity']) ?></td>
                <td><?= e($it['unit']) ?></td>
                <td><?= e($it['minimum_stock']) ?></td>
                <td><?= format_money($it['average_purchase_price']) ?></td>
                <td><?= format_money($it['last_purchase_price']) ?></td>
                <td><?= $it['current_quantity'] <= $it['minimum_stock'] ? '<span class="badge badge-low-stock">Low</span>' : '<span class="badge bg-success">OK</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
