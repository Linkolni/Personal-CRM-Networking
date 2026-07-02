<?php
/**
 * LoginAttempt.php - Brute-Force-Schutz auf Konto+IP-Ebene (siehe concept.md 4.1,
 * itdesign.md Abschnitt 4). identifier = "username|ip".
 */
class LoginAttempt extends BaseModel
{
    protected string $table = 'login_attempts';

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->db->fetchOne("SELECT * FROM login_attempts WHERE identifier = ?", [$identifier]);
    }

    public function recordFailedAttempt(string $identifier): void
    {
        $this->db->execute(
            "INSERT INTO login_attempts (identifier, attempts, last_attempt) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()",
            [$identifier]
        );
    }

    public function lock(string $identifier, string $lockedUntil): void
    {
        $this->db->execute(
            "UPDATE login_attempts SET locked_until = ? WHERE identifier = ?",
            [$lockedUntil, $identifier]
        );
    }

    public function resetAttempts(string $identifier): void
    {
        $this->db->execute("DELETE FROM login_attempts WHERE identifier = ?", [$identifier]);
    }
}
