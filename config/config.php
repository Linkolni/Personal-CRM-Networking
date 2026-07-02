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



// Anwendungs-Name (White-Label-fähig, siehe concept.md 4.11)
define('APP_NAME', 'Personal CRM');
define('APP_LOGO', 'images/android-chrome-192x192.png');
define('APP_BG_COLOR', '#1a1a1a');
define('APP_FONT_COLOR', '#ffffff');

// Rechtliche Seiten (concept.md 4.11)
define('IMPRESSUM_URL', 'https://cdn.solutor.de/themes/impressum.htm');
define('DATENSCHUTZ_URL', 'https://cdn.solutor.de/themes/datenschutz.htm');

// MIGRATION_PASSWORD/MIGRATIONS_TOOL_ENABLED werden in der gitignored config_environment.php definiert
// (Phase 6: zuvor hier als Klartext-Konstante, dadurch über das öffentliche Git-Repo einsehbar).

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
session_name('CRM_SOLUTOR_SESSION');

// Session-ID-Sicherheit
ini_set('session.use_strict_mode', 1);        // Nur vom Server generierte IDs akzeptieren
//obsolet: ini_set('session.sid_length', 48);            // Längere Session-IDs (Standard: 26)
//obsolet: ini_set('session.sid_bits_per_character', 6); // Mehr Entropie



