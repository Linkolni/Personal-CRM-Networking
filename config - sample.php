<?php
/**
 * Globale Konfigurationsdatei
 * Hier liegen sensible Daten wie API Keys und Datenbankzugang.
 * WICHTIG: Stelle sicher, dass diese Datei NICHT öffentlich erreichbar ist
 * (z. B. außerhalb des Webroots speichern oder per .htaccess schützen).
 */

// ChatGPT API Key
define('OPENAI_API_KEY', '<API KEY for CHATGPT');

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', '<name of database>');
define('DB_USER', '<username>');
define('DB_PASS', '<userpasswordS');

//Company Logo und Name
define('COMPANY_NAME', 'Solutor Personal CRM'); //Name der Firma oder Applikation
define('COMPANY_LOGO', 'companylogo.png'); // Pfad zum Logo-Bild
define('COMPANY_BACKGROUNDCOLOR', '#0A1E38'); //Hintergrundfarbe
define('COMPANY_COLOR', '#FFFFFF'); //Schriftfarbe

//Impressum und Co
define('COMPANY_IMPRESSUM', 'templates/impressum.htm'); //Pfad zum Impressum, keine Rechtliche Beratung!
define('COMPANY_DATENSCHUTZ', 'templates/datenschutz.htm'); //Pfad zur Datenschutzerklärung, keine Rechtliche Beratung!


// PDO Datenbankverbindung
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ Fehler bei der Datenbankverbindung: " . $e->getMessage());
}