<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$status = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$sql = 'SELECT id, order_number, customer_name, email, total_amount, payment_method, payment_status, order_status, created_at FROM orders';
$params = [];
if (in_array($status, $allowedStatuses, true)) {
    $sql .= ' WHERE order_status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Orders');
?>
<div class="mb-4"><h1 class="h2 mb-1">Orders</h1><p class="text-muted mb-0">Review customer orders and update their fulfilment status.</p></div>
<form class="mb-3" method="get">
    <label class="visually-hidden" for="status">Filter by status</label>
    <select class="form-select d-inline-block" style="max-width: 240px;" id="status" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach ($allowedStatuses as $option): ?><option value="<?= admin_escape($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= admin_escape(ucfirst($option)) ?></option><?php endforeach; ?>
    </select>
</form>
<section class="card admin-card"><div class="card-body">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead><tr><th>Order</th><th>Customer</th><th>Payment</th><th>Total</th><th>Payment status</th><th>Order status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><a href="order.php?id=<?= (int)$order['id'] ?>"><?= admin_escape($order['order_number']) ?></a></td>
                    <td><?= admin_escape($order['customer_name']) ?><div class="small text-muted"><?= admin_escape($order['email']) ?></div></td>
                    <td><?= admin_escape(str_replace('_', ' ', ucfirst($order['payment_method']))) ?></td>
                    <td>KSh <?= number_format((float)$order['total_amount'], 2) ?></td>
                    <td><?= admin_escape(ucfirst($order['payment_status'])) ?></td>
                    <td><?= admin_escape(ucfirst($order['order_status'])) ?></td>
                    <td><?= admin_escape($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></section>
<?php admin_footer(); ?>
