<?php
/**
 * Railway Management System (RMS) — Logout Handler
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

logout_user();
header('Location: login.php?logged_out=1');
exit;
