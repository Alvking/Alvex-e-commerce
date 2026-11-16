
<?php
require_once __DIR__ . '/config/database.php';

// Fetch products with active discounts
try {
    $stmt = $pdo->query("
        SELECT
            id,
            name,
            description,
            image,
            price,
            discount_price,
            stock
        FROM products
        WHERE discount_price IS NOT NULL
          AND is_active = 1
          AND discount_price > 0
          AND discount_price < price
          AND (stock IS NULL OR stock > 0)
        ORDER BY (price - discount_price) DESC
    ");

    $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $deals = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Special Deals | Our Store</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">

    <div class="container">

        <a class="navbar-brand fw-bold" href="index.html">
            OUR STORE
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >
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
                    <a class="nav-link" href="categories.php">Categories</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="deals.php">Deals</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- Deals Hero Section -->
<section class="deals-hero text-center py-5">

    <div class="container">

        <span class="badge bg-danger mb-3">
            SPECIAL OFFERS
        </span>

        <h1 class="fw-bold display-5">
            Exclusive Deals
        </h1>

        <p class="lead">
            Discover amazing products at special prices.
        </p>

        <a href="#deals" class="btn btn-dark btn-lg mt-3">
            Shop Deals
        </a>

    </div>

</section>


<!-- Deals Section -->
<section class="container py-5" id="deals">

    <div class="row align-items-center mb-4">

        <div class="col-md-6">
            <h2 class="fw-bold">Today's Best Deals</h2>
            <p class="text-muted">
                Save more on your favourite products.
            </p>
        </div>

        <div class="col-md-6">

            <input
                type="search"
                id="dealSearch"
                class="form-control"
                placeholder="Search deals..."
            >

        </div>

    </div>


    <div class="row g-4" id="dealsContainer">

        <?php if (!empty($deals)): ?>

            <?php foreach ($deals as $deal): ?>

                <?php
                    $originalPrice = (float)$deal['price'];
                    $discountPrice = (float)$deal['discount_price'];

                    $discountPercentage = round(
                        (($originalPrice - $discountPrice) / $originalPrice) * 100
                    );
                ?>

                <div class="col-6 col-md-4 col-lg-3 deal-item">

                    <div class="card h-100 border-0 shadow-sm position-relative">

                        <!-- Discount Badge -->
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                            -<?= $discountPercentage ?>%
                        </span>

                        <!-- Product Image -->
                        <a href="product.php?id=<?= (int)$deal['id'] ?>">

                            <img
                                src="<?= htmlspecialchars($deal['image'] ?: 'images/placeholder.jpg') ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($deal['name']) ?>"
                                style="height:250px; object-fit:cover;"
                            >

                        </a>

                        <div class="card-body">

                            <h5 class="card-title">
                                <?= htmlspecialchars($deal['name']) ?>
                            </h5>

                            <p class="text-muted small">
                                <?= htmlspecialchars($deal['description'] ?? '') ?>
                            </p>

                            <!-- Pricing -->
                            <div class="mb-3">

                                <span class="fw-bold text-danger fs-5">
                                    KSh <?= number_format($discountPrice, 2) ?>
                                </span>

                                <br>

                                <del class="text-muted small">
                                    KSh <?= number_format($originalPrice, 2) ?>
                                </del>

                            </div>

                            <a
                                href="product.php?id=<?= (int)$deal['id'] ?>"
                                class="btn btn-dark w-100"
                            >
                                View Deal
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12 text-center py-5">

                <h4>No active deals available</h4>

                <p class="text-muted">
                    Check back later for exciting offers.
                </p>

                <a href="shop.php" class="btn btn-dark">
                    Explore Shop
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- Promotional Banner -->
<section class="container mb-5">

    <div class="bg-dark text-white text-center rounded p-5">

        <h2 class="fw-bold">
            Don't Miss Out!
        </h2>

        <p>
            Explore our complete collection and discover more products.
        </p>

        <a href="shop.php" class="btn btn-light mt-2">
            Explore All Products
        </a>

    </div>

</section>


<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">

    <p class="mb-0">
        &copy; <?= date('Y') ?> Our Store. All Rights Reserved.
    </p>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/deals.js"></script>
<script src="assets/js/storefront.js"></script>

</body>
</html>
