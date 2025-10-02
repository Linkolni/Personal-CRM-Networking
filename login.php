<?php
// =========================================================================
// 1. INITIALISIERUNG
// =========================================================================
// Fehleranzeige für die Entwicklungsphase aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session starten und notwendige Dateien einbinden
session_start();
require_once 'config.php';       // Stellt die DB-Verbindung ($pdo) bereit
require_once 'user_functions.php'; // Enthält alle unsere Benutzer- und Sicherheitsfunktionen

$message = ""; // Variable für Feedback-Nachrichten an den Benutzer

// =========================================================================
// 2. BEREITS EINGELOGGTE BENUTZER WEITERLEITEN
//    Wenn der Benutzer bereits eine aktive Session hat, wird er direkt
//    zur Hauptanwendung weitergeleitet.
// =========================================================================
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// =========================================================================
// 3. BRUTE-FORCE-SCHUTZ
// =========================================================================
// Die IP-Adresse des aktuellen Benutzers sicher abrufen.
$user_ip = get_user_ip_address();

// Prüfen, ob diese IP-Adresse aufgrund zu vieler Fehlversuche temporär gesperrt ist.
// Dies geschieht, BEVOR wir überhaupt versuchen, das Formular zu verarbeiten.
if (is_ip_locked($pdo, $user_ip)) {
    // Wenn die IP gesperrt ist, wird eine feste Nachricht angezeigt.
    // Der Code zur Formularverarbeitung wird komplett übersprungen.
    $message = "🔒 Zu viele fehlgeschlagene Versuche. Bitte warten Sie 5 Minuten, bevor Sie es erneut versuchen.";
} else {
    // =========================================================================
    // 4. FORMULARVERARBEITUNG (NUR WENN IP NICHT GESPERRT IST)
    // =========================================================================
    // Prüfen, ob das Formular mit der POST-Methode abgeschickt wurde.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Benutzereingaben sicher aus dem Formular lesen und Leerzeichen entfernen.
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validierung: Sind Benutzername und Passwort leer?
        if (empty($username) || empty($password)) {
            $message = "❌ Bitte Benutzername und Passwort eingeben.";
        } else {
            // Versuch, den Benutzer mit der Funktion aus unserer Bibliothek anzumelden.
            if (login_user($pdo, $username, $password)) {
                // FALL 1: ANMELDUNG ERFOLGREICH (Authentifizierung ok)
                // Die Session-Variablen (user_id, role) wurden von login_user() gesetzt.

                // Nun prüfen wir die Berechtigung (Autorisierung): Ist der Benutzer auch aktiv?
                if ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'admin') {
                    // Ja, Benutzer ist aktiv -> zur Hauptanwendung weiterleiten.
                    header("Location: index.php");
                    exit;
                } else {
                    // Benutzer ist authentifiziert, aber nicht autorisiert (z.B. Rolle 'inactive').
                    // Wir zerstören die gerade erstellte Session sofort wieder.
                    logout_user();
                    $message = "⚠️ Ihr Account ist noch inaktiv und muss erst von einem Administrator freigeschaltet werden.";
                }
            } else {
                // FALL 2: ANMELDUNG FEHLGESCHLAGEN
                // Die Funktion login_user() hat 'false' zurückgegeben.

                //Wir warten erstmal 2 Sekunden            
                sleep(2);

                // WICHTIG: Wir protokollieren diesen fehlgeschlagenen Versuch für unsere Brute-Force-Logik.
                log_failed_login_attempt($pdo, $user_ip);

                // Eine allgemeine Fehlermeldung ausgeben. Aus Sicherheitsgründen verraten wir nicht,
                // ob der Benutzername oder das Passwort falsch war.
                $message = "❌ Ungültiger Benutzername oder Passwort.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>CRMNetworking - Login</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <style>
        /* Setzt die Hintergrundfarbe dynamisch aus der config.php.
           Die CSS-Variable wird im Body gesetzt und hier verwendet. */
        body {
            background-color: var(--company-bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        .login-card {
            background-color: #ffffff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>

</head>

<body style="--company-bg-color: <?= COMPANY_BACKGROUNDCOLOR ?>;">

<div class="login-container text-center">
    
    <!-- 1. Logo -->
    <img src="<?= COMPANY_LOGO ?>" alt="Logo" class="mb-3" style="max-height: 80px; width: auto;">
    
    <!-- 2. Firmenname -->
    <h1 class="h3 mb-4 fw-normal" style="color: <?= COMPANY_COLOR ?>;"><?= COMPANY_NAME ?></h1>
    
    <!-- 3. Login-Fenster (Card) -->
    <div class="card login-card p-4">
        <div class="card-body">
            
            <h2 class="card-title h4 mb-4">Login</h2>

            <!-- Fehlermeldung wird nur angezeigt, wenn sie existiert. -->
            <?php if (!empty($message)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Login-Formular -->
            <form action="login.php" method="post">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Benutzername" required autofocus>
                    <label for="username">Benutzername</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Passwort" required>
                    <label for="password">Passwort</label>
                </div>
                <button class="w-100 btn btn-lg btn-primary" type="submit">Anmelden</button>
            </form>
        </div>
    </div>
    
    <!-- Copyright-Hinweis im Footer-Stil -->
    <p class="mt-4 text-white-50">&copy; <?= date('Y') ?> Solutor - data and informataion<br>
    <a href="https://solutor.de/themes/impressum.htm">Impressum</a> - <a href="https://solutor.de/themes/datenschutz.htm">Datenschutz</a></p>

</div>

</body>
</html>