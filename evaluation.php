<?php
require_once 'database.php';
$db = new Database();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n') / 3);

$quotaStatus = $db->getQuarterQuotaStatus($year, $quarter);

// Vacation Stats
$totalVacation = (int)$db->getSetting('vacation_days');
$takenVacation = $db->getDb()->querySingle("SELECT COUNT(*) FROM bookings WHERE location = 'vacation' AND strftime('%Y', date) = '$year'");
$remainingVacation = $totalVacation - $takenVacation;

// Sick Days Stats
$sickDays = $db->getDb()->querySingle("SELECT COUNT(*) FROM bookings WHERE location = 'sick' AND strftime('%Y', date) = '$year'");

include 'templates/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header & Quota Status -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                <i class="material-icons text-2xl">analytics</i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Auswertung</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Detaillierte Statistiken und Berichte</p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Vacation Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
            <div class="mb-2 p-3 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-500 dark:text-purple-400">
                <i class="material-icons text-3xl">beach_access</i>
            </div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $remainingVacation; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">Resturlaub</div>
            
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $totalVacation > 0 ? ($takenVacation / $totalVacation * 100) : 0; ?>%"></div>
            </div>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                <?php echo $takenVacation; ?> von <?php echo $totalVacation; ?> genommen
            </span>
        </div>

        <!-- Sick Days -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
            <div class="mb-2 p-3 rounded-full bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400">
                <i class="material-icons text-3xl">healing</i>
            </div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $sickDays; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">Krankheitstage</div>
            
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 h-2.5 rounded-full" style="width: <?php echo min(100, $sickDays * 10); ?>%"></div>
            </div>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                in <?php echo $year; ?>
            </span>
        </div>

        <!-- Actual Quota -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
            <div class="mb-2 p-3 rounded-full <?php echo $quotaStatus['status'] === 'ok' ? 'bg-green-100 dark:bg-green-900/30 text-green-500 dark:text-green-400' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-500 dark:text-orange-400'; ?>">
                <i class="material-icons text-3xl"><?php echo $quotaStatus['status'] === 'ok' ? 'check_circle' : 'warning'; ?></i>
            </div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $quotaStatus['actual']; ?>%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">Aktuelle Quote</div>
            
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo min(100, $quotaStatus['actual']); ?>%"></div>
            </div>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $quotaStatus['status'] === 'ok' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'; ?>">
                <?php 
                if ($quotaStatus['status'] === 'ok') {
                    echo $quotaStatus['difference'] == 0 ? "Ziel erreicht" : "Noch " . (-$quotaStatus['difference']) . "% möglich";
                } else {
                    echo "+" . $quotaStatus['difference'] . "% über Ziel";
                }
                ?>
            </span>
        </div>

        <!-- Target Quota -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
            <div class="mb-2 p-3 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-500 dark:text-indigo-400">
                <i class="material-icons text-3xl">flag</i>
            </div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-1"><?php echo $quotaStatus['target']; ?>%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">Ziel-Quote</div>
            
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-400 to-indigo-500 h-2.5 rounded-full" style="width: <?php echo $quotaStatus['target']; ?>%"></div>
            </div>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300">
                Vorgabe
            </span>
        </div>

        <!-- Info -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 flex flex-col justify-center text-white col-span-1 md:col-span-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="p-3 rounded-xl bg-white/20 backdrop-blur">
                    <i class="material-icons text-3xl">lightbulb</i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium">Tipp zur Quote</h3>
                    <p class="mt-1 text-sm text-white/80">
                        Die Quote berechnet sich aus dem Verhältnis von Homeoffice-Tagen zu Gesamtarbeitstagen (Homeoffice + Büro) im Quartal.
                        <?php if ($quotaStatus['target'] <= 50): ?>
                            Dein Ziel von <?php echo $quotaStatus['target']; ?>% liegt im empfohlenen Bereich für eine gute Work-Life-Balance.
                        <?php else: ?>
                            Bei einer Quote über 50% solltest du regelmäßige Büropräsenz für Teamarbeit einplanen.
                        <?php endif; ?>
                    </p>
                </div>
                <a href="settings.php" class="shrink-0 px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors text-sm font-medium flex items-center gap-2">
                    <i class="material-icons text-lg">tune</i>
                    Ziel anpassen
                </a>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 transition-colors duration-200">
        <form method="get" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jahr</label>
                <select name="year" class="block w-32 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
                        $selected = $y == $year ? 'selected' : '';
                        echo "<option value=\"$y\" $selected>$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quartal</label>
                <select name="quarter" class="block w-32 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <?php
                    for ($q = 1; $q <= 4; $q++) {
                        $selected = $q == $quarter ? 'selected' : '';
                        echo "<option value=\"$q\" $selected>Q$q</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[38px] transition-colors">
                Anzeigen
            </button>
        </form>
    </div>

    <?php
    $result = $db->getQuarterBookings($year, $quarter);
    $stats = ['homeoffice' => 0, 'office' => 0, 'vacation' => 0, 'sick' => 0, 'training' => 0];
    $days = [];
    $monthlyStats = array_fill(1, 12, ['homeoffice' => 0, 'office' => 0, 'vacation' => 0, 'sick' => 0, 'training' => 0]);
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!isset($stats[$row['location']])) { $stats[$row['location']] = 0; }
        $stats[$row['location']]++;
        $days[$row['date']] = $row['location'];
        $month = (int)date('n', strtotime($row['date']));
        if (!isset($monthlyStats[$month][$row['location']])) { $monthlyStats[$month][$row['location']] = 0; }
        $monthlyStats[$month][$row['location']]++;
    }
    
    $totalAll = $stats['homeoffice'] + $stats['office'] + $stats['vacation'] + $stats['sick'] + $stats['training'];
    $workTotal = $stats['homeoffice'] + $stats['office'];
    
    if ($totalAll > 0) {
        $homeofficePercent = $workTotal > 0 ? round(($stats['homeoffice'] / $workTotal) * 100) : 0;
        $officePercent = $workTotal > 0 ? round(($stats['office'] / $workTotal) * 100) : 0;
    ?>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors duration-200">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Monatliche Entwicklung</h3>
            <div class="h-80">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors duration-200">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Verteilung</h3>
            <div class="h-64 flex items-center justify-center">
                <canvas id="distributionChart"></canvas>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?php echo $stats['homeoffice']; ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Homeoffice</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-orange-500 dark:text-orange-400"><?php echo $stats['office']; ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Büro</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export & Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Details</h3>
            <div class="flex gap-2">
                <a href="export_pdf.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none transition-colors">
                    <i class="material-icons text-sm mr-2">picture_as_pdf</i> PDF
                </a>
                <a href="export_csv.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none transition-colors">
                    <i class="material-icons text-sm mr-2">file_download</i> CSV
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Datum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Wochentag</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Art</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    foreach ($days as $date => $location) {
                        $displayDate = date('d.m.Y', strtotime($date));
                        $dayName = date('l', strtotime($date));
                        $germanDays = ['Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch', 'Thursday' => 'Donnerstag', 'Friday' => 'Freitag'];
                        
                        $badgeClass = 'bg-gray-100 text-gray-800';
                        $label = 'Unbekannt';
                        
                        switch ($location) {
                            case 'homeoffice': $badgeClass = 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300'; $label = 'Homeoffice'; break;
                            case 'office': $badgeClass = 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'; $label = 'Büro'; break;
                            case 'vacation': $badgeClass = 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300'; $label = 'Urlaub'; break;
                            case 'sick': $badgeClass = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'; $label = 'Krank'; break;
                            case 'training': $badgeClass = 'bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300'; $label = 'Schulung'; break;
                        }
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"><?php echo $displayDate; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo $germanDays[$dayName] ?? $dayName; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badgeClass; ?>">
                                <?php echo $label; ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthlyData = <?php
            $labels = [];
            $homeofficeData = [];
            $officeData = [];
            
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $germanMonths = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
            
            for ($month = $startMonth; $month <= $endMonth; $month++) {
                $labels[] = $germanMonths[$month];
                $homeofficeData[] = $monthlyStats[$month]['homeoffice'];
                $officeData[] = $monthlyStats[$month]['office'];
            }
            
            echo json_encode(['labels' => $labels, 'homeoffice' => $homeofficeData, 'office' => $officeData]);
        ?>;

        // Chart.js defaults for dark mode
        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563';
        Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';

        new Chart(document.getElementById('monthlyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Homeoffice',
                    data: monthlyData.homeoffice,
                    backgroundColor: '#4f46e5',
                    borderRadius: 4
                }, {
                    label: 'Büro',
                    data: monthlyData.office,
                    backgroundColor: '#f97316',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });

        new Chart(document.getElementById('distributionChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Homeoffice', 'Büro', 'Urlaub', 'Krank', 'Schulung'],
                datasets: [{
                    data: [
                        <?php echo $stats['homeoffice']; ?>,
                        <?php echo $stats['office']; ?>,
                        <?php echo $stats['vacation']; ?>,
                        <?php echo $stats['sick']; ?>,
                        <?php echo $stats['training']; ?>
                    ],
                    backgroundColor: ['#4f46e5', '#f97316', '#a855f7', '#ef4444', '#14b8a6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    });
    </script>

    <?php } else { ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 mt-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="material-icons text-yellow-400 dark:text-yellow-500">info</i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        Keine Buchungen im ausgewählten Zeitraum gefunden.
                    </p>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include 'templates/footer.php'; ?>
