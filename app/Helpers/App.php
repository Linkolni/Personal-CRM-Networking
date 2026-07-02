<?php
/**
 * App.php - Einfacher Service-Container (Registry-Pattern).
 * Übernommen aus bukido.solutor.de/app/Helpers/App.php.
 */
final class App
{
    private static array $services = [];

    public static function set(string $key, mixed $value): void
    {
        self::$services[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return self::$services[$key] ?? null;
    }
}
