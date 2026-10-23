<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'New Purchase';
$activeMenu = 'purchases';

$suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE status='active' ORDER BY name")->fetchAll();
$inventory = $pdo->query("SELECT id, name, unit FROM inventory_items WHERE status='active' ORDER BY name")->fetchAll();

if (is_post()) {
    require_csrf();

    $purchaseDate = post_string('purchase_date', date('Y-m-d'));
    $supplierId = post_int('supplier_id');
    $amountPaid = max(0, post_float('amount_paid'));
    $notes = post_string('notes');
    $itemIds = $_POST['inventory_item_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $units = $_POST['unit'] ?? [];
    $rates = $_POST['rate'] ?? [];

    try {
        if ($supplierId <= 0) {
            throw new RuntimeException('Select a supplier.');
        }

        $lines = [];
        $grandTotal = 0;

        foreach ($itemIds as $i => $invId) {
            $invId = (int) $invId;
            $qty = max(0, (float) ($quantities[$i] ?? 0));
            $rate = max(0, (float) ($rates[$i] ?? 0));
            if ($invId <= 0 || $qty <= 0) {
                continue;
            }

            $stmt = $pdo->prepare('SELECT name, unit FROM inventory_items WHERE id = ?');
            $stmt->execute([$invId]);
            $inv = $stmt->fetch();
            if (!$inv) {
                continue;
            }

            $lineTotal = round($qty * $rate, 2);
            $grandTotal += $lineTotal;
            $lines[] = [
                'inventory_item_id' => $invId,
                'item_name' => $inv['name'],
                'quantity' => $qty,
                'unit' => $units[$i] ?: $inv['unit'],
                'rate' => $rate,
                'line_total' => $lineTotal,
            ];
        }

        if (!$lines) {
            throw new RuntimeException('Add at least one purchase item.');
        }

        if ($amountPaid > $grandTotal) {
            throw new RuntimeException('Amount paid cannot exceed grand total.');
        }

        $remaining = $grandTotal - $amountPaid;
        $paymentStatus = purchase_payment_status($grandTotal, $amountPaid);
        $userId = (int) $_SESSION['user_id'];

        $pdo->beginTransaction();

        $purchaseNumber = generate_number('purchase_prefix', 'PUR');
        $stmt = $pdo->prepare('INSERT INTO purchases (purchase_number, purchase_date, supplier_id, grand_total, amount_paid, remaining, payment_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$purchaseNumber, $purchaseDate, $supplierId, $grandTotal, $amountPaid, $remaining, $paymentStatus, $notes, $userId]);
        $purchaseId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO purchase_items (purchase_id, inventory_item_id, item_name, quantity, unit, rate, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($lines as $line) {
            $itemStmt->execute([$purchaseId, $line['inventory_item_id'], $line['item_name'], $line['quantity'], $line['unit'], $line['rate'], $line['line_total']]);
            update_inventory_from_purchase($line['inventory_item_id'], $line['quantity'], $line['rate'], $purchaseId, $userId);
        }

        log_activity('create', 'purchases', "Purchase {$purchaseNumber} created");
        $pdo->commit();

        flash('success', 'Purchase saved and inventory updated.');
        redirect(url('purchases/view.php?id=' . $purchaseId));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $e->getMessage());
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">New Purchase</h4>
    <a href="<?= url('purchases/index.php') ?>" class="btn btn-outline-secondary">Back</a>
</div>

<form method="post" id="purchaseForm">
    <?= csrf_field() ?>
    <div class="card mb-3">
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Amount Paid</label><input type="number" step="0.01" min="0" name="amount_paid" id="amountPaid" class="form-control" value="0"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Purchase Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">Add Row</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="itemsTable">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Line Total</th><th></th></tr></thead>
                <tbody></tbody>
                <tfoot><tr><td colspan="4" class="text-end fw-bold">Grand Total</td><td class="fw-bold" id="grandTotal">Rs.0.00</td><td></td></tr>
                <tr><td colspan="4" class="text-end">Remaining</td><td id="remainingTotal">Rs.0.00</td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
    <button class="btn btn-primary-custom btn-lg">Save Purchase</button>
</form>

<script>
const inventoryOptions = <?= json_encode($inventory) ?>;
const tbody = document.querySelector('#itemsTable tbody');

function addRow() {
    let opts = '<option value="">Select Item</option>';
    inventoryOptions.forEach(i => { opts += `<option value="${i.id}" data-unit="${i.unit}">${i.name}</option>`; });
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="inventory_item_id[]" class="form-select item-select" required>${opts}</select></td>
        <td><input type="number" step="0.001" min="0" name="quantity[]" class="form-control qty-input" value="1"></td>
        <td><input type="text" name="unit[]" class="form-control unit-input" value="kg"></td>
        <td><input type="number" step="0.01" min="0" name="rate[]" class="form-control rate-input" value="0"></td>
        <td class="line-total">Rs.0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>`;
    tbody.appendChild(tr);
    bindRow(tr);
    calcTotals();
}

function bindRow(tr) {
    tr.querySelector('.item-select').addEventListener('change', function() {
        const unit = this.selectedOptions[0]?.dataset.unit || 'kg';
        tr.querySelector('.unit-input').value = unit;
        calcTotals();
    });
    tr.querySelectorAll('.qty-input, .rate-input').forEach(el => el.addEventListener('input', calcTotals));
    tr.querySelector('.remove-row').addEventListener('click', () => { tr.remove(); calcTotals(); });
}

function calcTotals() {
    let grand = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const rate = parseFloat(tr.querySelector('.rate-input').value) || 0;
        const line = qty * rate;
        grand += line;
        tr.querySelector('.line-total').textContent = formatMoney(line);
    });
    const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    document.getElementById('grandTotal').textContent = formatMoney(grand);
    document.getElementById('remainingTotal').textContent = formatMoney(Math.max(0, grand - paid));
}

document.getElementById('addRowBtn').addEventListener('click', addRow);
document.getElementById('amountPaid').addEventListener('input', calcTotals);
addRow();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
