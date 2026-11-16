
<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    http_response_code(400);
    exit("Invalid product ID.");
}

// Fetch product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        exit("Product not found.");
    }

    $imageStmt = $pdo->prepare(
        'SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order, id'
    );
    $imageStmt->execute([$product_id]);
    $productImages = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
    if ($productImages === [] && !empty($product['image'])) {
        $productImages = [$product['image']];
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($product['name']) ?> | Our Store
    </title>

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

        <button class="navbar-toggler"
                type="button"
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
                    <a class="nav-link" href="shop.php">Shop</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>

            </ul>

        </div>
    </div>
</nav>


<!-- Product Details -->
<section class="container py-5">

    <div class="row g-5 align-items-center">

        <!-- Product Image -->
        <div class="col-md-6">

            <div class="product-image-container">

                <img
                    src="<?= htmlspecialchars($productImages[0] ?? 'images/placeholder.jpg', ENT_QUOTES, 'UTF-8') ?>"
                    id="mainProductImage"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    class="img-fluid rounded shadow-sm w-100"
                    style="max-height: 550px; object-fit: cover;"
                >

            </div>
            <?php if (count($productImages) > 1): ?>
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="Product photos">
                    <?php foreach ($productImages as $imageIndex => $imagePath): ?>
                        <button class="btn p-0 border product-gallery-thumb" type="button" data-image="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" aria-label="Show product photo <?= $imageIndex + 1 ?>">
                            <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:76px;height:68px;object-fit:cover;">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>


        <!-- Product Information -->
        <div class="col-md-6">

            <p class="text-muted">
                <a href="index.html" class="text-decoration-none">Home</a>
                /
                <a href="shop.php" class="text-decoration-none">Shop</a>
                /
                <?= htmlspecialchars($product['name']) ?>
            </p>

            <h1 class="fw-bold mb-3">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <h3 class="fw-bold mb-4">
                <?php if (!empty($product['discount_price'])): ?>
                    <del class="text-muted fs-5">KSh <?= number_format((float)$product['price'], 2) ?></del>
                    KSh <?= number_format((float)$product['discount_price'], 2) ?>
                <?php else: ?>
                    KSh <?= number_format((float)$product['price'], 2) ?>
                <?php endif; ?>
            </h3>

            <div class="mb-4">

                <h5>Product Description</h5>

                <p class="text-muted">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
                </p>

            </div>

            <hr>

            <form action="cart.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
                >

                <input type="hidden" name="action" value="add">

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int)$product['id'] ?>"
                >

                <div class="mb-3">

                    <label for="quantity" class="form-label fw-bold">
                        Quantity
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        class="form-control"
                        value="1"
                        min="1"
                        max="99"
                        style="max-width:120px;"
                        required
                    >

                </div>

                <button type="submit" class="btn btn-dark btn-lg px-5">
                    Add to Cart
                </button>

            </form>

            <p class="small text-muted mt-3">
                Secure shopping. Thank you for choosing us.
            </p>

        </div>

    </div>

</section>


<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">

    <p class="mb-0">
        &copy; <?= date('Y') ?> Our Store. All Rights Reserved.
    </p>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/storefront.js"></script>
<script>
    document.querySelectorAll('.product-gallery-thumb').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('mainProductImage').src = button.dataset.image;
        });
    });
</script>

</body>
</html>
