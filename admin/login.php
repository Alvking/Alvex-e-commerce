<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    header('Location: setup.php');
    exit;
}
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT id, full_name, password FROM admins WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
        header('Location: index.php');
        exit;
    }

    $error = 'Email or password is incorrect, or this account is inactive.';
}
$flash = admin_take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | Alvex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
<main class="container py-5" style="max-width: 520px;">
    <section class="card admin-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-4">Admin sign in</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= admin_escape($flash['type']) ?>"><?= admin_escape($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= admin_escape($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" autocomplete="username" required value="<?= admin_escape($email) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-dark w-100" type="submit">Sign in</button>
            </form>
            <a class="d-inline-block mt-3" href="../index.html">Back to store</a>
        </div>
    </section>
</main>
</body>
</html>
