<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';

$todaySales = get_today_sales();
$todayOrders = get_today_orders_count();
$todayExpenses = get_today_expenses();
$todayProfit = $todaySales - $todayExpenses;
$pendingSupplier = get_pending_supplier_payments();

$monthStart = date('Y-m-01');
$today = date('Y-m-d');
$monthlySales = get_period_sales($monthStart, $today);
$monthlyExpenses = get_period_expenses($monthStart, $today);
$monthlyProfit = $monthlySales - $monthlyExpenses;

$stmt = $pdo->query("SELECT o.*, u.full_name AS created_by_name FROM orders o LEFT JOIN users u ON u.id = o.created_by ORDER BY o.created_at DESC LIMIT 8");
$recentOrders = $stmt->fetchAll();

$stmt = $pdo->query('SELECT e.*, ec.name AS category_name FROM expenses e JOIN expense_categories ec ON ec.id = e.category_id ORDER BY e.created_at DESC LIMIT 8');
$recentExpenses = $stmt->fetchAll();

$stmt = $pdo->query("SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.line_total) AS total_sales
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status = 'completed' AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY oi.item_name ORDER BY total_qty DESC LIMIT 5");
$bestSelling = $stmt->fetchAll();

$stmt = $pdo->query('SELECT * FROM inventory_items WHERE status = \'active\' AND current_quantity <= minimum_stock ORDER BY current_quantity ASC LIMIT 8');
$lowStock = $stmt->fetchAll();

$stmt = $pdo->query("SELECT order_date AS sale_date, COALESCE(SUM(grand_total), 0) AS total
    FROM orders WHERE status = 'completed' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY order_date ORDER BY order_date ASC");
$salesChart = $stmt->fetchAll();

$stmt = $pdo->query("SELECT expense_date AS edate, COALESCE(SUM(amount), 0) AS total FROM expenses
    WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY expense_date ORDER BY expense_date ASC");
$expensesChart = $stmt->fetchAll();

$labels = [];
$salesData = [];
$expensesData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d M', strtotime($d));
    $salesData[] = 0;
    $expensesData[] = 0;
}
foreach ($salesChart as $row) {
    $idx = array_search(date('d M', strtotime($row['sale_date'])), $labels, true);
    if ($idx !== false) {
        $salesData[$idx] = (float) $row['total'];
    }
}
foreach ($expensesChart as $row) {
    $idx = array_search(date('d M', strtotime($row['edate'])), $labels, true);
    if ($idx !== false) {
        $expensesData[$idx] = (float) $row['total'];
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-currency-rupee"></i></div>
                <div><div class="text-muted small">Today's Sales</div><div class="fs-5 fw-bold"><?= format_money($todaySales) ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></div>
                <div><div class="text-muted small">Today's Orders</div><div class="fs-5 fw-bold"><?= $todayOrders ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-wallet2"></i></div>
                <div><div class="text-muted small">Today's Expenses</div><div class="fs-5 fw-bold"><?= format_money($todayExpenses) ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-graph-up"></i></div>
                <div><div class="text-muted small">Today's Profit</div><div class="fs-5 fw-bold"><?= format_money($todayProfit) ?></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Pending Supplier Payments</div><div class="fs-4 fw-bold text-danger"><?= format_money($pendingSupplier) ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Monthly Sales</div><div class="fs-4 fw-bold"><?= format_money($monthlySales) ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Monthly Profit</div><div class="fs-4 fw-bold text-success"><?= format_money($monthlyProfit) ?></div><small class="text-muted">Expenses: <?= format_money($monthlyExpenses) ?></small></div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="mb-0">7 Day Sales</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="salesChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="mb-0">Sales vs Expenses (7 Days)</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="compareChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between"><h6 class="mb-0">Recent Orders</h6><a href="<?= url('orders/index.php') ?>">View All</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Order</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><a href="<?= url('orders/view.php?id=' . $o['id']) ?>"><?= e($o['order_number']) ?></a><br><small class="text-muted"><?= format_date($o['order_date']) ?></small></td>
                        <td><?= format_money($o['grand_total']) ?></td>
                        <td><span class="badge bg-<?= $o['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentOrders): ?><tr><td colspan="3" class="text-center text-muted">No orders yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between"><h6 class="mb-0">Recent Expenses</h6><a href="<?= url('expenses/index.php') ?>">View All</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Category</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentExpenses as $ex): ?>
                    <tr>
                        <td><?= e($ex['category_name']) ?><br><small class="text-muted"><?= e($ex['description'] ?? '') ?></small></td>
                        <td><?= format_money($ex['amount']) ?></td>
                        <td><?= format_date($ex['expense_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentExpenses): ?><tr><td colspan="3" class="text-center text-muted">No expenses yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Best Selling Items (30 Days)</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th>Qty</th><th>Sales</th></tr></thead>
                    <tbody>
                    <?php foreach ($bestSelling as $bs): ?>
                    <tr><td><?= e($bs['item_name']) ?></td><td><?= (int)$bs['total_qty'] ?></td><td><?= format_money($bs['total_sales']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$bestSelling): ?><tr><td colspan="3" class="text-center text-muted">No sales data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0 text-danger">Low Stock Alerts</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th>Current</th><th>Min</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $ls): ?>
                    <tr><td><?= e($ls['name']) ?></td><td><span class="badge badge-low-stock"><?= e($ls['current_quantity']) ?> <?= e($ls['unit']) ?></span></td><td><?= e($ls['minimum_stock']) ?> <?= e($ls['unit']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?><tr><td colspan="3" class="text-center text-muted">All stock levels OK</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
const labels = ' . json_encode($labels) . ';
const salesData = ' . json_encode($salesData) . ';
const expensesData = ' . json_encode($expensesData) . ';
new Chart(document.getElementById("salesChart"), { type: "line", data: { labels, datasets: [{ label: "Sales", data: salesData, borderColor: "#c0392b", backgroundColor: "rgba(192,57,43,0.1)", fill: true, tension: 0.3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById("compareChart"), { type: "bar", data: { labels, datasets: [{ label: "Sales", data: salesData, backgroundColor: "#c0392b" }, { label: "Expenses", data: expensesData, backgroundColor: "#3498db" }] }, options: { responsive: true, maintainAspectRatio: false } });
</script>';
require BASE_PATH . '/includes/footer.php';
