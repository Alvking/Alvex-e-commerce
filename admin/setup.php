<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    header('Location: login.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmation = (string)($_POST['password_confirmation'] ?? '');

    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = 'Enter a name of up to 150 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 12) {
        $errors[] = 'Choose a password with at least 12 characters.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'The password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO admins (full_name, email, password, role, status) VALUES (?, ?, ?, 'super_admin', 'active')"
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            admin_flash('success', 'Administrator created. Sign in to manage the store.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'The administrator could not be created. Confirm the email is not already in use.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up administrator | Alvex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
<main class="container py-5" style="max-width: 560px;">
    <section class="card admin-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-2">Set up the admin account</h1>
            <p class="text-muted">This one-time setup is available only while no administrator exists.</p>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= admin_escape($error) ?></div>
            <?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="full_name">Full name</label>
                    <input class="form-control" id="full_name" name="full_name" maxlength="150" required value="<?= admin_escape($name) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="150" required value="<?= admin_escape($email) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">Use at least 12 characters.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </div>
                <button class="btn btn-dark w-100" type="submit">Create admin account</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
