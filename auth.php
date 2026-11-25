<?php
/**
 * Railway Management System (RMS) — Authentication, Session & Validation Helpers
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

/**
 * Starts a hardened PHP session once per request.
 */
function start_rms_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Escapes text safely for HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Returns (and lazily creates) the current request CSRF token.
 */
function csrf_token(): string
{
    start_rms_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the session token.
 */
function verify_csrf_token(?string $token): bool
{
    start_rms_session();
    if (empty($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Returns the logged-in user array or null.
 */
function current_user(): ?array
{
    start_rms_session();
    $user = $_SESSION['rms_user'] ?? null;
    return is_array($user) ? $user : null;
}

/**
 * Redirects unauthenticated visitors to login.php.
 */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: login.php?auth_required=1');
        exit;
    }
    return $user;
}

/**
 * Performs basic field validation and verifies user credentials against UserAccount.
 *
 * @return array{ok: bool, error: ?string, user: ?array}
 */
function verify_user_login(PDO $pdo, string $rawEmail, string $password): array
{
    $email = strtolower(trim($rawEmail));

    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Please enter both your email address and password.', 'user' => null];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.', 'user' => null];
    }

    if (strlen($password) < 4) {
        return ['ok' => false, 'error' => 'Password must be at least 4 characters long.', 'user' => null];
    }

    $stmt = $pdo->prepare('
        SELECT u.UserID, u.PassengerID, u.FullName, u.Email, u.PasswordHash, u.Role,
               p.PhoneNumber, p.City, p.Gender, p.Age
        FROM UserAccount u
        LEFT JOIN Passenger p ON u.PassengerID = p.PassengerID
        WHERE LOWER(u.Email) = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['PasswordHash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password. Please check your credentials and try again.', 'user' => null];
    }

    start_rms_session();
    session_regenerate_id(true);

    $sessionUser = [
        'user_id' => (int) $row['UserID'],
        'passenger_id' => $row['PassengerID'] !== null ? (int) $row['PassengerID'] : null,
        'full_name' => $row['FullName'],
        'email' => $row['Email'],
        'role' => $row['Role'],
        'phone' => $row['PhoneNumber'] ?? null,
        'city' => $row['City'] ?? 'New Delhi (HQ)',
        'gender' => $row['Gender'] ?? null,
        'age' => $row['Age'] !== null ? (int) $row['Age'] : null,
        'logged_in_at' => date('Y-m-d H:i:s'),
    ];

    $_SESSION['rms_user'] = $sessionUser;

    return ['ok' => true, 'error' => null, 'user' => $sessionUser];
}

/**
 * Registers a new Passenger and linked UserAccount with basic validation.
 *
 * @return array{ok: bool, error: ?string}
 */
function register_passenger_account(PDO $pdo, array $input): array
{
    $firstName = trim((string) ($input['first_name'] ?? ''));
    $lastName = trim((string) ($input['last_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? ''));
    $city = trim((string) ($input['city'] ?? ''));
    $gender = strtoupper(trim((string) ($input['gender'] ?? 'M')));
    $age = (int) ($input['age'] ?? 0);
    $password = (string) ($input['password'] ?? '');
    $confirmPassword = (string) ($input['confirm_password'] ?? '');

    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $city === '') {
        return ['ok' => false, 'error' => 'All personal detail fields are required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please provide a valid email address.'];
    }
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        return ['ok' => false, 'error' => 'Phone number must contain 10 to 15 digits.'];
    }
    if (!in_array($gender, ['M', 'F', 'O'], true)) {
        return ['ok' => false, 'error' => 'Please select a valid gender option.'];
    }
    if ($age < 1 || $age > 120) {
        return ['ok' => false, 'error' => 'Age must be between 1 and 120.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Password must be at least 6 characters long.'];
    }
    if ($password !== $confirmPassword) {
        return ['ok' => false, 'error' => 'Passwords do not match.'];
    }

    $existsStmt = $pdo->prepare('
        SELECT 1 FROM UserAccount WHERE LOWER(Email) = ?
        UNION
        SELECT 1 FROM Passenger WHERE LOWER(Email) = ? OR PhoneNumber = ?
        LIMIT 1
    ');
    $existsStmt->execute([$email, $email, $phone]);
    if ($existsStmt->fetchColumn()) {
        return ['ok' => false, 'error' => 'An account with this email or phone number already exists.'];
    }

    $pdo->beginTransaction();
    try {
        $nextPassengerId = (int) $pdo->query('SELECT COALESCE(MAX(PassengerID), 0) + 1 FROM Passenger')->fetchColumn();

        $pStmt = $pdo->prepare('
            INSERT INTO Passenger (PassengerID, FirstName, LastName, Gender, Age, PhoneNumber, Email, City)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $pStmt->execute([$nextPassengerId, $firstName, $lastName, $gender, $age, $phone, $email, $city]);

        $uStmt = $pdo->prepare('
            INSERT INTO UserAccount (PassengerID, FullName, Email, PasswordHash, Role)
            VALUES (?, ?, ?, ?, ?)
        ');
        $uStmt->execute([
            $nextPassengerId,
            $firstName . ' ' . $lastName,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            'Passenger',
        ]);

        $pdo->commit();
        return ['ok' => true, 'error' => null];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Registration failed. Please verify your details and try again.'];
    }
}

/**
 * Logs out the current user and clears session state.
 */
function logout_user(): void
{
    start_rms_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
