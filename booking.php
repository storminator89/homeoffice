<?php
require_once 'database.php';

$db = new Database();
$messages = ['success' => [], 'error' => []];

// Bestimme die aktuelle Kalenderwoche basierend auf GET oder POST Parameter oder aktuellem Datum
$selectedWeek = isset($_POST['week']) ? $_POST['week'] : (isset($_GET['week']) ? $_GET['week'] : date('W'));
$selectedYear = isset($_POST['year']) ? $_POST['year'] : (isset($_GET['year']) ? $_GET['year'] : date('Y'));

// Berechne das Datum des Montags der ausgewählten Woche
$monday = new DateTime();
$monday->setISODate($selectedYear, $selectedWeek);
$mondayTimestamp = $monday->getTimestamp();

// Berechne vorherige und nächste Woche
$prevWeek = new DateTime();
$prevWeek->setISODate($selectedYear, $selectedWeek);
$prevWeek->modify('-1 week');

$nextWeek = new DateTime();
$nextWeek->setISODate($selectedYear, $selectedWeek);
$nextWeek->modify('+1 week');

// Lade die Buchungen für die ausgewählte Woche VOR der POST-Verarbeitung
$dates = [];
for ($i = 0; $i < 5; $i++) {
    $currentDate = strtotime("+$i days", $mondayTimestamp);
    $dates[] = date('Y-m-d', $currentDate);
}

$existingBookings = $db->getBookingsForDates($dates);

// Woche-Zusammenfassung initial berechnen
$weekCounts = [
    'homeoffice' => 0,
    'office' => 0,
    'vacation' => 0,
    'sick' => 0,
    'training' => 0,
    'none' => 0
];
foreach ($dates as $d) {
    $val = isset($existingBookings[$d]) ? $existingBookings[$d] : '';
    if ($val === '' || $val === null) {
        $weekCounts['none']++;
    } elseif (isset($weekCounts[$val])) {
        $weekCounts[$val]++;
    }
}

// Week headline metrics
$weekSet = 5 - $weekCounts['none'];
$workSet = $weekCounts['homeoffice'] + $weekCounts['office'];
$weekProgress = max(0, min(100, round(($weekSet / 5) * 100)));
$weekStartDisplay = date('d.m.Y', strtotime($dates[0]));
$weekEndDisplay   = date('d.m.Y', strtotime(end($dates)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['location'] as $date => $location) {
        $dateObj = DateTime::createFromFormat('d.m.Y', $date);
        if ($dateObj) {
            try {
                $dbDate = $dateObj->format('Y-m-d');
                $currentLocation = isset($existingBookings[$dbDate]) ? $existingBookings[$dbDate] : '';
                
                // Nur wenn sich der Wert tatsächlich geändert hat
                if ($location !== $currentLocation) {
                    $db->addBooking($dbDate, $location);
                    if (!empty($location)) {
                        $messages['success'][] = "Buchung für " . $date . " auf " . 
                            ($location === 'homeoffice' ? 'Homeoffice' : 'Büro') . " geändert.";
                    } elseif (!empty($currentLocation)) {
                        // Nur eine Meldung anzeigen, wenn vorher eine Buchung existierte
                        $messages['success'][] = "Buchung für " . $date . " wurde zurückgesetzt.";
                    }
                }
            } catch (Exception $e) {
                $messages['error'][] = "Fehler am " . $date . ": " . $e->getMessage();
            }
        }
    }
    // Lade die aktualisierten Buchungen nach der Verarbeitung
    $existingBookings = $db->getBookingsForDates($dates);
}

include 'templates/header.php';
?>

<div class="row">
    <div class="col s12">
        <?php if (!empty($messages)) { ?>
            <script>
                window.serverMessages = <?php echo json_encode($messages); ?>;
            </script>
        <?php } ?>
        
        <div class="card">
            <div class="card-content">
                <!-- Week Navigator -->
                <div class="row" style="margin-bottom: 2rem;">
                    <div class="col s12">
                        <div class="card hero-card gradient-card week-navigator">
                            <div class="card-content">
                                <div class="week-toolbar">
                                    <div class="nav left">
                                        <a href="?week=<?php echo $prevWeek->format('W'); ?>&year=<?php echo $prevWeek->format('Y'); ?>" 
                                           class="btn-floating btn-small waves-effect waves-light white">
                                            <i class="material-icons blue-text text-darken-2">chevron_left</i>
                                        </a>
                                    </div>
                                    <div class="center">
                                        <div class="title-row">
                                            <span class="kw">KW <?php echo $selectedWeek; ?></span>
                                            <span class="year"><?php echo $selectedYear; ?></span>
                                        </div>
                                        <div class="sub">
                                            <i class="material-icons tiny" style="vertical-align: text-bottom;">date_range</i>
                                            <?php echo $weekStartDisplay; ?> – <?php echo $weekEndDisplay; ?>
                                        </div>
                                    </div>
                                    <div class="nav right">
                                        <?php if ($selectedWeek != date('W') || $selectedYear != date('Y')) { ?>
                                            <a href="?week=<?php echo date('W'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-outline-light btn-small hide-on-small-only">
                                                <i class="material-icons left">today</i> Aktuelle Woche
                                            </a>
                                        <?php } ?>
                                        <a href="?week=<?php echo $nextWeek->format('W'); ?>&year=<?php echo $nextWeek->format('Y'); ?>" 
                                           class="btn-floating btn-small waves-effect waves-light white">
                                            <i class="material-icons blue-text text-darken-2">chevron_right</i>
                                        </a>
                                    </div>
                                </div>
                                <div class="row" style="margin: 0.5rem 0 0;">
                                    <div class="col s12">
                                        <div class="week-progress">
                                            <div class="progress" style="height: 10px; border-radius: 999px; background: rgba(255,255,255,0.35);">
                                                <div id="week-progress-bar" class="determinate" style="width: <?php echo $weekProgress; ?>%; background: linear-gradient(90deg, #42a5f5, #1e88e5);"></div>
                                            </div>
                                            <div class="grey-text text-lighten-4" id="week-progress-label" style="margin-top: .25rem; font-size: .95rem;">
                                                <?php echo $weekSet; ?>/5 gesetzt • HO: <?php echo $weekCounts['homeoffice']; ?> • Büro: <?php echo $weekCounts['office']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">
                    <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                    
                    <!-- Schnellaktionen & Zusammenfassung -->
                    <div class="row" style="margin-bottom: 1.5rem;">
                        <div class="col s12">
                            <div class="card-panel glass-panel sticky-actions" style="display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                                <span class="grey-text text-darken-1" style="margin-right: .5rem; display: inline-flex; align-items: center; gap: .4rem;">
                                    <i class="material-icons">bolt</i> Woche komplett setzen
                                </span>
                                <div class="actions" style="display:flex; gap:.5rem; flex-wrap: wrap; align-items:center;">
                                    <button type="button" class="btn blue waves-effect waves-light" data-week-action="homeoffice">
                                        <i class="material-icons left">home</i> Homeoffice
                                    </button>
                                    <button type="button" class="btn orange waves-effect waves-light" data-week-action="office">
                                        <i class="material-icons left">business</i> Büro
                                    </button>
                                    <button type="button" class="btn grey waves-effect waves-light" data-week-action="none">
                                        <i class="material-icons left">block</i> Keine
                                    </button>
                                    <button type="button" class="btn purple waves-effect waves-light" data-week-action="vacation">
                                        <i class="material-icons left">beach_access</i> Urlaub
                                    </button>
                                    <button type="button" class="btn red waves-effect waves-light" data-week-action="sick">
                                        <i class="material-icons left">healing</i> Krank
                                    </button>
                                    <button type="button" class="btn teal waves-effect waves-light" data-week-action="training">
                                        <i class="material-icons left">school</i> Schulung
                                    </button>
                                </div>

                                <div class="summary" style="display:flex; gap:.5rem; align-items:center; flex-wrap: wrap;">
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny blue-text">home</i> <span id="week-count-homeoffice"><?php echo $weekCounts['homeoffice']; ?></span></div>
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny orange-text">business</i> <span id="week-count-office"><?php echo $weekCounts['office']; ?></span></div>
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny purple-text">beach_access</i> <span id="week-count-vacation"><?php echo $weekCounts['vacation']; ?></span></div>
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny red-text">healing</i> <span id="week-count-sick"><?php echo $weekCounts['sick']; ?></span></div>
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny teal-text">school</i> <span id="week-count-training"><?php echo $weekCounts['training']; ?></span></div>
                                    <div class="chip white grey-text text-darken-2"><i class="material-icons tiny grey-text">remove_circle_outline</i> <span id="week-count-none"><?php echo $weekCounts['none']; ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12">
                            <ul class="collapsible popout">
                                <?php
                                foreach ($dates as $i => $dbDate) {
                                    $displayDate = date('d.m.Y', strtotime($dbDate));
                                    $dayName = date('l', strtotime($dbDate));
                                    $germanDays = [
                                        'Monday' => 'Montag',
                                        'Tuesday' => 'Dienstag',
                                        'Wednesday' => 'Mittwoch',
                                        'Thursday' => 'Donnerstag',
                                        'Friday' => 'Freitag'
                                    ];
                                    
                                    $existingLocation = isset($existingBookings[$dbDate]) ? $existingBookings[$dbDate] : '';
                                    $isToday = date('Y-m-d') === $dbDate;
                                    switch ($existingLocation) {
                                        case 'homeoffice':
                                            $icon = 'home';
                                            $statusColor = 'blue-text';
                                            $locationText = 'Homeoffice';
                                            $statusClass = 'status-homeoffice';
                                            break;
                                        case 'office':
                                            $icon = 'business';
                                            $statusColor = 'orange-text';
                                            $locationText = 'Büro';
                                            $statusClass = 'status-office';
                                            break;
                                        case 'vacation':
                                            $icon = 'beach_access';
                                            $statusColor = 'purple-text';
                                            $locationText = 'Urlaub';
                                            $statusClass = 'status-vacation';
                                            break;
                                        case 'sick':
                                            $icon = 'healing';
                                            $statusColor = 'red-text';
                                            $locationText = 'Krank';
                                            $statusClass = 'status-sick';
                                            break;
                                        case 'training':
                                            $icon = 'school';
                                            $statusColor = 'teal-text';
                                            $locationText = 'Schulung';
                                            $statusClass = 'status-training';
                                            break;
                                        default:
                                            $icon = 'radio_button_unchecked';
                                            $statusColor = 'grey-text';
                                            $locationText = 'Keine Angabe';
                                            $statusClass = 'status-none';
                                    }
                                    ?>
                                    <li class="day-item <?php echo $isToday ? 'active' : ''; ?> <?php echo $statusClass; ?>">
                                        <div class="collapsible-header <?php echo $isToday ? 'blue lighten-5' : ''; ?> day-header" style="display: flex; justify-content: space-between; align-items: center;">
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <i class="material-icons <?php echo $statusColor; ?>"><?php echo $icon; ?></i>
                                                <div>
                                                    <span class="<?php echo $isToday ? 'blue-text text-darken-2' : ''; ?>">
                                                        <?php echo $germanDays[$dayName]; ?>
                                                    </span>
                                                    <span class="grey-text">
                                                        <?php echo $displayDate; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <span class="<?php echo $statusColor; ?> hide-on-small-only">
                                                    <?php echo $locationText; ?>
                                                </span>
                                                <?php if ($isToday) { ?>
                                                    <span class="new badge blue" data-badge-caption="Heute"></span>
                                                <?php } ?>
                                                <i class="material-icons grey-text">expand_more</i>
                                            </div>
                                        </div>
                                        <div class="collapsible-body day-body">
                                            <div class="row" style="margin-bottom: 0;">
                                                <div class="col s12">
                                                    <div class="segmented center-align">
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="office" <?php echo $existingLocation === 'office' ? 'checked' : ''; ?>>
                                                            <span class="pill orange-text text-darken-1">
                                                                <i class="material-icons">business</i>
                                                                Büro
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="homeoffice" <?php echo $existingLocation === 'homeoffice' ? 'checked' : ''; ?>>
                                                            <span class="pill blue-text text-darken-1">
                                                                <i class="material-icons">home</i>
                                                                Homeoffice
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="vacation" <?php echo $existingLocation === 'vacation' ? 'checked' : ''; ?>>
                                                            <span class="pill purple-text text-darken-1">
                                                                <i class="material-icons">beach_access</i>
                                                                Urlaub
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="sick" <?php echo $existingLocation === 'sick' ? 'checked' : ''; ?>>
                                                            <span class="pill red-text text-darken-1">
                                                                <i class="material-icons">healing</i>
                                                                Krank
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="training" <?php echo $existingLocation === 'training' ? 'checked' : ''; ?>>
                                                            <span class="pill teal-text text-darken-1">
                                                                <i class="material-icons">school</i>
                                                                Schulung
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="" <?php echo $existingLocation === '' ? 'checked' : ''; ?>>
                                                            <span class="pill grey-text text-darken-1">
                                                                <i class="material-icons">block</i>
                                                                Keine Angabe
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12 center-align" style="margin-top: 2rem;">
                            <button class="btn-large waves-effect waves-light blue save-button" type="submit">
                                <i class="material-icons left">save</i>
                                Speichern
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Floating Save Button -->
<div class="fixed-action-btn">
  <a class="btn-floating btn-large blue" id="fab-save" title="Speichern">
    <i class="large material-icons">save</i>
  </a>
  <ul>
    <li><a class="btn-floating grey tooltipped" data-tooltip="Woche leeren" data-week-action="none"><i class="material-icons">block</i></a></li>
    <li><a class="btn-floating orange tooltipped" data-tooltip="Woche Büro" data-week-action="office"><i class="material-icons">business</i></a></li>
    <li><a class="btn-floating blue tooltipped" data-tooltip="Woche Homeoffice" data-week-action="homeoffice"><i class="material-icons">home</i></a></li>
  </ul>
</div>

<?php include 'templates/footer.php'; ?>
