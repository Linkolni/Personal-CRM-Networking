<?php
/**
 * index.php
 *
 * Dies ist der Haupteinstiegspunkt der Anwendung.
 *
 * AUFGABEN:
 * 1. Initialisierung: Startet die Session und bindet notwendige Bibliotheken ein.
 * 2. Sicherheitscheck: Stellt sicher, dass nur eingeloggte Benutzer Zugriff haben.
 * 3. Datenvorbereitung: Lädt die initiale Liste aller Personen für die linke Spalte.
 * 4. Darstellung: Ruft die Layout-Datei auf, die das HTML-Gerüst und die Daten zusammenfügt.
 */

// 1. Initialisierung
session_start();
require_once 'config.php';
require_once 'persons_functions.php'; // Unsere neue Funktionsbibliothek
require_once 'user_functions.php'; // Unsere neue Funktionsbibliothek

// 2. Sicherheitscheck
// Ruft die zentrale Funktion auf, um zu prüfen, ob der User angemeldet ist UND eine gültige Rolle hat.
if (!is_user_logged_in()) {
    // Wenn nicht, wird der User zum Login umgeleitet.
    header('Location: login.php');
    exit;
}

// 3. Datenvorbereitung für die erste Anzeige
// Wir holen alle Personen, die zum eingeloggten Benutzer gehören.
$persons = get_all_persons_for_user($pdo, $_SESSION['user_id']);

// Diese Variablen machen wir für das Template verfügbar.
$pageTitle = 'CRM Dashboard';
$username = $_SESSION['username'] ?? 'User'; // Falls der Username nicht in der Session ist

// 4. Darstellung
// Wir übergeben die Kontrolle an die Layout-Datei, die den Rest erledigt.
require_once 'templates/_layout.php';

// Nach diesem Punkt sollte kein Code mehr folgen. Die Arbeit hier ist getan.
?>
