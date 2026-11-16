<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$term = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT p.id, p.name, p.sku, p.image, p.price, p.stock, p.is_active, c.name AS category_name
        FROM products p LEFT JOIN categories c ON c.id = p.category_id';
$params = [];
if ($term !== '') {
    $sql .= ' WHERE p.name LIKE ? OR p.sku LIKE ?';
    $params = ['%' . $term . '%', '%' . $term . '%'];
}
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Products');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Products</h1><p class="text-muted mb-0">Manage products, photos, pricing, stock, and categories.</p></div>
    <a class="btn btn-dark" href="product-form.php">Add product</a>
</div>
<section class="card admin-card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="get">
            <div class="col-sm-8 col-md-5"><label class="visually-hidden" for="q">Search products</label><input class="form-control" id="q" name="q" value="<?= admin_escape($term) ?>" placeholder="Search name or SKU"></div>
            <div class="col-auto"><button class="btn btn-outline-dark">Search</button></div>
            <?php if ($term !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="products.php">Clear</a></div><?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead><tr><th>Photo</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img class="admin-thumb" src="<?= admin_escape($product['image'] ?: '../images/placeholder.jpg') ?>" alt=""></td>
                        <td><strong><?= admin_escape($product['name']) ?></strong><div class="small text-muted"><?= admin_escape($product['sku'] ?: 'No SKU') ?></div></td>
                        <td><?= admin_escape($product['category_name'] ?: 'Uncategorized') ?></td>
                        <td>KSh <?= number_format((float)$product['price'], 2) ?></td>
                        <td><?= (int)$product['stock'] ?></td>
                        <td><span class="badge text-bg-<?= $product['is_active'] ? 'success' : 'secondary' ?>"><?= $product['is_active'] ? 'Active' : 'Archived' ?></span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-dark" href="product-form.php?id=<?= (int)$product['id'] ?>">Edit</a>
                            <form class="d-inline" method="post" action="product-archive.php" onsubmit="return confirm('Change this product visibility?')">
                                <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="active" value="<?= $product['is_active'] ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-outline-<?= $product['is_active'] ? 'danger' : 'success' ?>" type="submit"><?= $product['is_active'] ? 'Archive' : 'Restore' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No matching products.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php admin_footer(); ?>
