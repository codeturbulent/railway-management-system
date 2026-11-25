<?php
/**
 * Railway Management System (RMS) — Database Connection & Auto-Initializer
 *
 * Connects via PDO (SQLite by default, or MySQL if configured) and ensures
 * both the core RMS tables and UserAccount authentication table are seeded.
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

/**
 * Returns a singleton PDO instance ready for queries.
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (RMS_DB_DRIVER === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            RMS_MYSQL_HOST,
            RMS_MYSQL_PORT,
            RMS_MYSQL_DB
        );
        $pdo = new PDO($dsn, RMS_MYSQL_USER, RMS_MYSQL_PASS, $options);
    } else {
        $pdo = new PDO('sqlite:' . RMS_SQLITE_PATH, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    initialize_database($pdo);

    return $pdo;
}

/**
 * Checks whether a table exists in the active database.
 */
function db_table_exists(PDO $pdo, string $tableName): bool
{
    if (RMS_DB_DRIVER === 'mysql') {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?");
    $stmt->execute([$tableName]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Seeds core RMS tables from railway_management_system.sql if missing,
 * then ensures UserAccount exists and has default demo accounts.
 */
function initialize_database(PDO $pdo): void
{
    if (!db_table_exists($pdo, 'Station') && is_readable(RMS_SEED_SQL_PATH)) {
        $sql = file_get_contents(RMS_SEED_SQL_PATH);
        if ($sql !== false) {
            if (RMS_DB_DRIVER === 'sqlite') {
                $pdo->exec($sql);
            } else {
                foreach (explode(';', $sql) as $statement) {
                    $trimmed = trim($statement);
                    if ($trimmed !== '') {
                        $pdo->exec($trimmed);
                    }
                }
            }
        }
    }

    if (!db_table_exists($pdo, 'UserAccount')) {
        if (RMS_DB_DRIVER === 'mysql') {
            $pdo->exec("
                CREATE TABLE UserAccount (
                    UserID INT AUTO_INCREMENT PRIMARY KEY,
                    PassengerID INT NULL UNIQUE,
                    FullName VARCHAR(100) NOT NULL,
                    Email VARCHAR(100) NOT NULL UNIQUE,
                    PasswordHash VARCHAR(255) NOT NULL,
                    Role VARCHAR(20) NOT NULL DEFAULT 'Passenger',
                    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (PassengerID) REFERENCES Passenger(PassengerID) ON DELETE SET NULL
                )
            ");
        } else {
            $pdo->exec("
                CREATE TABLE UserAccount (
                    UserID INTEGER PRIMARY KEY AUTOINCREMENT,
                    PassengerID INTEGER NULL UNIQUE,
                    FullName VARCHAR(100) NOT NULL,
                    Email VARCHAR(100) NOT NULL UNIQUE,
                    PasswordHash VARCHAR(255) NOT NULL,
                    Role VARCHAR(20) NOT NULL DEFAULT 'Passenger' CHECK (Role IN ('Passenger', 'Admin')),
                    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (PassengerID) REFERENCES Passenger(PassengerID) ON DELETE SET NULL
                )
            ");
        }
    }

    seed_default_user_accounts($pdo);
}

/**
 * Ensures demo Admin and Passenger login accounts exist.
 * Existing accounts are preserved via INSERT/skip check.
 */
function seed_default_user_accounts(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM UserAccount')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO UserAccount (PassengerID, FullName, Email, PasswordHash, Role) VALUES (?, ?, ?, ?, ?)'
    );

    // 1. Seed Admin account
    $insert->execute([
        null,
        'Railway Operations Admin',
        'admin@railway.in',
        password_hash('admin123', PASSWORD_DEFAULT),
        'Admin',
    ]);

    // 2. Seed one login account per sample Passenger (default demo password: rail1234)
    $passengerHash = password_hash('rail1234', PASSWORD_DEFAULT);
    $passengers = $pdo->query(
        'SELECT PassengerID, FirstName, LastName, Email FROM Passenger ORDER BY PassengerID'
    )->fetchAll();

    foreach ($passengers as $p) {
        $insert->execute([
            (int) $p['PassengerID'],
            trim($p['FirstName'] . ' ' . $p['LastName']),
            strtolower(trim($p['Email'])),
            $passengerHash,
            'Passenger',
        ]);
    }
}
