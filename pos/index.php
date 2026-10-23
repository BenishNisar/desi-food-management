<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$pageTitle = 'POS / New Order';
$activeMenu = 'pos';

$stmt = $pdo->query("SELECT m.*, c.name AS category_name FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id WHERE m.status = 'active' ORDER BY c.name, m.name");
$menuItems = $stmt->fetchAll();

$categories = [];
foreach ($menuItems as $item) {
    $cat = $item['category_name'] ?: 'Other';
    $categories[$cat][] = $item;
}

require BASE_PATH . '/includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-body">
                <input type="text" id="menuSearch" class="form-control form-control-lg mb-3" placeholder="Search menu items...">
                <?php foreach ($categories as $catName => $items): ?>
                <h6 class="text-muted mt-3 mb-2"><?= e($catName) ?></h6>
                <div class="row g-2 mb-2">
                    <?php foreach ($items as $item): ?>
                    <div class="col-6 col-md-4 menu-item-wrap">
                        <button type="button" class="btn pos-item-btn w-100 menu-add-btn"
                            data-id="<?= (int)$item['id'] ?>"
                            data-name="<?= e($item['name']) ?>"
                            data-price="<?= e($item['price']) ?>">
                            <?= e($item['name']) ?>
                            <span class="price"><?= format_money($item['price']) ?></span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card sticky-top" style="top:80px;">
            <div class="card-header bg-white"><h5 class="mb-0">Current Order</h5></div>
            <div class="card-body">
                <div id="cartItems"><p class="text-muted text-center">No items added</p></div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="orderNotes" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Discount (Rs.)</label>
                    <input type="number" id="orderDiscount" class="form-control" value="0" min="0" step="0.01">
                </div>
                <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><strong id="subtotal">Rs.0.00</strong></div>
                <div class="d-flex justify-content-between mb-3"><span>Grand Total</span><strong class="fs-4 text-danger" id="grandTotal">Rs.0.00</strong></div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select id="paymentMethod" class="form-select form-select-lg">
                        <?php foreach (payment_methods() as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" id="completeOrderBtn" class="btn btn-primary-custom btn-lg">Complete Order</button>
                    <button type="button" id="clearCartBtn" class="btn btn-outline-secondary">Clear Cart</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const cart = {};

document.querySelectorAll('.menu-add-btn').forEach(btn => {
    btn.addEventListener('click', () => addToCart(btn.dataset.id, btn.dataset.name, parseFloat(btn.dataset.price)));
});

document.getElementById('menuSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.menu-item-wrap').forEach(w => {
        const text = w.textContent.toLowerCase();
        w.style.display = text.includes(q) ? '' : 'none';
    });
});

document.getElementById('orderDiscount').addEventListener('input', renderCart);
document.getElementById('clearCartBtn').addEventListener('click', () => { Object.keys(cart).forEach(k => delete cart[k]); renderCart(); });

function addToCart(id, name, price) {
    if (!cart[id]) cart[id] = { id, name, price, qty: 0 };
    cart[id].qty++;
    renderCart();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const keys = Object.keys(cart);
    if (!keys.length) {
        container.innerHTML = '<p class="text-muted text-center">No items added</p>';
        document.getElementById('subtotal').textContent = formatMoney(0);
        document.getElementById('grandTotal').textContent = formatMoney(0);
        return;
    }
    let subtotal = 0;
    let html = '';
    keys.forEach(id => {
        const item = cart[id];
        const line = item.price * item.qty;
        subtotal += line;
        html += `<div class="cart-item d-flex justify-content-between align-items-center">
            <div><strong>${item.name}</strong><br><small>${formatMoney(item.price)} x ${item.qty}</small></div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary qty-btn" onclick="changeQty('${id}', -1)">-</button>
                <span>${item.qty}</span>
                <button class="btn btn-outline-secondary qty-btn" onclick="changeQty('${id}', 1)">+</button>
                <strong class="ms-2">${formatMoney(line)}</strong>
            </div></div>`;
    });
    container.innerHTML = html;
    const discount = parseFloat(document.getElementById('orderDiscount').value) || 0;
    const grand = Math.max(0, subtotal - discount);
    document.getElementById('subtotal').textContent = formatMoney(subtotal);
    document.getElementById('grandTotal').textContent = formatMoney(grand);
}

document.getElementById('completeOrderBtn').addEventListener('click', function() {
    const items = Object.values(cart);
    if (!items.length) { alert('Add at least one item.'); return; }
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('notes', document.getElementById('orderNotes').value);
    formData.append('discount', document.getElementById('orderDiscount').value);
    formData.append('payment_method', document.getElementById('paymentMethod').value);
    formData.append('items', JSON.stringify(items));
    fetch(BASE_URL + '/ajax/create_order.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = BASE_URL + '/orders/print.php?id=' + data.order_id;
        } else {
            alert(data.message || 'Failed to create order.');
        }
    });
});
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
