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
    $info = 'You have been signed out safely.';
} elseif (isset($_GET['registered'])) {
    $info = 'Account created successfully! Sign in with your new email and password.';
} elseif (isset($_GET['auth_required'])) {
    $error = 'Please sign in to access the Railway Management Portal.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rememberEmail = isset($_POST['remember_email']);
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        $error = 'Your session form token expired. Please submit the form again.';
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

// Fetch visible system highlights for the login preview panel
$stationCount = (int) $pdo->query('SELECT COUNT(*) FROM Station')->fetchColumn();
$trainCount = (int) $pdo->query('SELECT COUNT(*) FROM Train')->fetchColumn();
$bookingCount = (int) $pdo->query('SELECT COUNT(*) FROM Booking')->fetchColumn();

$featuredTrains = $pdo->query('
    SELECT t.TrainNumber, t.TrainName, s1.StationCode AS SrcCode, s2.StationCode AS DstCode
    FROM Train t
    JOIN Station s1 ON t.SourceStationID = s1.StationID
    JOIN Station s2 ON t.DestinationStationID = s2.StationID
    ORDER BY t.TrainID
    LIMIT 4
')->fetchAll();
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
    <main class="auth-shell">
        <section class="auth-brand">
            <div>
                <div class="brand-badge">🚆 RailPortal • Live Database Connected</div>
                <h1><?= e(RMS_APP_NAME) ?></h1>
                <p class="lead">
                    Sign in to view your confirmed PNR reservations, coach &amp; berth allocations,
                    live train itineraries, and station network details.
                </p>

                <div class="preview-stats">
                    <div class="preview-stat">
                        <span class="num"><?= $stationCount ?></span>
                        <span class="lbl">Stations</span>
                    </div>
                    <div class="preview-stat">
                        <span class="num"><?= $trainCount ?></span>
                        <span class="lbl">Active Trains</span>
                    </div>
                    <div class="preview-stat">
                        <span class="num"><?= $bookingCount ?></span>
                        <span class="lbl">Bookings</span>
                    </div>
                </div>
            </div>

            <div class="route-preview">
                <h3>Active Express &amp; Rajdhani Routes</h3>
                <?php foreach ($featuredTrains as $train): ?>
                    <div class="route-item">
                        <span><code><?= e($train['TrainNumber']) ?></code> <?= e($train['TrainName']) ?></span>
                        <span><?= e($train['SrcCode']) ?> → <?= e($train['DstCode']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="auth-form-panel">
            <h2>Member Sign In</h2>
            <p class="auth-subtitle">Enter your registered email and password to continue.</p>

            <?php if ($error !== null): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($info !== null): ?>
                <div class="alert alert-success" role="status"><?= e($info) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($submittedEmail) ?>"
                        placeholder="e.g. aarav.sharma@gmail.com"
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
                            placeholder="Enter your password"
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
                        Remember email
                    </label>
                    <span class="panel-sub">Verified via PHP Session</span>
                </div>

                <button type="submit" class="btn btn-primary">Sign In to RailPortal</button>
            </form>

            <div class="demo-box">
                <div class="demo-box-title">Quick Demo Login Accounts (Click to Fill)</div>
                <div class="demo-accounts">
                    <div class="demo-account-row">
                        <div>
                            <strong>Admin:</strong> <code>admin@railway.in</code> / <code>admin123</code>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm fill-demo" data-email="admin@railway.in" data-pass="admin123">
                            Fill
                        </button>
                    </div>
                    <div class="demo-account-row">
                        <div>
                            <strong>Passenger (Aarav):</strong> <code>aarav.sharma@gmail.com</code> / <code>rail1234</code>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm fill-demo" data-email="aarav.sharma@gmail.com" data-pass="rail1234">
                            Fill
                        </button>
                    </div>
                    <div class="demo-account-row">
                        <div>
                            <strong>Passenger (Priya):</strong> <code>priya.verma@yahoo.com</code> / <code>rail1234</code>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm fill-demo" data-email="priya.verma@yahoo.com" data-pass="rail1234">
                            Fill
                        </button>
                    </div>
                </div>
            </div>

            <p class="auth-footer-link">
                New passenger? <a href="register.php">Create a passenger account</a>
            </p>
        </section>
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
