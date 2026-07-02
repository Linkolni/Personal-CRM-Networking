<?php
/**
 * NavigationHelper.php - Liest config/navigation.php, wertet visible-Closures aus
 * und liefert ein gefiltertes Array für header.php (siehe itdesign.md Abschnitt 10).
 */
class NavigationHelper
{
    private static ?array $cache = null;

    public static function getNav(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $config = require __DIR__ . '/../../config/navigation.php';

        self::$cache = array_values(array_filter($config, function ($item) {
            return !isset($item['visible']) || ($item['visible'])();
        }));

        return self::$cache;
    }

    public static function isActive(array $item): bool
    {
        $currentPage = $_GET['page'] ?? 'dashboard';
        return isset($item['page']) && $item['page'] === $currentPage;
    }

    public static function url(array $item): string
    {
        if (empty($item['page'])) {
            return '#';
        }

        $params = ['page' => $item['page']];
        if (!empty($item['action'])) {
            $params['action'] = $item['action'];
        }

        return BASE_URL . '/index.php?' . http_build_query($params);
    }
}
