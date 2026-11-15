
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$deliveryFee = 300;
$errors = [];
$cartProducts = [];
$subtotal = 0;
$orderPlaced = false;
$orderReference = null;

// Check for successful order
if (isset($_GET['placed']) && isset($_SESSION['last_order'])) {
    $orderPlaced = true;
    $orderReference = $_SESSION['last_order'];
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Retrieve current products and prices
function getCheckoutProducts($pdo, $cart) {

    if (empty($cart)) {
        return [];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE id IN ($placeholders)"
    );

    $stmt->execute($ids);

    $products = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {

        $id = (int)$product['id'];
        $quantity = (int)$cart[$id];

        $price = (float)$product['price'];
        $discount = (float)($product['discount_price'] ?? 0);

        $unitPrice = (
            $discount > 0 && $discount < $price
        ) ? $discount : $price;

        $product['quantity'] = $quantity;
        $product['unit_price'] = $unitPrice;
        $product['line_total'] = $unitPrice * $quantity;

        $products[] = $product;
    }

    return $products;
}

// Load cart
try {
    $cartProducts = getCheckoutProducts($pdo, $_SESSION['cart']);

    foreach ($cartProducts as $product) {
        $subtotal += $product['line_total'];
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $errors[] = 'Unable to load your cart. Please try again.';
}


// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$orderPlaced) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    $customerName = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = preg_replace('/[\s-]+/', '', $_POST['phone'] ?? '');
    $county = trim($_POST['county'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';

    // Validate customer details
    if ($customerName === '' || strlen($customerName) > 150) {
        $errors[] = 'Please enter your full name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Kenya phone number validation
    if (!preg_match('/^(?:\+254|254|0)(?:7|1)[0-9]{8}$/', $phone)) {
        $errors[] = 'Enter a valid Kenyan phone number.';
    }

    if ($county === '' || strlen($county) > 100) {
        $errors[] = 'Please enter your county.';
    }

    if ($address === '' || strlen($address) > 500) {
        $errors[] = 'Please enter your delivery address.';
    }

    if (strlen($notes) > 1000) {
        $errors[] = 'Delivery notes are too long.';
    }

    if (!in_array($paymentMethod, ['mpesa', 'cash_on_delivery'], true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (empty($_SESSION['cart'])) {
        $errors[] = 'Your shopping cart is empty.';
    }

    // Save order
    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            // Lock products and verify stock
            $ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $pdo->prepare(
                "SELECT * FROM products
                 WHERE id IN ($placeholders)
                 FOR UPDATE"
            );

            $stmt->execute($ids);

            $lockedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($lockedProducts) !== count($ids)) {
                throw new Exception('One or more products are no longer available.');
            }

            $orderItems = [];
            $subtotal = 0;

            foreach ($lockedProducts as $product) {

                $id = (int)$product['id'];
                $quantity = (int)$_SESSION['cart'][$id];

                $originalPrice = (float)$product['price'];
                $discount = (float)($product['discount_price'] ?? 0);

                $unitPrice = (
                    $discount > 0 && $discount < $originalPrice
                ) ? $discount : $originalPrice;

                if (
                    $product['stock'] !== null &&
                    $quantity > (int)$product['stock']
                ) {
                    throw new Exception(
                        'Insufficient stock for ' . $product['name']
                    );
                }

                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'id' => $id,
                    'name' => $product['name'],
                    'price' => $unitPrice,
                    'quantity' => $quantity,
                    'total' => $lineTotal,
                    'stock' => $product['stock']
                ];
            }

            $deliveryFee = 300;
            $totalAmount = $subtotal + $deliveryFee;

            // Generate order reference
            $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));

            // Payment status
            $paymentStatus = 'pending';
            $orderStatus = 'pending';

            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number,
                    customer_name,
                    email,
                    phone,
                    county,
                    delivery_address,
                    notes,
                    payment_method,
                    subtotal,
                    delivery_fee,
                    total_amount,
                    payment_status,
                    order_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $orderNumber,
                $customerName,
                $email,
                $phone,
                $county,
                $address,
                $notes,
                $paymentMethod,
                $subtotal,
                $deliveryFee,
                $totalAmount,
                $paymentStatus,
                $orderStatus
            ]);

            $orderId = $pdo->lastInsertId();

            // Insert order items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    unit_price,
                    quantity,
                    line_total
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            // Reduce available stock
            $stockStmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock IS NOT NULL
            ");

            foreach ($orderItems as $item) {

                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item['total']
                ]);

                if ($item['stock'] !== null) {
                    $stockStmt->execute([
                        $item['quantity'],
                        $item['id']
                    ]);
                }
            }

            $pdo->commit();

            // Clear cart after successful order creation
            $_SESSION['cart'] = [];

            $_SESSION['last_order'] = [
                'number' => $orderNumber,
                'total' => $totalAmount,
                'payment_method' => $paymentMethod
            ];

            header('Location: checkout.php?placed=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = $e instanceof PDOException
                ? 'We could not process your order. Please try again.'
                : $e->getMessage();
        }
    }

    // Reload cart after failed checkout
    try {
        $cartProducts = getCheckoutProducts($pdo, $_SESSION['cart']);

        $subtotal = 0;

        foreach ($cartProducts as $product) {
            $subtotal += $product['line_total'];
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

$totalAmount = $subtotal + $deliveryFee;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Our Store</title>

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
                    <a class="nav-link" href="cart.php">Cart</a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<?php if ($orderPlaced): ?>

    <!-- Order Success -->
    <section class="container py-5">

        <div class="card border-0 shadow-sm mx-auto text-center p-4"
             style="max-width:650px;">

            <div class="mb-3">
                <span class="text-success display-1">&#10003;</span>
            </div>

            <h2 class="fw-bold">Order Placed Successfully!</h2>

            <p class="text-muted">
                Thank you for shopping with us.
                Your order has been recorded.
            </p>

            <hr>

            <h6>Order Reference</h6>

            <h4 class="fw-bold">
                <?= e($orderReference['number']) ?>
            </h4>

            <h6 class="mt-3">Order Total</h6>

            <h3 class="fw-bold">
                KSh <?= number_format($orderReference['total'], 2) ?>
            </h3>

            <?php if ($orderReference['payment_method'] === 'mpesa'): ?>

                <div class="alert alert-warning mt-3">
                    Your order is awaiting M-Pesa payment.
                    The STK Push payment integration is the next step.
                    No payment has been collected yet.
                </div>

            <?php else: ?>

                <div class="alert alert-info mt-3">
                    Your order is awaiting confirmation.
                    Payment will be made upon delivery.
                </div>

            <?php endif; ?>

            <a href="shop.php" class="btn btn-dark mt-3">
                Continue Shopping
            </a>

        </div>

    </section>

<?php elseif (empty($cartProducts)): ?>

    <section class="container text-center py-5">

        <h2 class="fw-bold">Your Cart is Empty</h2>

        <p class="text-muted">
            Add products before proceeding to checkout.
        </p>

        <a href="shop.php" class="btn btn-dark">
            Start Shopping
        </a>

    </section>

<?php else: ?>

    <!-- Checkout Header -->
    <section class="bg-light text-center py-5">

        <div class="container">

            <h1 class="fw-bold">Checkout</h1>

            <p class="text-muted">
                Complete your delivery and payment details.
            </p>

        </div>

    </section>


    <!-- Checkout Form -->
    <section class="container py-5">

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>Please correct the following:</strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <form action="checkout.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="row g-4">

                <!-- Customer Details -->
                <div class="col-lg-7">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <h4 class="fw-bold mb-4">
                                1. Customer Information
                            </h4>

                            <div class="mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="customer_name"
                                    class="form-control"
                                    placeholder="Enter your full name"
                                    maxlength="150"
                                    value="<?= e($_POST['customer_name'] ?? '') ?>"
                                    required
                                >

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="example@email.com"
                                    value="<?= e($_POST['email'] ?? '') ?>"
                                    required
                                >

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-control"
                                    placeholder="07XXXXXXXX or +2547XXXXXXXX"
                                    value="<?= e($_POST['phone'] ?? '') ?>"
                                    required
                                >

                            </div>

                            <hr class="my-4">

                            <h4 class="fw-bold mb-4">
                                2. Delivery Information
                            </h4>


                            <div class="mb-3">

                                <label class="form-label">
                                    County
                                </label>

                                <input
                                    type="text"
                                    name="county"
                                    class="form-control"
                                    placeholder="e.g. Nairobi"
                                    value="<?= e($_POST['county'] ?? '') ?>"
                                    required
                                >

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Delivery Address
                                </label>

                                <textarea
                                    name="address"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Enter your estate, building, street or delivery location"
                                    required
                                ><?= e($_POST['address'] ?? '') ?></textarea>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Additional Delivery Notes (Optional)
                                </label>

                                <textarea
                                    name="notes"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Any special delivery instructions?"
                                ><?= e($_POST['notes'] ?? '') ?></textarea>

                            </div>

                            <hr class="my-4">

                            <h4 class="fw-bold mb-4">
                                3. Payment Method
                            </h4>


                            <div class="form-check border rounded p-3 mb-3">

                                <input
                                    class="form-check-input ms-0 me-2"
                                    type="radio"
                                    name="payment_method"
                                    id="mpesa"
                                    value="mpesa"
                                    <?= ($_POST['payment_method'] ?? 'mpesa') === 'mpesa' ? 'checked' : '' ?>
                                >

                                <label class="form-check-label fw-bold" for="mpesa">
                                    M-Pesa
                                    <span class="badge bg-success ms-2">Kenya</span>

                                    <div class="text-muted small mt-1">
                                        STK Push payment integration to follow.
                                    </div>
                                </label>

                            </div>


                            <div class="form-check border rounded p-3 mb-3">

                                <input
                                    class="form-check-input ms-0 me-2"
                                    type="radio"
                                    name="payment_method"
                                    id="cod"
                                    value="cash_on_delivery"
                                    <?= ($_POST['payment_method'] ?? '') === 'cash_on_delivery' ? 'checked' : '' ?>
                                >

                                <label class="form-check-label fw-bold" for="cod">

                                    Cash on Delivery

                                    <div class="text-muted small mt-1">
                                        Pay when your order is delivered.
                                    </div>

                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Order Summary -->
                <div class="col-lg-5">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <h4 class="fw-bold mb-4">
                                Your Order
                            </h4>


                            <?php foreach ($cartProducts as $product): ?>

                                <div class="d-flex align-items-center gap-3 mb-3">

                                    <img
                                        src="<?= e($product['image'] ?: 'images/placeholder.jpg') ?>"
                                        alt="<?= e($product['name']) ?>"
                                        style="width:70px;height:70px;object-fit:cover;"
                                        class="rounded"
                                    >

                                    <div class="flex-grow-1">

                                        <h6 class="fw-bold mb-1">
                                            <?= e($product['name']) ?>
                                        </h6>

                                        <small class="text-muted">
                                            Quantity: <?= $product['quantity'] ?>
                                        </small>

                                    </div>

                                    <strong>
                                        KSh <?= number_format($product['line_total'], 2) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>


                            <hr>

                            <div class="d-flex justify-content-between mb-3">

                                <span>Subtotal</span>

                                <strong>
                                    KSh <?= number_format($subtotal, 2) ?>
                                </strong>

                            </div>


                            <div class="d-flex justify-content-between mb-3">

                                <span>Delivery Fee</span>

                                <strong>
                                    KSh <?= number_format($deliveryFee, 2) ?>
                                </strong>

                            </div>


                            <hr>

                            <div class="d-flex justify-content-between mb-4">

                                <h5 class="fw-bold">Total</h5>

                                <h5 class="fw-bold">
                                    KSh <?= number_format($totalAmount, 2) ?>
                                </h5>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-dark btn-lg w-100"
                            >
                                Place Order
                            </button>


                            <a
                                href="cart.php"
                                class="btn btn-outline-dark w-100 mt-3"
                            >
                                Back to Cart
                            </a>


                            <p class="text-muted small text-center mt-3">
                                Please verify your delivery details before placing your order.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </section>

<?php endif; ?>


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
