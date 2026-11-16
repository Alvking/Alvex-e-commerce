<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$overview = $pdo->query(
    "SELECT
        COUNT(*) AS order_count,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS revenue_30d,
        COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS orders_30d
     FROM orders"
)->fetch(PDO::FETCH_ASSOC);
$dailySales = $pdo->query(
    "SELECT DATE(created_at) AS sale_date,
            COUNT(*) AS order_count,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS revenue
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(created_at)
     ORDER BY sale_date DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$bestSellers = $pdo->query(
    'SELECT product_name, SUM(quantity) AS units_sold, SUM(line_total) AS sales_total
     FROM order_items
     GROUP BY product_id, product_name
     ORDER BY units_sold DESC, sales_total DESC
     LIMIT 10'
)->fetchAll(PDO::FETCH_ASSOC);

admin_header('Analytics');
?>
<div class="mb-4"><h1 class="h2 mb-1">Analytics</h1><p class="text-muted mb-0">Sales are based on orders marked as paid.</p></div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card admin-card h-100"><div class="card-body"><div class="text-muted small">All orders</div><div class="admin-stat"><?= (int)$overview['order_count'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card admin-card h-100"><div class="card-body"><div class="text-muted small">Paid revenue (all time)</div><div class="admin-stat">KSh <?= number_format((float)$overview['paid_revenue'], 2) ?></div></div></div></div>
    <div class="col-md-3"><div class="card admin-card h-100"><div class="card-body"><div class="text-muted small">Paid revenue (30 days)</div><div class="admin-stat">KSh <?= number_format((float)$overview['revenue_30d'], 2) ?></div></div></div></div>
    <div class="col-md-3"><div class="card admin-card h-100"><div class="card-body"><div class="text-muted small">Orders (30 days)</div><div class="admin-stat"><?= (int)$overview['orders_30d'] ?></div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-7"><section class="card admin-card"><div class="card-body">
        <h2 class="h5 mb-3">Daily sales, last 30 days</h2>
        <div class="table-responsive"><table class="table admin-table">
            <thead><tr><th>Date</th><th>Orders</th><th>Paid revenue</th></tr></thead>
            <tbody><?php foreach ($dailySales as $day): ?><tr><td><?= admin_escape($day['sale_date']) ?></td><td><?= (int)$day['order_count'] ?></td><td>KSh <?= number_format((float)$day['revenue'], 2) ?></td></tr><?php endforeach; ?>
            <?php if ($dailySales === []): ?><tr><td colspan="3" class="text-center text-muted">No orders in this period.</td></tr><?php endif; ?></tbody>
        </table></div>
    </div></section></div>
    <div class="col-lg-5"><section class="card admin-card"><div class="card-body">
        <h2 class="h5 mb-3">Best sellers</h2>
        <div class="table-responsive"><table class="table admin-table">
            <thead><tr><th>Product</th><th>Units</th><th>Sales</th></tr></thead>
            <tbody><?php foreach ($bestSellers as $item): ?><tr><td><?= admin_escape($item['product_name']) ?></td><td><?= (int)$item['units_sold'] ?></td><td>KSh <?= number_format((float)$item['sales_total'], 2) ?></td></tr><?php endforeach; ?>
            <?php if ($bestSellers === []): ?><tr><td colspan="3" class="text-center text-muted">No product sales yet.</td></tr><?php endif; ?></tbody>
        </table></div>
    </div></section></div>
</div>
<?php admin_footer(); ?>
