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
                        <div class="card z-depth-0 blue lighten-5 week-navigator">
                            <div class="card-content">
                                <div class="row valign-wrapper" style="margin-bottom: 0;">
                                    <div class="col s2 center-align">
                                        <a href="?week=<?php echo $prevWeek->format('W'); ?>&year=<?php echo $prevWeek->format('Y'); ?>" 
                                           class="btn-floating waves-effect waves-light blue darken-1 hoverable">
                                            <i class="material-icons">chevron_left</i>
                                        </a>
                                    </div>
                                    <div class="col s8 center-align">
                                        <div class="week-chips" style="margin-bottom: 8px;">
                                            <span class="chip blue darken-1 white-text hoverable" style="font-size: 1rem; height: auto; line-height: 2;">
                                                <i class="material-icons" style="float: none; font-size: inherit; line-height: inherit; margin-right: 4px;">calendar_today</i>
                                                KW <?php echo $selectedWeek; ?>
                                            </span>
                                            <span class="chip blue darken-2 white-text hoverable" style="font-size: 1rem; height: auto; line-height: 2;">
                                                <i class="material-icons" style="float: none; font-size: inherit; line-height: inherit; margin-right: 4px;">event</i>
                                                <?php echo $selectedYear; ?>
                                            </span>
                                        </div>
                                        <?php if ($selectedWeek != date('W') || $selectedYear != date('Y')) { ?>
                                            <div>
                                                <a href="?week=<?php echo date('W'); ?>&year=<?php echo date('Y'); ?>" 
                                                   class="btn waves-effect waves-light blue darken-1 hoverable">
                                                    <i class="material-icons" style="vertical-align: middle; line-height: inherit; margin: -2px 4px 0 -4px;">today</i>
                                                    <span style="vertical-align: middle;">Aktuelle Woche</span>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="col s2 center-align">
                                        <a href="?week=<?php echo $nextWeek->format('W'); ?>&year=<?php echo $nextWeek->format('Y'); ?>" 
                                           class="btn-floating waves-effect waves-light blue darken-1 hoverable">
                                            <i class="material-icons">chevron_right</i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">
                    <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                    
                    <!-- Schnellaktionen: ganze Woche setzen -->
                    <div class="row" style="margin-bottom: 1.5rem;">
                        <div class="col s12">
                            <div class="card-panel grey lighten-4" style="display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;">
                                <span class="grey-text text-darken-1" style="margin-right: .5rem; display: inline-flex; align-items: center; gap: .4rem;">
                                    <i class="material-icons">flash_on</i> Woche komplett:
                                </span>
                                <button type="button" class="btn blue waves-effect waves-light" data-week-action="homeoffice">
                                    <i class="material-icons left">home</i> Homeoffice
                                </button>
                                <button type="button" class="btn orange waves-effect waves-light" data-week-action="office">
                                    <i class="material-icons left">business</i> Büro
                                </button>
                                <button type="button" class="btn grey waves-effect waves-light" data-week-action="none">
                                    <i class="material-icons left">block</i> Keine Angabe
                                </button>
                                <div class="hide-on-small-only" style="width: 1px; height: 24px; background: #ddd; margin: 0 .5rem;"></div>
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
                                            break;
                                        case 'office':
                                            $icon = 'business';
                                            $statusColor = 'orange-text';
                                            $locationText = 'Büro';
                                            break;
                                        case 'vacation':
                                            $icon = 'beach_access';
                                            $statusColor = 'purple-text';
                                            $locationText = 'Urlaub';
                                            break;
                                        case 'sick':
                                            $icon = 'healing';
                                            $statusColor = 'red-text';
                                            $locationText = 'Krank';
                                            break;
                                        case 'training':
                                            $icon = 'school';
                                            $statusColor = 'teal-text';
                                            $locationText = 'Schulung';
                                            break;
                                        default:
                                            $icon = 'radio_button_unchecked';
                                            $statusColor = 'grey-text';
                                            $locationText = 'Keine Angabe';
                                    }
                                    ?>
                                    <li class="<?php echo $isToday ? 'active' : ''; ?>">
                                        <div class="collapsible-header <?php echo $isToday ? 'blue lighten-5' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
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
                                        <div class="collapsible-body">
                                            <div class="row" style="margin-bottom: 0;">
                                                <div class="col s12">
                                                    <div class="switch-field center-align">
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="office" <?php echo $existingLocation === 'office' ? 'checked' : ''; ?>>
                                                            <span>
                                                                <i class="material-icons">business</i>
                                                                Büro
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="homeoffice" <?php echo $existingLocation === 'homeoffice' ? 'checked' : ''; ?>>
                                                            <span>
                                                                <i class="material-icons">home</i>
                                                                Homeoffice
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="vacation" <?php echo $existingLocation === 'vacation' ? 'checked' : ''; ?>>
                                                            <span>
                                                                <i class="material-icons">beach_access</i>
                                                                Urlaub
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="sick" <?php echo $existingLocation === 'sick' ? 'checked' : ''; ?>>
                                                            <span>
                                                                <i class="material-icons">healing</i>
                                                                Krank
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="training" <?php echo $existingLocation === 'training' ? 'checked' : ''; ?>>
                                                            <span>
                                                                <i class="material-icons">school</i>
                                                                Schulung
                                                            </span>
                                                        </label>
                                                        <label>
                                                            <input name="location[<?php echo $displayDate; ?>]" type="radio" value="" <?php echo $existingLocation === '' ? 'checked' : ''; ?>>
                                                            <span>
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

<?php include 'templates/footer.php'; ?>
