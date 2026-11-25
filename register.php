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
        $error = 'Your session form token expired. Please submit the form again.';
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
    <main class="auth-shell">
        <section class="auth-brand">
            <div>
                <div class="brand-badge">🚆 New Passenger Onboarding</div>
                <h1>Create Your RailPortal Account</h1>
                <p class="lead">
                    Register as a passenger in the Railway Management System database to verify
                    your credentials and access schedules, stations, and PNR tools.
                </p>
            </div>

            <div class="route-preview">
                <h3>What You Get After Sign In</h3>
                <div class="route-item"><span>Verified Session Dashboard</span><span>Instant</span></div>
                <div class="route-item"><span>Live PNR Status Lookup</span><span>24/7</span></div>
                <div class="route-item"><span>Train Routes &amp; Timetables</span><span>All Trains</span></div>
                <div class="route-item"><span>Station Platform Directory</span><span>11+ Stations</span></div>
            </div>
        </section>

        <section class="auth-form-panel">
            <h2>Passenger Registration</h2>
            <p class="auth-subtitle">Fill in your details to create an authenticated account.</p>

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
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
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
                        <label for="password">Password (min 6 chars)</label>
                        <input type="password" id="password" name="password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Passenger Account</button>
            </form>

            <p class="auth-footer-link">
                Already registered? <a href="login.php">Back to Sign In</a>
            </p>
        </section>
    </main>
</body>
</html>
