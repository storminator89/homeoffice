<?php
require_once 'database.php';
$db = new Database();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n') / 3);

$quotaStatus = $db->getQuarterQuotaStatus($year, $quarter);

include 'templates/header.php';
?>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <div class="card-header blue darken-1 white-text">
                    <span class="card-title">
                        <i class="material-icons left">assessment</i>
                        Homeoffice Auswertung
                    </span>
                </div>

                <div class="section">
                    <div class="row">
                        <div class="col s12 m6 l4">
                            <div class="card z-depth-1"> <!-- Removed hoverable class -->
                                <div class="card-content center-align">
                                    <i class="material-icons medium <?php echo $quotaStatus['status'] === 'ok' ? 'green-text' : 'orange-text'; ?>">
                                        <?php echo $quotaStatus['status'] === 'ok' ? 'check_circle' : 'warning'; ?>
                                    </i>
                                    <h4 class="card-title"><?php echo $quotaStatus['actual']; ?>%</h4>
                                    <p>Aktuelle Homeoffice-Quote</p>
                                    <div class="progress">
                                        <div class="determinate <?php echo $quotaStatus['status'] === 'ok' ? 'blue' : 'orange'; ?>" 
                                             style="width: <?php echo min(100, $quotaStatus['actual']); ?>%">
                                        </div>
                                    </div>
                                    <div class="chip <?php echo $quotaStatus['status'] === 'ok' ? 'green' : 'orange'; ?> white-text">
                                        <i class="tiny material-icons">trending_<?php echo $quotaStatus['difference'] >= 0 ? 'up' : 'down'; ?></i>
                                        <?php 
                                        if ($quotaStatus['status'] === 'ok') {
                                            echo $quotaStatus['difference'] == 0 ? 
                                                "Zielquote exakt erreicht" : 
                                                "Noch " . (-$quotaStatus['difference']) . "% möglich";
                                        } else {
                                            echo "Quote um " . $quotaStatus['difference'] . "% überschritten";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col s12 m6 l4">
                            <div class="card z-depth-1"> <!-- Removed hoverable class -->
                                <div class="card-content center-align">
                                    <i class="material-icons medium blue-text">target</i>
                                    <h4 class="card-title"><?php echo $quotaStatus['target']; ?>%</h4>
                                    <p>Ziel Homeoffice-Quote</p>
                                    <div class="progress">
                                        <div class="determinate blue" style="width: <?php echo $quotaStatus['target']; ?>%"></div>
                                    </div>
                                    <div class="chip blue white-text">
                                        <i class="tiny material-icons">info</i>
                                        Eingestellte Zielquote
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col s12 m12 l4">
                            <div class="card z-depth-1"> <!-- Removed hoverable class -->
                                <div class="card-content">
                                    <div class="valign-wrapper" style="gap: 1rem;">
                                        <i class="material-icons medium blue-text">help</i>
                                        <div>
                                            <h5>Quotenberechnung</h5>
                                            <p class="grey-text">
                                                Die Quote wird aus dem Verhältnis von Homeoffice-Tagen zu Gesamtarbeitstagen berechnet.
                                                <?php if ($quotaStatus['target'] <= 50): ?>
                                                    Die eingestellte Quote von <?php echo $quotaStatus['target']; ?>% liegt im empfohlenen Bereich.
                                                <?php else: ?>
                                                    Die eingestellte Quote von <?php echo $quotaStatus['target']; ?>% liegt über der empfohlenen 50% Grenze.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <!-- Period Selection -->
                                <div class="row">
                                    <div class="col s12">
                                        <div class="card gradient-card">
                                            <div class="card-content white-text">
                                                <span class="card-title">
                                                    <i class="material-icons left">assessment</i>
                                                    Auswertung
                                                </span>
                                                <p>Hier sehen Sie die Statistiken Ihrer Homeoffice- und Bürozeiten.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <form class="col s12" method="get">
                                        <div class="period-selector">
                                            <div class="input-field inline">
                                                <select name="year" class="browser-default">
                                                    <?php
                                                    $currentYear = date('Y');
                                                    for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
                                                        $selected = $y == $year ? 'selected' : '';
                                                        echo "<option value=\"$y\" $selected>$y</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="input-field inline">
                                                <select name="quarter" class="browser-default">
                                                    <?php
                                                    for ($q = 1; $q <= 4; $q++) {
                                                        $selected = $q == $quarter ? 'selected' : '';
                                                        echo "<option value=\"$q\" $selected>Q$q</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn waves-effect waves-light blue">
                                                <i class="material-icons left">filter_list</i>
                                                Filtern
                                            </button>
                                        </div>
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
                                
                                $total = $stats['homeoffice'] + $stats['office'] + $stats['vacation'] + $stats['sick'] + $stats['training'];
                                if ($total > 0) {
                                    $homeofficePercent = round(($stats['homeoffice'] / $total) * 100);
                                    $officePercent = round(($stats['office'] / $total) * 100);
                                    ?>
                                    
                                    <div class="row">
                                        <div class="col s12">
                                            <ul class="tabs">
                                                <li class="tab col s6"><a href="#overview" class="active">Übersicht</a></li>
                                                <li class="tab col s6"><a href="#details">Details</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div id="overview">
                                        <!-- Statistics Cards -->
                                        <div class="row">
                                            <div class="col s12 m6 l3">
                                                <div class="card">
                                                    <div class="card-content center-align">
                                                        <i class="material-icons medium">event_note</i>
                                                        <span class="card-title"><?php echo $total; ?></span>
                                                        <p>Gesamtbuchungen</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col s12 m6 l3">
                                                <div class="card">
                                                    <div class="card-content center-align">
                                                        <i class="material-icons medium blue-text">home</i>
                                                        <span class="card-title"><?php echo $stats['homeoffice']; ?></span>
                                                        <p>Homeoffice Tage</p>
                                                        <div class="progress">
                                                            <div class="determinate blue" style="width: <?php echo $homeofficePercent; ?>%"></div>
                                                        </div>
                                                        <span class="percentage"><?php echo $homeofficePercent; ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col s12 m6 l3">
                                                <div class="card">
                                                    <div class="card-content center-align">
                                                        <i class="material-icons medium orange-text">business</i>
                                                        <span class="card-title"><?php echo $stats['office']; ?></span>
                                                        <p>Büro Tage</p>
                                                        <div class="progress">
                                                            <div class="determinate orange" style="width: <?php echo $officePercent; ?>%"></div>
                                                        </div>
                                                        <span class="percentage"><?php echo $officePercent; ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col s12 m6 l3">
                                                <div class="card">
                                                    <div class="card-content center-align">
                                                        <i class="material-icons medium green-text">trending_up</i>
                                                        <span class="card-title"><?php echo number_format($stats['homeoffice'] / $total * 5, 1); ?></span>
                                                        <p>Ø Homeoffice Tage/Woche</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart -->
                                        <div class="row">
                                            <div class="col s12 m8">
                                                <div class="card accent-card">
                                                    <div class="card-content">
                                                        <div class="card-header blue darken-1 white-text" style="padding: 16px; border-radius: 4px; margin-bottom: 20px;">
                                                            <span class="card-title" style="font-size: 1.8rem;">Monatsübersicht</span>
                                                        </div>
                                                        <div class="chart-container" style="position: relative; height: 350px; width: 100%;">
                                                            <canvas id="monthlyChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col s12 m4">
                                                <div class="card accent-card">
                                                    <div class="card-content">
                                                        <div class="card-header blue darken-1 white-text" style="padding: 16px; border-radius: 4px; margin-bottom: 20px;">
                                                            <span class="card-title" style="font-size: 1.8rem;">Verteilung</span>
                                                        </div>
                                                        <div class="chart-container" style="position: relative; height: 350px; width: 100%; display: flex; justify-content: center; align-items: center;">
                                                            <canvas id="distributionChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- End of overview tab -->
                                    
                                    <div id="details">
                                        <!-- Export Actions -->
                                        <div class="row">
                                            <div class="col s12">
                                                <div class="card-panel blue lighten-5">
                                                    <div class="export-actions center-align">
                                                        <a href="export_pdf.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" 
                                                           class="btn-large waves-effect waves-light grey darken-1">
                                                            <i class="material-icons left">picture_as_pdf</i>
                                                            PDF Export
                                                        </a>
                                                        <a href="export_csv.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" 
                                                           class="btn-large waves-effect waves-light teal">
                                                            <i class="material-icons left">file_download</i>
                                                            CSV Export
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Detailed Table -->
                                        <div class="row">
                                            <div class="col s12">
                                                <div class="card">
                                                    <div class="card-content">
                                                        <span class="card-title">Detaillierte Übersicht</span>
                                                        <div class="table-container">
                                                            <table class="striped highlight responsive-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Datum</th>
                                                                        <th>Wochentag</th>
                                                                        <th>Art</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    foreach ($days as $date => $location) {
                                                                        $displayDate = date('d.m.Y', strtotime($date));
                                                                        $dayName = date('l', strtotime($date));
                                                                        $germanDays = [
                                                                            'Monday' => 'Montag',
                                                                            'Tuesday' => 'Dienstag',
                                                                            'Wednesday' => 'Mittwoch',
                                                                            'Thursday' => 'Donnerstag',
                                                                            'Friday' => 'Freitag'
                                                                        ];
                                                                        switch ($location) {
                                                                            case 'homeoffice':
                                                                                $locationText = 'Homeoffice';
                                                                                $locationClass = 'blue-text';
                                                                                $locationIcon = 'home';
                                                                                break;
                                                                            case 'office':
                                                                                $locationText = 'Büro';
                                                                                $locationClass = 'orange-text';
                                                                                $locationIcon = 'business';
                                                                                break;
                                                                            case 'vacation':
                                                                                $locationText = 'Urlaub';
                                                                                $locationClass = 'purple-text';
                                                                                $locationIcon = 'beach_access';
                                                                                break;
                                                                            case 'sick':
                                                                                $locationText = 'Krank';
                                                                                $locationClass = 'red-text';
                                                                                $locationIcon = 'healing';
                                                                                break;
                                                                            case 'training':
                                                                                $locationText = 'Schulung';
                                                                                $locationClass = 'teal-text';
                                                                                $locationIcon = 'school';
                                                                                break;
                                                                            default:
                                                                                $locationText = 'Keine Angabe';
                                                                                $locationClass = 'grey-text';
                                                                                $locationIcon = 'radio_button_unchecked';
                                                                        }
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo $displayDate; ?></td>
                                                                            <td><?php echo $germanDays[$dayName]; ?></td>
                                                                            <td>
                                                                                <span class="<?php echo $locationClass; ?>">
                                                                                    <i class="material-icons tiny"><?php echo $locationIcon; ?></i>
                                                                                    <?php echo $locationText; ?>
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                                    <?php } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- End of details tab -->

                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var tabs = document.querySelectorAll('.tabs');
                                        M.Tabs.init(tabs);

                                        const monthlyData = <?php
                                            $labels = [];
                                            $homeofficeData = [];
                                            $officeData = [];
                                            
                                            $startMonth = ($quarter - 1) * 3 + 1;
                                            $endMonth = $quarter * 3;
                                            
                                            $germanMonths = [
                                                1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                                                'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
                                            ];
                                            
                                            for ($month = $startMonth; $month <= $endMonth; $month++) {
                                                $labels[] = $germanMonths[$month];
                                                $homeofficeData[] = $monthlyStats[$month]['homeoffice'];
                                                $officeData[] = $monthlyStats[$month]['office'];
                                            }
                                            
                                            echo json_encode([
                                                'labels' => $labels,
                                                'homeoffice' => $homeofficeData,
                                                'office' => $officeData
                                            ]);
                                        ?>;

                                        // Line Chart für monatliche Entwicklung
                                        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
                                        new Chart(ctxMonthly, {
                                            type: 'line',
                                            data: {
                                                labels: monthlyData.labels,
                                                datasets: [{
                                                    label: 'Homeoffice',
                                                    data: monthlyData.homeoffice,
                                                    backgroundColor: 'rgba(33, 150, 243, 0.2)',
                                                    borderColor: 'rgba(33, 150, 243, 1)',
                                                    borderWidth: 3,
                                                    tension: 0.3,
                                                    fill: true,
                                                    pointRadius: 5,
                                                    pointHoverRadius: 8,
                                                    pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                                                    pointHoverBackgroundColor: '#fff',
                                                    pointBorderColor: '#fff',
                                                    pointHoverBorderColor: 'rgba(33, 150, 243, 1)',
                                                    pointBorderWidth: 2,
                                                    pointHoverBorderWidth: 2
                                                }, {
                                                    label: 'Büro',
                                                    data: monthlyData.office,
                                                    backgroundColor: 'rgba(255, 152, 0, 0.2)',
                                                    borderColor: 'rgba(255, 152, 0, 1)',
                                                    borderWidth: 3,
                                                    tension: 0.3,
                                                    fill: true,
                                                    pointRadius: 5,
                                                    pointHoverRadius: 8,
                                                    pointBackgroundColor: 'rgba(255, 152, 0, 1)',
                                                    pointHoverBackgroundColor: '#fff',
                                                    pointBorderColor: '#fff',
                                                    pointHoverBorderColor: 'rgba(255, 152, 0, 1)',
                                                    pointBorderWidth: 2,
                                                    pointHoverBorderWidth: 2
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                interaction: {
                                                    mode: 'index',
                                                    intersect: false,
                                                    axis: 'x'
                                                },
                                                scales: {
                                                    y: {
                                                        beginAtZero: true,
                                                        ticks: {
                                                            stepSize: 1,
                                                            font: {
                                                                size: 12
                                                            }
                                                        },
                                                        grid: {
                                                            color: 'rgba(0, 0, 0, 0.05)'
                                                        }
                                                    },
                                                    x: {
                                                        grid: {
                                                            color: 'rgba(0, 0, 0, 0.05)'
                                                        },
                                                        ticks: {
                                                            font: {
                                                                size: 12
                                                            }
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    legend: {
                                                        position: 'bottom',
                                                        labels: {
                                                            padding: 20,
                                                            usePointStyle: true,
                                                            pointStyle: 'circle'
                                                        }
                                                    },
                                                    tooltip: {
                                                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                                        titleFont: {
                                                            size: 13
                                                        },
                                                        bodyFont: {
                                                            size: 13
                                                        },
                                                        padding: 12,
                                                        displayColors: true,
                                                        boxWidth: 10,
                                                        boxHeight: 10,
                                                        usePointStyle: true,
                                                        callbacks: {
                                                            label: function(context) {
                                                                return context.dataset.label + ': ' + context.parsed.y + ' Tage';
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });

                                        // Donut Chart für Gesamtverteilung (alle Typen)
                                        const ctxDistribution = document.getElementById('distributionChart').getContext('2d');
                                        new Chart(ctxDistribution, {
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
                                                    backgroundColor: [
                                                        'rgba(33, 150, 243, 0.8)',
                                                        'rgba(255, 152, 0, 0.8)',
                                                        'rgba(156, 39, 176, 0.8)',
                                                        'rgba(244, 67, 54, 0.8)',
                                                        'rgba(0, 150, 136, 0.8)'
                                                    ],
                                                    borderColor: [
                                                        'rgba(33, 150, 243, 1)',
                                                        'rgba(255, 152, 0, 1)',
                                                        'rgba(156, 39, 176, 1)',
                                                        'rgba(244, 67, 54, 1)',
                                                        'rgba(0, 150, 136, 1)'
                                                    ],
                                                    borderWidth: 2,
                                                    hoverOffset: 15,
                                                    hoverBorderWidth: 3
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                cutout: '60%',
                                                plugins: {
                                                    legend: {
                                                        position: 'bottom',
                                                        labels: {
                                                            padding: 20,
                                                            usePointStyle: true,
                                                            pointStyle: 'circle'
                                                        }
                                                    },
                                                    tooltip: {
                                                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                                        titleFont: {
                                                            size: 13
                                                        },
                                                        bodyFont: {
                                                            size: 13
                                                        },
                                                        padding: 12,
                                                        callbacks: {
                                                            label: function(context) {
                                                                const label = context.label || '';
                                                                const value = context.formattedValue;
                                                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                                                const percentage = Math.round((context.raw / total) * 100);
                                                                return `${label}: ${value} Tage (${percentage}%)`;
                                                            }
                                                        }
                                                    }
                                                },
                                                animation: {
                                                    animateRotate: true,
                                                    animateScale: true
                                                }
                                            }
                                        });

                                        // Animate the progress bars
                                        document.querySelectorAll('.progress .determinate').forEach(bar => {
                                            const width = bar.style.width;
                                            bar.style.width = '0%';
                                            setTimeout(() => {
                                                bar.style.transition = 'width 1s ease-in-out';
                                                bar.style.width = width;
                                            }, 200);
                                        });
                                    });
                                    </script>
                                <?php } else { ?>
                                    <div class="card-panel yellow lighten-4">
                                        <div class="valign-wrapper">
                                            <i class="material-icons medium">info</i>
                                            <span style="margin-left: 1rem;">
                                                Keine Buchungen im ausgewählten Zeitraum gefunden.
                                            </span>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php include 'templates/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
