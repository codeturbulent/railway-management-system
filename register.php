<?php
/**
 * Railway Management System (RMS) — Passenger Registration Page
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

start_rms_session();

if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$pdo = get_db();
$error = null;
$form = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'city' => '',
    'gender' => 'M',
    'age' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? $form[$field]));
    }

    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        $error = 'Session expired. Please try again.';
    } else {
        $result = register_passenger_account($pdo, $_POST);
        if ($result['ok']) {
            header('Location: login.php?registered=1');
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= e(RMS_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <main class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="auth-logo">🚆</div>
            <h1>Create Account</h1>
            <p class="auth-subtitle"><?= e(RMS_APP_NAME) ?></p>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?= e($form['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?= e($form['last_name']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= e($form['phone']) ?>" placeholder="10-15 digits" required>
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?= e($form['city']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="M" <?= $form['gender'] === 'M' ? 'selected' : '' ?>>Male</option>
                        <option value="F" <?= $form['gender'] === 'F' ? 'selected' : '' ?>>Female</option>
                        <option value="O" <?= $form['gender'] === 'O' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" min="1" max="120" value="<?= e($form['age']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p class="auth-footer-link">
            Already have an account? <a href="login.php">Sign In</a>
        </p>
    </main>
</body>
</html>
