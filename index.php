<?php 
include 'templates/header.php';
include 'database.php';

$db = new Database();
$dbConnection = $db->getDb();

// Handle Today's Action
$today = date('Y-m-d');
$todayDisplay = date('d.m.Y');
$todayDayName = date('l');
$germanDays = [
    'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch',
    'Thursday' => 'Donnerstag', 'Friday' => 'Freitag', 'Saturday' => 'Samstag', 'Sunday' => 'Sonntag'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['date'])) {
    $actionDate = $_POST['date'];
    $actionType = $_POST['action'];
    
    try {
        $db->addBooking($actionDate, $actionType);
    } catch (Exception $e) {
        // Silent fail
    }
}

// Get Today's Status
$todayBooking = $dbConnection->query("SELECT location, note FROM bookings WHERE date = '$today'")->fetchArray(SQLITE3_ASSOC);
$todayStatus = $todayBooking ? $todayBooking['location'] : null;
$todayNote = $todayBooking ? $todayBooking['note'] : null;

// Calculate Streak (consecutive work days with bookings)
$streakCount = 0;
$streakDate = new DateTime($today);
while (true) {
    $dayOfWeek = (int)$streakDate->format('N');
    if ($dayOfWeek > 5) {
        $streakDate->modify('-1 day');
        continue;
    }
    $checkDate = $streakDate->format('Y-m-d');
    $hasBooking = $dbConnection->querySingle("SELECT COUNT(*) FROM bookings WHERE date = '$checkDate' AND location IN ('homeoffice', 'office')");
    if ($hasBooking) {
        $streakCount++;
        $streakDate->modify('-1 day');
    } else {
        break;
    }
}

// Get Week Status
$monday = date('Y-m-d', strtotime('monday this week'));
$weekDates = [];
for ($i = 0; $i < 5; $i++) {
    $weekDates[] = date('Y-m-d', strtotime("$monday +$i days"));
}
$weekBookings = $db->getBookingsForDates($weekDates);

// Stats
$totalBookings = $dbConnection->query("SELECT COUNT(*) as count FROM bookings")->fetchArray(SQLITE3_ASSOC)['count'];
$homeofficeCount = $dbConnection->query("SELECT COUNT(*) as count FROM bookings WHERE location = 'homeoffice'")->fetchArray(SQLITE3_ASSOC)['count'];
$officeCount = $dbConnection->query("SELECT COUNT(*) as count FROM bookings WHERE location = 'office'")->fetchArray(SQLITE3_ASSOC)['count'];
$workTotal = $homeofficeCount + $officeCount;
$homeofficePercentage = $workTotal > 0 ? round(($homeofficeCount / $workTotal) * 100) : 0;
$officePercentage = $workTotal > 0 ? round(($officeCount / $workTotal) * 100) : 0;

// Next Office/Homeoffice Day
$nextOffice = $dbConnection->query("SELECT date FROM bookings WHERE location = 'office' AND date > '$today' ORDER BY date ASC LIMIT 1")->fetchArray(SQLITE3_ASSOC);
$nextHome = $dbConnection->query("SELECT date FROM bookings WHERE location = 'homeoffice' AND date > '$today' ORDER BY date ASC LIMIT 1")->fetchArray(SQLITE3_ASSOC);

$nextEvent = null;
if ($nextOffice) {
    $nextEvent = ['type' => 'office', 'date' => $nextOffice['date'], 'label' => 'Nächster Bürotag'];
}
// If homeoffice is sooner, overwrite (or if no office day)
if ($nextHome && (!$nextEvent || $nextHome['date'] < $nextEvent['date'])) {
    $nextEvent = ['type' => 'homeoffice', 'date' => $nextHome['date'], 'label' => 'Nächstes Homeoffice'];
}

$nextEventText = "Keine Planung";
if ($nextEvent) {
    $ts = strtotime($nextEvent['date']);
    $nextEventText = $germanDays[date('l', $ts)] . ', ' . date('d.m.', $ts);
}

// Greeting based on time
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = 'Guten Morgen';
    $emoji = '☀️';
} elseif ($hour < 18) {
    $greeting = 'Guten Tag';
    $emoji = '👋';
} else {
    $greeting = 'Guten Abend';
    $emoji = '🌙';
}

// This month stats
$thisMonth = date('Y-m');
$monthHomeoffice = $dbConnection->querySingle("SELECT COUNT(*) FROM bookings WHERE location = 'homeoffice' AND date LIKE '$thisMonth%'");
$monthOffice = $dbConnection->querySingle("SELECT COUNT(*) FROM bookings WHERE location = 'office' AND date LIKE '$thisMonth%'");
$monthTotal = $monthHomeoffice + $monthOffice;
?>

<!-- Header Section -->
<div class="mb-8 animate-fade-in">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Nutzer'); ?> <?php echo $emoji; ?></h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Heute ist <?php echo $germanDays[$todayDayName] . ', ' . $todayDisplay; ?></p>
        </div>
        <?php if ($streakCount > 0): ?>
        <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full text-white shadow-lg shadow-orange-500/30">
            <i class="material-icons text-xl animate-pulse">local_fire_department</i>
            <span class="font-bold"><?php echo $streakCount; ?> Tage Streak</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.5s ease-out; }
.animate-fade-in-delay { animation: fade-in 0.5s ease-out 0.1s both; }
.animate-fade-in-delay-2 { animation: fade-in 0.5s ease-out 0.2s both; }
</style>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 animate-fade-in-delay">
    <!-- Today's Action Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-200 hover:shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Wo arbeitest du heute?</h2>
            <?php if ($todayStatus): ?>
            <form method="POST" class="inline">
                <input type="hidden" name="date" value="<?php echo $today; ?>">
                <input type="hidden" name="action" value="">
                <button type="submit" class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Eintrag löschen">
                    <i class="material-icons text-sm align-middle">close</i> Löschen
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <form method="POST" class="contents">
                <input type="hidden" name="date" value="<?php echo $today; ?>">
                <input type="hidden" name="action" value="homeoffice">
                <button type="submit" class="group relative flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all duration-200 <?php echo $todayStatus === 'homeoffice' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 scale-[1.02] shadow-lg shadow-indigo-500/20' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 hover:scale-[1.02]'; ?>">
                    <?php if ($todayStatus === 'homeoffice'): ?>
                        <div class="absolute top-3 right-3 text-indigo-600 dark:text-indigo-400">
                            <i class="material-icons text-xl">check_circle</i>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 rounded-full <?php echo $todayStatus === 'homeoffice' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 group-hover:text-indigo-600 dark:group-hover:text-indigo-300'; ?> mb-3 transition-all duration-200">
                        <i class="material-icons text-2xl">home</i>
                    </div>
                    <span class="font-semibold">Homeoffice</span>
                </button>
            </form>

            <form method="POST" class="contents">
                <input type="hidden" name="date" value="<?php echo $today; ?>">
                <input type="hidden" name="action" value="office">
                <button type="submit" class="group relative flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all duration-200 <?php echo $todayStatus === 'office' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 scale-[1.02] shadow-lg shadow-orange-500/20' : 'border-gray-100 dark:border-gray-700 hover:border-orange-200 dark:hover:border-orange-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 hover:scale-[1.02]'; ?>">
                    <?php if ($todayStatus === 'office'): ?>
                        <div class="absolute top-3 right-3 text-orange-500 dark:text-orange-400">
                            <i class="material-icons text-xl">check_circle</i>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 rounded-full <?php echo $todayStatus === 'office' ? 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-orange-100 dark:group-hover:bg-orange-900/50 group-hover:text-orange-600 dark:group-hover:text-orange-300'; ?> mb-3 transition-all duration-200">
                        <i class="material-icons text-2xl">business</i>
                    </div>
                    <span class="font-semibold">Büro</span>
                </button>
            </form>
        </div>
        
        <!-- Quick Status Options -->
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Oder:</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" class="contents">
                    <input type="hidden" name="date" value="<?php echo $today; ?>">
                    <input type="hidden" name="action" value="vacation">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all <?php echo $todayStatus === 'vacation' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 ring-2 ring-purple-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-purple-100 dark:hover:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300'; ?>">
                        <i class="material-icons text-sm align-middle mr-1">beach_access</i>Urlaub
                    </button>
                </form>
                <form method="POST" class="contents">
                    <input type="hidden" name="date" value="<?php echo $today; ?>">
                    <input type="hidden" name="action" value="sick">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all <?php echo $todayStatus === 'sick' ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 ring-2 ring-red-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300'; ?>">
                        <i class="material-icons text-sm align-middle mr-1">healing</i>Krank
                    </button>
                </form>
                <form method="POST" class="contents">
                    <input type="hidden" name="date" value="<?php echo $today; ?>">
                    <input type="hidden" name="action" value="training">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all <?php echo $todayStatus === 'training' ? 'bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 ring-2 ring-teal-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-teal-100 dark:hover:bg-teal-900/30 hover:text-teal-700 dark:hover:text-teal-300'; ?>">
                        <i class="material-icons text-sm align-middle mr-1">school</i>Schulung
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Week Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-200 hover:shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Diese Woche</h2>
            <a href="booking.php" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium flex items-center gap-1 transition-colors">
                <span>Bearbeiten</span>
                <i class="material-icons text-sm">arrow_forward</i>
            </a>
        </div>
        
        <div class="flex justify-between items-start">
            <?php foreach ($weekDates as $date): 
                $booking = $weekBookings[$date] ?? null;
                $dayStatus = $booking ? $booking['location'] : null;
                $dayNote = $booking ? $booking['note'] : null;
                $isToday = $date === $today;
                $isPast = $date < $today;
                $dayLabel = substr($germanDays[date('l', strtotime($date))], 0, 2);
                
                $bgColor = 'bg-gray-50 dark:bg-gray-700';
                $textColor = 'text-gray-400 dark:text-gray-500';
                $icon = '';
                
                if ($dayStatus === 'homeoffice') {
                    $bgColor = 'bg-indigo-100 dark:bg-indigo-900/50';
                    $textColor = 'text-indigo-600 dark:text-indigo-300';
                    $icon = 'home';
                } elseif ($dayStatus === 'office') {
                    $bgColor = 'bg-orange-100 dark:bg-orange-900/50';
                    $textColor = 'text-orange-600 dark:text-orange-300';
                    $icon = 'business';
                } elseif ($dayStatus === 'vacation') {
                    $bgColor = 'bg-purple-100 dark:bg-purple-900/50';
                    $textColor = 'text-purple-600 dark:text-purple-300';
                    $icon = 'beach_access';
                } elseif ($dayStatus === 'sick') {
                    $bgColor = 'bg-red-100 dark:bg-red-900/50';
                    $textColor = 'text-red-600 dark:text-red-300';
                    $icon = 'healing';
                } elseif ($dayStatus === 'training') {
                    $bgColor = 'bg-teal-100 dark:bg-teal-900/50';
                    $textColor = 'text-teal-600 dark:text-teal-300';
                    $icon = 'school';
                }
            ?>
            <div class="flex flex-col items-center gap-2 flex-1">
                <span class="text-xs font-medium uppercase <?php echo $isToday ? 'text-indigo-600 dark:text-indigo-400' : ($isPast ? 'text-gray-400 dark:text-gray-500' : 'text-gray-500 dark:text-gray-400'); ?>">
                    <?php echo $dayLabel; ?>
                </span>
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $bgColor; ?> <?php echo $textColor; ?> <?php echo $isToday ? 'ring-2 ring-indigo-600 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-800' : ''; ?> transition-all duration-200 relative group/note hover:scale-110 cursor-pointer">
                    <?php if ($icon): ?>
                        <i class="material-icons text-lg"><?php echo $icon; ?></i>
                    <?php elseif ($isPast && !$dayStatus): ?>
                        <span class="text-lg text-gray-300 dark:text-gray-600">–</span>
                    <?php else: ?>
                        <span class="text-lg">•</span>
                    <?php endif; ?>
                    
                    <?php if ($dayNote): ?>
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full border-2 border-white dark:border-gray-800"></div>
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-max max-w-[150px] p-2 bg-gray-900 text-white text-xs rounded shadow-lg opacity-0 group-hover/note:opacity-100 transition-opacity pointer-events-none z-10">
                            <?php echo htmlspecialchars($dayNote); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] text-gray-400 dark:text-gray-500"><?php echo date('d.', strtotime($date)); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Week Progress -->
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
            <?php 
            $weekSet = 0;
            foreach ($weekDates as $date) {
                if (isset($weekBookings[$date]) && !empty($weekBookings[$date]['location'])) {
                    $weekSet++;
                }
            }
            $weekProgress = round(($weekSet / 5) * 100);
            ?>
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                <span>Woche geplant</span>
                <span class="font-medium"><?php echo $weekSet; ?>/5 Tage</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2 rounded-full transition-all duration-500" style="width: <?php echo $weekProgress; ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Overview -->
<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Übersicht</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-fade-in-delay-2">
    <!-- Homeoffice Card -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:scale-[1.02] group">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                <i class="material-icons text-lg">home</i>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo $homeofficeCount; ?> Tage</span>
        </div>
        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-1"><?php echo $homeofficePercentage; ?>%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Homeoffice</div>
    </div>
    
    <!-- Office Card -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:scale-[1.02] group">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 rounded-lg bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform">
                <i class="material-icons text-lg">business</i>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo $officeCount; ?> Tage</span>
        </div>
        <div class="text-3xl font-bold text-orange-500 dark:text-orange-400 mb-1"><?php echo $officePercentage; ?>%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Büro</div>
    </div>
    
    <!-- Monthly Stats -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md group">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
                <i class="material-icons text-lg">date_range</i>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"><?php echo date('M'); ?></span>
        </div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $monthTotal; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Diesen Monat</div>
        <div class="mt-2 flex gap-2 text-xs">
            <span class="text-indigo-600 dark:text-indigo-400"><?php echo $monthHomeoffice; ?> HO</span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="text-orange-500 dark:text-orange-400"><?php echo $monthOffice; ?> Büro</span>
        </div>
    </div>
    
    <!-- Total Bookings -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition-all duration-200 hover:shadow-md group">
        <div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $totalBookings; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Gesamtbuchungen</div>
        </div>
        <a href="evaluation.php" class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all group-hover:scale-110">
            <i class="material-icons">arrow_forward</i>
        </a>
    </div>
    
    <!-- Next Event -->
    <div class="bg-gradient-to-br <?php echo $nextEvent && $nextEvent['type'] === 'office' ? 'from-orange-500 to-orange-600' : ($nextEvent ? 'from-indigo-500 to-indigo-600' : 'from-gray-400 to-gray-500'); ?> p-4 rounded-xl shadow-lg col-span-2 flex items-center justify-between text-white transition-all duration-200 hover:shadow-xl hover:scale-[1.01]">
        <div>
            <div class="text-xs font-medium opacity-80 mb-1">
                <?php echo $nextEvent ? $nextEvent['label'] : 'Nächster Termin'; ?>
            </div>
            <div class="text-xl font-bold"><?php echo $nextEventText; ?></div>
        </div>
        <div class="p-3 rounded-full bg-white/20 backdrop-blur">
            <i class="material-icons text-2xl"><?php echo $nextEvent && $nextEvent['type'] === 'office' ? 'business' : ($nextEvent ? 'home' : 'event_busy'); ?></i>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 col-span-2 transition-all duration-200 hover:shadow-md">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Schnellzugriff</h4>
        <div class="flex flex-wrap gap-2">
            <a href="calendar.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-sm">
                <i class="material-icons text-lg">calendar_month</i>
                <span>Kalender</span>
            </a>
            <a href="booking.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-sm">
                <i class="material-icons text-lg">edit_calendar</i>
                <span>Woche planen</span>
            </a>
            <a href="evaluation.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-sm">
                <i class="material-icons text-lg">analytics</i>
                <span>Auswertung</span>
            </a>
            <a href="export_pdf.php?year=<?php echo date('Y'); ?>&quarter=<?php echo ceil(date('n')/3); ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-sm">
                <i class="material-icons text-lg">picture_as_pdf</i>
                <span>Export</span>
            </a>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
