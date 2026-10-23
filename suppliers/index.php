<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Suppliers';
$activeMenu = 'suppliers';

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $id = post_int('id');
        $name = post_string('name');
        $contact = post_string('contact_person');
        $phone = post_string('phone');
        $address = post_string('address');
        $opening = post_float('opening_balance');
        $status = post_string('status', 'active');

        if ($name === '') {
            flash('danger', 'Supplier name is required.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE suppliers SET name=?, contact_person=?, phone=?, address=?, opening_balance=?, status=?, updated_at=NOW() WHERE id=?')
                    ->execute([$name, $contact, $phone, $address, $opening, $status, $id]);
                flash('success', 'Supplier updated.');
            } else {
                $pdo->prepare('INSERT INTO suppliers (name, contact_person, phone, address, opening_balance, status) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$name, $contact, $phone, $address, $opening, $status]);
                flash('success', 'Supplier created.');
            }
            log_activity('save', 'suppliers', "Saved supplier {$name}");
        }
        redirect(url('suppliers/index.php'));
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY status DESC, name')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Suppliers</h4></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0"><?= $edit ? 'Edit' : 'Add' ?> Supplier</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Name" required value="<?= e($edit['name'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="contact_person" class="form-control" placeholder="Contact person" value="<?= e($edit['contact_person'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="phone" class="form-control" placeholder="Phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
                    <div class="mb-2"><textarea name="address" class="form-control" placeholder="Address" rows="2"><?= e($edit['address'] ?? '') ?></textarea></div>
                    <div class="mb-2"><input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="Opening balance" value="<?= e($edit['opening_balance'] ?? '0') ?>"></div>
                    <?php if ($edit): ?>
                    <div class="mb-2"><select name="status" class="form-select"><option value="active" <?= ($edit['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                    <?php endif; ?>
                    <button class="btn btn-primary-custom w-100">Save Supplier</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Name</th><th>Phone</th><th>Total Purchases</th><th>Total Paid</th><th>Outstanding</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($suppliers as $s):
                        $stmt = $pdo->prepare('SELECT COALESCE(SUM(grand_total),0) FROM purchases WHERE supplier_id=?');
                        $stmt->execute([$s['id']]);
                        $totalPurchases = (float) $stmt->fetchColumn();
                        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE supplier_id=?');
                        $stmt->execute([$s['id']]);
                        $totalPaid = (float) $stmt->fetchColumn();
                        $outstanding = get_supplier_outstanding((int)$s['id']);
                    ?>
                    <tr>
                        <td><?= e($s['name']) ?></td>
                        <td><?= e($s['phone'] ?? '-') ?></td>
                        <td><?= format_money($totalPurchases) ?></td>
                        <td><?= format_money($totalPaid) ?></td>
                        <td class="<?= $outstanding > 0 ? 'text-danger fw-bold' : '' ?>"><?= format_money($outstanding) ?></td>
                        <td class="text-end">
                            <a href="<?= url('suppliers/view.php?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="<?= url('suppliers/index.php?edit=' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
