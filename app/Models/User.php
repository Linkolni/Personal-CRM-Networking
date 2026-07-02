<?php
/**
 * User.php - Model für Benutzerkonten (Login, Rollen, Persona, KI-Kennzahlen).
 * Siehe concept.md Abschnitt 3/7 und itdesign.md Abschnitt 7.
 */
class User extends BaseModel
{
    protected string $table = 'users';

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public function count(): int
    {
        $row = $this->db->fetchOne("SELECT COUNT(*) AS c FROM users");
        return (int)($row['c'] ?? 0);
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM users ORDER BY id ASC");
    }

    /**
     * Legt einen neuen Benutzer an.
     * Der erste Account im System wird automatisch 'admin' (siehe concept.md 4.2),
     * alle weiteren starten 'inactive' bis ein Admin sie freischaltet.
     */
    public function create(string $username, string $password): int
    {
        $role = $this->count() === 0 ? 'admin' : 'inactive';

        return $this->db->insert(
            "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)",
            [$username, password_hash($password, PASSWORD_DEFAULT), $role]
        );
    }

    public function updateRole(int $id, string $role): bool
    {
        return $this->db->execute("UPDATE users SET role = ? WHERE id = ?", [$role, $id]) > 0;
    }

    public function updatePersona(int $id, string $persona): bool
    {
        return $this->db->execute("UPDATE users SET persona = ? WHERE id = ?", [$persona, $id]) > 0;
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->db->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [password_hash($password, PASSWORD_DEFAULT), $id]
        ) > 0;
    }

    public function addTokenUsage(int $id, int $tokensSent, int $tokensGenerated, float $tokensCost): bool
    {
        return $this->db->execute(
            "UPDATE users SET tokens_sent = tokens_sent + ?, tokens_generated = tokens_generated + ?, tokens_cost = tokens_cost + ?
             WHERE id = ?",
            [$tokensSent, $tokensGenerated, $tokensCost, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        // interactions/persons werden über FK CASCADE entfernt
        return $this->db->execute("DELETE FROM users WHERE id = ?", [$id]) > 0;
    }
}
