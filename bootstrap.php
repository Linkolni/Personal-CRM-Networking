<?php
/**
 * bootstrap.php - Klassen-Autoloading
 *
 * Lädt Models, Helpers, Services und Controllers automatisch aus app/,
 * damit Controller kein manuelles require_once schreiben müssen
 * (siehe itdesign.md Abschnitt 1).
 */

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/app/';

    $paths = [
        'Models/',
        'Helpers/',
        'Services/',
        'Controllers/',
    ];

    foreach ($paths as $path) {
        $file = $base_dir . $path . $class . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    if (!class_exists($class, false)) {
        throw new Exception(
            "Autoload-Fehler: Die Klasse '$class' konnte in den Verzeichnissen " .
            implode(', ', $paths) . " nicht gefunden werden. Bitte Dateibenennung (Case-Sensitivity!) prüfen."
        );
    }
});

// Funktions-Bibliotheken (keine Klassen) müssen weiterhin explizit geladen werden:
require_once __DIR__ . '/app/Helpers/I18nHelper.php';
