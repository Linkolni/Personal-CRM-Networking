<?php
/**
 * TranslationService.php - Lädt Sprachdateien (PHP-Arrays) und übersetzt Keys.
 * Übernommen aus bukido.solutor.de/app/Services/TranslationService.php.
 * Für den ersten Release ist nur 'de' vorhanden (siehe itdesign.md Abschnitt 10).
 */
class TranslationService
{
    private static array $cache = [];

    public function __construct(
        private string $currentLang,
        private string $fallbackLang = 'de',
        private string $basePath = __DIR__ . '/../../resources/lang'
    ) {
    }

    public function t(string $key, array $params = []): string
    {
        $dictCurrent  = $this->loadDictionary($this->currentLang);
        $dictFallback = $this->loadDictionary($this->fallbackLang);

        $text = $dictCurrent[$key] ?? ($dictFallback[$key] ?? $key);

        foreach ($params as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', (string)$value, $text);
        }

        return $text;
    }

    private function loadDictionary(string $lang): array
    {
        if (isset(self::$cache[$lang])) {
            return self::$cache[$lang];
        }

        $file = $this->basePath . '/' . $lang . '.php';

        if (file_exists($file)) {
            $data = require $file;
            self::$cache[$lang] = is_array($data) ? $data : [];
        } else {
            self::$cache[$lang] = [];
        }

        return self::$cache[$lang];
    }
}
