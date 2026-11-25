<?php
/**
 * Railway Management System (RMS) — Minimal Booking & Data View
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

$user = require_login();
$pdo = get_db();
$isAdmin = ($user['role'] === 'Admin');

$fareByClass = [
    'Sleeper' => 650.00,
    'AC 3-Tier' => 1650.00,
    'AC 2-Tier' => 2450.00,
    'AC 1-Tier' => 3850.00,
];

$bookingError = null;
$bookingSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        $bookingError = 'Session expired. Please try again.';
    } else {
        $trainId = (int) ($_POST['train_id'] ?? 0);
        $journeyDate = trim((string) ($_POST['journey_date'] ?? ''));
        $coachClass = (string) ($_POST['coach_class'] ?? '');
        $passengerId = $isAdmin
            ? (int) ($_POST['passenger_id'] ?? 0)
            : (int) ($user['passenger_id'] ?? 0);

        $trainCheck = $pdo->prepare('SELECT TrainID FROM Train WHERE TrainID = ?');
        $trainCheck->execute([$trainId]);

        $passCheck = $pdo->prepare('SELECT PassengerID FROM Passenger WHERE PassengerID = ?');
        $passCheck->execute([$passengerId]);

        if (!$trainCheck->fetchColumn()) {
            $bookingError = 'Please select a valid train.';
        } elseif (!$passCheck->fetchColumn()) {
            $bookingError = 'Please select a valid passenger.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $journeyDate)) {
            $bookingError = 'Please choose a valid journey date.';
        } elseif (!isset($fareByClass[$coachClass])) {
            $bookingError = 'Please select a valid travel class.';
        } else {
            $pdo->beginTransaction();
            try {
                $nextId = (int) $pdo->query('SELECT COALESCE(MAX(BookingID), 1000) + 1 FROM Booking')->fetchColumn();
                $pnr = 'PNR' . (812300 + ($nextId - 1000));
                $fare = $fareByClass[$coachClass];

                $ins = $pdo->prepare('
                    INSERT INTO Booking (BookingID, PNR, PassengerID, TrainID, JourneyDate, BookingDate, TotalFare, BookingStatus)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $ins->execute([$nextId, $pnr, $passengerId, $trainId, $journeyDate, date('Y-m-d'), $fare, 'Confirmed']);
                $pdo->commit();

                $bookingSuccess = "Booking confirmed! PNR: {$pnr}";
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $bookingError = 'Could not create booking. Please try again.';
            }
        }
    }
}

$trains = $pdo->query('
    SELECT
        t.TrainID, t.TrainNumber, t.TrainName, t.TrainType, t.TotalDistanceKm,
        s1.StationCode AS SrcCode, s2.StationCode AS DstCode
    FROM Train t
    JOIN Station s1 ON t.SourceStationID = s1.StationID
    JOIN Station s2 ON t.DestinationStationID = s2.StationID
    ORDER BY t.TrainNumber ASC
')->fetchAll();

$passengers = $isAdmin
    ? $pdo->query('SELECT PassengerID, FirstName, LastName, Email FROM Passenger ORDER BY FirstName, LastName')->fetchAll()
    : [];

$bookingsSql = '
    SELECT
        b.PNR, b.JourneyDate, b.TotalFare, b.BookingStatus,
        p.FirstName, p.LastName,
        t.TrainNumber, t.TrainName,
        s1.StationCode AS SrcCode, s2.StationCode AS DstCode
    FROM Booking b
    JOIN Passenger p ON b.PassengerID = p.PassengerID
    JOIN Train t ON b.TrainID = t.TrainID
    JOIN Station s1 ON t.SourceStationID = s1.StationID
    JOIN Station s2 ON t.DestinationStationID = s2.StationID
';

if ($isAdmin) {
    $bookings = $pdo->query($bookingsSql . ' ORDER BY b.BookingID DESC')->fetchAll();
} else {
    $bStmt = $pdo->prepare($bookingsSql . ' WHERE b.PassengerID = ? ORDER BY b.BookingID DESC');
    $bStmt->execute([(int) $user['passenger_id']]);
    $bookings = $bStmt->fetchAll();
}

$defaultDate = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(RMS_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <?= e(RMS_APP_NAME) ?>
        </div>
        <div class="topbar-user">
            <span class="user-name"><?= e($user['full_name']) ?></span>
            <span class="role-tag">(<?= e($user['role']) ?>)</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <section class="panel">
            <h2 class="panel-title">Book Ticket</h2>

            <?php if ($bookingError !== null): ?>
                <div class="alert alert-error" role="alert"><?= e($bookingError) ?></div>
            <?php endif; ?>

            <?php if ($bookingSuccess !== null): ?>
                <div class="alert alert-success" role="status"><?= e($bookingSuccess) ?></div>
            <?php endif; ?>

            <form method="POST" action="dashboard.php" class="booking-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="book">

                <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="passenger_id">Passenger</label>
                        <select id="passenger_id" name="passenger_id" required>
                            <?php foreach ($passengers as $p): ?>
                                <option value="<?= (int) $p['PassengerID'] ?>">
                                    <?= e($p['FirstName'] . ' ' . $p['LastName'] . ' (' . $p['Email'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="train_id">Train</label>
                    <select id="train_id" name="train_id" required>
                        <?php foreach ($trains as $t): ?>
                            <option value="<?= (int) $t['TrainID'] ?>">
                                <?= e($t['TrainNumber'] . ' - ' . $t['TrainName'] . ' (' . $t['SrcCode'] . ' → ' . $t['DstCode'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="journey_date">Date</label>
                    <input type="date" id="journey_date" name="journey_date" value="<?= e($defaultDate) ?>" required>
                </div>

                <div class="form-group">
                    <label for="coach_class">Class</label>
                    <select id="coach_class" name="coach_class" required>
                        <?php foreach ($fareByClass as $class => $fare): ?>
                            <option value="<?= e($class) ?>">
                                <?= e($class) ?> (₹<?= number_format($fare, 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">Book</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2 class="panel-title"><?= $isAdmin ? 'All Bookings' : 'My Bookings' ?></h2>

            <?php if (empty($bookings)): ?>
                <p class="empty-state">No bookings yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>PNR</th>
                                <?php if ($isAdmin): ?>
                                    <th>Passenger</th>
                                <?php endif; ?>
                                <th>Train</th>
                                <th>Route</th>
                                <th>Date</th>
                                <th>Fare</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php
                                    $badgeClass = match ($b['BookingStatus']) {
                                        'Confirmed' => 'badge-success',
                                        'Waiting' => 'badge-warning',
                                        default => 'badge-danger',
                                    };
                                ?>
                                <tr>
                                    <td><strong><?= e($b['PNR']) ?></strong></td>
                                    <?php if ($isAdmin): ?>
                                        <td><?= e($b['FirstName'] . ' ' . $b['LastName']) ?></td>
                                    <?php endif; ?>
                                    <td><?= e($b['TrainNumber'] . ' ' . $b['TrainName']) ?></td>
                                    <td><?= e($b['SrcCode']) ?> → <?= e($b['DstCode']) ?></td>
                                    <td><?= e($b['JourneyDate']) ?></td>
                                    <td>₹<?= number_format((float) $b['TotalFare'], 2) ?></td>
                                    <td><span class="badge <?= e($badgeClass) ?>"><?= e($b['BookingStatus']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2 class="panel-title">Trains</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Route</th>
                            <th>Distance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trains as $t): ?>
                            <tr>
                                <td><strong><?= e($t['TrainNumber']) ?></strong></td>
                                <td><?= e($t['TrainName']) ?></td>
                                <td><?= e($t['TrainType']) ?></td>
                                <td><?= e($t['SrcCode']) ?> → <?= e($t['DstCode']) ?></td>
                                <td><?= e((string) $t['TotalDistanceKm']) ?> km</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
