<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Orders';
$activeMenu = 'orders';

$status = $_GET['status'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

$sql = 'SELECT o.*, u.full_name AS created_by_name FROM orders o LEFT JOIN users u ON u.id = o.created_by WHERE 1=1';
$params = [];

if ($status && in_array($status, ['completed', 'cancelled'], true)) {
    $sql .= ' AND o.status = ?';
    $params[] = $status;
}
if ($dateFrom) {
    $sql .= ' AND o.order_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= ' AND o.order_date <= ?';
    $params[] = $dateTo;
}

$sql .= ' ORDER BY o.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Orders</h4>
    <a href="<?= url('pos/index.php') ?>" class="btn btn-primary-custom"><i class="bi bi-plus-lg"></i> New Order</a>
</div>

<div class="filter-bar">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>"></div>
        <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>"></div>
        <div class="col-md-3"><button class="btn btn-primary-custom w-100">Filter</button></div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th>By</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['order_number']) ?></td>
                <td><?= format_date($o['order_date']) ?> <?= e(substr($o['order_time'], 0, 5)) ?></td>
                <td><?= format_money($o['grand_total']) ?></td>
                <td><?= e(payment_methods()[$o['payment_method']] ?? $o['payment_method']) ?></td>
                <td><span class="badge bg-<?= $o['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                <td><?= e($o['created_by_name'] ?? '-') ?></td>
                <td class="text-end">
                    <a href="<?= url('orders/view.php?id=' . $o['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                    <a href="<?= url('orders/print.php?id=' . $o['id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="7" class="text-center text-muted py-4">No orders found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
