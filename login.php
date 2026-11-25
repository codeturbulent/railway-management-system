<?php
/**
 * Railway Management System (RMS) — User Login Page
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
$info = null;
$submittedEmail = $_COOKIE['rms_remember_email'] ?? '';

if (isset($_GET['logged_out'])) {
    $info = 'Signed out.';
} elseif (isset($_GET['registered'])) {
    $info = 'Account created. Please sign in.';
} elseif (isset($_GET['auth_required'])) {
    $error = 'Please sign in to continue.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rememberEmail = isset($_POST['remember_email']);
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        $error = 'Session expired. Please try again.';
    } else {
        $result = verify_user_login($pdo, $submittedEmail, $password);
        if ($result['ok']) {
            if ($rememberEmail) {
                setcookie('rms_remember_email', strtolower($submittedEmail), [
                    'expires' => time() + (30 * 86400),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie('rms_remember_email', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            header('Location: dashboard.php');
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
    <title>Login — <?= e(RMS_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">🚆</div>
            <h1><?= e(RMS_APP_NAME) ?></h1>
            <p class="auth-subtitle">Sign in to continue</p>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($info !== null): ?>
            <div class="alert alert-success" role="status"><?= e($info) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($submittedEmail) ?>"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Password"
                        required
                        minlength="4"
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-pass" id="togglePassword" aria-label="Show password">
                        Show
                    </button>
                </div>
            </div>

            <div class="form-meta">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_email" value="1" <?= $submittedEmail !== '' ? 'checked' : '' ?>>
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="demo-strip">
            <span>Demo:</span>
            <div class="demo-btns">
                <button type="button" class="btn btn-outline btn-sm fill-demo" data-email="admin@railway.in" data-pass="admin123">
                    Admin
                </button>
                <button type="button" class="btn btn-outline btn-sm fill-demo" data-email="aarav.sharma@gmail.com" data-pass="rail1234">
                    Passenger
                </button>
            </div>
        </div>

        <p class="auth-footer-link">
            No account? <a href="register.php">Register</a>
        </p>
    </main>

    <script>
        const passInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const emailInput = document.getElementById('email');

        toggleBtn.addEventListener('click', () => {
            const isHidden = passInput.type === 'password';
            passInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'Hide' : 'Show';
        });

        document.querySelectorAll('.fill-demo').forEach((btn) => {
            btn.addEventListener('click', () => {
                emailInput.value = btn.dataset.email;
                passInput.value = btn.dataset.pass;
                emailInput.focus();
            });
        });
    </script>
</body>
</html>
