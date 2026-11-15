
<?php
require_once __DIR__ . '/config/database.php';

// Fetch all categories
try {
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name,
            c.description,
            c.image,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id, c.name, c.description, c.image
        ORDER BY c.name ASC
    ");

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Categories | Our Store</title>

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
                    <a class="nav-link active" href="categories.php">Categories</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- Categories Header -->
<section class="categories-header text-center py-5">

    <div class="container">

        <h1 class="fw-bold">Explore Our Categories</h1>

        <p class="text-muted">
            Browse our collections and discover something you love.
        </p>

    </div>

</section>


<!-- Categories Section -->
<section class="container py-5">

    <div class="row mb-4">

        <div class="col-md-6">
            <h3 class="fw-bold">Shop by Category</h3>
        </div>

        <div class="col-md-6">

            <input
                type="search"
                id="categorySearch"
                class="form-control"
                placeholder="Search categories..."
            >

        </div>

    </div>


    <div class="row g-4" id="categoriesContainer">

        <?php if (!empty($categories)): ?>

            <?php foreach ($categories as $category): ?>

                <div class="col-6 col-md-4 col-lg-3 category-item">

                    <div class="card h-100 border-0 shadow-sm">

                        <a href="shop.php?category=<?= (int)$category['id'] ?>"
                           class="text-decoration-none">

                            <img
                                src="<?= htmlspecialchars($category['image'] ?: 'images/placeholder.jpg') ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($category['name']) ?>"
                                style="height:220px; object-fit:cover;"
                            >

                        </a>

                        <div class="card-body text-center">

                            <h5 class="fw-bold">

                                <?= htmlspecialchars($category['name']) ?>

                            </h5>

                            <p class="text-muted small">

                                <?= htmlspecialchars($category['description'] ?? '') ?>

                            </p>

                            <p class="small text-muted">

                                <?= (int)$category['product_count'] ?> Products

                            </p>

                            <a
                                href="shop.php?category=<?= (int)$category['id'] ?>"
                                class="btn btn-dark w-100"
                            >
                                Explore Category
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12 text-center">

                <h5>No categories available</h5>

                <p class="text-muted">
                    Please check back later.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">

    <p class="mb-0">
        &copy; <?= date('Y') ?> Our Store. All Rights Reserved.
    </p>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/categoties.js"></script>
<script src="assets/js/storefront.js"></script>

</body>
</html>
