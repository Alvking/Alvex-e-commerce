<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid order ID.');
}
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $status = (string)($_POST['order_status'] ?? '');
    if (!in_array($status, $statuses, true)) {
        admin_flash('danger', 'Select a valid order status.');
    } else {
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        admin_flash('success', 'Order status updated.');
    }
    header('Location: order.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
$stmt = $pdo->prepare('SELECT product_name, unit_price, quantity, line_total FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Order ' . $order['order_number']);
?>
<div class="mb-4"><a href="orders.php">&larr; Orders</a><h1 class="h2 mt-2 mb-1">Order <?= admin_escape($order['order_number']) ?></h1><p class="text-muted mb-0">Placed <?= admin_escape($order['created_at']) ?></p></div>
<div class="row g-4">
    <div class="col-lg-8">
        <section class="card admin-card"><div class="card-body">
            <h2 class="h5 mb-3">Items</h2>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Total</th></tr></thead>
                <tbody><?php foreach ($items as $item): ?><tr><td><?= admin_escape($item['product_name']) ?></td><td>KSh <?= number_format((float)$item['unit_price'], 2) ?></td><td><?= (int)$item['quantity'] ?></td><td>KSh <?= number_format((float)$item['line_total'], 2) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
            <div class="text-end"><div>Subtotal: KSh <?= number_format((float)$order['subtotal'], 2) ?></div><div>Delivery: KSh <?= number_format((float)$order['delivery_fee'], 2) ?></div><strong>Total: KSh <?= number_format((float)$order['total_amount'], 2) ?></strong></div>
        </div></section>
    </div>
    <div class="col-lg-4">
        <section class="card admin-card mb-4"><div class="card-body">
            <h2 class="h5">Customer and delivery</h2>
            <p class="mb-1"><strong><?= admin_escape($order['customer_name']) ?></strong></p>
            <p class="mb-1"><?= admin_escape($order['email']) ?></p>
            <p class="mb-1"><?= admin_escape($order['phone']) ?></p>
            <p class="mb-1"><?= admin_escape($order['county']) ?></p>
            <p class="mb-1"><?= nl2br(admin_escape($order['delivery_address'])) ?></p>
            <?php if (!empty($order['notes'])): ?><p class="mb-0 mt-2"><strong>Notes:</strong> <?= nl2br(admin_escape($order['notes'])) ?></p><?php endif; ?>
        </div></section>
        <section class="card admin-card"><div class="card-body">
            <h2 class="h5">Payment and fulfilment</h2>
            <p>Payment: <?= admin_escape(str_replace('_', ' ', ucfirst($order['payment_method']))) ?> (<?= admin_escape($order['payment_status']) ?>)</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                <label class="form-label" for="order_status">Order status</label>
                <select class="form-select mb-3" id="order_status" name="order_status">
                    <?php foreach ($statuses as $status): ?><option value="<?= admin_escape($status) ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= admin_escape(ucfirst($status)) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-dark" type="submit">Update status</button>
            </form>
        </div></section>
    </div>
</div>
<?php admin_footer(); ?>
