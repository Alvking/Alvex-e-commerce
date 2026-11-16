<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
admin_verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$active = filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);
if (!$id || !in_array($active, [0, 1], true)) {
    admin_flash('danger', 'Invalid product visibility request.');
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare('UPDATE products SET is_active = ? WHERE id = ?');
$stmt->execute([$active, $id]);
admin_flash('success', $active ? 'Product restored to the storefront.' : 'Product archived and removed from the storefront.');
header('Location: products.php');
exit;
