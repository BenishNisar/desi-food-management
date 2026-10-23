<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$shopName = get_setting('shop_name');
$shopAddress = get_setting('shop_address');
$shopPhone = get_setting('shop_phone');
$footer = get_setting('receipt_footer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= e($order['order_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="print-page">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary-custom">Print</button>
        <a href="<?= url('orders/view.php?id=' . $id) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
    <div class="text-center mb-4">
        <h3 class="mb-0"><?= e($shopName) ?></h3>
        <p class="mb-0"><?= e($shopAddress) ?></p>
        <p class="mb-0"><?= e($shopPhone) ?></p>
    </div>
    <hr>
    <p><strong>Order:</strong> <?= e($order['order_number']) ?><br>
    <strong>Date:</strong> <?= format_date($order['order_date']) ?> <?= e(substr($order['order_time'], 0, 8)) ?><br>
    <strong>Status:</strong> <?= e(ucfirst($order['status'])) ?><br>
    <strong>Payment:</strong> <?= e(payment_methods()[$order['payment_method']] ?? $order['payment_method']) ?></p>
    <table class="table table-sm">
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
    <?php if ($order['notes']): ?><p><strong>Notes:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
    <hr>
    <p class="text-center text-muted"><?= e($footer) ?></p>
</div>
</body>
</html>
