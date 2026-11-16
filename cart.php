
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid request. Please refresh the page.');
    }

    $action = $_POST['action'] ?? '';

    try {

        // Add product to cart
        if ($action === 'add') {

            $productId = filter_var(
                $_POST['product_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $_POST['quantity'] ?? 1,
                FILTER_VALIDATE_INT
            );

            if (!$productId || !$quantity || $quantity < 1 || $quantity > 99) {
                throw new Exception('Invalid product or quantity.');
            }

            $stmt = $pdo->prepare(
                "SELECT id, stock FROM products WHERE id = ? AND is_active = 1"
            );

            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception('Product not found.');
            }

            $newQuantity = ($_SESSION['cart'][$productId] ?? 0) + $quantity;

            if ($product['stock'] !== null && $newQuantity > (int)$product['stock']) {
                throw new Exception('Requested quantity exceeds available stock.');
            }

            $_SESSION['cart'][$productId] = $newQuantity;

            $_SESSION['cart_message'] = 'Product added to your cart!';

        }

        // Update product quantity
        elseif ($action === 'update') {

            $productId = filter_var(
                $_POST['product_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $_POST['quantity'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$productId || !isset($_SESSION['cart'][$productId])) {
                throw new Exception('Product not found in cart.');
            }

            if (!$quantity || $quantity < 1 || $quantity > 99) {
                throw new Exception('Please enter a valid quantity.');
            }

            $stmt = $pdo->prepare(
                "SELECT stock FROM products WHERE id = ? AND is_active = 1"
            );

            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                unset($_SESSION['cart'][$productId]);
                throw new Exception('Product is no longer available.');
            }

            if ($product['stock'] !== null && $quantity > (int)$product['stock']) {
                throw new Exception('Requested quantity exceeds available stock.');
            }

            $_SESSION['cart'][$productId] = $quantity;

            $_SESSION['cart_message'] = 'Cart updated successfully!';

        }

        // Remove product
        elseif ($action === 'remove') {

            $productId = filter_var(
                $_POST['product_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$productId) {
                throw new Exception('Invalid product.');
            }

            unset($_SESSION['cart'][$productId]);

            $_SESSION['cart_message'] = 'Product removed from cart.';

        }

        // Clear cart
        elseif ($action === 'clear') {

            $_SESSION['cart'] = [];

            $_SESSION['cart_message'] = 'Your cart has been cleared.';

        }

    } catch (Exception $e) {
        $_SESSION['cart_error'] = $e->getMessage();
    }

    header('Location: cart.php');
    exit;
}

// Retrieve messages
$message = $_SESSION['cart_message'] ?? '';
$error = $_SESSION['cart_error'] ?? '';

unset($_SESSION['cart_message'], $_SESSION['cart_error']);

// Fetch products in cart
$cartProducts = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {

    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1"
    );

    $stmt->execute($productIds);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {

        $id = (int)$product['id'];
        $quantity = (int)$_SESSION['cart'][$id];

        $originalPrice = (float)$product['price'];

        $discountPrice = isset($product['discount_price'])
            ? (float)$product['discount_price']
            : 0;

        $unitPrice = (
            $discountPrice > 0 &&
            $discountPrice < $originalPrice
        ) ? $discountPrice : $originalPrice;

        $lineTotal = $unitPrice * $quantity;

        $product['quantity'] = $quantity;
        $product['unit_price'] = $unitPrice;
        $product['line_total'] = $lineTotal;

        $subtotal += $lineTotal;

        $cartProducts[] = $product;
    }
}

$cartCount = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart | Our Store</title>

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
                    <a class="nav-link" href="deals.php">Deals</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="cart.php">
                        Cart (<?= $cartCount ?>)
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- Cart Header -->
<section class="bg-light py-5 text-center">

    <div class="container">

        <h1 class="fw-bold">Shopping Cart</h1>

        <p class="text-muted">
            Review your items before proceeding to checkout.
        </p>

    </div>

</section>


<!-- Shopping Cart -->
<section class="container py-5">

    <?php if ($message): ?>

        <div class="alert alert-success">
            <?= escape($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= escape($error) ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($cartProducts)): ?>

        <div class="row g-4">

            <!-- Cart Items -->
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <h4 class="fw-bold mb-4">
                            Your Items (<?= $cartCount ?>)
                        </h4>

                        <?php foreach ($cartProducts as $product): ?>

                            <div class="row align-items-center g-3 mb-4">

                                <div class="col-4 col-md-3">

                                    <img
                                        src="<?= escape($product['image'] ?: 'images/placeholder.jpg') ?>"
                                        alt="<?= escape($product['name']) ?>"
                                        class="img-fluid rounded"
                                        style="height:130px; width:100%; object-fit:cover;"
                                    >

                                </div>


                                <div class="col-8 col-md-9">

                                    <div class="d-flex justify-content-between flex-wrap">

                                        <div>

                                            <h5 class="fw-bold">
                                                <?= escape($product['name']) ?>
                                            </h5>

                                            <p class="text-muted mb-1">
                                                KSh <?= number_format($product['unit_price'], 2) ?>
                                                each
                                            </p>

                                            <?php if (
                                                $product['unit_price'] < (float)$product['price']
                                            ): ?>

                                                <del class="text-muted small">
                                                    KSh <?= number_format((float)$product['price'], 2) ?>
                                                </del>

                                            <?php endif; ?>

                                        </div>

                                        <div class="text-md-end">

                                            <h5 class="fw-bold">
                                                KSh <?= number_format($product['line_total'], 2) ?>
                                            </h5>

                                        </div>

                                    </div>


                                    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">

                                        <!-- Update Quantity -->
                                        <form action="cart.php" method="POST"
                                              class="d-flex align-items-center gap-2">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= escape($_SESSION['csrf_token']) ?>"
                                            >

                                            <input type="hidden" name="action" value="update">

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)$product['id'] ?>"
                                            >

                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= $product['quantity'] ?>"
                                                min="1"
                                                max="<?= $product['stock'] !== null ? max(1, (int)$product['stock']) : 99 ?>"
                                                class="form-control"
                                                style="width:85px;"
                                                required
                                            >

                                            <button type="submit" class="btn btn-outline-dark btn-sm">
                                                Update
                                            </button>

                                        </form>


                                        <!-- Remove Product -->
                                        <form action="cart.php" method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= escape($_SESSION['csrf_token']) ?>"
                                            >

                                            <input type="hidden" name="action" value="remove">

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)$product['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                            >
                                                Remove
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                            <hr>

                        <?php endforeach; ?>


                        <div class="d-flex justify-content-between flex-wrap gap-2">

                            <a href="shop.php" class="btn btn-outline-dark">
                                Continue Shopping
                            </a>

                            <form action="cart.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= escape($_SESSION['csrf_token']) ?>"
                                >

                                <input type="hidden" name="action" value="clear">

                                <button type="submit" class="btn btn-outline-danger">
                                    Clear Cart
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Cart Summary -->
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">

                        <h4 class="fw-bold mb-4">
                            Order Summary
                        </h4>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Items</span>

                            <span><?= $cartCount ?></span>

                        </div>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Subtotal</span>

                            <span>
                                KSh <?= number_format($subtotal, 2) ?>
                            </span>

                        </div>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Delivery</span>

                            <span>Calculated at checkout</span>

                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">

                            <h5 class="fw-bold">Total</h5>

                            <h5 class="fw-bold">
                                KSh <?= number_format($subtotal, 2) ?>
                            </h5>

                        </div>

                        <a href="checkout.php"
                           class="btn btn-dark btn-lg w-100">

                            Proceed to Checkout

                        </a>

                        <p class="text-muted small text-center mt-3">
                            Delivery charges will be added during checkout.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    <?php else: ?>

        <!-- Empty Cart -->
        <div class="text-center py-5">

            <h2 class="fw-bold mb-3">
                Your Cart is Empty
            </h2>

            <p class="text-muted mb-4">
                You haven't added any products to your shopping cart yet.
            </p>

            <a href="shop.php" class="btn btn-dark btn-lg px-5">
                Start Shopping
            </a>

        </div>

    <?php endif; ?>

</section>


<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">

    <p class="mb-0">
        &copy; <?= date('Y') ?> Our Store. All Rights Reserved.
    </p>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/storefront.js"></script>

</body>
</html>
