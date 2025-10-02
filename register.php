<?php
require_once 'config.php';
require_once 'user_functions.php';

// Fehler- und Erfolgsmeldungen
$message = "";

// Rechenaufgabe für die Challenge generieren
session_start();
if (!isset($_SESSION['captcha_num1'])) {
    $_SESSION['captcha_num1'] = rand(1, 9);
    $_SESSION['captcha_num2'] = rand(1, 9);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha_answer = (int)($_POST['captcha'] ?? 0);

    // 1. Validierung der Eingaben (unverändert)
    $expected = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
    if ($captcha_answer !== $expected) {
        $message = "❌ Die Sicherheitsfrage wurde falsch beantwortet.";
    } elseif (empty($username) || empty($password)) {
        $message = "❌ Bitte alle Felder ausfüllen.";
    } else {
        // 2. NEU: Aufruf der zentralen Registrierungsfunktion
        $registration_success = register_user($pdo, $username, $password);

        // 3. NEU: Auswertung des booleschen Rückgabewertes der Funktion
        if ($registration_success) {
            $message = "✅ Registrierung erfolgreich! Ihr Konto wurde angelegt und wartet auf Freischaltung durch einen Administrator.";
            }
        else {
            // Wenn die Funktion `false` zurückgibt, war der Benutzername bereits vergeben.
            $message = "❌ Dieser Benutzername ist bereits vergeben. Bitte wählen Sie einen anderen.";
        }
    }

    // 4. Neue Challenge für nächsten Versuch generieren (unverändert)
    $_SESSION['captcha_num1'] = rand(1, 9);
    $_SESSION['captcha_num2'] = rand(1, 9);
}


?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>CRMNetworking - Registrierung</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-3">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center">Registrierung</h3>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Sicherheitsfrage: Wieviel ist <?= $_SESSION['captcha_num1'] ?> + <?= $_SESSION['captcha_num2'] ?> ?
                            </label>
                            <input type="number" class="form-control" name="captcha" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                    </form>

                    <p class="text-muted small mt-3 text-center">
                        Hinweis: Neue Accounts werden zunächst auf <b>inactive</b> gesetzt und müssen freigeschaltet werden.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
