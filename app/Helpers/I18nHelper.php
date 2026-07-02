<?php
/**
 * I18nHelper.php - Globale t()-Hilfsfunktion für Übersetzungen.
 * Übernommen aus bukido.solutor.de/app/Helpers/I18nHelper.php.
 */

if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        $translator = App::get('translator');

        if (!$translator) {
            return $key;
        }

        return $translator->t($key, $params);
    }
}
