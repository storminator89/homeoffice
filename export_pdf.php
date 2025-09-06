<?php
require_once 'database.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

$db = new Database();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n') / 3);

$dompdf = new Dompdf();

$html = '<h1>Quartalauswertung Q' . $quarter . '/' . $year . '</h1>';
$html .= '<table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Wochentag</th>
                    <th>Arbeitsort</th>
                </tr>
            </thead>
            <tbody>';

$result = $db->getQuarterBookings($year, $quarter);
$germanDays = [
    'Monday' => 'Montag',
    'Tuesday' => 'Dienstag',
    'Wednesday' => 'Mittwoch',
    'Thursday' => 'Donnerstag',
    'Friday' => 'Freitag'
];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $dateObj = new DateTime($row['date']);
    $germanDate = $dateObj->format('d.m.Y');
    $dayName = $germanDays[$dateObj->format('l')];
    switch ($row['location']) {
        case 'homeoffice': $locationText = 'Homeoffice'; break;
        case 'office': $locationText = 'Büro'; break;
        case 'vacation': $locationText = 'Urlaub'; break;
        case 'sick': $locationText = 'Krank'; break;
        case 'training': $locationText = 'Schulung'; break;
        default: $locationText = 'Keine Angabe';
    }
    $html .= '<tr>
                <td>' . $germanDate . '</td>
                <td>' . $dayName . '</td>
                <td>' . $locationText . '</td>
              </tr>';
}

$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('export.pdf');
?>
