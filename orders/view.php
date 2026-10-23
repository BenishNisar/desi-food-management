<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, u.full_name AS created_by_name FROM orders o LEFT JOIN users u ON u.id = o.created_by WHERE o.id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash('danger', 'Order not found.');
    redirect(url('orders/index.php'));
}

$pageTitle = 'Order ' . $order['order_number'];
$activeMenu = 'orders';

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'cancel' && $order['status'] === 'completed') {
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $upd->execute([$id]);
            recalculate_daily_cash($order['order_date']);
            log_activity('cancel', 'orders', 'Cancelled order ' . $order['order_number']);
            $pdo->commit();
            flash('success', 'Order cancelled.');
            redirect(url('orders/view.php?id=' . $id));
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('danger', $e->getMessage());
        }
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0"><?= e($order['order_number']) ?></h4>
    <div class="d-flex gap-2">
        <a href="<?= url('orders/print.php?id=' . $id) ?>" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i> Print</a>
        <a href="<?= url('orders/index.php') ?>" class="btn btn-outline-primary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Order Items</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['item_name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= format_money($item['unit_price']) ?></td>
                        <td><?= format_money($item['line_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td colspan="3" class="text-end">Subtotal</td><td><?= format_money($order['subtotal']) ?></td></tr>
                    <tr><td colspan="3" class="text-end">Discount</td><td><?= format_money($order['discount']) ?></td></tr>
                    <tr><td colspan="3" class="text-end fw-bold">Grand Total</td><td class="fw-bold"><?= format_money($order['grand_total']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <p><strong>Date:</strong> <?= format_date($order['order_date']) ?> <?= e(substr($order['order_time'], 0, 8)) ?></p>
                <p><strong>Payment:</strong> <?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?></p>
                <p><strong>Status:</strong> <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= e(ucfirst($order['status'])) ?></span></p>
                <p><strong>Created By:</strong> <?= e($order['created_by_name'] ?? '-') ?></p>
                <?php if ($order['notes']): ?><p><strong>Notes:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
                <?php if ($order['status'] === 'completed'): ?>
                <form method="post" onsubmit="return confirm('Cancel this order? It will not count as sales.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn-danger w-100">Cancel Order</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
