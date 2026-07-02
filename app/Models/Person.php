<?php
/**
 * Person.php - Model für Kontakte (siehe concept.md 4.4-4.7, itdesign.md Abschnitt 7).
 */
class Person extends BaseModel
{
    protected string $table = 'persons';

    /** Whitelist erlaubter Felder für create()/update() - Schutz vor Mass Assignment. */
    private const WRITABLE_FIELDS = [
        'first_name', 'last_name', 'email1', 'email2', 'phone1', 'phone2',
        'company', 'position', 'linkedin_profile', 'website', 'birthday',
        'status', 'priority', 'circles', 'contact_cycle', 'notes',
    ];

    /** Felder, die bei leerem String zu NULL normalisiert werden müssen (ENUM/DATE-Spalten). */
    private const NULLABLE_IF_EMPTY = ['birthday', 'priority', 'contact_cycle'];

    /** Whitelist erlaubter Sortierfelder (concept.md 4.6) -> tatsächlicher SQL-Ausdruck. */
    private const SORTABLE_FIELDS = [
        'last_name'     => 'p.last_name',
        'company'       => 'p.company',
        'priority'      => 'p.priority',
        'contact_cycle' => 'p.contact_cycle',
        'last_contact'  => 'last_contact',
    ];

    public function getAllForUser(int $userId, string $sort = 'last_name', string $dir = 'ASC'): array
    {
        $sortColumn = self::SORTABLE_FIELDS[$sort] ?? self::SORTABLE_FIELDS['last_name'];
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT p.*, MAX(i.interaction_date) AS last_contact
                FROM persons p
                LEFT JOIN interactions i ON i.person_id = p.person_id
                WHERE p.user_id = ?
                GROUP BY p.person_id
                ORDER BY $sortColumn $direction";

        return $this->db->query($sql, [$userId]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT p.*, MAX(i.interaction_date) AS last_contact
             FROM persons p
             LEFT JOIN interactions i ON i.person_id = p.person_id
             WHERE p.person_id = ?
             GROUP BY p.person_id",
            [$id]
        );
    }

    public function create(int $userId, array $data): int
    {
        $fields = $this->filterWritableFields($data);
        $fields['user_id'] = $userId;
        $fields['status'] = 'NEW';

        $columns = array_keys($fields);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO persons (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

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
            "UPDATE persons SET $set, updated_at = NOW() WHERE person_id = ?",
            $params
        ) >= 0;
    }

    public function delete(int $id): bool
    {
        // interactions werden über FK CASCADE entfernt (siehe concept.md 4.4)
        return $this->db->execute("DELETE FROM persons WHERE person_id = ?", [$id]) > 0;
    }

    /**
     * D1 (concept.md 4.12): Personen mit Ampelstatus gelb oder rot, rot vor gelb,
     * innerhalb gleicher Farbe längste Überfälligkeit zuerst (siehe itdesign.md Abschnitt 8).
     * Ampel-Berechnung ist reine PHP-Logik ohne SQL-Äquivalent, daher Filterung/Sortierung
     * über alle Personen des Benutzers statt in der Query.
     */
    public function getDueContacts(int $userId, int $limit = 10): array
    {
        $due = [];

        foreach ($this->getAllForUser($userId) as $person) {
            $status = ContactCycleHelper::getStatus($person['last_contact'] ?? null, $person['contact_cycle'] ?? null);

            if (!in_array($status['color'], ['red', 'yellow'], true)) {
                continue;
            }

            $person['cycle_status'] = $status;
            $person['days_since_contact'] = $person['last_contact']
                ? (int) floor((time() - strtotime($person['last_contact'])) / 86400)
                : PHP_INT_MAX;
            $due[] = $person;
        }

        usort($due, function (array $a, array $b): int {
            $colorOrder = ['red' => 0, 'yellow' => 1];
            $colorCompare = $colorOrder[$a['cycle_status']['color']] <=> $colorOrder[$b['cycle_status']['color']];

            return $colorCompare !== 0 ? $colorCompare : $b['days_since_contact'] <=> $a['days_since_contact'];
        });

        return array_slice($due, 0, $limit);
    }

    /**
     * D2 (concept.md 4.12): Personen mit Geburtstag (Jahrestag) in den nächsten $days Tagen
     * inkl. heute, sortiert nach Nähe zum heutigen Datum (siehe itdesign.md Abschnitt 8).
     */
    public function getUpcomingBirthdays(int $userId, int $days = 14): array
    {
        $windowDates = [];
        for ($i = 0; $i <= $days; $i++) {
            $windowDates[] = date('m-d', strtotime("+{$i} days"));
        }

        $placeholders = implode(',', array_fill(0, count($windowDates), '?'));
        $sql = "SELECT *, DATE_FORMAT(birthday, '%m-%d') AS birthday_md
                FROM persons
                WHERE user_id = ? AND birthday IS NOT NULL
                  AND DATE_FORMAT(birthday, '%m-%d') IN ($placeholders)";

        $rows = $this->db->query($sql, array_merge([$userId], $windowDates));

        usort($rows, fn(array $a, array $b): int =>
            array_search($a['birthday_md'], $windowDates, true) <=> array_search($b['birthday_md'], $windowDates, true)
        );

        return $rows;
    }

    /**
     * D3 (concept.md 4.12): Personen einer bestimmten Priorität, inkl. letztem Kontaktdatum.
     */
    public function getByPriority(int $userId, string $priority): array
    {
        return $this->db->query(
            "SELECT p.*, MAX(i.interaction_date) AS last_contact
             FROM persons p
             LEFT JOIN interactions i ON i.person_id = p.person_id
             WHERE p.user_id = ? AND p.priority = ?
             GROUP BY p.person_id
             ORDER BY p.last_name ASC",
            [$userId, $priority]
        );
    }

    /**
     * D4 (concept.md 4.12): Personen mit Status NEW, älteste zuerst.
     */
    public function getNewContacts(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM persons WHERE user_id = ? AND status = 'NEW' ORDER BY created_at ASC",
            [$userId]
        );
    }

    /**
     * D6 (concept.md 4.12): Kennzahlen - Anzahl Kontakte je Ampelfarbe.
     */
    public function getCountsForUser(int $userId): array
    {
        $byColor = ['red' => 0, 'yellow' => 0, 'green' => 0, 'gray' => 0];
        foreach ($this->getAllForUser($userId) as $person) {
            $status = ContactCycleHelper::getStatus($person['last_contact'] ?? null, $person['contact_cycle'] ?? null);
            $byColor[$status['color']]++;
        }

        return ['byColor' => $byColor];
    }

    /**
     * D7 (concept.md 4.12): Personen ohne Kontaktzyklus, deren letzte Interaktion länger als
     * $months Monate zurückliegt oder die noch nie eine Interaktion hatten, älteste zuerst.
     */
    public function getStaleContacts(int $userId, int $months = 6, int $limit = 5): array
    {
        $threshold = strtotime("-{$months} months");

        $stale = array_values(array_filter($this->getAllForUser($userId), function (array $person) use ($threshold): bool {
            if (!empty($person['contact_cycle'])) {
                return false;
            }

            return empty($person['last_contact']) || strtotime($person['last_contact']) < $threshold;
        }));

        usort($stale, fn(array $a, array $b): int =>
            strtotime($a['last_contact'] ?? '1970-01-01') <=> strtotime($b['last_contact'] ?? '1970-01-01')
        );

        return array_slice($stale, 0, $limit);
    }

    /**
     * D8 (concept.md 4.12): Circle-Name => Anzahl zugeordneter Personen, sortiert nach Name.
     */
    public function getCircleCounts(int $userId): array
    {
        $rows = $this->db->query(
            "SELECT circles FROM persons WHERE user_id = ? AND circles IS NOT NULL AND circles != ''",
            [$userId]
        );

        $counts = [];
        foreach ($rows as $row) {
            foreach (explode(',', $row['circles']) as $circle) {
                $circle = trim($circle);
                if ($circle !== '') {
                    $counts[$circle] = ($counts[$circle] ?? 0) + 1;
                }
            }
        }

        ksort($counts, SORT_STRING | SORT_FLAG_CASE);

        return $counts;
    }

    /**
     * Ermittelt eine bereinigte, dedupliziert-sortierte Liste aller Circle-Namen
     * eines Benutzers (siehe concept.md 4.5).
     */
    public function getUniqueCirclesForUser(int $userId): array
    {
        $rows = $this->db->query(
            "SELECT circles FROM persons WHERE user_id = ? AND circles IS NOT NULL AND circles != ''",
            [$userId]
        );

        $circles = [];
        foreach ($rows as $row) {
            foreach (explode(',', $row['circles']) as $circle) {
                $circle = trim($circle);
                if ($circle !== '') {
                    $circles[$circle] = true;
                }
            }
        }

        $result = array_keys($circles);
        sort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }

    private function filterWritableFields(array $data): array
    {
        $fields = [];

        foreach (self::WRITABLE_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if (in_array($field, self::NULLABLE_IF_EMPTY, true) && ($value === '' || $value === null)) {
                $fields[$field] = null;
                continue;
            }

            $fields[$field] = $value;
        }

        return $fields;
    }
}
