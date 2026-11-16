<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = $id !== false && $id !== null && $id > 0;
$product = [
    'name' => '',
    'sku' => '',
    'description' => '',
    'category_id' => '',
    'price' => '',
    'discount_price' => '',
    'stock' => '0',
    'is_active' => 1,
];
$images = [];
$errors = [];
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Product not found.');
    }
    $product = $found;
    $stmt = $pdo->prepare('SELECT id, image_path, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($images === [] && !empty($product['image'])) {
        $images[] = ['id' => 0, 'image_path' => $product['image'], 'sort_order' => 0];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $product['name'] = trim((string)($_POST['name'] ?? ''));
    $product['sku'] = trim((string)($_POST['sku'] ?? ''));
    $product['description'] = trim((string)($_POST['description'] ?? ''));
    $product['category_id'] = trim((string)($_POST['category_id'] ?? ''));
    $product['price'] = trim((string)($_POST['price'] ?? ''));
    $product['discount_price'] = trim((string)($_POST['discount_price'] ?? ''));
    $product['stock'] = trim((string)($_POST['stock'] ?? ''));
    $product['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    $nameLength = mb_strlen($product['name']);
    if ($nameLength < 2 || $nameLength > 255) {
        $errors[] = 'Product name must be between 2 and 255 characters.';
    }
    if (strlen($product['sku']) > 100) {
        $errors[] = 'SKU must be 100 characters or fewer.';
    }
    if (!is_numeric($product['price']) || (float)$product['price'] <= 0 || (float)$product['price'] > 99999999.99) {
        $errors[] = 'Enter a valid product price greater than zero.';
    }
    if ($product['discount_price'] !== '' && (!is_numeric($product['discount_price']) || (float)$product['discount_price'] <= 0 || (float)$product['discount_price'] >= (float)$product['price'])) {
        $errors[] = 'Discount price must be greater than zero and lower than the regular price.';
    }
    if (!ctype_digit($product['stock']) || (int)$product['stock'] < 0 || (int)$product['stock'] > 2147483647) {
        $errors[] = 'Stock must be a non-negative whole number.';
    }
    $categoryId = $product['category_id'] === '' ? null : filter_var($product['category_id'], FILTER_VALIDATE_INT);
    if ($product['category_id'] !== '' && (!$categoryId || !in_array((int)$categoryId, array_column($categories, 'id'), true))) {
        $errors[] = 'Choose a valid category.';
    }

    $stmt = $pdo->prepare('SELECT id, image_path, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $stmt->execute([$id]);
    $currentImages = $isEdit ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($isEdit && $currentImages === [] && !empty($product['image'])) {
        $currentImages[] = ['id' => 0, 'image_path' => $product['image'], 'sort_order' => 0];
    }
    $removeIds = array_map('intval', is_array($_POST['remove_images'] ?? null) ? $_POST['remove_images'] : []);
    $keptImages = array_values(array_filter(
        $currentImages,
        static fn(array $image): bool => (int)$image['id'] === 0 || !in_array((int)$image['id'], $removeIds, true)
    ));

    $files = $_FILES['product_images'] ?? null;
    $uploadedCount = 0;
    if (is_array($files) && isset($files['error']) && is_array($files['error'])) {
        foreach ($files['error'] as $errorCode) {
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                $uploadedCount++;
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errors[] = 'One or more photo uploads failed. Each photo must be no larger than 5 MB.';
                    break;
                }
            }
        }
    }
    if (count($keptImages) + $uploadedCount < 3 || count($keptImages) + $uploadedCount > 5) {
        $errors[] = 'Each product must have between 3 and 5 photos. Remove photos or upload enough to meet that limit.';
    }
    if ($product['description'] === '') {
        $errors[] = 'Enter a product description.';
    }

    if ($errors === []) {
        $storedFiles = [];
        $pdo->beginTransaction();
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET category_id = ?, sku = ?, name = ?, description = ?, price = ?, discount_price = ?, stock = ?, is_active = ? WHERE id = ?'
                );
                $stmt->execute([
                    $categoryId ?: null,
                    $product['sku'] !== '' ? $product['sku'] : null,
                    $product['name'],
                    $product['description'],
                    $product['price'],
                    $product['discount_price'] !== '' ? $product['discount_price'] : null,
                    $product['stock'],
                    $product['is_active'],
                    $id,
                ]);
                foreach ($removeIds as $removeId) {
                    $delete = $pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
                    $delete->execute([$removeId, $id]);
                }
                $productId = $id;
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, sku, name, description, price, discount_price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $categoryId ?: null,
                    $product['sku'] !== '' ? $product['sku'] : null,
                    $product['name'],
                    $product['description'],
                    $product['price'],
                    $product['discount_price'] !== '' ? $product['discount_price'] : null,
                    $product['stock'],
                    $product['is_active'],
                ]);
                $productId = (int)$pdo->lastInsertId();
            }

            $uploadDirectory = __DIR__ . '/../images/products';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Unable to create product image directory.');
            }
            $fileStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)');
            $position = count($keptImages);
            if (is_array($files) && isset($files['error']) && is_array($files['error'])) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                foreach ($files['error'] as $index => $errorCode) {
                    if ($errorCode === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($errorCode !== UPLOAD_ERR_OK || (int)$files['size'][$index] > 5 * 1024 * 1024) {
                        throw new RuntimeException('Each image must be a valid upload no larger than 5 MB.');
                    }
                    $tmpName = (string)$files['tmp_name'][$index];
                    $mimeType = $finfo->file($tmpName);
                    $dimensions = @getimagesize($tmpName);
                    if (!isset($allowedMimeTypes[$mimeType]) || $dimensions === false || $dimensions[0] > 10000 || $dimensions[1] > 10000) {
                        throw new RuntimeException('Upload JPG, PNG, or WebP images with valid image dimensions.');
                    }
                    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
                    $destination = $uploadDirectory . '/' . $fileName;
                    if (!move_uploaded_file($tmpName, $destination)) {
                        throw new RuntimeException('A product image could not be saved to the upload folder.');
                    }
                    $storedFiles[] = $destination;
                    $path = 'images/products/' . $fileName;
                    $fileStmt->execute([$productId, $path, $position++]);
                }
            }

            $galleryStmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
            $galleryStmt->execute([$productId]);
            $gallery = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($gallery === []) {
                throw new RuntimeException('The product must have at least three photos.');
            }
            $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$gallery[0], $productId]);
            $pdo->commit();
            admin_flash('success', 'Product and photo gallery saved.');
            header('Location: products.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($storedFiles as $storedFile) {
                if (is_file($storedFile)) {
                    unlink($storedFile);
                }
            }
            error_log($e->getMessage());
            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'The product could not be saved. Check that the SKU is unique and the database is available.';
        }
    }
}

$title = $isEdit ? 'Edit product' : 'Add product';
admin_header($title);
?>
<div class="mb-4">
    <a href="products.php">&larr; Products</a>
    <h1 class="h2 mt-2"><?= admin_escape($title) ?></h1>
</div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= admin_escape($error) ?></div><?php endforeach; ?>
<form class="card admin-card" method="post" enctype="multipart/form-data">
    <div class="card-body p-4">
        <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="name">Product name</label>
                <input class="form-control" id="name" name="name" maxlength="255" required value="<?= admin_escape($product['name']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sku">SKU (optional)</label>
                <input class="form-control" id="sku" name="sku" maxlength="100" value="<?= admin_escape($product['sku'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="category_id">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Uncategorized</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>" <?= (string)$product['category_id'] === (string)$category['id'] ? 'selected' : '' ?>><?= admin_escape($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($categories === []): ?><div class="form-text">Create a category before assigning products.</div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="price">Price (KSh)</label>
                <input class="form-control" type="number" id="price" name="price" min="0.01" step="0.01" required value="<?= admin_escape($product['price']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="discount_price">Discount price (optional)</label>
                <input class="form-control" type="number" id="discount_price" name="discount_price" min="0.01" step="0.01" value="<?= admin_escape($product['discount_price'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="stock">Stock</label>
                <input class="form-control" type="number" id="stock" name="stock" min="0" step="1" required value="<?= admin_escape($product['stock']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?= admin_escape($product['description']) ?></textarea>
            </div>
            <?php if ($images !== []): ?>
                <div class="col-12">
                    <label class="form-label">Current photos (select any to remove)</label>
                    <div class="admin-gallery">
                    <?php foreach ($images as $image): ?>
                        <label class="admin-gallery-item small">
                            <img src="<?= admin_escape($image['image_path']) ?>" alt="">
                            <span class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="remove_images[]" value="<?= (int)$image['id'] ?>" id="remove-<?= (int)$image['id'] ?>" <?= (int)$image['id'] === 0 ? 'disabled' : '' ?>>
                                <span class="form-check-label" for="remove-<?= (int)$image['id'] ?>">Remove</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label" for="product_images">Add photos</label>
                <input class="form-control" type="file" id="product_images" name="product_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                <div class="form-text">Each product must have 3–5 photos total. JPG, PNG, or WebP; maximum 5 MB each.</div>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($product['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Show this product on the storefront</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white p-3 d-flex gap-2">
        <button class="btn btn-dark" type="submit">Save product</button>
        <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
    </div>
</form>
<?php admin_footer(); ?>
