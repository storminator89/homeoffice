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

include 'templates/header.php';
?>

<div class="login-wrapper">
    <div class="row">
        <div class="col s12 m8 l6 offset-m2 offset-l3">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Anmeldung</span>
                    
                    <?php if ($error): ?>
                        <div class="red-text"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="green-text"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <?php if (!$hasUsers): ?>
                    <div class="row">
                        <div class="col s12">
                            <ul class="tabs">
                                <li class="tab col s6"><a class="active" href="#login">Anmelden</a></li>
                                <li class="tab col s6"><a href="#register">Registrieren</a></li>
                            </ul>
                        </div>
                        
                        <!-- Login Form -->
                        <div id="login" class="col s12">
                    <?php endif; ?>
                            <form method="POST" class="row">
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input id="username" name="username" type="text" required>
                                    <label for="username">Benutzername</label>
                                </div>
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">lock</i>
                                    <input id="password" name="password" type="password" required>
                                    <label for="password">Passwort</label>
                                </div>
                                <div class="col s12 center-align">
                                    <button class="btn waves-effect waves-light blue darken-3" type="submit" name="login">
                                        Anmelden
                                        <i class="material-icons right">send</i>
                                    </button>
                                </div>
                            </form>
                    <?php if (!$hasUsers): ?>
                        </div>

                        <!-- Register Form -->
                        <div id="register" class="col s12">
                            <form method="POST" class="row">
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input id="reg_username" name="username" type="text" required>
                                    <label for="reg_username">Benutzername</label>
                                </div>
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">lock</i>
                                    <input id="reg_password" name="password" type="password" required>
                                    <label for="reg_password">Passwort</label>
                                    <span class="helper-text">Mindestens 6 Zeichen</span>
                                </div>
                                <div class="col s12 center-align">
                                    <button class="btn waves-effect waves-light blue darken-3" type="submit" name="register">
                                        Registrieren
                                        <i class="material-icons right">person_add</i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>