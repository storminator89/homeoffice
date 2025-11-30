<?php
session_start();
include 'database.php';

$db = new Database();
$hasUsers = $db->hasUsers();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            $userId = $db->validateLogin($username, $password);
            if ($userId) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Ungültige Anmeldedaten';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['register']) && !$hasUsers) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            if (strlen($password) < 6) {
                throw new Exception('Passwort muss mindestens 6 Zeichen lang sein');
            }
            $db->createUser($username, $password);
            $success = 'Registrierung erfolgreich. Sie können sich jetzt anmelden.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de" class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Homeoffice Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#10b981',
                    }
                }
            }
        }
        
        // Check theme on load
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center text-indigo-600 dark:text-indigo-400">
            <i class="material-icons text-5xl">work_outline</i>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Homeoffice Tracker
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            <?php echo $hasUsers ? 'Bitte melden Sie sich an' : 'Erstellen Sie den ersten Benutzer'; ?>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10 border dark:border-gray-700 transition-colors duration-200">
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

            <form class="space-y-6" method="POST">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Benutzername
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-gray-400 text-sm">person</i>
                        </div>
                        <input id="username" name="username" type="text" required class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md h-10 border px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Passwort
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-gray-400 text-sm">lock</i>
                        </div>
                        <input id="password" name="password" type="password" required class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md h-10 border px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <?php if (!$hasUsers): ?>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mindestens 6 Zeichen</p>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" name="<?php echo $hasUsers ? 'login' : 'register'; ?>" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <?php echo $hasUsers ? 'Anmelden' : 'Registrieren'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
