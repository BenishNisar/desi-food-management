<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'User Management';
$activeMenu = 'users';

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $id = post_int('id');
        $username = post_string('username');
        $fullName = post_string('full_name');
        $role = post_string('role', 'staff');
        $status = post_string('status', 'active');
        $password = post_string('password');

        if ($username === '' || $fullName === '') {
            flash('danger', 'Username and full name required.');
        } else {
            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, status=?, password=?, updated_at=NOW() WHERE id=?')
                        ->execute([$username, $fullName, $role, $status, $hash, $id]);
                } else {
                    $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, status=?, updated_at=NOW() WHERE id=?')
                        ->execute([$username, $fullName, $role, $status, $id]);
                }
                flash('success', 'User updated.');
            } else {
                if ($password === '') {
                    flash('danger', 'Password required for new user.');
                    redirect(url('users/index.php'));
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$username, $hash, $fullName, $role, $status]);
                flash('success', 'User created.');
            }
            log_activity('save', 'users', "Saved user {$username}");
        }
        redirect(url('users/index.php'));
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, username, full_name, role, status FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}

$users = $pdo->query('SELECT id, username, full_name, role, status, created_at FROM users ORDER BY id')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">User Management</h4></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0"><?= $edit ? 'Edit' : 'Add' ?> User</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    <div class="mb-2"><input type="text" name="username" class="form-control" placeholder="Username" required value="<?= e($edit['username'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="text" name="full_name" class="form-control" placeholder="Full name" required value="<?= e($edit['full_name'] ?? '') ?>"></div>
                    <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="<?= $edit ? 'New password (optional)' : 'Password' ?>" <?= $edit ? '' : 'required' ?>></div>
                    <div class="mb-2"><select name="role" class="form-select"><option value="staff" <?= ($edit['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option><option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
                    <div class="mb-2"><select name="status" class="form-select"><option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                    <button class="btn btn-primary-custom w-100">Save User</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td><?= e(ucfirst($u['status'])) ?></td>
                        <td><?= format_date($u['created_at']) ?></td>
                        <td><a href="<?= url('users/index.php?edit=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
