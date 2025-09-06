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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeoffice Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js"></script>
    <script src="/assets/js/script.js"></script>
</head>
<body data-theme="light">
    <!-- Sidebar -->
    <ul id="slide-out" class="sidenav sidenav-fixed">
        <li>
            <div class="user-view">
                <div class="background blue darken-3">
                </div>
                <a href="index.php"><span class="white-text name">Homeoffice Tracker</span></a>
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="white-text email"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <?php endif; ?>
            </div>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="index.php" class="waves-effect"><i class="material-icons">home</i>Dashboard</a></li>
            <li><a href="booking.php" class="waves-effect"><i class="material-icons">event_note</i>Buchung</a></li>
            <li><a href="calendar.php" class="waves-effect"><i class="material-icons">calendar_today</i>Kalender</a></li>
            <li><a href="evaluation.php" class="waves-effect"><i class="material-icons">assessment</i>Auswertung</a></li>
            <li><a href="settings.php" class="waves-effect"><i class="material-icons">settings</i>Einstellungen</a></li>
            <li><div class="divider"></div></li>
            <li>
                <a class="waves-effect" onclick="toggleTheme()">
                    <i class="material-icons">brightness_medium</i>
                    Theme ändern
                </a>
            </li>
            <li>
                <a href="login.php?logout=1" class="waves-effect">
                    <i class="material-icons">exit_to_app</i>
                    Abmelden
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Top navbar for mobile -->
    <div class="navbar-fixed hide-on-large-only">
        <nav class="blue darken-3">
            <div class="nav-wrapper">
                <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                <a href="index.php" class="brand-logo center">Homeoffice Tracker</a>
            </div>
        </nav>
    </div>

    <main>
        <div class="container">
