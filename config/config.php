<?php
/**
 * config.php - Haupt-Konfigurationsdatei
 * 
 * Beschreibung:
 * - Globale Konstanten
 * - Umgebungs-Einstellungen
 * - Session-Konfiguration
 */


// ============================================================
// Fehlerausgabe – nur lokal, nie auf Prod
// ============================================================
if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// ============================================================
// Applikations-eigenes Error-Log (statt Apache-Log)
// ============================================================
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/crm_error.log');

// Umgebung (development | production)
//wird in config_environment.php definiert!!!



// Anwendungs-Name
define('APP_NAME', 'CRM');
define('APP_LOGO', 'images/android-chrome-192x192.png');

// Migrations-Tool Passwortschutz, migration.php
define('MIGRATION_PASSWORD', 'crm_migrate_2026!');

// Session-Konfiguration
ini_set('session.cookie_httponly', 1); // XSS-Schutz
ini_set('session.use_strict_mode', 1); // Session-Fixation-Schutz
ini_set('session.cookie_samesite', 'Lax'); // CSRF-Schutz

// Nur bei HTTPS (Produktion)
if (ENVIRONMENT === 'production') {
    ini_set('session.cookie_secure', 1);
}

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Upload-Limits
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');

// Bootstrap
require_once __DIR__ . '/database.php';     // Datenbankverbindungzugriff
require_once __DIR__ . '/apikeyconfig.php'; // API-Keys, Logins und spezifische Konfigurationen


// ============================================================================
// ASSET_VERSION für Zwangsupdate bei CSS oder JS (Cachingproblem)
// ============================================================================
define('ASSET_VERSION', '20260403');


// ============================================================================
// SESSION-SICHERHEIT (VOR session_start()!)
// ============================================================================

// Session-Cookie-Sicherheit
ini_set('session.cookie_httponly', 1);        // Verhindert JavaScript-Zugriff
ini_set('session.use_only_cookies', 1);       // Nur Cookies, keine URL-Parameter
ini_set('session.cookie_samesite', 'Strict'); // CSRF-Schutz

// HTTPS-Cookie nur in Production (automatische Erkennung)
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
ini_set('session.cookie_secure', ENVIRONMENT === 'production' && $is_https ? 1 : 0);

// Session-Timeout (8 Stunden = 28800 Sekunden)
define('SESSION_TIMEOUT', 28800);
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);

// Session-Name ändern (versteckt Standard-PHP-Namen)
session_name('FIRE123123123_SESSION');

// Session-ID-Sicherheit
ini_set('session.use_strict_mode', 1);        // Nur vom Server generierte IDs akzeptieren
//obsolet: ini_set('session.sid_length', 48);            // Längere Session-IDs (Standard: 26)
//obsolet: ini_set('session.sid_bits_per_character', 6); // Mehr Entropie


// ============================================================================
// RECHNUNGSDATEN (B2C Deutschland, Kleinunternehmer ohne MwSt.)
// ============================================================================

// Verkäufer / Aussteller der Rechnung
define('INVOICE_SELLER_NAME',    'Alexander Volland');           // z. B. 'Max Mustermann'
define('INVOICE_SELLER_COMPANY', '');           // z. B. '' (leer lassen wenn kein Firmenname)
define('INVOICE_SELLER_STREET',  'Am Sportfeld 18');           // z. B. 'Musterstraße 1'
define('INVOICE_SELLER_ZIP',     '63110');           // z. B. '12345'
define('INVOICE_SELLER_CITY',    'Rodgau');           // z. B. 'Musterstadt'
define('INVOICE_SELLER_EMAIL',   'kontakt@bukido.de');           // z. B. 'info@example.com'
define('INVOICE_SELLER_PHONE',   '');           // z. B. '+49 123 456789' (optional, leer lassen)
define('INVOICE_SELLER_TAX_ID',  '044 878 32225');           // Steuernummer, z. B. '12/345/67890'

// Kleinunternehmer-Hinweis (§ 19 UStG) – erscheint auf der Rechnung
define('INVOICE_KLEINUNTERNEHMER_HINWEIS',
    'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.');

// Bankverbindung (optional, für Überweisungs-Hinweis)
define('INVOICE_BANK_IBAN',      'DE96 2022 0800 0040 2031 96');           // z. B. 'DE96 2022 0800 0040 2031 96'
define('INVOICE_BANK_BIC',       '');           // z. B. 'BELADEBEXXX'
define('INVOICE_BANK_NAME',      'Vivid Money');           // z. B. 'Berliner Sparkasse'

