<?php
/**
 * Railway Management System (RMS) — Authenticated Dashboard
 *
 * Displays verified user session info, KPI summary, PNR lookup,
 * passenger/admin bookings, train schedules, and station directory.
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

$user = require_login();
$pdo = get_db();
$isAdmin = ($user['role'] === 'Admin');

/**
 * Maps booking or payment status text to a CSS badge class.
 */
function status_badge_class(string $status): string
{
    return match ($status) {
        'Confirmed', 'Success' => 'badge-success',
        'Waiting', 'Refunded' => 'badge-warning',
        'Cancelled', 'Failed' => 'badge-danger',
        default => 'badge-info',
    };
}

// 1. KPI Summary
$stationCount = (int) $pdo->query('SELECT COUNT(*) FROM Station')->fetchColumn();
$trainCount = (int) $pdo->query('SELECT COUNT(*) FROM Train')->fetchColumn();

if ($isAdmin) {
    $kpiBookings = (int) $pdo->query('SELECT COUNT(*) FROM Booking')->fetchColumn();
    $kpiConfirmed = (int) $pdo->query("SELECT COUNT(*) FROM Booking WHERE BookingStatus = 'Confirmed'")->fetchColumn();
    $kpiAmount = (float) $pdo->query("SELECT COALESCE(SUM(TotalFare), 0) FROM Booking WHERE BookingStatus = 'Confirmed'")->fetchColumn();
} else {
    $pid = (int) $user['passenger_id'];
    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) AS total_bookings,
            COALESCE(SUM(CASE WHEN BookingStatus = \'Confirmed\' THEN 1 ELSE 0 END), 0) AS confirmed_bookings,
            COALESCE(SUM(CASE WHEN BookingStatus != \'Cancelled\' THEN TotalFare ELSE 0 END), 0) AS total_spent
        FROM Booking
        WHERE PassengerID = ?
    ');
    $stmt->execute([$pid]);
    $stats = $stmt->fetch() ?: [];
    $kpiBookings = (int) ($stats['total_bookings'] ?? 0);
    $kpiConfirmed = (int) ($stats['confirmed_bookings'] ?? 0);
    $kpiAmount = (float) ($stats['total_spent'] ?? 0);
}

// 2. PNR Lookup
$pnrQuery = strtoupper(trim((string) ($_GET['pnr'] ?? '')));
$pnrResult = null;
$pnrNotFound = false;

if ($pnrQuery !== '') {
    $pnrStmt = $pdo->prepare('
        SELECT
            b.PNR, b.JourneyDate, b.BookingDate, b.TotalFare, b.BookingStatus,
            p.FirstName, p.LastName, p.City AS PassengerCity,
            t.TrainNumber, t.TrainName, t.TrainType,
            s1.StationCode AS SrcCode, s1.StationName AS SrcName,
            s2.StationCode AS DstCode, s2.StationName AS DstName,
            c.CoachNumber, c.CoachType, sr.SeatNumber, sr.BerthType,
            pay.PaymentMethod, pay.PaymentStatus
        FROM Booking b
        JOIN Passenger p ON b.PassengerID = p.PassengerID
        JOIN Train t ON b.TrainID = t.TrainID
        JOIN Station s1 ON t.SourceStationID = s1.StationID
        JOIN Station s2 ON t.DestinationStationID = s2.StationID
        LEFT JOIN SeatReservation sr ON b.BookingID = sr.BookingID
        LEFT JOIN Coach c ON sr.CoachID = c.CoachID
        LEFT JOIN Payment pay ON b.BookingID = pay.BookingID
        WHERE UPPER(b.PNR) = ?
        LIMIT 1
    ');
    $pnrStmt->execute([$pnrQuery]);
    $pnrResult = $pnrStmt->fetch() ?: null;
    if ($pnrResult === null) {
        $pnrNotFound = true;
    }
}

// 3. Bookings List (All for Admin, Personal for Passenger)
$bookingsSql = '
    SELECT
        b.PNR, b.JourneyDate, b.TotalFare, b.BookingStatus,
        p.FirstName, p.LastName,
        t.TrainNumber, t.TrainName,
        s1.StationCode AS SrcCode, s2.StationCode AS DstCode,
        c.CoachNumber, sr.SeatNumber, sr.BerthType,
        pay.PaymentMethod, pay.PaymentStatus
    FROM Booking b
    JOIN Passenger p ON b.PassengerID = p.PassengerID
    JOIN Train t ON b.TrainID = t.TrainID
    JOIN Station s1 ON t.SourceStationID = s1.StationID
    JOIN Station s2 ON t.DestinationStationID = s2.StationID
    LEFT JOIN SeatReservation sr ON b.BookingID = sr.BookingID
    LEFT JOIN Coach c ON sr.CoachID = c.CoachID
    LEFT JOIN Payment pay ON b.BookingID = pay.BookingID
';

if ($isAdmin) {
    $bookings = $pdo->query($bookingsSql . ' ORDER BY b.JourneyDate ASC, b.BookingID ASC')->fetchAll();
} else {
    $bStmt = $pdo->prepare($bookingsSql . ' WHERE b.PassengerID = ? ORDER BY b.JourneyDate ASC, b.BookingID ASC');
    $bStmt->execute([(int) $user['passenger_id']]);
    $bookings = $bStmt->fetchAll();
}

// 4. Trains & Schedules Directory (supports search filter ?q=...)
$trainSearch = trim((string) ($_GET['q'] ?? ''));
$trainParams = [];
$trainWhere = '';

if ($trainSearch !== '') {
    $trainWhere = ' WHERE t.TrainNumber LIKE ? OR t.TrainName LIKE ? OR s1.City LIKE ? OR s2.City LIKE ? ';
    $like = '%' . $trainSearch . '%';
    $trainParams = [$like, $like, $like, $like];
}

$tStmt = $pdo->prepare("
    SELECT
        t.TrainID, t.TrainNumber, t.TrainName, t.TrainType, t.TotalDistanceKm,
        s1.StationCode AS SrcCode, s1.StationName AS SrcName,
        s2.StationCode AS DstCode, s2.StationName AS DstName
    FROM Train t
    JOIN Station s1 ON t.SourceStationID = s1.StationID
    JOIN Station s2 ON t.DestinationStationID = s2.StationID
    {$trainWhere}
    ORDER BY t.TrainNumber ASC
");
$tStmt->execute($trainParams);
$trains = $tStmt->fetchAll();

// Map TrainID => ordered schedule stops
$scheduleRows = $pdo->query('
    SELECT ts.TrainID, ts.StopNumber, ts.ArrivalTime, ts.DepartureTime, s.StationCode, s.City
    FROM TrainSchedule ts
    JOIN Station s ON ts.StationID = s.StationID
    ORDER BY ts.TrainID, ts.StopNumber
')->fetchAll();

$schedulesByTrain = [];
foreach ($scheduleRows as $stop) {
    $schedulesByTrain[(int) $stop['TrainID']][] = $stop;
}

// 5. Stations Network
$stations = $pdo->query('
    SELECT StationCode, StationName, City, State, PlatformsCount
    FROM Station
    ORDER BY StationID ASC
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(RMS_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <div class="topbar-logo">🚆</div>
            <div>
                <h1><?= e(RMS_APP_NAME) ?></h1>
                <span><?= e(RMS_APP_TAGLINE) ?></span>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-pill">
                <span><?= e($user['full_name']) ?></span>
                <span class="role-tag"><?= e($user['role']) ?></span>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <section class="welcome-banner">
            <div>
                <h2>Welcome back, <?= e($user['full_name']) ?></h2>
                <div class="welcome-meta">
                    <span><strong>Email:</strong> <?= e($user['email']) ?></span>
                    <span><strong>City:</strong> <?= e($user['city']) ?></span>
                    <?php if (!empty($user['phone'])): ?>
                        <span><strong>Phone:</strong> <?= e($user['phone']) ?></span>
                    <?php endif; ?>
                    <span><strong>Signed in:</strong> <?= e($user['logged_in_at']) ?></span>
                </div>
            </div>
            <div class="verified-pill">✓ Login Verified (Active Session)</div>
        </section>

        <section class="kpi-grid">
            <article class="kpi-card">
                <div class="kpi-label"><?= $isAdmin ? 'Total Bookings' : 'My Bookings' ?></div>
                <div class="kpi-value"><?= $kpiBookings ?></div>
                <div class="kpi-sub"><?= $kpiConfirmed ?> confirmed ticket<?= $kpiConfirmed === 1 ? '' : 's' ?></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label"><?= $isAdmin ? 'Confirmed Revenue' : 'Active Fare Total' ?></div>
                <div class="kpi-value">₹<?= number_format($kpiAmount, 2) ?></div>
                <div class="kpi-sub"><?= $isAdmin ? 'Across confirmed bookings' : 'Excludes cancelled tickets' ?></div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Active Trains</div>
                <div class="kpi-value"><?= $trainCount ?></div>
                <div class="kpi-sub">Rajdhani, Superfast &amp; Mail</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Railway Stations</div>
                <div class="kpi-value"><?= $stationCount ?></div>
                <div class="kpi-sub">Connected nationwide</div>
            </article>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Quick PNR Status Lookup</h3>
                    <p class="panel-sub">Verify any reservation directly from the database.</p>
                </div>
            </div>

            <form method="GET" action="dashboard.php" class="pnr-form">
                <input
                    type="text"
                    name="pnr"
                    value="<?= e($pnrQuery) ?>"
                    placeholder="Enter PNR (e.g. PNR812301)"
                    maxlength="12"
                >
                <button type="submit" class="btn btn-accent">Check PNR</button>
                <?php if ($pnrQuery !== ''): ?>
                    <a href="dashboard.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>

            <div class="pnr-chips">
                <span>Try sample PNRs:</span>
                <?php foreach (['PNR812301', 'PNR812305', 'PNR812310', 'PNR812312'] as $samplePnr): ?>
                    <a class="pnr-chip" href="dashboard.php?pnr=<?= e($samplePnr) ?>"><?= e($samplePnr) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($pnrNotFound): ?>
                <div class="alert alert-error" style="margin-top: 14px; margin-bottom: 0;">
                    No booking record found for PNR <strong><?= e($pnrQuery) ?></strong>.
                </div>
            <?php elseif ($pnrResult !== null): ?>
                <div class="pnr-result">
                    <div class="pnr-field">
                        <span>PNR Number</span>
                        <strong><?= e($pnrResult['PNR']) ?></strong>
                    </div>
                    <div class="pnr-field">
                        <span>Passenger</span>
                        <strong><?= e($pnrResult['FirstName'] . ' ' . $pnrResult['LastName']) ?></strong>
                    </div>
                    <div class="pnr-field">
                        <span>Train &amp; Route</span>
                        <strong><?= e($pnrResult['TrainNumber'] . ' ' . $pnrResult['TrainName']) ?> (<?= e($pnrResult['SrcCode']) ?> → <?= e($pnrResult['DstCode']) ?>)</strong>
                    </div>
                    <div class="pnr-field">
                        <span>Journey Date</span>
                        <strong><?= e($pnrResult['JourneyDate']) ?></strong>
                    </div>
                    <div class="pnr-field">
                        <span>Coach / Seat</span>
                        <strong>
                            <?php if (!empty($pnrResult['CoachNumber'])): ?>
                                <?= e($pnrResult['CoachNumber']) ?> • Seat <?= e((string) $pnrResult['SeatNumber']) ?> (<?= e($pnrResult['BerthType']) ?>)
                            <?php else: ?>
                                Not Assigned
                            <?php endif; ?>
                        </strong>
                    </div>
                    <div class="pnr-field">
                        <span>Booking &amp; Payment</span>
                        <strong>
                            <span class="badge <?= e(status_badge_class($pnrResult['BookingStatus'])) ?>">
                                <?= e($pnrResult['BookingStatus']) ?>
                            </span>
                            <?php if (!empty($pnrResult['PaymentStatus'])): ?>
                                <span class="badge <?= e(status_badge_class($pnrResult['PaymentStatus'])) ?>">
                                    <?= e($pnrResult['PaymentMethod'] . ': ' . $pnrResult['PaymentStatus']) ?>
                                </span>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3><?= $isAdmin ? 'All System Bookings' : 'My Ticket Bookings' ?></h3>
                    <p class="panel-sub">
                        <?= $isAdmin ? 'Complete reservation ledger across all passengers.' : 'Reservations linked to your passenger account.' ?>
                    </p>
                </div>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    No bookings found for your account yet. Use the PNR Lookup or browse Trains &amp; Schedules below.
                </div>
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
                                <th>Journey Date</th>
                                <th>Coach / Seat</th>
                                <th>Fare</th>
                                <th>Booking Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= e($b['PNR']) ?></strong></td>
                                    <?php if ($isAdmin): ?>
                                        <td><?= e($b['FirstName'] . ' ' . $b['LastName']) ?></td>
                                    <?php endif; ?>
                                    <td><?= e($b['TrainNumber'] . ' — ' . $b['TrainName']) ?></td>
                                    <td><?= e($b['SrcCode']) ?> → <?= e($b['DstCode']) ?></td>
                                    <td><?= e($b['JourneyDate']) ?></td>
                                    <td>
                                        <?php if (!empty($b['CoachNumber'])): ?>
                                            <?= e($b['CoachNumber']) ?> / #<?= e((string) $b['SeatNumber']) ?> (<?= e($b['BerthType']) ?>)
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?= number_format((float) $b['TotalFare'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= e(status_badge_class($b['BookingStatus'])) ?>">
                                            <?= e($b['BookingStatus']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($b['PaymentStatus'])): ?>
                                            <span class="badge <?= e(status_badge_class($b['PaymentStatus'])) ?>">
                                                <?= e($b['PaymentMethod']) ?> (<?= e($b['PaymentStatus']) ?>)
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Trains &amp; Route Schedules</h3>
                    <p class="panel-sub">Live routes and intermediate stop timings from TrainSchedule.</p>
                </div>
                <form method="GET" action="dashboard.php" class="pnr-form">
                    <input
                        type="text"
                        name="q"
                        value="<?= e($trainSearch) ?>"
                        placeholder="Search train or city..."
                    >
                    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                    <?php if ($trainSearch !== ''): ?>
                        <a href="dashboard.php" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($trains)): ?>
                <div class="empty-state">No trains matched "<?= e($trainSearch) ?>".</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Train</th>
                                <th>Type</th>
                                <th>Origin → Destination</th>
                                <th>Distance</th>
                                <th>Stop Itinerary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trains as $t): ?>
                                <?php $stops = $schedulesByTrain[(int) $t['TrainID']] ?? []; ?>
                                <tr>
                                    <td><strong><?= e($t['TrainNumber']) ?></strong><br><?= e($t['TrainName']) ?></td>
                                    <td><span class="badge badge-info"><?= e($t['TrainType']) ?></span></td>
                                    <td><?= e($t['SrcName']) ?> (<?= e($t['SrcCode']) ?>) → <?= e($t['DstName']) ?> (<?= e($t['DstCode']) ?>)</td>
                                    <td><?= e((string) $t['TotalDistanceKm']) ?> km</td>
                                    <td>
                                        <div class="schedule-stops">
                                            <?php foreach ($stops as $stop): ?>
                                                <?php
                                                    $timeLabel = $stop['DepartureTime'] ?: $stop['ArrivalTime'];
                                                    $shortTime = $timeLabel ? substr((string) $timeLabel, 0, 5) : '--:--';
                                                ?>
                                                <span class="stop-pill">
                                                    <?= e($stop['StationCode']) ?> (<?= e($shortTime) ?>)
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Station Network Directory</h3>
                    <p class="panel-sub">All <?= count($stations) ?> railway terminals registered in the system.</p>
                </div>
            </div>
            <div class="station-grid">
                <?php foreach ($stations as $st): ?>
                    <article class="station-card">
                        <span class="station-code"><?= e($st['StationCode']) ?></span>
                        <h4><?= e($st['StationName']) ?></h4>
                        <p><?= e($st['City']) ?>, <?= e($st['State']) ?> • <?= e((string) $st['PlatformsCount']) ?> Platforms</p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
