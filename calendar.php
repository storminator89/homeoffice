<?php
require_once 'database.php';
$db = new Database();

$month = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// First and last day of selected month
$firstOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$lastOfMonth = (clone $firstOfMonth)->modify('last day of this month');

// Determine calendar range: start Monday before/at first day, end Sunday after/at last day
$start = (clone $firstOfMonth)->modify('monday this week');
if ((int)$firstOfMonth->format('N') === 1) { $start = clone $firstOfMonth; }
$end = (clone $lastOfMonth)->modify('sunday this week');

$startDate = $start->format('Y-m-d');
$endDate = $end->format('Y-m-d');

// Fetch bookings for calendar range
$stmt = $db->getDb()->prepare('SELECT date, location FROM bookings WHERE date BETWEEN :start AND :end');
$stmt->bindValue(':start', $startDate, SQLITE3_TEXT);
$stmt->bindValue(':end', $endDate, SQLITE3_TEXT);
$res = $stmt->execute();
$map = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $map[$row['date']] = $row['location'];
}

include 'templates/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <!-- Calendar Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                    <i class="material-icons text-2xl">calendar_month</i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kalender</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Jahresübersicht deiner Buchungen</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-full px-2 py-1 shadow-sm">
                <?php
                $prev = (clone $firstOfMonth)->modify('-1 month');
                $next = (clone $firstOfMonth)->modify('+1 month');
                $months = [1=>'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
                ?>
                <a href="?month=<?php echo (int)$prev->format('n'); ?>&year=<?php echo (int)$prev->format('Y'); ?>" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-all hover:scale-110">
                    <i class="material-icons">chevron_left</i>
                </a>
                <span class="text-lg font-semibold text-gray-900 dark:text-white min-w-[160px] text-center px-4">
                    <?php echo $months[$month] . ' ' . $year; ?>
                </span>
                <a href="?month=<?php echo (int)$next->format('n'); ?>&year=<?php echo (int)$next->format('Y'); ?>" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-all hover:scale-110">
                    <i class="material-icons">chevron_right</i>
                </a>
            </div>
            
            <?php if ($month != date('n') || $year != date('Y')): ?>
            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:bg-indigo-200 dark:hover:bg-indigo-900/70 transition-colors">
                <i class="material-icons text-sm mr-1">today</i>
                Heute
            </a>
            <?php endif; ?>
        </div>

        <!-- Legend -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/30"></span> Homeoffice</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500 shadow-sm shadow-orange-500/30"></span> Büro</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500 shadow-sm shadow-purple-500/30"></span> Urlaub</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-500/30"></span> Krank</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-teal-500 shadow-sm shadow-teal-500/30"></span> Schulung</div>
        </div>

        <!-- Calendar Grid -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-fixed min-w-[800px]">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <?php foreach (['Mo','Di','Mi','Do','Fr','Sa','So'] as $w): ?>
                            <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700"><?php echo $w; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    <?php
                    $cursor = clone $start;
                    while ($cursor <= $end) {
                        echo '<tr>';
                        for ($d = 1; $d <= 7; $d++) {
                            $dateStr = $cursor->format('Y-m-d');
                            $isCurrentMonth = ((int)$cursor->format('n') === $month);
                            $isWeekend = ((int)$cursor->format('N') >= 6);
                            $isToday = ($dateStr === date('Y-m-d'));
                            
                            $bgClass = $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/50';
                            if ($isWeekend && $isCurrentMonth) $bgClass = 'bg-gray-50/50 dark:bg-gray-800/50';
                            if ($isToday) $bgClass = 'bg-indigo-50/30 dark:bg-indigo-900/20';
                            
                            $textClass = $isCurrentMonth ? 'text-gray-900 dark:text-gray-200' : 'text-gray-400 dark:text-gray-600';
                            if ($isToday) $textClass = 'text-indigo-600 dark:text-indigo-400 font-bold';

                            $loc = $map[$dateStr] ?? '';
                            $badge = '';
                            
                            switch ($loc) {
                                case 'homeoffice': $badge = '<div class="mt-1 px-2 py-1 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 truncate flex items-center gap-1"><i class="material-icons text-[10px]">home</i> Home</div>'; break;
                                case 'office': $badge = '<div class="mt-1 px-2 py-1 rounded text-xs font-medium bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 truncate flex items-center gap-1"><i class="material-icons text-[10px]">business</i> Büro</div>'; break;
                                case 'vacation': $badge = '<div class="mt-1 px-2 py-1 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 truncate flex items-center gap-1"><i class="material-icons text-[10px]">beach_access</i> Urlaub</div>'; break;
                                case 'sick': $badge = '<div class="mt-1 px-2 py-1 rounded text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 truncate flex items-center gap-1"><i class="material-icons text-[10px]">healing</i> Krank</div>'; break;
                                case 'training': $badge = '<div class="mt-1 px-2 py-1 rounded text-xs font-medium bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 truncate flex items-center gap-1"><i class="material-icons text-[10px]">school</i> Schulung</div>'; break;
                            }

                            // Link zur Buchungswoche - nutze ISO-Wochenjahr (format 'o')
                            $week = (int)$cursor->format('W');
                            $yForWeek = (int)$cursor->format('o'); // 'o' = ISO-Wochenjahr
                            $link = 'booking.php?week=' . $week . '&year=' . $yForWeek;

                            echo '<td class="h-32 border border-gray-100 dark:border-gray-700 p-2 align-top transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 ' . $bgClass . '">';
                            echo '<div class="flex justify-between items-start">';
                            echo '<span class="text-sm ' . $textClass . '">' . (int)$cursor->format('j') . '</span>';
                            echo '<a href="' . $link . '" class="text-gray-300 dark:text-gray-600 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="material-icons text-sm">edit</i></a>';
                            echo '</div>';
                            echo $badge;
                            echo '</td>';
                            
                            $cursor->modify('+1 day');
                        }
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
