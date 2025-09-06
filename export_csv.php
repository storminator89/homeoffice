<?php
require_once 'database.php';
$db = new Database();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n') / 3);

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Datum', 'Wochentag', 'Arbeitsort']);

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
    fputcsv($output, [$germanDate, $dayName, $locationText]);
}

fclose($output);
?>
