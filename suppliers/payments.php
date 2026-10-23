<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Supplier Payments';
$activeMenu = 'supplier_payments';

$suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE status='active' ORDER BY name")->fetchAll();

if (is_post()) {
    require_csrf();
    $supplierId = post_int('supplier_id');
    $paymentDate = post_string('payment_date', date('Y-m-d'));
    $amount = post_float('amount');
    $method = post_string('payment_method', 'cash');
    $reference = post_string('reference');
    $notes = post_string('notes');
    $userId = (int) $_SESSION['user_id'];

    if ($supplierId <= 0 || $amount <= 0) {
        flash('danger', 'Supplier and valid amount required.');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO supplier_payments (supplier_id, payment_date, amount, payment_method, reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$supplierId, $paymentDate, $amount, $method, $reference, $notes, $userId]);

            $stmt = $pdo->prepare("SELECT id, remaining, grand_total, amount_paid FROM purchases WHERE supplier_id = ? AND payment_status IN ('partial','unpaid') ORDER BY purchase_date ASC, id ASC");
            $stmt->execute([$supplierId]);
            $remainingPayment = $amount;

            while ($remainingPayment > 0 && ($purchase = $stmt->fetch())) {
                $apply = min($remainingPayment, (float) $purchase['remaining']);
                $newPaid = (float) $purchase['amount_paid'] + $apply;
                $newRemaining = (float) $purchase['grand_total'] - $newPaid;
                $status = purchase_payment_status((float) $purchase['grand_total'], $newPaid);

                $upd = $pdo->prepare('UPDATE purchases SET amount_paid=?, remaining=?, payment_status=?, updated_at=NOW() WHERE id=?');
                $upd->execute([$newPaid, max(0, $newRemaining), $status, $purchase['id']]);
                $remainingPayment -= $apply;
            }

            recalculate_daily_cash($paymentDate);
            log_activity('payment', 'suppliers', "Supplier payment of {$amount} for supplier #{$supplierId}");
            $pdo->commit();
            flash('success', 'Payment recorded and applied to purchases.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $e->getMessage());
        }
    }
    redirect(url('suppliers/payments.php'));
}

$payments = $pdo->query('SELECT sp.*, s.name AS supplier_name, u.full_name FROM supplier_payments sp JOIN suppliers s ON s.id = sp.supplier_id LEFT JOIN users u ON u.id = sp.created_by ORDER BY sp.payment_date DESC, sp.id DESC LIMIT 200')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Supplier Payments</h4></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Record Payment</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Method</label>
                        <select name="payment_method" class="form-select">
                            <?php foreach (payment_methods() as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><input type="text" name="reference" class="form-control" placeholder="Reference"></div>
                    <div class="mb-3"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Payment</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Supplier</th><th>Amount</th><th>Method</th><th>By</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= format_date($p['payment_date']) ?></td>
                        <td><?= e($p['supplier_name']) ?></td>
                        <td><?= format_money($p['amount']) ?></td>
                        <td><?= e(payment_methods()[$p['payment_method']] ?? $p['payment_method']) ?></td>
                        <td><?= e($p['full_name'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
