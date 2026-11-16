<?php
declare(strict_types=1);

session_name('alvex_admin_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
    'path' => '/',
]);
session_start();

require_once __DIR__ . '/../config/database.php';

function admin_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function admin_verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['admin_csrf_token'] ?? '';

    if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Invalid request token. Please go back and try again.');
    }
}

function admin_require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_take_flash(): ?array
{
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return is_array($flash) ? $flash : null;
}

function admin_header(string $title): void
{
    $flash = admin_take_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= admin_escape($title) ?> | Alvex Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="admin.css">
    </head>
    <body class="admin-body">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">Alvex Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <div class="navbar-nav me-auto">
                    <a class="nav-link" href="index.php">Overview</a>
                    <a class="nav-link" href="products.php">Products</a>
                    <a class="nav-link" href="categories.php">Categories</a>
                    <a class="nav-link" href="orders.php">Orders</a>
                    <a class="nav-link" href="analytics.php">Analytics</a>
                    <a class="nav-link" href="../shop.php" target="_blank" rel="noopener">View store</a>
                </div>
                <?php if (!empty($_SESSION['admin_id'])): ?>
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                        <button class="btn btn-outline-light btn-sm" type="submit">Sign out</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container-fluid admin-main">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= admin_escape($flash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
                <?= admin_escape($flash['message'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
