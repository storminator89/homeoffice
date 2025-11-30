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
                        $messages['success'][] = "Buchung für " . $date . " gespeichert.";
                    } elseif (!empty($currentLocation)) {
                        $messages['success'][] = "Buchung für " . $date . " entfernt.";
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

<div class="max-w-3xl mx-auto">
    <?php if (!empty($messages['success'])): ?>
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300">
            <ul class="list-disc list-inside">
                <?php foreach ($messages['success'] as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Week Navigator -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 transition-colors duration-200">
        <div class="flex items-center justify-between">
            <a href="?week=<?php echo $prevWeek->format('W'); ?>&year=<?php echo $prevWeek->format('Y'); ?>" 
               class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                <i class="material-icons">chevron_left</i>
            </a>
            
            <div class="text-center">
                <div class="text-lg font-bold text-gray-900 dark:text-white">KW <?php echo $selectedWeek; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center gap-1">
                    <i class="material-icons text-sm">date_range</i>
                    <?php echo $weekStartDisplay; ?> – <?php echo $weekEndDisplay; ?>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <?php if ($selectedWeek != date('W') || $selectedYear != date('Y')) { ?>
                    <a href="?week=<?php echo date('W'); ?>&year=<?php echo date('Y'); ?>" class="hidden sm:inline-flex items-center px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                        Aktuelle Woche
                    </a>
                <?php } ?>
                <a href="?week=<?php echo $nextWeek->format('W'); ?>&year=<?php echo $nextWeek->format('Y'); ?>" 
                   class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                    <i class="material-icons">chevron_right</i>
                </a>
            </div>
        </div>
    </div>

    <form method="post" id="bookingForm">
        <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">
        <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
        
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 overflow-x-auto transition-colors duration-200">
            <div class="flex items-center gap-4 min-w-max">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <i class="material-icons text-sm">bolt</i>
                    Woche setzen:
                </span>
                <button type="button" onclick="setWeek('homeoffice')" class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors flex items-center gap-1">
                    <i class="material-icons text-sm">home</i> Homeoffice
                </button>
                <button type="button" onclick="setWeek('office')" class="px-3 py-1.5 rounded-lg bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-sm font-medium hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors flex items-center gap-1">
                    <i class="material-icons text-sm">business</i> Büro
                </button>
                <button type="button" onclick="setWeek('')" class="px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors flex items-center gap-1">
                    <i class="material-icons text-sm">block</i> Leeren
                </button>
            </div>
        </div>
        
        <div class="space-y-4">
            <?php
            foreach ($dates as $i => $dbDate) {
                $displayDate = date('d.m.Y', strtotime($dbDate));
                $dayName = date('l', strtotime($dbDate));
                $germanDays = [
                    'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch',
                    'Thursday' => 'Donnerstag', 'Friday' => 'Freitag'
                ];
                
                $existingLocation = isset($existingBookings[$dbDate]) ? $existingBookings[$dbDate] : '';
                $isToday = date('Y-m-d') === $dbDate;
                
                $statusColors = [
                    'homeoffice' => 'border-l-4 border-indigo-500 bg-indigo-50/30 dark:bg-indigo-900/20',
                    'office' => 'border-l-4 border-orange-500 bg-orange-50/30 dark:bg-orange-900/20',
                    'vacation' => 'border-l-4 border-purple-500 bg-purple-50/30 dark:bg-purple-900/20',
                    'sick' => 'border-l-4 border-red-500 bg-red-50/30 dark:bg-red-900/20',
                    'training' => 'border-l-4 border-teal-500 bg-teal-50/30 dark:bg-teal-900/20',
                    '' => 'border-l-4 border-gray-200 dark:border-gray-700'
                ];
                $currentClass = $statusColors[$existingLocation] ?? $statusColors[''];
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200 <?php echo $currentClass; ?>">
                    <details class="group" <?php echo $isToday ? 'open' : ''; ?>>
                        <summary class="flex items-center justify-between p-4 cursor-pointer list-none">
                            <div class="flex items-center gap-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 dark:text-white <?php echo $isToday ? 'text-indigo-600 dark:text-indigo-400' : ''; ?>">
                                        <?php echo $germanDays[$dayName]; ?>
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $displayDate; ?></span>
                                </div>
                                <?php if ($isToday): ?>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">HEUTE</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300 hidden sm:block location-label">
                                    <?php 
                                    $labels = [
                                        'homeoffice' => 'Homeoffice', 'office' => 'Büro', 
                                        'vacation' => 'Urlaub', 'sick' => 'Krank', 
                                        'training' => 'Schulung', '' => 'Keine Angabe'
                                    ];
                                    echo $labels[$existingLocation] ?? 'Keine Angabe';
                                    ?>
                                </span>
                                <i class="material-icons text-gray-400 dark:text-gray-500 transition-transform group-open:rotate-180">expand_more</i>
                            </div>
                        </summary>
                        
                        <div class="p-4 pt-0 border-t border-gray-50 dark:border-gray-700">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 mt-4">
                                <?php
                                $options = [
                                    ['value' => 'office', 'icon' => 'business', 'label' => 'Büro', 'color' => 'orange'],
                                    ['value' => 'homeoffice', 'icon' => 'home', 'label' => 'Home', 'color' => 'indigo'],
                                    ['value' => 'vacation', 'icon' => 'beach_access', 'label' => 'Urlaub', 'color' => 'purple'],
                                    ['value' => 'sick', 'icon' => 'healing', 'label' => 'Krank', 'color' => 'red'],
                                    ['value' => 'training', 'icon' => 'school', 'label' => 'Schulung', 'color' => 'teal'],
                                    ['value' => '', 'icon' => 'block', 'label' => 'Leer', 'color' => 'gray']
                                ];
                                
                                foreach ($options as $opt) {
                                    $checked = $existingLocation === $opt['value'] ? 'checked' : '';
                                    $colorClass = $opt['color'];
                                    ?>
                                    <label class="cursor-pointer relative">
                                        <input type="radio" name="location[<?php echo $displayDate; ?>]" value="<?php echo $opt['value']; ?>" class="peer sr-only location-radio" <?php echo $checked; ?>>
                                        <div class="flex flex-col items-center justify-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 peer-checked:border-<?php echo $colorClass; ?>-500 peer-checked:bg-<?php echo $colorClass; ?>-50 dark:peer-checked:bg-<?php echo $colorClass; ?>-900/30 peer-checked:text-<?php echo $colorClass; ?>-700 dark:peer-checked:text-<?php echo $colorClass; ?>-300 transition-all text-gray-600 dark:text-gray-300">
                                            <i class="material-icons mb-1"><?php echo $opt['icon']; ?></i>
                                            <span class="text-xs font-medium"><?php echo $opt['label']; ?></span>
                                        </div>
                                    </label>
                                <?php } ?>
                            </div>
                        </div>
                    </details>
                </div>
            <?php } ?>
        </div>
        
        <div class="sticky bottom-4 mt-6 flex justify-center z-40">
            <button class="shadow-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-full flex items-center gap-2 transition-transform hover:scale-105 active:scale-95" type="submit">
                <i class="material-icons">save</i>
                <span>Speichern</span>
            </button>
        </div>
    </form>
</div>

<script>
function setWeek(type) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${type}"]`);
    radios.forEach(radio => {
        radio.checked = true;
        // Trigger change event visually if needed, though CSS handles peer-checked
    });
}
</script>

<?php include 'templates/footer.php'; ?>
