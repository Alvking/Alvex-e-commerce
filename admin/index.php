<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$stats = [
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn(),
    'sales' => (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
];
$recentOrders = $pdo->query(
    'SELECT id, order_number, customer_name, total_amount, order_status, payment_status, created_at
     FROM orders ORDER BY created_at DESC LIMIT 8'
)->fetchAll(PDO::FETCH_ASSOC);

admin_header('Overview');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Store overview</h1>
        <p class="text-muted mb-0">Welcome, <?= admin_escape($_SESSION['admin_name'] ?? 'Admin') ?>.</p>
    </div>
    <a class="btn btn-dark" href="product-form.php">Add product</a>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Active products', $stats['products']],
        ['Categories', $stats['categories']],
        ['Orders', $stats['orders']],
        ['Pending orders', $stats['pending']],
        ['Paid sales', 'KSh ' . number_format($stats['sales'], 2)],
    ] as [$label, $value]): ?>
        <div class="col-12 col-sm-6 col-xl">
            <div class="card admin-card h-100"><div class="card-body">
                <div class="text-muted small"><?= admin_escape($label) ?></div>
                <div class="admin-stat"><?= admin_escape($value) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
<section class="card admin-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Recent orders</h2>
            <a href="orders.php">All orders</a>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Order status</th><th>Payment</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><a href="order.php?id=<?= (int)$order['id'] ?>"><?= admin_escape($order['order_number']) ?></a></td>
                        <td><?= admin_escape($order['customer_name']) ?></td>
                        <td>KSh <?= number_format((float)$order['total_amount'], 2) ?></td>
                        <td><?= admin_escape(ucfirst($order['order_status'])) ?></td>
                        <td><?= admin_escape(ucfirst($order['payment_status'])) ?></td>
                        <td><?= admin_escape($order['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recentOrders === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No orders have been placed yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php admin_footer(); ?>
