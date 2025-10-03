<?php
// =========================================================================
// 1. INITIALISIERUNG UND SICHERHEIT
// =========================================================================
session_start();
require_once 'config.php';       // Stellt die DB-Verbindung ($pdo) bereit
require_once 'user_functions.php'; // Bindet unsere Bibliothek mit Benutzerfunktionen ein

// Zugriffskontrolle: Nur Benutzer mit der Rolle 'admin' dürfen diese Seite sehen.
// Wir nutzen unsere saubere, gekapselte Funktion is_user_logged_in().
if (!is_user_logged_in(['admin'])) {
    // Wenn die Bedingung nicht erfüllt ist, wird der Benutzer zum Login umgeleitet.
    header("Location: login.php");
    exit; // Wichtig: Beendet die Skriptausführung sofort nach der Umleitung.
}

// Variable für Feedback-Nachrichten nach einer Aktion (Löschen, Rolle ändern).
$message = "";

// =========================================================================
// 2. FORMULARVERARBEITUNG (POST-REQUESTS)
//    Dieser Block wird nur ausgeführt, wenn der Admin einen Button klickt.
// =========================================================================
// Prüfen, ob die Anfrage per POST kommt und die notwendigen Daten ('action', 'user_id') enthält.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['user_id'])) {
    // Die übermittelte Benutzer-ID sicher als Ganzzahl (integer) speichern.
    $userId = (int)$_POST['user_id'];
    // Die übermittelte Aktion (z.B. 'delete', 'admin') speichern.
    $action = $_POST['action'];

    // --- FALL 1: Benutzer soll gelöscht werden ---
    if ($action === "delete") {
        // Wir rufen die Funktion delete_user() aus unserer Bibliothek auf.
        // Die gesamte "DELETE FROM..."-Logik ist dort gekapselt.
        if (delete_user($pdo, $userId)) {
            // Wenn die Funktion 'true' zurückgibt, war die Aktion erfolgreich.
            $message = "🗑️ Benutzer mit der ID $userId wurde erfolgreich gelöscht.";
        } else {
            // Wenn die Funktion 'false' zurückgibt, ist ein Fehler aufgetreten.
            $message = "❌ Fehler beim Löschen des Benutzers.";
        }
    // --- FALL 2: Die Rolle des Benutzers soll geändert werden ---
    } elseif (in_array($action, ['user', 'admin', 'inactive'])) {
        // Wir prüfen, ob die 'action' eine gültige Rolle ist.
        // Wir rufen die Funktion change_user_role() aus unserer Bibliothek auf.
        if (change_user_role($pdo, $userId, $action)) {
            // Erfolgsmeldung bei Rückgabewert 'true'.
            $message = "✅ Rolle für Benutzer-ID $userId wurde erfolgreich auf '$action' geändert.";
        } else {
            // Fehlermeldung bei Rückgabewert 'false'.
            $message = "❌ Fehler beim Ändern der Rolle.";
        }
    }
}

// =========================================================================
// 3. DATEN FÜR DIE ANZEIGE LADEN
//    Diese Abfrage wird immer ausgeführt, um die aktuelle Benutzerliste anzuzeigen.
// =========================================================================
// Alle Benutzer aus der Datenbank abrufen und nach dem Erstellungsdatum sortieren.
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
// Das Ergebnis als assoziatives Array speichern.
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Adminbereich</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-center">👑 Adminbereich – Benutzerverwaltung</h2>

    <?php // Zeigt die Erfolgs- oder Fehlermeldung an, wenn eine Aktion ausgeführt wurde. ?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped shadow-sm bg-white">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Benutzername</th>
                <th>Rolle</th>
                <th>Registriert am</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php // Schleife durch alle geladenen Benutzer, um je eine Tabellenzeile zu erzeugen. ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <?php // Jede Aktion ist ein eigenes kleines Formular, das die user_id und die action übermittelt. ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button name="action" value="user" class="btn btn-success btn-sm">User</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button name="action" value="admin" class="btn btn-primary btn-sm">Admin</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button name="action" value="inactive" class="btn btn-secondary btn-sm">Inactive</button>
                        </form>
                        
                        <?php // Das Löschen-Formular hat eine zusätzliche JavaScript-Bestätigung. ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Soll der Benutzer ID <?= $user['id'] ?> wirklich gelöscht werden?');">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button name="action" value="delete" class="btn btn-danger btn-sm">Löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-center mt-3">
        <a href="index.php" class="btn btn-outline-dark">Zurück zur Anwendung</a>
        <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
    </div>
</div>
</body>
</html>
