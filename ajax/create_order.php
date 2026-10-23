<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

if (!is_post()) {
    json_response(['success' => false, 'message' => 'Invalid request'], 405);
}

require_csrf();

$items = json_decode($_POST['items'] ?? '[]', true);
$notes = post_string('notes');
$discount = max(0, post_float('discount'));
$paymentMethod = post_string('payment_method', 'cash');

if (!in_array($paymentMethod, array_keys(payment_methods()), true)) {
    json_response(['success' => false, 'message' => 'Invalid payment method']);
}

if (!is_array($items) || count($items) === 0) {
    json_response(['success' => false, 'message' => 'No items in order']);
}

try {
    $pdo->beginTransaction();

    $subtotal = 0;
    $validatedItems = [];

    foreach ($items as $item) {
        $menuId = (int) ($item['id'] ?? 0);
        $qty = max(1, (int) ($item['qty'] ?? 1));

        $stmt = $pdo->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND status = ?');
        $stmt->execute([$menuId, 'active']);
        $menu = $stmt->fetch();

        if (!$menu) {
            throw new RuntimeException('Invalid menu item.');
        }

        $lineTotal = (float) $menu['price'] * $qty;
        $subtotal += $lineTotal;
        $validatedItems[] = [
            'menu_item_id' => (int) $menu['id'],
            'item_name' => $menu['name'],
            'quantity' => $qty,
            'unit_price' => (float) $menu['price'],
            'line_total' => $lineTotal,
        ];
    }

    if ($discount > $subtotal) {
        throw new RuntimeException('Discount cannot exceed subtotal.');
    }

    $grandTotal = $subtotal - $discount;
    $orderNumber = generate_number('order_prefix', 'ORD');
    $userId = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare('INSERT INTO orders (order_number, order_date, order_time, subtotal, discount, grand_total, payment_method, status, notes, created_by) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$orderNumber, $subtotal, $discount, $grandTotal, $paymentMethod, 'completed', $notes, $userId]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($validatedItems as $vi) {
        $itemStmt->execute([$orderId, $vi['menu_item_id'], $vi['item_name'], $vi['quantity'], $vi['unit_price'], $vi['line_total']]);
    }

    recalculate_daily_cash(date('Y-m-d'));
    log_activity('create', 'orders', "Order {$orderNumber} created");

    $pdo->commit();
    json_response(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => $e->getMessage()]);
}
