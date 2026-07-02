<?php
/**
 * RateLimitAttempt.php - Generischer Anfrage-Zähler für RateLimitService, nutzt bewusst dieselbe
 * Tabelle wie LoginAttempt (identifier/attempts/last_attempt/locked_until ist generisch genug für
 * beide Zwecke) statt einer eigenen Migration - siehe itdesign.md Abschnitt 4/14.
 * Namensraum der Identifier: "ratelimit|<ip>" (getrennt von LoginAttempts "<username>|<ip>" und
 * migrations.php' "migrations_tool|<ip>").
 */
class RateLimitAttempt extends BaseModel
{
    protected string $table = 'login_attempts';

    public function isLocked(string $identifier): bool
    {
        $row = $this->db->fetchOne("SELECT locked_until FROM login_attempts WHERE identifier = ?", [$identifier]);
        return $row !== null && $row['locked_until'] !== null && strtotime($row['locked_until']) > time();
    }

    /**
     * Erhöht den Zähler für $identifier; setzt ihn zurück auf 1, wenn der letzte Versuch länger als
     * $windowSeconds zurückliegt (gleitendes Zeitfenster ohne separate Spalte für den Fensterstart).
     *
     * @return int Zähler nach dieser Anfrage.
     */
    public function recordRequest(string $identifier, int $windowSeconds): int
    {
        $this->db->execute(
            "INSERT INTO login_attempts (identifier, attempts, last_attempt) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                attempts = IF(last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND), 1, attempts + 1),
                last_attempt = NOW()",
            [$identifier, $windowSeconds]
        );

        $row = $this->db->fetchOne("SELECT attempts FROM login_attempts WHERE identifier = ?", [$identifier]);
        return (int) ($row['attempts'] ?? 0);
    }

    public function lock(string $identifier, string $lockedUntil): void
    {
        $this->db->execute("UPDATE login_attempts SET locked_until = ? WHERE identifier = ?", [$lockedUntil, $identifier]);
    }
}
