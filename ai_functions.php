<?php

require_once 'config.php';
require_once 'persons_functions.php'; // Pfad ggf. anpassen
require_once 'ai_functions.php';
require_once 'user_functions.php';


/**
 * Holt eine KI-Antwort über den OpenAI 'responses' API-Endpunkt.
 *
 * @param PDO $pdo Das Datenbankverbindungsobjekt.
 * @param int $person_id Die ID der Person, für die die Antwort generiert wird.
 * @param string $prompt Die aktuelle Aufgabe oder Frage an die KI.
 * @return string Die generierte Textantwort von der KI.
 * @throws Exception Bei Konfigurations-, Netzwerk- oder API-Fehlern.
 */
function get_ai_response(PDO $pdo, int $person_id, string $prompt): string
{
    // --- 1. Vorbereitungen und Validierungen ---

    // Sicherstellen, dass der API-Schlüssel definiert ist
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('OpenAI API Key ist nicht in der config.php definiert.');
    }

    // Benutzerdaten für die Persona abrufen
    // Annahme: session_start() wurde bereits außerhalb der Funktion aufgerufen
    $user = get_user_by_id($pdo, $_SESSION['user_id']);
    if (!$user) {
        throw new Exception("Angemeldeter Benutzer konnte nicht gefunden werden.");
    }

    // --- 2. Daten für den API-Aufruf zusammenstellen ---

    // Lade die bestehende Konversations-ID für die Person
    $stmt = $pdo->prepare("SELECT openai_conversation_id FROM persons WHERE person_id = ?");
    $stmt->execute([$person_id]);
    $previous_id = $stmt->fetchColumn(); // liefert die ID oder false

    // Basisdaten für den API-Aufruf
    $data = [
        'model' => 'gpt-5-nano', // KORREKTUR: 'gpt-5-mini' ist spekulativ, 'gpt-4o' ist ein reales, starkes Modell
        'input' => [['role' => 'user', 'content' => $prompt]], 
        'instructions' => $user['persona'] ?? 'Antworte direkt, freundlich und auf Deutsch.'
    ];

    // Füge die vorherige ID hinzu, falls vorhanden
    if ($previous_id) {
        $data['previous_response_id'] = $previous_id;
    }

    // --- 3. cURL-Request durchführen ---

    $api_key = OPENAI_API_KEY;
    $url = 'https://api.openai.com/v1/responses';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    $response_body = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // --- 4. Antwort auswerten und Fehler behandeln ---

    if ($curl_errno > 0) {
        throw new Exception("Netzwerkfehler bei der Verbindung zur API: " . curl_error($ch));
    }

    $result = json_decode($response_body, true);

    if ($http_code !== 200) {
        $error_message = $result['error']['message'] ?? 'Unbekannter API-Fehler.';
        throw new Exception("API-Fehler (HTTP {$http_code}): {$error_message}");
    }

    // --- 5. Erfolgreiche Antwort verarbeiten ---

    // Durchlaufe alle Blöcke im "output"-Array
    foreach ($result['output'] as $output_item) {
        // Prüfe, ob der Block der Typ 'message' ist und Text enthält
        if ($output_item['type'] === 'message' && !empty($output_item['content'][0]['text'])) {
            $ai_text = $output_item['content'][0]['text'];

            // Speichere die neue Konversations-ID für zukünftige Anfragen
            if (isset($result['conversation']['id'])) {
                $new_conversation_id = $result['conversation']['id'];
                // FEHLER 3 BEHOBEN: 'update_person' ist vermutlich nicht korrekt, direkter SQL ist klarer
                $update_stmt = $pdo->prepare("UPDATE persons SET openai_conversation_id = ? WHERE id = ?");
                $update_stmt->execute([$new_conversation_id, $person_id]);
            }

            // Erfolg! Gib den gefundenen Text zurück.
            return $ai_text;
        }
    }

    // Fallback, falls kein passender Inhaltsblock gefunden wurde
    throw new Exception('Unerwartete Antwortstruktur von OpenAI. Rohe Antwort: ' . $response_body);
}


/**
 * Erstellt einen detaillierten Prompt für die OpenAI API basierend auf Personendaten und Kommunikationshistorie.
 *
 * @param PDO $pdo Das Datenbankverbindungsobjekt.
 * @param int $person_id Die ID der Person, für die der Prompt erstellt wird.
 * @return string Der fertig zusammengebaute Prompt-String.
 * @throws Exception Wenn die Person mit der angegebenen ID nicht gefunden wird.
 */
function create_AI_interaction_prompt(PDO $pdo, int $person_id): string
{
    // 1. Personendaten laden
    $person = get_person_by_id($pdo, $person_id);
    if (!$person) {
        // Wenn die Person nicht existiert, kann kein Prompt erstellt werden.
        throw new Exception("Person mit der ID {$person_id} wurde nicht gefunden.");
    }

    // 2. Alle Interaktionen für diese Person laden
    $interactions = get_interactions_for_person($pdo, $person_id);

    // 3. Den Prompt-String schrittweise aufbauen
    
    // Teil 1: Die Hauptanweisung an die KI
    $prompt = "Schreibe eine Kontaktaufnahme per Email ohne  Betreff oder Gegenfragen. Beachte die Eigenschaften des folgenden Empfängers:\n";

    // Teil 2: Wichtige Personendaten hinzufügen
    // Definiere hier, welche Felder aus der `persons`-Tabelle für die KI relevant sind.
    $important_person_fields = ['name', 'position', 'company', 'industry', 'notes', 'status_prio'];
    foreach ($important_person_fields as $field) {
        if (!empty($person[$field])) {
            $label = ucfirst($field); // Macht z.B. aus 'status_prio' -> 'Status_prio'
            $prompt .= "{$label}: {$person[$field]}\n";
        }
    }

    // Teil 3: Überleitung zur Kommunikationshistorie
    $prompt .= "\nBeachte die Kommunikationshistorie mit Datum:\n";

    // Teil 4: Die komplette Historie anfügen
    if (empty($interactions)) {
        $prompt .= "Es gibt noch keine Kommunikationshistorie. Dies ist der Erstkontakt.\n";
    } else {
        foreach ($interactions as $interaction) {
            $interaction_details = [];
            // Alle Felder jeder Interaktion dynamisch zusammenfügen
            foreach ($interaction as $key => $value) {
                // IDs sind für die KI meist nicht relevant, können aber drin bleiben
                $interaction_details[] = "{$key}: {$value}";
            }
            $prompt .= "- " . implode(', ', $interaction_details) . "\n";
        }
    }

    return $prompt;
}
?>
