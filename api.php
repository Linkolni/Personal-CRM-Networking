<?php
/**
 * api.php
 *
 * Dies ist der zentrale API-Endpunkt für alle AJAX-Anfragen aus dem Frontend (js/app.js).
 * Die Datei selbst enthält keine Logik, sondern agiert als Verteiler (Dispatcher).
 *
 * ABLAUF:
 * 1. Sicherheitscheck: Prüft, ob der Benutzer authentifiziert ist.
 * 2. Aktion ermitteln: Liest den 'action'-Parameter aus der URL.
 * 3. Aktion ausführen: Ruft basierend auf der Aktion die passende Funktion aus der `persons_functions.php` auf.
 * 4. Antwort senden: Wandelt das Ergebnis in JSON um und sendet es an den Browser zurück.
 */

// Antwort-Header auf JSON setzen, damit der Browser weiß, was er bekommt.
header('Content-Type: application/json');

session_start();
require_once 'config.php';
require_once 'persons_functions.php'; // Pfad ggf. anpassen

// 1. Sicherheitscheck: Ohne gültige Session gibt es keine Daten.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 Unauthorized
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert.']);
    exit;
}

// 2. Aktion aus der GET-Anfrage lesen.
$action = $_GET['action'] ?? '';

try {
    // 3. Aktionen mit einer switch-Anweisung verteilen.
    switch ($action) {

        case 'get_circles':
            // Ruft die neue Funktion auf, um alle einzigartigen Circles zu holen.
            $data = get_all_unique_circles($pdo, $_SESSION['user_id']);
            echo json_encode(['success' => true, 'data' => $data]);
            break;


        case 'get_persons':
            $sortField = $_GET['sort'] ?? 'last_name';
            $sortDir = $_GET['dir'] ?? 'ASC';
            $data = get_all_persons_with_last_interaction($pdo, $_SESSION['user_id'], $sortField, $sortDir);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'get_person':
            $person_id = (int) ($_GET['id'] ?? 0);
            if ($person_id > 0) {
                $data = get_person_by_id($pdo, $person_id);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Ungültige Personen-ID.');
            }
            break;

        case 'get_interactions':
            $person_id = (int) ($_GET['id'] ?? 0);
            if ($person_id > 0) {
                $data = get_interactions_for_person($pdo, $person_id);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Ungültige Personen-ID.');
            }
            break;

        case 'add_interaction':
            $requestData = json_decode(file_get_contents('php://input'), true);
            if (empty($requestData['person_id']) || empty($requestData['interaction_date']) || empty($requestData['interaction_type'])) {
                echo json_encode(['success' => false, 'message' => 'Personen-ID, Datum und Art sind Pflichtfelder.']);
                break;
            }
            $newId = add_interaction($pdo, $requestData, $_SESSION['user_id']);
            if ($newId !== false) {
                echo json_encode(['success' => true, 'id' => $newId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der Interaktion.']);
            }
            break;

        case 'update_interaction':
            $requestData = json_decode(file_get_contents('php://input'), true);
            $interaction_id = (int) ($requestData['interaction_id'] ?? 0);
            if ($interaction_id <= 0 || empty($requestData['interaction_date']) || empty($requestData['interaction_type'])) {
                echo json_encode(['success' => false, 'message' => 'Interaktions-ID, Datum und Art sind Pflichtfelder.']);
                break;
            }
            $success = update_interaction($pdo, $interaction_id, $requestData, $_SESSION['user_id']);
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Fehler beim Aktualisieren der Interaktion oder keine Berechtigung.']);
            }
            break;

        case 'save_person':
            $requestData = json_decode(file_get_contents('php://input'), true);

            // Sicherheitscheck: Ist die Eingabe überhaupt ein gültiges JSON-Objekt?
            if (!is_array($requestData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ungültiges Datenformat. JSON erwartet.']);
                break;
            }
            // --- VALIDIERUNG FÜR BEIDE FÄLLE (CREATE & UPDATE) ---
            if (empty($requestData['last_name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Der Nachname ist ein Pflichtfeld.']);
                break;
            }
            // Hier könnten weitere allgemeingültige Validierungen hinzukommen.
            // --- ENDE VALIDIERUNG ---

            // Entscheiden, ob Create oder Update
            if (!isset($requestData['person_id']) || (int) $requestData['person_id'] <= 0) {
                // CREATE
                $requestData['user_id'] = $_SESSION['user_id'];
                $result = create_person($pdo, $requestData);
            } else {
                // UPDATE
                $result = update_person($pdo, (int) $requestData['person_id'], $requestData);
            }

            if (!$result['success']) {
                http_response_code(400);
            }

            echo json_encode($result);
            break;

        case 'delete_person':
            $person_id = (int) ($_GET['id'] ?? 0);
            if ($person_id > 0) {
                // Rufe die verbesserte delete_person Funktion auf
                $result = delete_person($pdo, $person_id, $_SESSION['user_id']);
                if (!$result['success']) {
                    http_response_code(403); // 403 Forbidden oder 400 Bad Request
                }
                echo json_encode($result);
            } else {
                throw new Exception('Ungültige Personen-ID zum Löschen.');
            }
            break;

        default:
            throw new Exception('Unbekannte Aktion.');
    }
} catch (Exception $e) {
    http_response_code(400); // 400 Bad Request bei allgemeinen Fehlern
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>