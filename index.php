<?php 
include 'templates/header.php';
include 'database.php';

// Get statistics
$db = new Database();
$dbConnection = $db->getDb();

// Counts
$totalBookings = $dbConnection->query("SELECT COUNT(*) as count FROM bookings")->fetchArray(SQLITE3_ASSOC)['count'];
$homeofficeCount = $dbConnection->query("SELECT COUNT(*) as count FROM bookings WHERE location = 'homeoffice'")->fetchArray(SQLITE3_ASSOC)['count'];
$officeCount = $dbConnection->query("SELECT COUNT(*) as count FROM bookings WHERE location = 'office'")->fetchArray(SQLITE3_ASSOC)['count'];
$workTotal = $homeofficeCount + $officeCount; // neutral types are excluded

// Calculate percentages
$homeofficePercentage = $workTotal > 0 ? round(($homeofficeCount / $workTotal) * 100) : 0;
$officePercentage = $workTotal > 0 ? round(($officeCount / $workTotal) * 100) : 0;
?>

<!-- Welcome Section -->
<div class="row">
    <div class="col s12">
        <div class="card gradient-card">
            <div class="card-content white-text">
                <span class="card-title"><i class="material-icons left">dashboard</i>Dashboard</span>
                <p>Willkommen im Homeoffice Tracker. Hier finden Sie eine Übersicht Ihrer Buchungen und schnellen Zugriff auf wichtige Funktionen.</p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="row">
    <div class="col s12 m4">
        <div class="card">
            <div class="card-content center-align">
                <i class="material-icons medium blue-text">event_note</i>
                <span class="card-title"><?php echo $totalBookings; ?></span>
                <p>Gesamtbuchungen</p>
            </div>
        </div>
    </div>
    <div class="col s12 m4">
        <div class="card">
            <div class="card-content center-align">
                <i class="material-icons medium green-text">home</i>
                <span class="card-title"><?php echo $homeofficePercentage; ?>%</span>
                <p>Homeoffice</p>
                <div class="progress">
                    <div class="determinate green" style="width: <?php echo $homeofficePercentage; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12 m4">
        <div class="card">
            <div class="card-content center-align">
                <i class="material-icons medium orange-text">business</i>
                <span class="card-title"><?php echo $officePercentage; ?>%</span>
                <p>Büro</p>
                <div class="progress">
                    <div class="determinate orange" style="width: <?php echo $officePercentage; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="row">
    <div class="col s12">
        <h5><i class="material-icons left">flash_on</i>Schnellzugriff</h5>
    </div>
</div>

<div class="row">
    <div class="col s12 m6 l4">
        <div class="card hoverable quick-action">
            <a href="booking.php" class="card-content blue white-text center-align waves-effect waves-light">
                <i class="material-icons medium">add_circle</i>
                <span class="card-title">Neue Buchung</span>
                <p>Tragen Sie einen neuen Arbeitstag ein</p>
            </a>
        </div>
    </div>
    <div class="col s12 m6 l4">
        <div class="card hoverable quick-action">
            <a href="evaluation.php" class="card-content orange white-text center-align waves-effect waves-light">
                <i class="material-icons medium">assessment</i>
                <span class="card-title">Auswertung</span>
                <p>Sehen Sie Ihre Statistiken ein</p>
            </a>
        </div>
    </div>
    <div class="col s12 m6 l4">
        <div class="card hoverable quick-action">
            <a href="export_pdf.php" class="card-content grey darken-1 white-text center-align waves-effect waves-light">
                <i class="material-icons medium">picture_as_pdf</i>
                <span class="card-title">PDF Export</span>
                <p>Laden Sie Ihre Daten herunter</p>
            </a>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
