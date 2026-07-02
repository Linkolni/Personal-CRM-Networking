<?php
/**
 * RateLimitService.php - Generischer IP-/Bot-Schutz (siehe itdesign.md Abschnitt 3/4/14).
 * Wird sehr früh in index.php aufgerufen, vor jeglicher Verarbeitung.
 *
 * Übernommen aus bukido.solutor.de/app/Services/RateLimitService.php, aber DB-gestützt statt
 * session-basiert: Die bukido-Fassung speichert Zähler in $_SESSION, was gegen genau die Angreifer
 * wirkungslos ist, die sie abwehren soll (Bots/Skripte ohne Cookie-Unterstützung starten pro Anfrage
 * eine neue Session, der Zähler kommt nie über 1 hinaus). Diese Fassung zählt daher pro IP über
 * RateLimitAttempt (Tabelle login_attempts), analog zum bestehenden Login-Brute-Force-Schutz.
 */
class RateLimitService
{
    private const WINDOW_SECONDS = 60;
    private const MAX_REQUESTS_PER_WINDOW = 120;
    private const MAX_BOT_REQUESTS_PER_WINDOW = 10;
    private const BAN_MINUTES = 15;

    private const SUSPICIOUS_USER_AGENT_PATTERNS = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java', 'node',
        'ahrefs', 'semrush', 'dotbot', 'mj12', 'petalbot', 'youbot', 'blexbot',
        'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'sogou',
        'exabot', 'ia_archiver', 'nikto', 'sqlmap', 'nmap', 'metasploit',
    ];

    /**
     * Prüft das Rate Limit für die aktuelle Anfrage und beendet den Request mit HTTP 429,
     * falls das Limit überschritten oder die IP aktuell gesperrt ist.
     */
    public static function check(): void
    {
        $ip = self::getClientIp();
        $identifier = 'ratelimit|' . $ip;
        $model = new RateLimitAttempt();

        if ($model->isLocked($identifier)) {
            self::reject();
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $limit = self::isSuspiciousUserAgent($userAgent) ? self::MAX_BOT_REQUESTS_PER_WINDOW : self::MAX_REQUESTS_PER_WINDOW;

        $attempts = $model->recordRequest($identifier, self::WINDOW_SECONDS);

        if ($attempts > $limit) {
            $lockUntil = date('Y-m-d H:i:s', strtotime('+' . self::BAN_MINUTES . ' minutes'));
            $model->lock($identifier, $lockUntil);
            self::reject();
        }
    }

    private static function reject(): void
    {
        http_response_code(429);
        header('Retry-After: ' . (self::BAN_MINUTES * 60));
        exit('Zu viele Anfragen. Bitte in einigen Minuten erneut versuchen.');
    }

    private static function isSuspiciousUserAgent(string $userAgent): bool
    {
        $lowerAgent = strtolower($userAgent);

        foreach (self::SUSPICIOUS_USER_AGENT_PATTERNS as $pattern) {
            if ($lowerAgent === '' || stripos($lowerAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ermittelt die Client-IP, berücksichtigt gängige Proxy-/CDN-Header.
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
