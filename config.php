<?php
/**
 * Railway Management System (RMS) — Application Configuration
 *
 * Defaults to SQLite (auto-initialized from railway_management_system.sql).
 * Override via environment variables or constants below to use MySQL/MariaDB.
 */

declare(strict_types=1);

define('RMS_APP_NAME', 'Railway Management System');
define('RMS_APP_TAGLINE', 'Passenger & Operations Portal');

// Supported drivers: 'sqlite' (zero-config default) or 'mysql'
define('RMS_DB_DRIVER', getenv('RMS_DB_DRIVER') ?: 'sqlite');

// SQLite settings (railway.db is already listed in .gitignore)
define('RMS_SQLITE_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'railway.db');
define('RMS_SEED_SQL_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'railway_management_system.sql');

// Optional MySQL settings (used when RMS_DB_DRIVER === 'mysql')
define('RMS_MYSQL_HOST', getenv('RMS_MYSQL_HOST') ?: '127.0.0.1');
define('RMS_MYSQL_PORT', getenv('RMS_MYSQL_PORT') ?: '3306');
define('RMS_MYSQL_DB', getenv('RMS_MYSQL_DB') ?: 'railway_management_system');
define('RMS_MYSQL_USER', getenv('RMS_MYSQL_USER') ?: 'root');
define('RMS_MYSQL_PASS', getenv('RMS_MYSQL_PASS') ?: '');
