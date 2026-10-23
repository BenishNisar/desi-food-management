<?php
declare(strict_types=1);

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf($token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

function format_money(float|string $amount): string
{
    $symbol = get_setting('currency_symbol', 'Rs.');
    return $symbol . number_format((float) $amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    return date('d M Y h:i A', strtotime($datetime));
}

function get_setting(string $key, string $default = ''): string
{
    global $pdo;
    static $cache = [];

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    $cache[$key] = $value !== false ? (string) $value : $default;
    return $cache[$key];
}

function set_setting(string $key, string $value): void
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
    $stmt->execute([$key, $value]);
}

function generate_number(string $prefixKey, string $prefixDefault): string
{
    global $pdo;

    $pdo->beginTransaction();
    try {
        $prefix = get_setting($prefixKey === 'order_prefix' ? 'order_prefix' : 'purchase_prefix', $prefixDefault);
        $counterKey = $prefixKey === 'order_prefix' ? 'last_order_number' : 'last_purchase_number';

        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? FOR UPDATE');
        $stmt->execute([$counterKey]);
        $last = (int) ($stmt->fetchColumn() ?: 0);
        $next = $last + 1;

        set_setting($counterKey, (string) $next);
        $pdo->commit();

        return $prefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function log_activity(string $action, string $module, string $description = ''): void
{
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $module, $description, $ip]);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function payment_methods(): array
{
    return [
        'cash'           => 'Cash',
        'bank_transfer'  => 'Bank Transfer',
        'easypaisa'      => 'Easypaisa',
        'jazzcash'       => 'JazzCash',
        'other'          => 'Other',
    ];
}

function purchase_payment_status(float $grandTotal, float $amountPaid): string
{
    if ($amountPaid <= 0) {
        return 'unpaid';
    }
    if ($amountPaid >= $grandTotal) {
        return 'paid';
    }
    return 'partial';
}

function salary_payment_status(float $netPayable, float $amountPaid): string
{
    if ($amountPaid <= 0) {
        return 'unpaid';
    }
    if ($amountPaid >= $netPayable) {
        return 'paid';
    }
    return 'partial';
}

function get_date_range(string $period, ?string $from = null, ?string $to = null): array
{
    $today = date('Y-m-d');

    switch ($period) {
        case 'yesterday':
            $start = date('Y-m-d', strtotime('-1 day'));
            $end = $start;
            break;
        case 'week':
            $start = date('Y-m-d', strtotime('monday this week'));
            $end = $today;
            break;
        case 'month':
            $start = date('Y-m-01');
            $end = $today;
            break;
        case 'custom':
            $start = $from ?: $today;
            $end = $to ?: $today;
            break;
        default:
            $start = $today;
            $end = $today;
    }

    return [$start, $end];
}

function get_today_sales(): float
{
    global $pdo;
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_date = CURDATE() AND status = 'completed'");
    return (float) $stmt->fetchColumn();
}

function get_today_orders_count(): int
{
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_date = CURDATE() AND status = 'completed'");
    return (int) $stmt->fetchColumn();
}

function get_today_expenses(): float
{
    global $pdo;
    $stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURDATE()');
    return (float) $stmt->fetchColumn();
}

function get_period_sales(string $start, string $end): float
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_date BETWEEN ? AND ? AND status = 'completed'");
    $stmt->execute([$start, $end]);
    return (float) $stmt->fetchColumn();
}

function get_period_purchases(string $start, string $end): float
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(grand_total), 0) FROM purchases WHERE purchase_date BETWEEN ? AND ?');
    $stmt->execute([$start, $end]);
    return (float) $stmt->fetchColumn();
}

function get_period_expenses(string $start, string $end): float
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN ? AND ?');
    $stmt->execute([$start, $end]);
    return (float) $stmt->fetchColumn();
}

function get_period_salaries(string $start, string $end): float
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_paid), 0) FROM salary_payments WHERE payment_date BETWEEN ? AND ?');
    $stmt->execute([$start, $end]);
    return (float) $stmt->fetchColumn();
}

function get_pending_supplier_payments(): float
{
    global $pdo;
    $stmt = $pdo->query("SELECT COALESCE(SUM(remaining), 0) FROM purchases WHERE payment_status IN ('partial', 'unpaid')");
    return (float) $stmt->fetchColumn();
}

function update_inventory_from_purchase(int $inventoryItemId, float $quantity, float $rate, int $purchaseId, ?int $userId): void
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT current_quantity, average_purchase_price FROM inventory_items WHERE id = ? FOR UPDATE');
    $stmt->execute([$inventoryItemId]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new RuntimeException('Inventory item not found.');
    }

    $oldQty = (float) $item['current_quantity'];
    $oldAvg = (float) $item['average_purchase_price'];
    $newQty = $oldQty + $quantity;
    $newAvg = $newQty > 0 ? (($oldQty * $oldAvg) + ($quantity * $rate)) / $newQty : $rate;

    $update = $pdo->prepare('UPDATE inventory_items SET current_quantity = ?, average_purchase_price = ?, last_purchase_price = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$newQty, $newAvg, $rate, $inventoryItemId]);

    $movement = $pdo->prepare('INSERT INTO stock_movements (inventory_item_id, movement_type, quantity, unit_price, reference_type, reference_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $movement->execute([$inventoryItemId, 'purchase', $quantity, $rate, 'purchase', $purchaseId, 'Stock added from purchase', $userId]);
}

function add_stock_movement(int $inventoryItemId, string $type, float $quantity, ?float $unitPrice, ?string $notes, ?int $userId, ?string $refType = null, ?int $refId = null): void
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT current_quantity FROM inventory_items WHERE id = ? FOR UPDATE');
    $stmt->execute([$inventoryItemId]);
    $current = (float) $stmt->fetchColumn();

    if (in_array($type, ['usage', 'wastage'], true)) {
        $newQty = $current - abs($quantity);
    } elseif ($type === 'adjustment' || $type === 'correction') {
        $newQty = $quantity;
        $quantity = abs($newQty - $current);
    } else {
        $newQty = $current + abs($quantity);
    }

    if ($newQty < 0) {
        throw new RuntimeException('Insufficient stock for this operation.');
    }

    $update = $pdo->prepare('UPDATE inventory_items SET current_quantity = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$newQty, $inventoryItemId]);

    $movement = $pdo->prepare('INSERT INTO stock_movements (inventory_item_id, movement_type, quantity, unit_price, reference_type, reference_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $movement->execute([$inventoryItemId, $type, abs($quantity), $unitPrice, $refType, $refId, $notes, $userId]);
}

function get_supplier_outstanding(int $supplierId): float
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT opening_balance FROM suppliers WHERE id = ?');
    $stmt->execute([$supplierId]);
    $opening = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(grand_total), 0) FROM purchases WHERE supplier_id = ?');
    $stmt->execute([$supplierId]);
    $purchases = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE supplier_id = ?');
    $stmt->execute([$supplierId]);
    $paid = (float) $stmt->fetchColumn();

    return $opening + $purchases - $paid;
}

function recalculate_daily_cash(string $date): void
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_date = ? AND status = 'completed' AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $cashSales = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $supplierPayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM salary_payments WHERE payment_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $employeePayments = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = ? AND payment_method = 'cash'");
    $stmt->execute([$date]);
    $expensesTotal = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT * FROM daily_cash WHERE cash_date = ?');
    $stmt->execute([$date]);
    $record = $stmt->fetch();

    if ($record) {
        $opening = (float) $record['opening_cash'];
        $otherIncome = (float) $record['other_income'];
        $expected = $opening + $cashSales + $otherIncome - $supplierPayments - $employeePayments - $expensesTotal;
        $actual = $record['actual_closing'] !== null ? (float) $record['actual_closing'] : null;
        $difference = $actual !== null ? $actual - $expected : null;

        $update = $pdo->prepare('UPDATE daily_cash SET cash_sales = ?, supplier_payments = ?, employee_payments = ?, expenses_total = ?, expected_closing = ?, difference = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$cashSales, $supplierPayments, $employeePayments, $expensesTotal, $expected, $difference, $record['id']]);
    }
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
}

function post_float(string $key, float $default = 0.0): float
{
    return isset($_POST[$key]) ? (float) $_POST[$key] : $default;
}

function post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
