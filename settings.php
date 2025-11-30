<?php
require_once 'database.php';
$db = new Database();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $quota = (int)$_POST['quota'];
        $vacation = (int)$_POST['vacation'];
        
        if ($quota >= 0 && $quota <= 100) {
            $db->setSetting('homeoffice_quota', $quota);
            $db->setSetting('vacation_days', $vacation);
            $success = 'Einstellungen gespeichert.';
        } else {
            $error = 'Quote muss zwischen 0 und 100 liegen.';
        }
    } elseif (isset($_POST['save_api_key'])) {
        $apiKey = trim($_POST['api_key'] ?? '');
        $db->setSetting('api_key', $apiKey);
        $success = $apiKey ? 'API-Key gespeichert.' : 'API-Key entfernt (kein Schutz).';
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $error = 'Die neuen Passwörter stimmen nicht überein.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Das neue Passwort muss mindestens 6 Zeichen lang sein.';
        } else {
            try {
                $db->changePassword($_SESSION['user_id'], $current_password, $new_password);
                $success = 'Passwort erfolgreich geändert.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$currentQuota = $db->getSetting('homeoffice_quota');
$currentVacation = $db->getSetting('vacation_days');

include 'templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                    <i class="material-icons">settings</i>
                </div>
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Einstellungen</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Verwalten Sie Ihre Homeoffice-Präferenzen.</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-400 dark:border-green-600 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="material-icons text-green-400 dark:text-green-500">check_circle</i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-400 dark:border-red-600 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="material-icons text-red-400 dark:text-red-500">error</i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Ziel-Homeoffice-Quote (%)
                    </label>
                    <div class="mt-1">
                        <input type="range" id="quota" name="quota" min="0" max="100" value="<?php echo $currentQuota; ?>" 
                               class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                               oninput="document.getElementById('quotaValue').innerText = this.value + '%'">
                    </div>
                    <div class="mt-2 flex justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>0%</span>
                        <span id="quotaValue" class="font-bold text-indigo-600 dark:text-indigo-400"><?php echo $currentQuota; ?>%</span>
                        <span>100%</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Legen Sie fest, wie viel Prozent Ihrer Arbeitszeit Sie im Homeoffice verbringen möchten.
                    </p>
                </div>

                <div>
                    <label for="vacation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Jahresurlaub (Tage)
                    </label>
                    <div class="mt-1">
                        <input type="number" id="vacation" name="vacation" min="0" max="365" value="<?php echo $currentVacation; ?>" 
                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md border p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Ihr Urlaubsanspruch pro Jahr.
                    </p>
                </div>

                <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-end">
                        <button type="submit" name="save_settings" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Speichern
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- API Settings -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400">
                    <i class="material-icons">api</i>
                </div>
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">API-Zugang</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ermöglicht Buchungen per URL/Shortcut</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php $currentApiKey = $db->getSetting('api_key') ?? ''; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        API-Key (optional)
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="api_key" name="api_key" value="<?php echo htmlspecialchars($currentApiKey); ?>" 
                               class="flex-1 shadow-sm focus:ring-violet-500 focus:border-violet-500 block sm:text-sm border-gray-300 dark:border-gray-600 rounded-md border p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono"
                               placeholder="Leer = kein Schutz">
                        <button type="button" onclick="document.getElementById('api_key').value = Math.random().toString(36).substring(2) + Math.random().toString(36).substring(2)" 
                                class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 transition-colors">
                            Generieren
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Wenn gesetzt, muss der Key bei API-Aufrufen mitgesendet werden.
                    </p>
                </div>
                
                <div class="pt-4">
                    <button type="submit" name="save_api_key" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 transition-colors">
                        API-Key speichern
                    </button>
                </div>
            </form>
            
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Beispiel-URLs</h4>
                    <button type="button" onclick="document.getElementById('apiHelpModal').classList.remove('hidden')" 
                            class="p-1.5 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-violet-100 dark:hover:bg-violet-900/30 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors" title="Hilfe">
                        <i class="material-icons text-lg">help_outline</i>
                    </button>
                </div>
                <div class="space-y-2 text-sm">
                    <?php 
                    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api.php';
                    $keyParam = $currentApiKey ? "&api_key={$currentApiKey}" : '';
                    ?>
                    <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg group">
                        <span class="text-lg">📍</span>
                        <code class="flex-1 text-xs text-violet-600 dark:text-violet-400 truncate"><?php echo $baseUrl; ?>?location=office<?php echo $keyParam; ?></code>
                        <button onclick="navigator.clipboard.writeText('<?php echo $baseUrl; ?>?location=office<?php echo $keyParam; ?>');this.innerHTML='<i class=\'material-icons text-sm\'>check</i>';setTimeout(()=>this.innerHTML='<i class=\'material-icons text-sm\'>content_copy</i>',1500)" 
                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 opacity-0 group-hover:opacity-100 transition-all">
                            <i class="material-icons text-sm">content_copy</i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg group">
                        <span class="text-lg">🏠</span>
                        <code class="flex-1 text-xs text-violet-600 dark:text-violet-400 truncate"><?php echo $baseUrl; ?>?location=homeoffice<?php echo $keyParam; ?></code>
                        <button onclick="navigator.clipboard.writeText('<?php echo $baseUrl; ?>?location=homeoffice<?php echo $keyParam; ?>');this.innerHTML='<i class=\'material-icons text-sm\'>check</i>';setTimeout(()=>this.innerHTML='<i class=\'material-icons text-sm\'>content_copy</i>',1500)" 
                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 opacity-0 group-hover:opacity-100 transition-all">
                            <i class="material-icons text-sm">content_copy</i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Help Modal -->
    <div id="apiHelpModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('apiHelpModal').classList.add('hidden')"></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="material-icons text-violet-500">api</i>
                        API-Dokumentation
                    </h3>
                    <button onclick="document.getElementById('apiHelpModal').classList.add('hidden')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                
                <div class="space-y-4 text-sm">
                    <!-- Auth -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🔑 Authentifizierung</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 space-y-2">
                            <p class="text-gray-600 dark:text-gray-400">API-Key als URL-Parameter:</p>
                            <code class="block text-xs text-violet-600 dark:text-violet-400">?location=office&api_key=DEIN_KEY</code>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Oder als Header:</p>
                            <code class="block text-xs text-violet-600 dark:text-violet-400">X-API-Key: DEIN_KEY</code>
                        </div>
                    </div>
                    
                    <!-- Locations -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📍 Standorte & Kurzformen</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-lg p-2">
                                <span class="font-medium text-indigo-700 dark:text-indigo-300">homeoffice</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">h, ho, home</span>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/30 rounded-lg p-2">
                                <span class="font-medium text-orange-700 dark:text-orange-300">office</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">b, o, büro</span>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-2">
                                <span class="font-medium text-purple-700 dark:text-purple-300">vacation</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">u, urlaub</span>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-2">
                                <span class="font-medium text-red-700 dark:text-red-300">sick</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">k, krank</span>
                            </div>
                            <div class="bg-teal-50 dark:bg-teal-900/30 rounded-lg p-2">
                                <span class="font-medium text-teal-700 dark:text-teal-300">training</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">t, schulung</span>
                            </div>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">löschen</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">x, delete</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📅 Datum (optional)</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-xs space-y-1">
                            <code class="block text-violet-600 dark:text-violet-400">&date=heute</code>
                            <code class="block text-violet-600 dark:text-violet-400">&date=morgen</code>
                            <code class="block text-violet-600 dark:text-violet-400">&date=2025-12-02</code>
                            <code class="block text-violet-600 dark:text-violet-400">&date=02.12.2025</code>
                        </div>
                    </div>
                    
                    <!-- Note -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📝 Notiz (optional)</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <code class="text-xs text-violet-600 dark:text-violet-400">&note=Meeting%20mit%20Team</code>
                        </div>
                    </div>
                    
                    <!-- Other actions -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🔧 Weitere Aktionen</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-xs space-y-1">
                            <code class="block text-violet-600 dark:text-violet-400">?action=status</code>
                            <span class="text-gray-500 dark:text-gray-400">→ Heutigen Status abfragen</span>
                            <code class="block text-violet-600 dark:text-violet-400 mt-2">?action=week</code>
                            <span class="text-gray-500 dark:text-gray-400">→ Aktuelle Woche abfragen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Passwort ändern</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aktualisieren Sie Ihr Anmeldepasswort.</p>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aktuelles Passwort</label>
                    <div class="mt-1">
                        <input type="password" name="current_password" id="current_password" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md border p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Neues Passwort</label>
                    <div class="mt-1">
                        <input type="password" name="new_password" id="new_password" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md border p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Neues Passwort bestätigen</label>
                    <div class="mt-1">
                        <input type="password" name="confirm_password" id="confirm_password" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md border p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-end">
                        <button type="submit" name="change_password" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Passwort ändern
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                <i class="material-icons text-gray-500 dark:text-gray-400">info</i>
                Über die App
            </h2>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Homeoffice Tracker</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Version 1.2.0</p>
                </div>
                <div class="p-3 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30">
                    <i class="material-icons text-3xl">work_outline</i>
                </div>
            </div>
            
            <div class="space-y-3">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tastenkürzel</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Dark Mode</span>
                        <kbd class="px-2 py-1 rounded bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-mono">Ctrl + D</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Dashboard</span>
                        <kbd class="px-2 py-1 rounded bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-mono">Ctrl + H</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Planung</span>
                        <kbd class="px-2 py-1 rounded bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-mono">Ctrl + B</kbd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
