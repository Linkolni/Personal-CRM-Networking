<?php
/**
 * user_functions.php
 *
 * Eine Sammlung von Funktionen für das Management von Benutzern,
 * Authentifizierung und Token-Verbrauch.
 *
 * Benötigt eine existierende PDO-Datenbankverbindung.
 */

/**
 * NEU & GEÄNDERT: Registriert einen neuen Benutzer und setzt die Rolle auf 'inactive'.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param string $username Der gewünschte Benutzername.
 * @param string $password Das Passwort im Klartext.
 * @return bool True bei Erfolg, False bei einem Fehler (z.B. Benutzername bereits vergeben).
 */
function register_user(PDO $pdo, string $username, string $password): bool
{
    // Prüfen, ob der Benutzername bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        return false; // Benutzername bereits vergeben
    }

    // Passwort sicher hashen
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    if (users_exist($pdo)) {
        // Ja, es gibt bereits Benutzer -> neuer Benutzer wird 'inactive'.
        $role = 'inactive';
    } else {
        // Nein, die Tabelle ist leer -> erster Benutzer wird 'admin' und ist aktiv.
        $role = 'admin';

    }


    // Neuen Benutzer mit der Rolle 'inactive' in die Datenbank einfügen
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)");
    return $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);

}

/**
 * Prüft, ob bereits Benutzer in der Datenbank vorhanden sind.
 *
 * Diese Funktion ist nützlich, um z.B. bei der Erstinstallation zu entscheiden,
 * ob der Registrierungs-Link angezeigt oder direkt zum Login weitergeleitet werden soll.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @return bool True, wenn mindestens ein Benutzer existiert, sonst False.
 */
function users_exist(PDO $pdo): bool
{
    // Führt eine Zählabfrage auf die 'users'-Tabelle aus. query() ist hier sicher,
    // da keine benutzereingaben verarbeitet werden.
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");

    // Holt das Ergebnis der ersten Spalte (den Zählwert).
    $user_count = (int) $stmt->fetchColumn();

    // Gibt true zurück, wenn der Zählwert größer als 0 ist, andernfalls false.
    return $user_count > 0;
}


/**
 * Überprüft die Anmeldedaten eines Benutzers und startet eine Session.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param string $username Der Benutzername.
 * @param string $password Das Passwort im Klartext.
 * @return bool True bei erfolgreichem Login, False bei falschen Anmeldedaten.
 */
/*function login_user(PDO $pdo, string $username, string $password): bool
{
    // Benutzerdaten abrufen. Wichtig: Der Login ist auch für 'inactive' Benutzer möglich,
    // aber der Zugriff auf andere Seiten wird durch is_user_logged_in() gesteuert.
    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}*/

//neu: inaktive ausschließen
function login_user(PDO $pdo, string $username, string $password): bool
{
    // Benutzerdaten abrufen.
    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Prüfen, ob ein Benutzer gefunden wurde
    if ($user) {
        // NEU: Prüfen, ob die Rolle 'inactive' ist.
        if ($user['role'] === 'inactive') {
            // Wirf eine Exception, um den Login-Prozess mit einer klaren Fehlermeldung abzubrechen.
            throw new Exception('Ihr Benutzerkonto ist deaktiviert. Bitte wenden Sie sich an den Administrator.');
        }

        // Wenn der Benutzer aktiv ist, das Passwort verifizieren.
        if (password_verify($password, $user['password_hash'])) {
            // Login erfolgreich: Session-Daten setzen.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }

    // Benutzer nicht gefunden oder falsches Passwort.
    // Aus Sicherheitsgründen wird hier keine spezifische Fehlermeldung zurückgegeben.
    return false;
}

/**
 * Beendet die aktuelle Benutzersession (Logout).
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

/**
 * Prüft, ob der aktuelle Besucher ein angemeldeter und aktiver Benutzer ist.
 *
 * @param array $allowed_roles Ein Array von Rollen, die Zugriff haben.
 * @return bool True, wenn der Benutzer angemeldet und berechtigt ist, sonst False.
 */
function is_user_logged_in(array $allowed_roles = ['user', 'admin']): bool
{
    // Die Rolle 'inactive' hat standardmäßig keinen Zugriff.
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], $allowed_roles);
}


/**
 * NEU: Ändert die Rolle eines bestimmten Benutzers.
 * Diese Funktion sollte nur von Administratoren aufgerufen werden.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param int $user_id Die ID des zu ändernden Benutzers.
 * @param string $new_role Die neue Rolle ('admin', 'user' oder 'inactive').
 * @return bool True bei Erfolg, False bei ungültiger Rolle oder Fehler.
 */
function change_user_role(PDO $pdo, int $user_id, string $new_role): bool
{
    $valid_roles = ['admin', 'user', 'inactive'];
    if (!in_array($new_role, $valid_roles)) {
        return false; // Ungültige Rolle angegeben
    }

    $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
    return $stmt->execute([
        ':role' => $new_role,
        ':id' => $user_id
    ]);
}

/**
 * NEU: Löscht einen Benutzer aus der Datenbank.
 * Diese Funktion sollte nur von Administratoren aufgerufen werden.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param int $user_id Die ID des zu löschenden Benutzers.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function delete_user(PDO $pdo, int $user_id): bool
{
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    return $stmt->execute([':id' => $user_id]);
}


/**
 * Ruft die aktuellen Token-Zähler für einen bestimmten Benutzer ab.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param int $user_id Die ID des Benutzers.
 * @return array Ein assoziatives Array mit 'tokens_sent' und 'tokens_generated'.
 */
function get_user_tokens(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("SELECT tokens_sent, tokens_generated FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $tokens = $stmt->fetch();
    return [
        'tokens_sent' => $tokens['tokens_sent'] ?? 0,
        'tokens_generated' => $tokens['tokens_generated'] ?? 0
    ];
}

/**
 * Aktualisiert die Token-Zähler für einen Benutzer durch Addition der neuen Werte.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param int $user_id Die ID des Benutzers.
 * @param int $new_sent Die Anzahl der neu gesendeten Tokens.
 * @param int $new_generated Die Anzahl der neu generierten Tokens.
 * @return array Die neuen Gesamt-Token-Werte als assoziatives Array.
 */
function update_user_tokens(PDO $pdo, int $user_id, int $new_sent, int $new_generated): array
{
    $sql = "UPDATE users SET tokens_sent = tokens_sent + :new_sent, tokens_generated = tokens_generated + :new_generated WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':new_sent' => $new_sent, ':new_generated' => $new_generated, ':id' => $user_id]);
    return get_user_tokens($pdo, $user_id);
}


/**
 * NEU: Protokolliert einen fehlgeschlagenen Login-Versuch für eine IP-Adresse.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param string $ip_address Die IP-Adresse des Benutzers.
 */
function log_failed_login_attempt(PDO $pdo, string $ip_address): void
{
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
    $stmt->execute([':ip' => $ip_address]);
}

/**
 * NEU: Prüft, ob eine IP-Adresse aufgrund zu vieler Login-Versuche gesperrt ist.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param string $ip_address Die IP-Adresse des Benutzers.
 * @param int $max_attempts Maximale Anzahl an Versuchen.
 * @param int $lockout_period Sperrzeit in Sekunden.
 * @return bool True, wenn die IP gesperrt ist, sonst False.
 */
function is_ip_locked(PDO $pdo, string $ip_address, int $max_attempts = 5, int $lockout_period = 300): bool
{
    // Zähle die fehlgeschlagenen Versuche innerhalb der letzten $lockout_period Sekunden
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts 
         WHERE ip_address = :ip AND attempt_time > (NOW() - INTERVAL :period SECOND)"
    );
    $stmt->execute([':ip' => $ip_address, ':period' => $lockout_period]);

    $attempts = (int) $stmt->fetchColumn();

    return $attempts >= $max_attempts;
}

// Hilfsfunktion, um die IP-Adresse des Benutzers sicher abzurufen
function get_user_ip_address(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Ruft die Daten eines Benutzers anhand seiner ID ab.
 *
 * @param PDO $pdo Das PDO-Datenbankverbindungsobjekt.
 * @param int $user_id Die ID des abzurufenden Benutzers.
 * @return array|false Die Benutzerdaten als assoziatives Array oder false, wenn nicht gefunden.
 */
function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare(
        'SELECT * 
         FROM users 
         WHERE id = ?'
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Aktualisiert das Profil eines Benutzers (Persona und optional das Passwort).
 *
 * @param PDO $pdo Das PDO-Datenbankverbindungsobjekt.
 * @param int $user_id Die ID des zu aktualisierenden Benutzers.
 * @param string $persona Die neue Persona des Benutzers.
 * @param string|null $new_password Das neue, unverschlüsselte Passwort. Wenn null, wird es nicht geändert.
 * @return bool True bei Erfolg, false bei einem Fehler.
 */
function update_user_profile($pdo, $user_id, $persona, $new_password = null) {
    if (!empty($new_password)) {
        // Passwort nur aktualisieren, wenn ein neues angegeben wurde
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'UPDATE users 
             SET persona = ?, password_hash = ? 
             WHERE id = ?'
        );
        return $stmt->execute([$persona, $password_hash, $user_id]);
    } else {
        // Nur die Persona aktualisieren
        $stmt = $pdo->prepare(
            'UPDATE users 
             SET persona = ? 
             WHERE id = ?'
        );
        return $stmt->execute([$persona, $user_id]);
    }
}

?>