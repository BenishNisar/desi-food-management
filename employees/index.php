<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Employees';
$activeMenu = 'employees';

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $id = post_int('id');
        $data = [
            post_string('name'),
            post_string('phone'),
            post_string('cnic') ?: null,
            post_string('designation'),
            post_string('joining_date') ?: null,
            post_float('monthly_salary'),
            post_string('status', 'active'),
            post_string('address'),
            post_string('notes'),
        ];

        if ($data[0] === '') {
            flash('danger', 'Employee name is required.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE employees SET name=?, phone=?, cnic=?, designation=?, joining_date=?, monthly_salary=?, status=?, address=?, notes=?, updated_at=NOW() WHERE id=?')
                    ->execute([...$data, $id]);
                flash('success', 'Employee updated.');
            } else {
                $pdo->prepare('INSERT INTO employees (name, phone, cnic, designation, joining_date, monthly_salary, status, address, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute($data);
                flash('success', 'Employee added.');
            }
            log_activity('save', 'employees', 'Saved employee ' . $data[0]);
        }
        redirect(url('employees/index.php'));
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}

$employees = $pdo->query('SELECT * FROM employees ORDER BY status DESC, name')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Employees</h4>
    <a href="<?= url('employees/advances.php') ?>" class="btn btn-outline-primary">Employee Advances</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0"><?= $edit ? 'Edit' : 'Add' ?> Employee</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Name" required value="<?= e($edit['name'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="phone" class="form-control" placeholder="Phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="cnic" class="form-control" placeholder="CNIC (optional)" value="<?= e($edit['cnic'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="designation" class="form-control" placeholder="Designation" value="<?= e($edit['designation'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="date" name="joining_date" class="form-control" value="<?= e($edit['joining_date'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="number" step="0.01" name="monthly_salary" class="form-control" placeholder="Monthly salary" value="<?= e($edit['monthly_salary'] ?? '') ?>"></div>
                    <div class="mb-2"><select name="status" class="form-select"><option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                    <div class="mb-2"><textarea name="address" class="form-control" placeholder="Address" rows="2"><?= e($edit['address'] ?? '') ?></textarea></div>
                    <div class="mb-2"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"><?= e($edit['notes'] ?? '') ?></textarea></div>
                    <button class="btn btn-primary-custom w-100">Save Employee</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Name</th><th>Designation</th><th>Phone</th><th>Salary</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= e($emp['name']) ?></td>
                        <td><?= e($emp['designation'] ?? '-') ?></td>
                        <td><?= e($emp['phone'] ?? '-') ?></td>
                        <td><?= format_money($emp['monthly_salary']) ?></td>
                        <td><span class="badge bg-<?= $emp['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($emp['status'])) ?></span></td>
                        <td><a href="<?= url('employees/index.php?edit=' . $emp['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
