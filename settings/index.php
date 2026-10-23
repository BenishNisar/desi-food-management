<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pageTitle = 'Settings';
$activeMenu = 'settings';

$settingsKeys = ['shop_name', 'shop_address', 'shop_phone', 'currency_symbol', 'receipt_footer', 'order_prefix', 'purchase_prefix'];

if (is_post()) {
    require_csrf();
    foreach ($settingsKeys as $key) {
        if (isset($_POST[$key])) {
            set_setting($key, post_string($key));
        }
    }
    log_activity('update', 'settings', 'Settings updated');
    flash('success', 'Settings saved successfully.');
    redirect(url('settings/index.php'));
}

$settings = [];
foreach ($settingsKeys as $key) {
    $settings[$key] = get_setting($key);
}

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header"><h4 class="mb-0">Settings</h4></div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Shop Name</label><input type="text" name="shop_name" class="form-control" value="<?= e($settings['shop_name']) ?>" required></div>
                    <div class="mb-3"><label class="form-label">Shop Address</label><textarea name="shop_address" class="form-control" rows="2"><?= e($settings['shop_address']) ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Shop Phone</label><input type="text" name="shop_phone" class="form-control" value="<?= e($settings['shop_phone']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?= e($settings['currency_symbol']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Receipt Footer</label><textarea name="receipt_footer" class="form-control" rows="2"><?= e($settings['receipt_footer']) ?></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Order Prefix</label><input type="text" name="order_prefix" class="form-control" value="<?= e($settings['order_prefix']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Purchase Prefix</label><input type="text" name="purchase_prefix" class="form-control" value="<?= e($settings['purchase_prefix']) ?>"></div>
                    </div>
                    <button class="btn btn-primary-custom">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
