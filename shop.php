
<?php
require_once __DIR__ . '/config/database.php';

$category = trim((string)($_GET['category'] ?? ''));
$selectedCategory = null;
$categories = [];
$products = [];

try {
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    if ($category !== '') {
        if (ctype_digit($category)) {
            $categoryId = (int)$category;
            foreach ($categories as $candidate) {
                if ((int)$candidate['id'] === $categoryId) {
                    $selectedCategory = $candidate;
                    break;
                }
            }
        } else {
            $categorySlug = mb_strtolower(str_replace('-', ' ', $category));
            foreach ($categories as $candidate) {
                if (mb_strtolower($candidate['name']) === $categorySlug) {
                    $selectedCategory = $candidate;
                    break;
                }
            }
        }
    }

    $query = "
        SELECT p.*,
               COALESCE(
                   (SELECT pi.image_path FROM product_images pi
                    WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1),
                   p.image
               ) AS display_image
        FROM products p
        WHERE p.is_active = 1
    ";
    $params = [];
    if ($category !== '') {
        if ($selectedCategory === null) {
            $query .= ' AND 1 = 0';
        } else {
            $query .= ' AND p.category_id = ?';
            $params[] = $selectedCategory['id'];
        }
    }
    $query .= ' ORDER BY p.created_at DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('The product catalog could not be loaded. Please try again later.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shop | Our Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">
            OUR STORE
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.html">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="shop.php">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        Cart
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Shop Header -->
<section class="shop-header text-center py-5">
    <div class="container">
        <h1 class="fw-bold"><?= $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'Our Collection' ?></h1>
        <p>Discover products selected just for you.</p>
    </div>
</section>

<!-- Products -->
<section class="container py-5">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3><?= $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'Shop All Products' ?></h3>
        </div>

        <div class="col-md-6">
            <div class="d-flex gap-2">
                <select class="form-select" aria-label="Filter by category" onchange="window.location.href=this.value">
                    <option value="shop.php" <?= $category === '' ? 'selected' : '' ?>>All categories</option>
                    <?php foreach ($categories as $option): ?>
                        <option value="shop.php?category=<?= (int)$option['id'] ?>" <?= $selectedCategory && (int)$selectedCategory['id'] === (int)$option['id'] ? 'selected' : '' ?>><?= htmlspecialchars($option['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" id="productSearch" class="form-control" placeholder="Search products..." aria-label="Search products">
            </div>
        </div>
    </div>

    <div class="row g-4" id="productContainer">

        <?php if (!empty($products)): ?>

            <?php foreach ($products as $product): ?>

                <div class="col-6 col-md-4 col-lg-3 product-item">

                    <div class="card h-100 border-0 shadow-sm">

                        <a href="product.php?id=<?= (int)$product['id'] ?>">
                            <img
                                src="<?= htmlspecialchars($product['display_image'] ?: 'images/placeholder.jpg', ENT_QUOTES, 'UTF-8') ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                style="height:250px; object-fit:cover;"
                            >
                        </a>

                        <div class="card-body">

                            <h5 class="card-title">
                                <?= htmlspecialchars($product['name']) ?>
                            </h5>

                            <p class="text-muted small">
                                <?= htmlspecialchars(mb_strimwidth((string)($product['description'] ?? ''), 0, 130, '…'), ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <h6 class="fw-bold">
                                <?php if (!empty($product['discount_price'])): ?>
                                    <del class="text-muted fw-normal">KSh <?= number_format((float)$product['price'], 2) ?></del>
                                    KSh <?= number_format((float)$product['discount_price'], 2) ?>
                                <?php else: ?>
                                    KSh <?= number_format((float)$product['price'], 2) ?>
                                <?php endif; ?>
                            </h6>

                            <button
                                class="btn btn-dark w-100 add-to-cart"
                                data-id="<?= (int)$product['id'] ?>"
                            >
                                Add to Cart
                            </button>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12 text-center">
                <p>No products available yet.</p>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">
    <p class="mb-0">&copy; <?= date('Y') ?> Our Store. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/storefront.js"></script>

</body>
</html>
