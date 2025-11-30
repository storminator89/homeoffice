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
$todayBooking = $dbConnection->query("SELECT location FROM bookings WHERE date = '$today'")->fetchArray(SQLITE3_ASSOC);
$todayStatus = $todayBooking ? $todayBooking['location'] : null;

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
?>

<!-- Header Section -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Guten Morgen, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Nutzer'); ?> 👋</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-1">Heute ist <?php echo $germanDays[$todayDayName] . ', ' . $todayDisplay; ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Today's Action Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors duration-200">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Wo arbeitest du heute?</h2>
        
        <div class="grid grid-cols-2 gap-4">
            <form method="POST" class="contents">
                <input type="hidden" name="date" value="<?php echo $today; ?>">
                <input type="hidden" name="action" value="homeoffice">
                <button type="submit" class="relative flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all duration-200 <?php echo $todayStatus === 'homeoffice' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'; ?>">
                    <?php if ($todayStatus === 'homeoffice'): ?>
                        <div class="absolute top-3 right-3 text-indigo-600 dark:text-indigo-400">
                            <i class="material-icons text-xl">check_circle</i>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 rounded-full <?php echo $todayStatus === 'homeoffice' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'; ?> mb-3 transition-colors duration-200">
                        <i class="material-icons text-2xl">home</i>
                    </div>
                    <span class="font-semibold">Homeoffice</span>
                </button>
            </form>

            <form method="POST" class="contents">
                <input type="hidden" name="date" value="<?php echo $today; ?>">
                <input type="hidden" name="action" value="office">
                <button type="submit" class="relative flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all duration-200 <?php echo $todayStatus === 'office' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' : 'border-gray-100 dark:border-gray-700 hover:border-orange-200 dark:hover:border-orange-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'; ?>">
                    <?php if ($todayStatus === 'office'): ?>
                        <div class="absolute top-3 right-3 text-orange-500 dark:text-orange-400">
                            <i class="material-icons text-xl">check_circle</i>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 rounded-full <?php echo $todayStatus === 'office' ? 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'; ?> mb-3 transition-colors duration-200">
                        <i class="material-icons text-2xl">business</i>
                    </div>
                    <span class="font-semibold">Büro</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Week Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors duration-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Diese Woche</h2>
            <a href="booking.php" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">Bearbeiten</a>
        </div>
        
        <div class="flex justify-between items-start">
            <?php foreach ($weekDates as $date): 
                $dayStatus = $weekBookings[$date] ?? null;
                $isToday = $date === $today;
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
                }
            ?>
            <div class="flex flex-col items-center gap-2 flex-1">
                <span class="text-xs font-medium uppercase <?php echo $isToday ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'; ?>">
                    <?php echo $dayLabel; ?>
                </span>
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $bgColor; ?> <?php echo $textColor; ?> <?php echo $isToday ? 'ring-2 ring-indigo-600 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-800' : ''; ?> transition-all duration-200">
                    <?php if ($icon): ?>
                        <i class="material-icons text-lg"><?php echo $icon; ?></i>
                    <?php else: ?>
                        <span class="text-lg">•</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Stats Overview -->
<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Übersicht</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-colors duration-200">
        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-1"><?php echo $homeofficePercentage; ?>%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Homeoffice</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-colors duration-200">
        <div class="text-3xl font-bold text-orange-500 dark:text-orange-400 mb-1"><?php echo $officePercentage; ?>%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Büro</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 col-span-2 flex items-center justify-between transition-colors duration-200">
        <div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $totalBookings; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Gesamtbuchungen</div>
        </div>
        <a href="evaluation.php" class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
            <i class="material-icons">arrow_forward</i>
        </a>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
