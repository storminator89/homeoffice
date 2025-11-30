<?php
require_once 'database.php';

$db = new Database();
$dbConnection = $db->getDb();

// Create webhooks table if not exists
$dbConnection->exec("CREATE TABLE IF NOT EXISTS webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    secret TEXT,
    events TEXT NOT NULL DEFAULT 'booking_created',
    active INTEGER DEFAULT 1,
    last_triggered TIMESTAMP,
    last_status INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create webhook logs table
$dbConnection->exec("CREATE TABLE IF NOT EXISTS webhook_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id INTEGER NOT NULL,
    event TEXT NOT NULL,
    payload TEXT,
    response_code INTEGER,
    response_body TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
)");

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_webhook'])) {
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $secret = trim($_POST['secret'] ?? '');
        $events = isset($_POST['events']) ? implode(',', $_POST['events']) : 'booking_created';
        
        if (empty($name) || empty($url)) {
            $error = 'Name und URL sind erforderlich.';
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
            $error = 'Bitte geben Sie eine gültige URL ein.';
        } else {
            $stmt = $dbConnection->prepare('INSERT INTO webhooks (name, url, secret, events) VALUES (:name, :url, :secret, :events)');
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
            $stmt->bindValue(':secret', $secret, SQLITE3_TEXT);
            $stmt->bindValue(':events', $events, SQLITE3_TEXT);
            $stmt->execute();
            $success = 'Webhook erfolgreich erstellt.';
        }
    } elseif (isset($_POST['delete_webhook'])) {
        $id = (int)$_POST['webhook_id'];
        $dbConnection->exec("DELETE FROM webhooks WHERE id = $id");
        $dbConnection->exec("DELETE FROM webhook_logs WHERE webhook_id = $id");
        $success = 'Webhook gelöscht.';
    } elseif (isset($_POST['toggle_webhook'])) {
        $id = (int)$_POST['webhook_id'];
        $dbConnection->exec("UPDATE webhooks SET active = NOT active WHERE id = $id");
        $success = 'Webhook-Status geändert.';
    } elseif (isset($_POST['test_webhook'])) {
        $id = (int)$_POST['webhook_id'];
        $webhook = $dbConnection->query("SELECT * FROM webhooks WHERE id = $id")->fetchArray(SQLITE3_ASSOC);
        
        if ($webhook) {
            $testPayload = [
                'event' => 'test',
                'timestamp' => date('c'),
                'data' => [
                    'message' => 'Dies ist ein Test-Webhook',
                    'webhook_id' => $id,
                    'webhook_name' => $webhook['name']
                ]
            ];
            
            $result = triggerWebhook($webhook, $testPayload);
            
            if ($result['success']) {
                $success = "Test erfolgreich! Status: {$result['code']}";
            } else {
                $error = "Test fehlgeschlagen: {$result['error']}";
            }
        }
    }
}

// Function to trigger a webhook
function triggerWebhook($webhook, $payload) {
    global $dbConnection;
    
    $jsonPayload = json_encode($payload);
    
    $headers = [
        'Content-Type: application/json',
        'User-Agent: HomeofficeTracker/1.0',
        'X-Webhook-Event: ' . ($payload['event'] ?? 'unknown')
    ];
    
    // Add signature if secret is set
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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log the webhook call
    $stmt = $dbConnection->prepare('INSERT INTO webhook_logs (webhook_id, event, payload, response_code, response_body) VALUES (:webhook_id, :event, :payload, :code, :response)');
    $stmt->bindValue(':webhook_id', $webhook['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':event', $payload['event'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':payload', $jsonPayload, SQLITE3_TEXT);
    $stmt->bindValue(':code', $httpCode, SQLITE3_INTEGER);
    $stmt->bindValue(':response', $response ?: $error, SQLITE3_TEXT);
    $stmt->execute();
    
    // Update webhook status
    $dbConnection->exec("UPDATE webhooks SET last_triggered = CURRENT_TIMESTAMP, last_status = $httpCode WHERE id = {$webhook['id']}");
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Get all webhooks
$webhooks = $dbConnection->query("SELECT * FROM webhooks ORDER BY created_at DESC");
$webhookList = [];
while ($row = $webhooks->fetchArray(SQLITE3_ASSOC)) {
    $webhookList[] = $row;
}

// Get recent logs
$logs = $dbConnection->query("SELECT l.*, w.name as webhook_name FROM webhook_logs l JOIN webhooks w ON l.webhook_id = w.id ORDER BY l.created_at DESC LIMIT 20");
$logList = [];
while ($row = $logs->fetchArray(SQLITE3_ASSOC)) {
    $logList[] = $row;
}

include 'templates/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/30">
                    <i class="material-icons text-2xl">webhook</i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Webhooks</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Automatische Benachrichtigungen bei Buchungen</p>
                </div>
            </div>
            <button onclick="document.getElementById('addWebhookModal').classList.remove('hidden')" 
                    class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white rounded-xl shadow-lg shadow-violet-500/30 transition-all hover:scale-105 active:scale-95">
                <i class="material-icons">add</i>
                <span>Webhook hinzufügen</span>
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-300 flex items-center gap-3">
            <i class="material-icons">check_circle</i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 flex items-center gap-3">
            <i class="material-icons">error</i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Webhooks List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="material-icons text-violet-500">list</i>
                Konfigurierte Webhooks
            </h2>
        </div>
        
        <?php if (empty($webhookList)): ?>
            <div class="p-12 text-center">
                <div class="p-4 rounded-full bg-gray-100 dark:bg-gray-700 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="material-icons text-3xl text-gray-400">webhook</i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Noch keine Webhooks</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Erstelle deinen ersten Webhook, um Benachrichtigungen zu erhalten.</p>
                <button onclick="document.getElementById('addWebhookModal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors">
                    <i class="material-icons">add</i>
                    Webhook erstellen
                </button>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($webhookList as $webhook): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="p-2 rounded-lg <?php echo $webhook['active'] ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'; ?>">
                                    <i class="material-icons"><?php echo $webhook['active'] ? 'power' : 'power_off'; ?></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($webhook['name']); ?></h3>
                                        <?php if ($webhook['last_status']): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $webhook['last_status'] >= 200 && $webhook['last_status'] < 300 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'; ?>">
                                                <?php echo $webhook['last_status']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate mb-2"><?php echo htmlspecialchars($webhook['url']); ?></p>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach (explode(',', $webhook['events']) as $event): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                                                <?php echo htmlspecialchars($event); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($webhook['last_triggered']): ?>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                            Zuletzt: <?php echo date('d.m.Y H:i', strtotime($webhook['last_triggered'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                    <button type="submit" name="test_webhook" class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors" title="Testen">
                                        <i class="material-icons">send</i>
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                    <button type="submit" name="toggle_webhook" class="p-2 rounded-lg <?php echo $webhook['active'] ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50' : 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50'; ?> transition-colors" title="<?php echo $webhook['active'] ? 'Deaktivieren' : 'Aktivieren'; ?>">
                                        <i class="material-icons"><?php echo $webhook['active'] ? 'pause' : 'play_arrow'; ?></i>
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Webhook wirklich löschen?')">
                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                    <button type="submit" name="delete_webhook" class="p-2 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors" title="Löschen">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="material-icons text-gray-500">history</i>
                Letzte Aktivitäten
            </h2>
        </div>
        
        <?php if (empty($logList)): ?>
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <i class="material-icons text-4xl mb-2">inbox</i>
                <p>Noch keine Webhook-Aufrufe</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Zeit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Webhook</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php foreach ($logList as $log): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    <?php echo date('d.m. H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($log['webhook_name']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                                        <?php echo htmlspecialchars($log['event']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium <?php echo $log['response_code'] >= 200 && $log['response_code'] < 300 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'; ?>">
                                        <i class="material-icons text-xs"><?php echo $log['response_code'] >= 200 && $log['response_code'] < 300 ? 'check' : 'close'; ?></i>
                                        <?php echo $log['response_code']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Webhook Modal -->
<div id="addWebhookModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('addWebhookModal').classList.add('hidden')"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="material-icons text-violet-500">add_circle</i>
                    Neuer Webhook
                </h3>
                <button onclick="document.getElementById('addWebhookModal').classList.add('hidden')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                    <i class="material-icons">close</i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                           placeholder="z.B. Slack Notification">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                    <input type="url" name="url" required 
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                           placeholder="https://hooks.example.com/webhook">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret (optional)</label>
                    <input type="text" name="secret" 
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                           placeholder="Für HMAC-Signatur">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Wird für X-Webhook-Signature Header verwendet</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Events</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="events[]" value="booking_created" checked class="rounded text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Buchung erstellt</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="events[]" value="booking_updated" class="rounded text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Buchung geändert</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="events[]" value="booking_deleted" class="rounded text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Buchung gelöscht</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('addWebhookModal').classList.add('hidden')" 
                            class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit" name="add_webhook" 
                            class="flex-1 px-4 py-2 rounded-lg bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white transition-all">
                        Erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
