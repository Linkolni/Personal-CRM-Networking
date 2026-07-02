<?php
/**
 * Interaction.php - Model für das Interaktionsprotokoll (siehe concept.md 4.8).
 *
 * Versionshistorie:
 * - getAllForUser() ergänzt für den Datenexport (concept.md 4.14, itdesign.md Abschnitt 11).
 */
class Interaction extends BaseModel
{
    protected string $table = 'interactions';

    /** Whitelist erlaubter Felder für create()/update() - Schutz vor Mass Assignment. */
    private const WRITABLE_FIELDS = ['interaction_date', 'interaction_type', 'memo'];

    public const TYPES = [
        'COFFEE_MEETING', 'EMAIL', 'LINKEDIN_MESSAGE', 'PHONE_CALL',
        'LUNCH', 'MEETING', 'CONFERENCE', 'OTHER',
    ];

    public function getAllForPerson(int $personId): array
    {
        return $this->db->query(
            "SELECT * FROM interactions WHERE person_id = ? ORDER BY interaction_date DESC, interaction_id DESC",
            [$personId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM interactions WHERE interaction_id = ?", [$id]);
    }

    /**
     * Alle Interaktionen eines Benutzers über alle Personen hinweg, ohne Personenfilter
     * (für den Datenexport, siehe concept.md 4.14, itdesign.md Abschnitt 11).
     */
    public function getAllForUser(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM interactions WHERE user_id = ? ORDER BY interaction_date DESC, interaction_id DESC",
            [$userId]
        );
    }

    /**
     * D5 (concept.md 4.12): Die $limit jüngsten Interaktionen über alle Personen eines
     * Benutzers, mit Personenname per JOIN (siehe itdesign.md Abschnitt 8).
     */
    public function getRecentForUser(int $userId, int $limit = 5): array
    {
        return $this->db->query(
            "SELECT i.*, p.first_name, p.last_name, p.person_id
             FROM interactions i
             JOIN persons p ON p.person_id = i.person_id
             WHERE i.user_id = ?
             ORDER BY i.interaction_date DESC, i.interaction_id DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }


    public function create(int $personId, int $userId, array $data): int
    {
        $fields = $this->filterWritableFields($data);
        $fields['person_id'] = $personId;
        $fields['user_id'] = $userId;

        $columns = array_keys($fields);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO interactions (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

        return $this->db->insert($sql, array_values($fields));
    }

    public function update(int $id, array $data): bool
    {
        $fields = $this->filterWritableFields($data);

        if (empty($fields)) {
            return false;
        }

        $set = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($fields)));
        $params = array_values($fields);
        $params[] = $id;

        return $this->db->execute(
            "UPDATE interactions SET $set WHERE interaction_id = ?",
            $params
        ) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM interactions WHERE interaction_id = ?", [$id]) > 0;
    }

    private function filterWritableFields(array $data): array
    {
        $fields = [];

        foreach (self::WRITABLE_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field];
            }
        }

        return $fields;
    }
}
