<?php
session_start();
require_once 'config.php';
require_once 'user_functions.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Benutzerdaten für die Passwortprüfung und Formularanzeige vorab laden
$user = get_user_by_id($pdo, $user_id);
if (!$user) {
    die('Benutzer nicht gefunden!');
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $persona = $_POST['persona'] ?? '';
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $password_to_update = null;
    $password_change_attempted = !empty($new_password) || !empty($confirm_password);

    // Nur validieren, wenn eine Passwortänderung versucht wurde
    if ($password_change_attempted) {
        // 1. Altes Passwort verifizieren
        if (!password_verify($old_password, $user['password_hash'])) {
            $error_message = 'Das aktuelle Passwort ist nicht korrekt.';
        }
        // 2. Neue Passwörter auf Übereinstimmung prüfen
        elseif ($new_password !== $confirm_password) {
            $error_message = 'Die neuen Passwörter stimmen nicht überein.';
        }
        // 3. Mindestlänge für neues Passwort prüfen
        elseif (strlen($new_password) < 8) {
            $error_message = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
        } else {
            // Alle Prüfungen erfolgreich, Passwort kann geändert werden
            $password_to_update = $new_password;
        }
    }

    // Update nur durchführen, wenn kein Passwort-Fehler aufgetreten ist
    if (empty($error_message)) {
        if (update_user_profile($pdo, $user_id, $persona, $password_to_update)) {
            $success_message = 'Ihr Profil wurde erfolgreich aktualisiert.';
            if ($password_to_update) {
                $success_message .= ' Ihr Passwort wurde geändert.';
            }
        } else {
            $error_message = 'Es ist ein Fehler beim Speichern aufgetreten.';
        }
    }

    // Benutzerdaten neu laden, um die aktualisierte Persona anzuzeigen
    $user = get_user_by_id($pdo, $user_id);
}

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Profil - <?= COMPANY_NAME ?? 'Personal CRM' ?></title>

    <!-- Bootstrap CSS via CDN -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/custom.css"> <!-- Ihre eigene CSS-Datei -->
</head>

<body>

    <?php include 'templates/_header.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Profil bearbeiten</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <!-- Read-only Sektion -->
                    <fieldset disabled>
                        <div class="mb-3">
                            <label for="username" class="form-label">Benutzername</label>
                            <input type="text" id="username" class="form-control"
                                value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tokens gesendet</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($user['tokens_sent']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tokens generiert</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($user['tokens_generated']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kosten</label>
                                <div class="input-group">
                                    <span class="input-group-text">€</span>
                                    <input type="text" class="form-control"
                                        value="<?php echo number_format($user['tokens_cost'], 2, ',', '.'); ?>">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <hr class="my-4">

                    <!-- Editierbare Sektion -->
                    <div class="mb-3">
                        <label for="persona" class="form-label">Ihre Persona</label>
                        <textarea class="form-control" id="persona" name="persona" rows="5"
                            placeholder="Beschreiben Sie hier die Persona, die die KI für Ihre Antwortvorschläge verwenden soll. Z.B. 'Ich bin ein förmlicher Geschäftsmann, der direkt auf den Punkt kommt.'"><?php echo htmlspecialchars($user['persona'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="old_password" class="form-label">Aktuelles Passwort</label>
                        <input type="password" class="form-control" id="old_password" name="old_password"
                            autocomplete="current-password" placeholder="Nur ausfüllen, um das Passwort zu ändern">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Neues Passwort</label>
                        <input type="password" class="form-control" id="new_password" name="new_password"
                            autocomplete="new-password" placeholder="Mindestens 8 Zeichen">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Neues Passwort wiederholen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Profil speichern</button>
                    <a href="index.php" class="btn btn-outline-dark">Abbrechen/zurück</a>
            </div>
            </form>
        </div>
    </div>
    </div>

    <script src="bootstrap.bundle.min.js"></script>
    <?php include 'templates/_footer.php'; ?>
</body>

</html>