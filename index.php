<?php
/**
 * Railway Management System (RMS) — Entry Point
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
