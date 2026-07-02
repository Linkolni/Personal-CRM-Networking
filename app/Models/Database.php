<?php
/**
 * Database.php - Datenbank-Singleton für MySQL/MariaDB (mysqli, Prepared Statements)
 * Übernommen aus bukido.solutor.de/app/Models/Database.php (siehe itdesign.md Abschnitt 6).
 *
 * Versionshistorie:
 * - escape() ergänzt für den Datenexport-Dump (concept.md 4.14, itdesign.md Abschnitt 11).
 */

class Database
{
    private static $instance = null;
    private $connection = null;

    private function __construct()
    {
        // Verbindung wird erst bei getConnection() aufgebaut (Lazy Loading)
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
                die("<h2 style='color:red;'>Konfigurationsfehler</h2>" .
                    "<p>Datenbank-Konstanten sind nicht definiert! Bitte config/database.php prüfen.</p>");
            }

            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                defined('DB_PASS') ? DB_PASS : '',
                DB_NAME
            );

            if ($this->connection->connect_error) {
                if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                    die("<h2 style='color:red;'>Datenbankverbindung fehlgeschlagen</h2>" .
                        "<p><strong>Fehler:</strong> " . htmlspecialchars($this->connection->connect_error) . "</p>" .
                        "<p><strong>Host:</strong> " . DB_HOST . "</p>" .
                        "<p><strong>User:</strong> " . DB_USER . "</p>" .
                        "<p><strong>Datenbank:</strong> " . DB_NAME . "</p>");
                }
                die("Datenbankverbindung fehlgeschlagen. Bitte kontaktieren Sie den Administrator.");
            }

            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
            $this->connection->set_charset($charset);
        }

        return $this->connection;
    }

    private function bindTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's'; // String, NULL und alles andere
            }
        }
        return $types;
    }

    public function fetchOne($sql, $params = [])
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Fehler beim Prepare: " . $conn->error . " | SQL: " . $sql);
        }

        if (!empty($params)) {
            $stmt->bind_param($this->bindTypes($params), ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            $stmt->close();
            return null;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function query($sql, $params = [])
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Fehler beim Prepare: " . $conn->error . " | SQL: " . $sql);
        }

        if (!empty($params)) {
            $stmt->bind_param($this->bindTypes($params), ...$params);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("SQL Fehler beim Execute: " . $error . " | SQL: " . $sql);
        }

        $result = $stmt->get_result();

        if ($result === false) {
            $stmt->close();
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return is_array($rows) ? $rows : [];
    }

    public function execute($sql, $params = [])
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Fehler beim Prepare: " . $conn->error . " | SQL: " . $sql);
        }

        if (!empty($params)) {
            $stmt->bind_param($this->bindTypes($params), ...$params);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("SQL Fehler beim Execute: " . $error . " | SQL: " . $sql);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    public function insert($sql, $params = [])
    {
        $this->execute($sql, $params);
        return $this->lastInsertId();
    }

    public function lastInsertId()
    {
        return $this->getConnection()->insert_id;
    }

    public function beginTransaction()
    {
        return $this->getConnection()->begin_transaction();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollback();
    }

    /**
     * Escaped einen Wert für die direkte Verwendung in einem SQL-Statement außerhalb eines
     * Prepared Statements - wird ausschließlich für den Bau des Datenexport-Dumps benötigt
     * (siehe ExportService, itdesign.md Abschnitt 11), sonst gilt weiterhin: nur Prepared Statements.
     */
    public function escape(string $value): string
    {
        return $this->getConnection()->real_escape_string($value);
    }

    public function tableExists(string $table): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
        );
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (bool) $count;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
