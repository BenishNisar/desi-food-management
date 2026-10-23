<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'Menu Management';
$activeMenu = 'menu';

if (is_post()) {
    require_csrf();
    $action = post_string('action');

    if ($action === 'save_item') {
        $id = post_int('id');
        $name = post_string('name');
        $categoryId = post_int('category_id') ?: null;
        $price = post_float('price');
        $description = post_string('description');
        $status = post_string('status', 'active');

        if ($name === '' || $price < 0) {
            flash('danger', 'Name and valid price are required.');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE menu_items SET category_id=?, name=?, price=?, description=?, status=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$categoryId, $name, $price, $description, $status, $id]);
                log_activity('update', 'menu', "Updated menu item {$name}");
                flash('success', 'Menu item updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO menu_items (category_id, name, price, description, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$categoryId, $name, $price, $description, $status]);
                log_activity('create', 'menu', "Created menu item {$name}");
                flash('success', 'Menu item created.');
            }
        }
        redirect(url('menu/index.php'));
    }

    if ($action === 'save_category') {
        $catId = post_int('category_id');
        $catName = post_string('category_name');
        if ($catName !== '') {
            if ($catId > 0) {
                $pdo->prepare('UPDATE categories SET name=? WHERE id=?')->execute([$catName, $catId]);
            } else {
                $pdo->prepare('INSERT INTO categories (name, status) VALUES (?, ?)')->execute([$catName, 'active']);
            }
            flash('success', 'Category saved.');
        }
        redirect(url('menu/index.php'));
    }

    if ($action === 'delete_item') {
        $delId = post_int('id');
        $pdo->prepare('UPDATE menu_items SET status=?, updated_at=NOW() WHERE id=?')->execute(['inactive', $delId]);
        flash('success', 'Menu item deactivated.');
        redirect(url('menu/index.php'));
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editItem = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch();
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$items = $pdo->query('SELECT m.*, c.name AS category_name FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id ORDER BY m.status DESC, m.name')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h4 class="mb-0">Menu Management</h4>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><?= $editItem ? 'Edit Item' : 'Add Menu Item' ?></h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_item">
                    <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="<?= e($editItem['name'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (($editItem['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Price (Rs.)</label><input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e($editItem['price'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e($editItem['description'] ?? '') ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= (($editItem['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($editItem['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <button class="btn btn-primary-custom w-100"><?= $editItem ? 'Update' : 'Add' ?> Item</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Add Category</h6></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_category">
                    <input type="text" name="category_name" class="form-control mb-2" placeholder="Category name" required>
                    <button class="btn btn-outline-primary w-100">Save Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= e($item['category_name'] ?? '-') ?></td>
                        <td><?= format_money($item['price']) ?></td>
                        <td><span class="badge bg-<?= $item['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($item['status'])) ?></span></td>
                        <td class="text-end">
                            <a href="<?= url('menu/index.php?edit=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <?php if ($item['status'] === 'active'): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Deactivate this item?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                            </form>
                            <?php endif; ?>
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
