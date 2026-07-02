<?php
/**
 * config_environment.example.php
 * Vorlage für config_environment.php (siehe itdesign.md Abschnitt 10) - Datei kopieren, umbenennen zu
 * config_environment.php und mit echten Werten füllen. config_environment.php selbst ist gitignored und
 * darf nie mit echten Werten committet werden.
 */

// Umgebung (development | production)
define('ENVIRONMENT', 'development');
// Basis-URL der Anwendung ohne / am Ende
define('BASE_URL', 'http://localhost/crm.solutor.de');

// Migrations-Tool (config/migrations.php) - Zugangsdaten und Freischaltung.
// Zufälliges, langes Passwort verwenden (z. B. `php -r "echo bin2hex(random_bytes(24));"`), NIE den
// Wert aus dieser Beispieldatei oder aus der Git-Historie übernehmen.
// MIGRATIONS_TOOL_ENABLED auf Produktivsystemen auf false lassen, außer für die Dauer eines bewussten
// Migrationslaufs kurz aktivieren und danach wieder deaktivieren.
define('MIGRATION_PASSWORD', 'CHANGE_ME');
define('MIGRATIONS_TOOL_ENABLED', false);
