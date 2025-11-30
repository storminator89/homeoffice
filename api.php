<?php
/**
 * API Endpoint für Homeoffice Tracker
 * 
 * Erlaubt das Erstellen/Aktualisieren von Buchungen via HTTP Request
 * 
 * Beispiel-Anfragen:
 * 
 * 1. Einfacher GET-Request (für Shortcuts/Automationen):
 *    GET /api.php?action=booking&location=office
 *    GET /api.php?action=booking&location=homeoffice&date=2025-12-02
 * 
 * 2. POST-Request mit JSON:
 *    POST /api.php
 *    {"action": "booking", "location": "office", "date": "2025-12-01", "note": "Meeting"}
 * 
 * 3. Mit API-Key (wenn in Settings konfiguriert):
 *    GET /api.php?action=booking&location=office&api_key=YOUR_KEY
 *    Header: X-API-Key: YOUR_KEY
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'database.php';

$db = new Database();
$dbConnection = $db->getDb();

// Ensure api_key setting exists
$existingKey = $db->getSetting('api_key');
if ($existingKey === null) {
    $db->setSetting('api_key', '', false);
}

// Get API key from various sources
$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$configuredKey = $db->getSetting('api_key');

// Check API key if one is configured
if (!empty($configuredKey) && $apiKey !== $configuredKey) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Ungültiger API-Key',
        'hint' => 'Sende den API-Key als ?api_key=XXX oder Header X-API-Key'
    ]);
    exit;
}

// Parse input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    $input = array_merge($_POST, $jsonInput ?? []);
} else {
    $input = $_GET;
}

$action = $input['action'] ?? 'booking';
$response = ['success' => false];

switch ($action) {
    case 'booking':
        $location = strtolower(trim($input['location'] ?? ''));
        $date = $input['date'] ?? date('Y-m-d');
        $note = $input['note'] ?? '';
        
        // Alias-Unterstützung für einfachere Eingabe
        $locationAliases = [
            'home' => 'homeoffice',
            'ho' => 'homeoffice',
            'h' => 'homeoffice',
            'remote' => 'homeoffice',
            'büro' => 'office',
            'buero' => 'office',
            'o' => 'office',
            'b' => 'office',
            'urlaub' => 'vacation',
            'u' => 'vacation',
            'v' => 'vacation',
            'krank' => 'sick',
            'k' => 'sick',
            's' => 'sick',
            'schulung' => 'training',
            't' => 'training',
            'löschen' => '',
            'delete' => '',
            'clear' => '',
            'x' => ''
        ];
        
        if (isset($locationAliases[$location])) {
            $location = $locationAliases[$location];
        }
        
        // Validate location
        $allowedLocations = ['homeoffice', 'office', 'vacation', 'sick', 'training', ''];
        if (!in_array($location, $allowedLocations)) {
            http_response_code(400);
            $response = [
                'success' => false,
                'error' => 'Ungültiger Standort',
                'allowed' => $allowedLocations,
                'aliases' => array_keys($locationAliases)
            ];
            break;
        }
        
        // Parse date (support various formats)
        $parsedDate = null;
        if ($date === 'heute' || $date === 'today') {
            $parsedDate = date('Y-m-d');
        } elseif ($date === 'morgen' || $date === 'tomorrow') {
            $parsedDate = date('Y-m-d', strtotime('+1 day'));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $parsedDate = $date;
        } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
            $parsedDate = DateTime::createFromFormat('d.m.Y', $date)->format('Y-m-d');
        } elseif (preg_match('/^\d{2}\.\d{2}\.?$/', $date)) {
            $parsedDate = DateTime::createFromFormat('d.m', rtrim($date, '.'))->format('Y-m-d');
        } else {
            $parsedDate = date('Y-m-d', strtotime($date));
        }
        
        if (!$parsedDate || $parsedDate === '1970-01-01') {
            http_response_code(400);
            $response = [
                'success' => false,
                'error' => 'Ungültiges Datum',
                'hint' => 'Formate: 2025-12-01, 01.12.2025, 01.12, heute, morgen'
            ];
            break;
        }
        
        try {
            $db->addBooking($parsedDate, $location, $note);
            
            $locationLabels = [
                'homeoffice' => 'Homeoffice',
                'office' => 'Büro',
                'vacation' => 'Urlaub',
                'sick' => 'Krank',
                'training' => 'Schulung',
                '' => 'Gelöscht'
            ];
            
            $response = [
                'success' => true,
                'message' => $location ? "Buchung gespeichert: {$locationLabels[$location]}" : "Buchung gelöscht",
                'data' => [
                    'date' => $parsedDate,
                    'date_formatted' => date('d.m.Y', strtotime($parsedDate)),
                    'location' => $location,
                    'location_label' => $locationLabels[$location] ?? $location,
                    'note' => $note
                ]
            ];
            
            // Trigger webhooks
            triggerBookingWebhooks($db, 'booking_created', $response['data']);
            
        } catch (Exception $e) {
            http_response_code(400);
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
        
    case 'status':
        $date = $input['date'] ?? date('Y-m-d');
        $booking = $dbConnection->querySingle("SELECT location, note FROM bookings WHERE date = '$date'", true);
        
        $response = [
            'success' => true,
            'data' => [
                'date' => $date,
                'date_formatted' => date('d.m.Y', strtotime($date)),
                'location' => $booking['location'] ?? null,
                'note' => $booking['note'] ?? null,
                'booked' => !empty($booking['location'])
            ]
        ];
        break;
        
    case 'week':
        $monday = date('Y-m-d', strtotime('monday this week'));
        $dates = [];
        for ($i = 0; $i < 5; $i++) {
            $dates[] = date('Y-m-d', strtotime("$monday +$i days"));
        }
        
        $bookings = $db->getBookingsForDates($dates);
        $weekData = [];
        
        $germanDays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'];
        foreach ($dates as $i => $date) {
            $weekData[] = [
                'date' => $date,
                'day' => $germanDays[$i],
                'location' => $bookings[$date]['location'] ?? null,
                'note' => $bookings[$date]['note'] ?? null
            ];
        }
        
        $response = [
            'success' => true,
            'data' => $weekData
        ];
        break;
        
    case 'help':
        $response = [
            'success' => true,
            'endpoints' => [
                'booking' => [
                    'description' => 'Buchung erstellen/aktualisieren',
                    'params' => [
                        'location' => 'homeoffice, office, vacation, sick, training (oder Aliase: h, ho, home, b, o, büro, u, k, s, t)',
                        'date' => 'Optional: 2025-12-01, 01.12.2025, 01.12, heute, morgen (Standard: heute)',
                        'note' => 'Optional: Notiz zur Buchung'
                    ],
                    'examples' => [
                        'GET /api.php?action=booking&location=office',
                        'GET /api.php?action=booking&location=h&date=morgen',
                        'POST {"action":"booking","location":"homeoffice","note":"Meeting"}'
                    ]
                ],
                'status' => [
                    'description' => 'Status für ein Datum abfragen',
                    'params' => ['date' => 'Optional (Standard: heute)']
                ],
                'week' => [
                    'description' => 'Aktuelle Woche abfragen'
                ]
            ],
            'authentication' => 'Optional: api_key als Query-Parameter oder X-API-Key Header'
        ];
        break;
        
    default:
        http_response_code(400);
        $response = [
            'success' => false,
            'error' => 'Unbekannte Aktion',
            'hint' => 'Verfügbare Aktionen: booking, status, week, help'
        ];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Helper function to trigger webhooks
function triggerBookingWebhooks($db, $event, $data) {
    $dbConnection = $db->getDb();
    
    $webhooks = $dbConnection->query("SELECT * FROM webhooks WHERE active = 1 AND events LIKE '%$event%'");
    if (!$webhooks) return;
    
    while ($webhook = $webhooks->fetchArray(SQLITE3_ASSOC)) {
        $payload = [
            'event' => $event,
            'timestamp' => date('c'),
            'data' => $data
        ];
        
        $jsonPayload = json_encode($payload);
        
        $headers = [
            'Content-Type: application/json',
            'User-Agent: HomeofficeTracker/1.0',
            'X-Webhook-Event: ' . $event
        ];
        
        if (!empty($webhook['secret'])) {
            $signature = hash_hmac('sha256', $jsonPayload, $webhook['secret']);
            $headers[] = 'X-Webhook-Signature: sha256=' . $signature;
        }
        
        $ch = curl_init($webhook['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log
        $stmt = $dbConnection->prepare('INSERT INTO webhook_logs (webhook_id, event, payload, response_code, response_body) VALUES (?, ?, ?, ?, ?)');
        $stmt->bindValue(1, $webhook['id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $event, SQLITE3_TEXT);
        $stmt->bindValue(3, $jsonPayload, SQLITE3_TEXT);
        $stmt->bindValue(4, $httpCode, SQLITE3_INTEGER);
        $stmt->bindValue(5, $response, SQLITE3_TEXT);
        $stmt->execute();
        
        $dbConnection->exec("UPDATE webhooks SET last_triggered = CURRENT_TIMESTAMP, last_status = $httpCode WHERE id = {$webhook['id']}");
    }
}
