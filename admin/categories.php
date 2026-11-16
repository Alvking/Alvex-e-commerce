<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
admin_require_login();

$errors = [];
$name = '';
$description = '';
$image = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        $categoryId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$categoryId) {
            admin_flash('danger', 'Invalid category.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$categoryId]);
            admin_flash('success', $stmt->rowCount() ? 'Category deleted. Its products are now uncategorized.' : 'Category not found.');
        }
        header('Location: categories.php');
        exit;
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $image = trim((string)($_POST['image'] ?? ''));
    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = 'Category name is required and must be 150 characters or fewer.';
    }
    if (strlen($image) > 2048) {
        $errors[] = 'Category image URL must be 2048 characters or fewer.';
    } elseif ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL) && !preg_match('~^[a-zA-Z0-9_./-]+$~', $image)) {
        $errors[] = 'Use a valid image URL or a path relative to the website root.';
    }

    if ($errors === []) {
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, description, image) VALUES (?, ?, ?)');
            $stmt->execute([$name, $description !== '' ? $description : null, $image !== '' ? $image : null]);
            admin_flash('success', 'Category created.');
            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Category could not be saved. Check that the name is unique and the database is available.';
        }
    }
}

$categories = $pdo->query(
    'SELECT c.id, c.name, c.description, c.image, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.description, c.image
     ORDER BY c.name'
)->fetchAll(PDO::FETCH_ASSOC);

admin_header('Categories');
?>
<div class="mb-4"><h1 class="h2 mb-1">Categories</h1><p class="text-muted mb-0">Create categories and assign products to them in the product editor.</p></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= admin_escape($error) ?></div><?php endforeach; ?>
<div class="row g-4">
    <div class="col-lg-4">
        <form class="card admin-card" method="post">
            <div class="card-body">
                <h2 class="h5 mb-3">Add category</h2>
                <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                <div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" maxlength="150" required value="<?= admin_escape($name) ?>"></div>
                <div class="mb-3"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3"><?= admin_escape($description) ?></textarea></div>
                <div class="mb-3"><label class="form-label" for="image">Category image URL or root-relative path (optional)</label><input class="form-control" id="image" name="image" maxlength="2048" value="<?= admin_escape($image) ?>"></div>
                <button class="btn btn-dark" type="submit">Add category</button>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        <section class="card admin-card"><div class="card-body">
            <h2 class="h5 mb-3">Current categories</h2>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead><tr><th>Name</th><th>Description</th><th>Products</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="fw-semibold"><?= admin_escape($category['name']) ?></td>
                            <td><?= admin_escape($category['description'] ?? '') ?></td>
                            <td><?= (int)$category['product_count'] ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this category? Products in it will become uncategorized.')">
                                    <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($categories === []): ?><tr><td colspan="4" class="text-center text-muted">No categories yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></section>
    </div>
</div>
<?php admin_footer(); ?>
