<?php
/**
 * logout.php
 *
 * Beendet die aktuelle Benutzersession und leitet zur Startseite weiter.
 */

session_start();
require_once 'user_functions.php'; // Wir brauchen nur die User-Funktionen

// Ruft die saubere Logout-Funktion auf
logout_user();

// Leitet den Benutzer zur Startseite (index.php) weiter
header('Location: index.php');
exit;
