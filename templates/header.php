<?php
session_start();

// Redirect to login if not authenticated
$publicPages = ['login.php'];
$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && !in_array($currentPage, $publicPages)) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeoffice Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js"></script>
    <script src="assets/js/app.js"></script>
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
    </script>
    <style>
        /* Custom scrollbar for webkit */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #4b5563; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        
        .material-icons { vertical-align: middle; }
        
        /* Smooth page transitions */
        .page-transition { animation: pageIn 0.3s ease-out; }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Button press effect */
        .btn-press:active { transform: scale(0.97); }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-200 page-transition">

    <!-- Navigation -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50 border-b dark:border-gray-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php" class="flex items-center gap-2 font-bold text-xl group">
                            <div class="p-1.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                                <i class="material-icons">work_outline</i>
                            </div>
                            <span class="gradient-text">Homeoffice</span>
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            Dashboard
                        </a>
                        <a href="booking.php" class="<?php echo $currentPage == 'booking.php' ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            Planung
                        </a>
                        <a href="calendar.php" class="<?php echo $currentPage == 'calendar.php' ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            Kalender
                        </a>
                        <a href="evaluation.php" class="<?php echo $currentPage == 'evaluation.php' ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            Auswertung
                        </a>
                        <a href="webhooks.php" class="<?php echo $currentPage == 'webhooks.php' ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            Webhooks
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center gap-4">
                    <button onclick="toggleTheme()" class="p-2 rounded-full text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none transition-colors duration-200">
                        <i class="material-icons theme-toggle-icon">brightness_medium</i>
                    </button>
                    <div class="ml-3 relative group">
                        <button type="button" class="bg-white dark:bg-gray-700 rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 items-center gap-2 transition-colors duration-200" id="user-menu-button">
                            <span class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </span>
                            <span class="text-gray-700 dark:text-gray-200 font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                        </button>
                        <!-- Dropdown -->
                        <div class="hidden group-hover:block absolute right-0 top-full pt-2 w-48 z-50">
                            <div class="rounded-md shadow-lg py-1 bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none border dark:border-gray-600">
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Einstellungen</a>
                                <a href="login.php?logout=1" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">Abmelden</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="-mr-2 flex items-center sm:hidden gap-2">
                    <button onclick="toggleTheme()" class="p-2 rounded-full text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none transition-colors duration-200">
                        <i class="material-icons theme-toggle-icon">brightness_medium</i>
                    </button>
                    <a href="settings.php" class="p-2 rounded-md text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors duration-200">
                        <span class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Nav -->
    <div class="sm:hidden fixed bottom-0 left-0 w-full bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-around py-2 z-50 pb-safe transition-colors duration-200">
        <a href="index.php" class="flex flex-col items-center p-2 <?php echo $currentPage == 'index.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'; ?>">
            <i class="material-icons">dashboard</i>
            <span class="text-xs mt-1">Home</span>
        </a>
        <a href="booking.php" class="flex flex-col items-center p-2 <?php echo $currentPage == 'booking.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'; ?>">
            <i class="material-icons">calendar_today</i>
            <span class="text-xs mt-1">Planung</span>
        </a>
        <a href="calendar.php" class="flex flex-col items-center p-2 <?php echo $currentPage == 'calendar.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'; ?>">
            <i class="material-icons">date_range</i>
            <span class="text-xs mt-1">Kalender</span>
        </a>
        <a href="evaluation.php" class="flex flex-col items-center p-2 <?php echo $currentPage == 'evaluation.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'; ?>">
            <i class="material-icons">assessment</i>
            <span class="text-xs mt-1">Stats</span>
        </a>
    </div>
    <?php endif; ?>

    <main class="flex-1 pb-20 sm:pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Toast Container -->
            <div id="toast-container" class="fixed bottom-20 right-4 z-50 flex flex-col items-end sm:bottom-4"></div>
