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

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <div class="card-header blue darken-1 white-text" style="margin: -20px -20px 20px -20px; padding: 20px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:.5rem;">
                        <i class="material-icons">calendar_today</i>
                        <span class="card-title" style="margin:0;">Kalender</span>
                    </div>
                    <div>
                        <?php
                        $prev = (clone $firstOfMonth)->modify('-1 month');
                        $next = (clone $firstOfMonth)->modify('+1 month');
                        ?>
                        <a href="?month=<?php echo (int)$prev->format('n'); ?>&year=<?php echo (int)$prev->format('Y'); ?>" class="btn-flat waves-effect white-text"><i class="material-icons">chevron_left</i></a>
                        <span style="margin: 0 1rem; font-weight: 600;">
                            <?php
                            $months = [1=>'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
                            echo $months[$month] . ' ' . $year;
                            ?>
                        </span>
                        <a href="?month=<?php echo (int)$next->format('n'); ?>&year=<?php echo (int)$next->format('Y'); ?>" class="btn-flat waves-effect white-text"><i class="material-icons">chevron_right</i></a>
                    </div>
                </div>

                <div class="section">
                    <div class="row" style="margin-bottom: 1rem;">
                        <div class="col s12">
                            <div class="grey-text" style="display:flex; gap:1rem; flex-wrap:wrap;">
                                <span><i class="material-icons tiny blue-text">home</i> Homeoffice</span>
                                <span><i class="material-icons tiny orange-text">business</i> Büro</span>
                                <span><i class="material-icons tiny purple-text">beach_access</i> Urlaub</span>
                                <span><i class="material-icons tiny red-text">healing</i> Krank</span>
                                <span><i class="material-icons tiny teal-text">school</i> Schulung</span>
                            </div>
                        </div>
                    </div>

                    <div class="responsive-table">
                        <table class="highlight" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <?php foreach (['Mo','Di','Mi','Do','Fr','Sa','So'] as $w): ?>
                                        <th class="center-align"><?php echo $w; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cursor = clone $start;
                                while ($cursor <= $end) {
                                    echo '<tr>';
                                    for ($d = 1; $d <= 7; $d++) {
                                        $dateStr = $cursor->format('Y-m-d');
                                        $isCurrentMonth = ((int)$cursor->format('n') === $month);
                                        $isWeekend = ((int)$cursor->format('N') >= 6);
                                        $classes = [];
                                        if (!$isCurrentMonth) $classes[] = 'grey lighten-4';
                                        if ($isWeekend) $classes[] = 'grey lighten-5';
                                        $displayDay = (int)$cursor->format('j');

                                        $loc = $map[$dateStr] ?? '';
                                        $icon = 'radio_button_unchecked';
                                        $color = 'grey-text';
                                        $label = '';
                                        switch ($loc) {
                                            case 'homeoffice': $icon='home'; $color='blue-text'; $label='Homeoffice'; break;
                                            case 'office': $icon='business'; $color='orange-text'; $label='Büro'; break;
                                            case 'vacation': $icon='beach_access'; $color='purple-text'; $label='Urlaub'; break;
                                            case 'sick': $icon='healing'; $color='red-text'; $label='Krank'; break;
                                            case 'training': $icon='school'; $color='teal-text'; $label='Schulung'; break;
                                        }

                                        // Link zur Buchungswoche
                                        $week = (int)$cursor->format('W');
                                        $yForWeek = (int)$cursor->format('Y');
                                        $link = 'booking.php?week=' . $week . '&year=' . $yForWeek;

                                        echo '<td class="' . implode(' ', $classes) . '" style="vertical-align: top; height: 110px;">';
                                        echo '<div style="padding:8px; display:flex; justify-content:space-between; align-items:center;">';
                                        echo '<span class="' . ($isCurrentMonth ? '' : 'grey-text') . '">' . $displayDay . '</span>';
                                        echo '<a href="' . $link . '" class="btn-flat btn-small tooltipped" data-tooltip="Zur Buchung">';
                                        echo '<i class="material-icons">open_in_new</i></a></div>';

                                        if ($label) {
                                            echo '<div style="padding: 0 8px;">';
                                            echo '<div class="chip ' . $color . ' white" style="height:auto; line-height:1.8;">';
                                            echo '<i class="material-icons tiny ' . $color . '">' . $icon . '</i> ' . $label;
                                            echo '</div></div>';
                                        }
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
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

