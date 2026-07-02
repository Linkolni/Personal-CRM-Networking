<?php
/**
 * ExportService.php - Baut den Datenexport-Dump für einen Benutzer als SQL-Datei
 * (siehe concept.md 4.14, itdesign.md Abschnitt 11).
 *
 * Erzeugt INSERT-Statements ausschließlich programmatisch aus bereits geladenen, per
 * user_id gefilterten Datensätzen - kein Shell-Aufruf von mysqldump (keine
 * Command-Injection-Fläche), jeder Wert wird über Database::escape() escaped.
 */
class ExportService
{
    /** Spaltenreihenfolge für persons, exakt wie migrations/002_create_persons.sql. */
    private const PERSON_COLUMNS = [
        'person_id', 'user_id', 'first_name', 'last_name', 'email1', 'email2',
        'phone1', 'phone2', 'company', 'position', 'linkedin_profile', 'website',
        'birthday', 'status', 'priority', 'circles', 'contact_cycle', 'notes',
        'openai_conversation_id', 'created_at', 'updated_at',
    ];

    /** Spaltenreihenfolge für interactions, exakt wie migrations/003_create_interactions.sql. */
    private const INTERACTION_COLUMNS = [
        'interaction_id', 'person_id', 'user_id', 'interaction_date',
        'interaction_type', 'memo', 'created_at',
    ];

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Baut den vollständigen SQL-Dump für die übergebenen Personen/Interaktionen.
     *
     * @param array $persons      Zeilen aus Person::getAllForUser() - kann zusätzliche,
     *                            berechnete Felder (z.B. last_contact) enthalten, die hier
     *                            bewusst über die Spalten-Whitelist ausgefiltert werden.
     * @param array $interactions Zeilen aus Interaction::getAllForUser().
     */
    public function buildSqlDump(array $persons, array $interactions): string
    {
        $lines = [
            '-- Personal CRM - Datenexport (concept.md 4.14)',
            '-- Erzeugt am ' . date('Y-m-d H:i:s'),
            '-- Enthält ausschliesslich die eigenen Personen und Interaktionen des exportierenden Benutzers.',
            '-- Hinweis: Die internen IDs (person_id/interaction_id/user_id) bleiben unveraendert erhalten.',
            '-- Ein Import in eine bereits befuellte Datenbank kann daher zu ID-Kollisionen fuehren -',
            '-- am sichersten ist der Import in eine leere Datenbank mit identischem Schema (siehe migrations/).',
            '-- Der Export enthaelt bewusst KEINE users-Zeile (nur persons/interactions) - die Zieldatenbank',
            '-- braucht daher bereits einen Benutzer mit passender user_id, sonst schlaegt der Fremdschluessel fehl.',
            '',
            'SET NAMES utf8mb4;',
            '',
        ];

        foreach ($persons as $person) {
            $lines[] = $this->buildInsert('persons', self::PERSON_COLUMNS, $person);
        }

        $lines[] = '';

        foreach ($interactions as $interaction) {
            $lines[] = $this->buildInsert('interactions', self::INTERACTION_COLUMNS, $interaction);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Baut ein einzelnes INSERT-Statement aus der Spalten-Whitelist und einer Datenzeile.
     * Werte ausserhalb der Whitelist (z.B. last_contact aus einem JOIN) werden ignoriert.
     */
    private function buildInsert(string $table, array $columns, array $row): string
    {
        $values = [];

        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            $values[] = $value === null ? 'NULL' : "'" . $this->db->escape((string)$value) . "'";
        }

        return "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");";
    }
}
