-- =============================================================================
-- OPTIONAL AUTHENTICATION TABLE FOR MANUAL MYSQL/POSTGRESQL SETUP
-- (SQLite auto-creates and seeds UserAccount automatically via db.php)
-- =============================================================================

CREATE TABLE IF NOT EXISTS UserAccount (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    PassengerID INT NULL UNIQUE,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL DEFAULT 'Passenger',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PassengerID) REFERENCES Passenger(PassengerID) ON DELETE SET NULL
);
