<?php
require_once 'database.php';

if (isset($_GET['ajax'])) {
    $db = new Database();
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n') / 3);
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    
    $result = $db->getQuarterBookings($year, $quarter);
    $days = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $days[$row['date']] = $row['location'];
    }

    $itemsPerPage = 10;
    $totalItems = count($days);
    $totalPages = ceil($totalItems / $itemsPerPage);
    $startIndex = ($page - 1) * $itemsPerPage;
    $currentPageDays = array_slice($days, $startIndex, $itemsPerPage, true);

    $germanDays = [
        'Monday' => 'Montag',
        'Tuesday' => 'Dienstag',
        'Wednesday' => 'Mittwoch',
        'Thursday' => 'Donnerstag',
        'Friday' => 'Freitag'
    ];

    $html = '';
    foreach ($currentPageDays as $date => $location) {
        $dateObj = new DateTime($date);
        $germanDate = $dateObj->format('d.m.Y');
        $dayName = $germanDays[$dateObj->format('l')];
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
        
        $html .= "<tr>
            <td>{$germanDate}</td>
            <td>{$dayName}</td>
            <td class='{$locationClass}'><i class='material-icons tiny'>{$locationIcon}</i> {$locationText}</td>
        </tr>";
    }

    // Sende nur die Tabellendaten zurück
    echo json_encode([
        'rows' => $html,
        'currentPage' => $page,
        'totalPages' => $totalPages
    ]);
    exit;
}
