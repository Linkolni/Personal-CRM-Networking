<?php
/**
 * BaseModel.php - Prüft beim Instanziieren, ob die zugehörige Tabelle existiert
 * (verhindert stille Fehler bei fehlender Migration, siehe itdesign.md Abschnitt 7).
 */
abstract class BaseModel
{
    protected Database $db;
    protected string $table;

    private static array $verified = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->checkTable();
    }

    private function checkTable(): void
    {
        if (isset(self::$verified[$this->table])) {
            return;
        }

        if (!$this->db->tableExists($this->table)) {
            throw new RuntimeException(
                "Deployment-Fehler: Tabelle '{$this->table}' fehlt. Migration ausstehend?"
            );
        }

        self::$verified[$this->table] = true;
    }
}
