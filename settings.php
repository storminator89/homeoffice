<?php
require_once 'database.php';
$db = new Database();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quota'])) {
        $quota = (int)$_POST['quota'];
        if ($quota >= 0 && $quota <= 100) {
            $db->setSetting('homeoffice_quota', $quota);
            $success = 'Einstellungen gespeichert.';
        } else {
            $error = 'Quote muss zwischen 0 und 100 liegen.';
        }
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

include 'templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Einstellungen</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Verwalten Sie Ihre Homeoffice-Präferenzen.</p>
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

                <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-end">
                        <button type="submit" name="quota" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Speichern
                        </button>
                    </div>
                </div>
            </form>
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
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Über die App</h2>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Version</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">1.1.0 (Dark Mode Edition)</p>
                </div>
                <div class="text-indigo-600 dark:text-indigo-400">
                    <i class="material-icons text-3xl">work_outline</i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
