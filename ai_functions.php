<?php
/**
 * ai_functions.php (Finale Debugging-Version)
 */

require_once 'config.php';
require_once 'persons_functions.php';

function get_ai_response(PDO $pdo, int $person_id, string $prompt): string
{
    // ... (der obere Teil der Funktion bleibt gleich)
    $person = get_person_by_id($pdo, $person_id);
    if (!$person) {
        throw new Exception("Person mit der ID {$person_id} wurde nicht gefunden.");
    }
    $conversation_id = $person['openai_conversation_id'] ?? null;

    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('OpenAI API Key ist nicht in der config.php definiert.');
    }

    $api_key = OPENAI_API_KEY;
    $url = 'https://api.openai.com/v1/responses';

    $data = [
        'model' => 'gpt-5-mini',
        'input' => [['type' => 'message', 'role' => 'user', 'content' => $prompt]]
    ];

    if ($conversation_id) {
        $data['conversation_id'] = $conversation_id;
    } else {
        $data['instructions'] = 'Antworte freundlich und wertschätzend auf Deutsch. Stelle keine Gegenfragen.'; // Gekürzt
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    // --- FINALES DEBUGGING: Detaillierte cURL-Fehler abfangen ---
    $response_body = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Wenn ein cURL-Fehler aufgetreten ist (z.B. SSL-Problem, Netzwerkfehler)
    if ($curl_errno > 0) {
        throw new Exception("cURL Fehler (Code {$curl_errno}): {$curl_error}");
    }
    
    $result = json_decode($response_body, true);
    
    // Wenn die Antwort kein gültiges JSON ist
    if ($result === null && $http_code !== 200) {
         throw new Exception("Server-Fehler (HTTP-Status: {$http_code}). Die Antwort war kein gültiges JSON. Rohe Antwort: " . $response_body);
    }
    // --- ENDE DES DEBUGGING-TEILS ---

if (isset($result['output'])) {
    // Durchlaufe alle Blöcke im "output"-Array
    foreach ($result['output'] as $output_item) {
        // Prüfe, ob der aktuelle Block der richtige Typ ("message") ist UND Text enthält
        if ($output_item['type'] === 'message' && isset($output_item['content'][0]['text'])) {
            
            // Extrahiere den Text
            $ai_text = $output_item['content'][0]['text'];

            // Speichere die Konversations-ID, falls es eine neue war
            if (!$conversation_id && isset($result['conversation']['id'])) {
                $new_conversation_id = $result['conversation']['id'];
                update_person($pdo, $person_id, ['openai_conversation_id' => $new_conversation_id]);
            }

            // Erfolg! Beende die Funktion und gib den gefundenen Text zurück.
            return $ai_text;
        }
    }
}

    if (isset($result['error'])) {
        throw new Exception('OpenAI API Fehler: ' . $result['error']['message']);
    }

    throw new Exception('Unerwartete Antwort von OpenAI. Rohe Antwort: ' . $response_body);
}

function reset_ai_conversation(PDO $pdo, int $person_id): void
{
    update_person($pdo, $person_id, ['openai_conversation_id' => null]);
}
?>
