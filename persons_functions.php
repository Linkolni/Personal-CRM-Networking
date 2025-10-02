<?php
/**
 * persons_functions.php
 *
 * Diese Datei dient als Bibliothek und enthält alle Kernfunktionen für die Verwaltung
 * von Personen (Kontakten) und deren Interaktionen in der Datenbank.
 *
 * Jede Funktion erwartet als ersten Parameter die aktive PDO-Datenbankverbindung ($pdo).
 * Die Funktionen nutzen Prepared Statements, um SQL-Injection-Angriffe zu verhindern.
 */

// =========================================================================
// FUNKTIONEN FÜR PERSONEN (CRUD - Create, Read, Update, Delete)
// =========================================================================

/**
 * Erstellt eine neue Person in der Datenbank.
 *
 * @param PDO   $pdo  Die aktive Datenbankverbindung.
 * @param array $data Ein assoziatives Array mit den Daten der Person.
 *                    Erforderlich: 'user_id', 'last_name'. Alle anderen Schlüssel sind optional.
 * @return int|false  Gibt die ID der neu erstellten Person bei Erfolg zurück, ansonsten false.
 */
/**
 * Erstellt eine neue Person in der Datenbank.
 * Gibt immer ein Array mit dem Erfolgsstatus zurück, um konsistent mit update_person() zu sein.
 */
function create_person(PDO $pdo, array $data): array
{
    // SQL-Statement mit allen Spalten
    $sql = "INSERT INTO persons
                (user_id, first_name, last_name, email1, email2, phone1, phone2, company,
                 position, linkedin_profile, website, birthday, status, priority, contact_cycle, notes, circles)
            VALUES
                (:user_id, :first_name, :last_name, :email1, :email2, :phone1, :phone2, :company,
                 :position, :linkedin_profile, :website, :birthday, :status, :priority, :contact_cycle, :notes, :circles)";

    try {
        $stmt = $pdo->prepare($sql);

        // Binden der Werte aus dem $data-Array.
        // Die ??-Operatoren sichern gegen fehlende optionale Felder ab.
        $stmt->execute([
            ':user_id' => $data['user_id'], // Wird in api.php sicher gesetzt
            ':last_name' => $data['last_name'], // Wird in api.php validiert
            ':first_name' => $data['first_name'] ?? null,
            ':email1' => $data['email1'] ?? null,
            ':email2' => $data['email2'] ?? null,
            ':phone1' => $data['phone1'] ?? null,
            ':phone2' => $data['phone2'] ?? null,
            ':company' => $data['company'] ?? null,
            ':position' => $data['position'] ?? null,
            ':linkedin_profile' => $data['linkedin_profile'] ?? null,
            ':website' => $data['website'] ?? null,
            ':birthday' => empty($data['birthday']) ? null : $data['birthday'],
            ':status' => $data['status'] ?? 'NEW',
            ':priority' => $data['priority'] ?? null,
            ':contact_cycle' => empty($data['contact_cycle']) ? null : $data['contact_cycle'],
            ':notes' => $data['notes'] ?? null,
            ':circles' => $data['circles'] ?? null
        ]);

        $newId = (int) $pdo->lastInsertId();

        // ERFOLG: Gib ein Array mit dem Erfolgsstatus und der neuen ID zurück.
        return ['success' => true, 'id' => $newId];

    } catch (PDOException $e) {
        // FEHLER: Gib ein Array mit dem Fehlerstatus und der Fehlermeldung zurück.
        return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
    }
}


/**
 * Lädt alle Daten einer einzelnen Person anhand ihrer ID.
 *
 * @param PDO $pdo       Die aktive Datenbankverbindung.
 * @param int $person_id Die ID der zu ladenden Person.
 * @return array|false   Gibt ein assoziatives Array mit den Personendaten zurück oder false, wenn die Person nicht gefunden wurde.
 */
function get_person_by_id(PDO $pdo, int $person_id): array|false
{
    $stmt = $pdo->prepare("SELECT * FROM persons WHERE person_id = ?");
    $stmt->execute([$person_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Lädt alle Personen, die einem bestimmten Benutzer gehören.
 * Ideal für die Anzeige einer Gesamtübersicht (Tabelle).
 *
 * @param PDO $pdo     Die aktive Datenbankverbindung.
 * @param int $user_id Die ID des Benutzers, dessen Kontakte geladen werden sollen.
 * @return array       Gibt ein Array von Personen-Arrays zurück, sortiert nach Nachname.
 */
function get_all_persons_for_user(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("SELECT * FROM persons WHERE user_id = ? ORDER BY last_name, first_name");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Aktualisiert die Daten einer bestehenden Person.
 *
 * @param PDO   $pdo       Die aktive Datenbankverbindung.
 * @param int   $person_id Die ID der zu aktualisierenden Person.
 * @param array $data      Ein assoziatives Array mit den Feldern, die geändert werden sollen (z.B. ['email1' => 'neue@mail.de']).
 * @return bool            Gibt true bei Erfolg zurück, ansonsten false.
 * Verwendet eine Whitelist, um nur erlaubte Felder zu aktualisieren (Schutz vor Mass Assignment).
 * Gibt ein Array mit dem Erfolgsstatus zurück, um konsistent mit create_person() zu sein.
 */
function update_person(PDO $pdo, int $person_id, array $data): array
{
    // 1. Whitelist der Spalten, die per Update geändert werden dürfen.
    // Schützt davor, dass z.B. die user_id oder Timestamps manipuliert werden.
    $allowedFields = [
        'first_name',
        'last_name',
        'email1',
        'email2',
        'phone1',
        'phone2',
        'company',
        'position',
        'linkedin_profile',
        'website',
        'birthday',
        'status',
        'priority',
        'contact_cycle',
        'notes',
        'circles'
    ];

    // 2. Filtere die ankommenden Daten, um nur die erlaubten Schlüssel zu behalten.
    $filteredData = array_filter(
        $data,
        fn($key) => in_array($key, $allowedFields),
        ARRAY_FILTER_USE_KEY
    );

    // 2a. Wenn 'birthday' im Update-Befehl enthalten und leer ist, wandle es in NULL um.
    if (isset($filteredData['birthday']) && empty($filteredData['birthday'])) {
        $filteredData['birthday'] = null;
    }

    // 2b. Prüft, ob 'contact_cycle' aktualisiert wird und leer ist. -> wenn "" dann NULL wegen Datenbank EInstellung
    if (isset($filteredData['contact_cycle']) && empty($filteredData['contact_cycle'])) {
        $filteredData['contact_cycle'] = null;
    }

    // 2c. Absicherung für 'priority' -> wenn "" dann NULL wegen Datenbank EInstellung
    if (isset($filteredData['priority']) && empty($filteredData['priority'])) {
        $filteredData['priority'] = null;
    }

    // 3. Nur fortfahren, wenn es tatsächlich etwas zu aktualisieren gibt.
    if (empty($filteredData)) {
        // Nichts zu tun. Man könnte hier auch einen Fehler oder eine Warnung zurückgeben.
        return ['success' => true, 'message' => 'Keine Daten zum Aktualisieren vorhanden.'];
    }

    // 4. Dynamisch den SET-Teil des SQL-Statements erstellen.
    $fields = [];
    foreach (array_keys($filteredData) as $key) {
        $fields[] = "$key = :$key";
    }
    $sql_set_part = implode(', ', $fields);

    $sql = "UPDATE persons SET $sql_set_part WHERE person_id = :person_id";

    try {
        $stmt = $pdo->prepare($sql);

        // Füge die person_id für die WHERE-Klausel zu den zu bindenden Daten hinzu.
        $filteredData['person_id'] = $person_id;

        $success = $stmt->execute($filteredData);

        // 5. Gib ein konsistentes Array zurück.
        return ['success' => $success];

    } catch (PDOException $e) {
        // Gib im Fehlerfall ebenfalls ein konsistentes Array mit der Fehlermeldung zurück.
        return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
    }
}


/**
 * Löscht eine Person, nachdem geprüft wurde, ob der aktuelle Benutzer der Eigentümer ist.
 * Gibt ein Array mit dem Erfolgsstatus zurück.
 */
function delete_person(PDO $pdo, int $person_id, int $current_user_id): array
{
    // Sicherheitscheck: Hole die user_id der zu löschenden Person.
    $stmt = $pdo->prepare("SELECT user_id FROM persons WHERE person_id = ?");
    $stmt->execute([$person_id]);
    $owner_id = $stmt->fetchColumn();

    // Vergleiche den Eigentümer mit dem aktuell angemeldeten Benutzer.
    if ($owner_id != $current_user_id) {
        return ['success' => false, 'message' => 'Keine Berechtigung zum Löschen dieser Person.'];
    }

    // Wenn die Prüfung erfolgreich war, fahre mit dem Löschen fort.
    // Dank "ON DELETE CASCADE" werden verknüpfte Interaktionen automatisch entfernt.
    $stmt = $pdo->prepare("DELETE FROM persons WHERE person_id = ?");
    $success = $stmt->execute([$person_id]);
    
    if ($success) {
        return ['success' => true];
    } else {
        // Dieser Fall tritt selten auf, ist aber eine gute Absicherung.
        return ['success' => false, 'message' => 'Ein unerwarteter Datenbankfehler ist aufgetreten.'];
    }
}


/**
 * Lädt alle Personen eines Benutzers inklusive des Datums der letzten Interaktion.
 * Die Funktion unterstützt dynamische Sortierung.
 *
 * @param PDO    $pdo         Die aktive Datenbankverbindung.
 * @param int    $user_id     Die ID des Benutzers.
 * @param string $order_field Das Feld, nach dem sortiert werden soll (z.B. 'last_name').
 * @param string $order_dir   Die Sortierrichtung ('ASC' für aufsteigend, 'DESC' für absteigend).
 * @return array             Gibt ein Array von Personen-Arrays zurück.
 */
function get_all_persons_with_last_interaction(PDO $pdo, int $user_id, string $order_field = 'last_name', string $order_dir = 'ASC'): array
{
    // Sicherheitsmaßnahme: Nur eine feste Liste von Feldern für die Sortierung erlauben,
    // um SQL-Injection über die $order_field Variable zu verhindern.
    $allowed_fields = ['first_name', 'last_name', 'company', 'position', 'birthday', 'status', 'priority', 'contact_cycle', 'last_interaction'];
    if (!in_array(strtolower($order_field), $allowed_fields)) {
        $order_field = 'last_name'; // Fallback auf einen sicheren Standardwert
    }

    // Sicherstellen, dass die Sortierrichtung nur 'ASC' oder 'DESC' ist.
    $order_dir = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';

    // SQL-Statement mit LEFT JOIN und GROUP BY
    // - LEFT JOIN: Nimmt alle Personen, auch wenn sie keine Interaktionen haben.
    // - MAX(i.interaction_date): Findet das neueste Datum pro Person.
    // - GROUP BY p.person_id: Stellt sicher, dass wir pro Person nur eine Zeile bekommen.
    $sql = "SELECT
                p.*, 
                MAX(i.interaction_date) AS last_interaction
            FROM 
                persons p
            LEFT JOIN 
                interactions i ON p.person_id = i.person_id
            WHERE 
                p.user_id = :user_id
            GROUP BY 
                p.person_id
            ORDER BY 
                $order_field $order_dir";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Holt alle einzigartigen "Circles" für einen bestimmten Benutzer.
 *
 * Liest die kommaseparierten Strings aus der Datenbank, verarbeitet sie zu einer
 * einzigen, duplikatfreien und alphabetisch sortierten Liste.
 *
 * @param PDO $pdo Die Datenbankverbindung.
 * @param int $user_id Die ID des aktuellen Benutzers.
 * @return array Ein Array mit den einzigartigen Circle-Namen.
 */
function get_all_unique_circles(PDO $pdo, int $user_id): array
{
    // 1. Alle 'circles'-Strings für den Benutzer aus der DB holen, die nicht leer sind.
    $sql = "SELECT circles FROM persons WHERE user_id = :user_id AND circles IS NOT NULL AND circles != ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $allCircles = [];
    // 2. Jeden String ("Freunde, Firma1") in ein Array zerlegen und zusammenführen.
    foreach ($results as $circleString) {
        // array_map('trim', ...) entfernt automatisch Leerzeichen vor/nach dem Komma.
        $circles = array_map('trim', explode(',', $circleString));
        $allCircles = array_merge($allCircles, $circles);
    }

    // 3. Duplikate aus der Gesamtliste entfernen und alphabetisch sortieren.
    $uniqueCircles = array_unique($allCircles);
    sort($uniqueCircles, SORT_STRING | SORT_FLAG_CASE); // Sortiert Groß-/Kleinschreibung-unabhängig

    // 4. Leere Einträge entfernen, falls jemand ",," eingegeben hat.
    return array_filter($uniqueCircles);
}



// =========================================================================
// FUNKTIONEN FÜR INTERAKTIONEN
// =========================================================================

/**
 * Fügt eine neue Interaktion für eine Person hinzu.
 * Die user_id wird direkt aus der Session geholt.
 *
 * @param PDO   $pdo     Die Datenbankverbindung.
 * @param array $data    Ein assoziatives Array mit den Interaktionsdaten
 *                       (erwartet 'person_id', 'interaction_date', 'interaction_type', 'memo').
 * @param int   $user_id Die ID des aktuell angemeldeten Benutzers aus der Session.
 * @return int|false     Gibt die ID der neuen Interaktion zurück oder false bei einem Fehler.
 */
function add_interaction(PDO $pdo, array $data, int $user_id): int|false
{
    // --- HIER IST DIE KORREKTUR ---
    // Wir fügen die user_id explizit zum SQL-Statement und zu den Parametern hinzu.
    $sql = "INSERT INTO interactions (person_id, user_id, interaction_date, interaction_type, memo) VALUES (:person_id, :user_id, :interaction_date, :interaction_type, :memo)";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        ':person_id' => $data['person_id'],
        ':user_id' => $user_id, // Die ID aus der Session wird hier eingefügt.
        ':interaction_date' => $data['interaction_date'],
        ':interaction_type' => $data['interaction_type'],
        ':memo' => $data['memo'] ?? null
    ]);

    return $success ? (int) $pdo->lastInsertId() : false;
}


/**
 * Lädt alle Interaktionen, die zu einer bestimmten Person gehören.
 *
 * @param PDO $pdo       Die aktive Datenbankverbindung.
 * @param int $person_id Die ID der Person.
 * @return array         Gibt ein Array von Interaktions-Arrays zurück, sortiert nach dem neuesten Datum.
 */
function get_interactions_for_person(PDO $pdo, int $person_id): array
{
    $stmt = $pdo->prepare("SELECT * FROM interactions WHERE person_id = ? ORDER BY interaction_date DESC");
    $stmt->execute([$person_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Aktualisiert eine bestehende Interaktion.
 *
 * @param PDO   $pdo            Die Datenbankverbindung.
 * @param int   $interaction_id Die ID der zu aktualisierenden Interaktion.
 * @param array $data           Die neuen Daten aus dem Formular.
 * @param int   $user_id        Die ID des angemeldeten Benutzers (für die Sicherheitsprüfung).
 * @return bool                Gibt true bei Erfolg zurück, sonst false.
 */
function update_interaction(PDO $pdo, int $interaction_id, array $data, int $user_id): bool
{
    // Sicherheitsprüfung: Gehört diese Interaktion (über die Person) wirklich dem User?
    $sql_check = "SELECT p.user_id FROM interactions i JOIN persons p ON i.person_id = p.person_id WHERE i.interaction_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$interaction_id]);
    $owner = $stmt_check->fetchColumn();

    if ($owner != $user_id) {
        return false; // Verhindert, dass fremde Daten bearbeitet werden.
    }

    // Das eigentliche Update-Statement
    $sql = "UPDATE interactions SET 
                interaction_date = :interaction_date,
                interaction_type = :interaction_type,
                memo = :memo
            WHERE interaction_id = :interaction_id";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':interaction_date' => $data['interaction_date'],
        ':interaction_type' => $data['interaction_type'],
        ':memo' => $data['memo'] ?? null,
        ':interaction_id' => $interaction_id
    ]);
}


/**
 * Löscht eine einzelne Interaktion anhand ihrer ID.
 *
 * @param PDO $pdo            Die aktive Datenbankverbindung.
 * @param int $interaction_id Die ID der zu löschenden Interaktion.
 * @return bool               Gibt true bei Erfolg zurück, ansonsten false.
 */
function delete_interaction(PDO $pdo, int $interaction_id): bool
{
    $stmt = $pdo->prepare("DELETE FROM interactions WHERE interaction_id = ?");
    return $stmt->execute([$interaction_id]);
}

?>