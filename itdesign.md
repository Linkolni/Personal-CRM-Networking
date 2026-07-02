# Technisches Konzept: Neuaufbau Personal CRM (MVC)

Status: Zielarchitektur für die vollständige Neuimplementierung von `crm.solutor.de`.
Fachliche Grundlage: [`concept.md`](concept.md) (Funktionsumfang, Geschäftsregeln, Datenmodell).
Architektur: itdesign.md

Das **Datenmodell bleibt unverändert** (siehe Abschnitt 6): siehe migrations/*.sql

Du kannst für Architekturentscheidungen hier abschauen: C:\laragon\www\fire.solutor.de

---

## 1. Architekturprinzipien (aus bukido übernommen)

- **Klassisches MVC in PHP**, monolithisch, mit leichten prozeduralen Restanteilen (Helper-Funktionsbibliotheken)
  — keine Frameworks, keine Composer-Pflichtabhängigkeiten für den Kern.
- **Front Controller**: Ein einziger Einstiegspunkt `index.php`, der Requests über `?page=...&action=...`
  an Controller/Methoden routet.
- **Autoloading**: `spl_autoload_register` in `bootstrap.php` lädt Models, Helpers, Services, Controllers
  automatisch aus festen Verzeichnissen — kein manuelles `require_once` in Controllern.
- **Datenbankzugriff ausschließlich über ein Singleton** (`Database`) mit Prepared Statements; kein
  direktes SQL außerhalb von Models.
- **Models erben von `BaseModel`**, das bei Instanziierung prüft, ob die zugehörige Tabelle existiert
  (verhindert stille Fehler bei fehlender Migration).
- **RBAC & Session-Handling zentral über `AuthHelper`** (statt verstreuter `is_user_logged_in()`-Aufrufe).
- **CSRF-Schutz global** in `index.php` für alle POST-Requests, nicht pro Formular einzeln.
- **Ownership-Checks im Controller, nicht im Model**: Jede Aktion auf einer fremden ID lädt zuerst den
  Datensatz und vergleicht dessen Besitzer mit dem angemeldeten Benutzer (Muster aus bukidos
  `BookController`), bevor irgendetwas gelesen oder verändert wird.
- **Mehrsprachigkeit von Anfang an vorgesehen** (auch wenn aktuell nur Deutsch benötigt wird), über einen
  `t()`-Helper und Sprachdateien.
- **Migrations statt einmaligem Dump**: Das Schema entsteht aus nummerierten, versionierten SQL-Dateien statt
  einer einzigen `datamodel.sql`.
- **Konfiguration nach Zweck getrennt** (Umgebung / App-Konstanten / DB-Zugang / Secrets), sensible Dateien
  über `.gitignore` ausgeschlossen.

## 2. Verzeichnisstruktur (Zielzustand)

```
index.php                      # Front Controller – Routing, Auth-Check, CSRF-Check, Exception-Handling
bootstrap.php                  # Autoloader (Models, Helpers, Services, Controllers)
config/
  config_environment.php       # ENVIRONMENT (development/production), BASE_URL — gitignored
  config.php                   # App-Konstanten, Session-Sicherheit, Zeitzone, Upload-Limits
  database.php                 # DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET — gitignored
  apikeyconfig.php             # OPENAI_API_KEY etc. — gitignored
  navigation.php                # Navigationsstruktur (Closures für visible/locked je Rolle)
app/
  Controllers/
    AuthController.php         # Login, Logout, Registrierung, Passwort vergessen
    DashboardController.php    # Startseite: Begrüßung + Dashboard-Blöcke D1-D8 (siehe Abschnitt 8)
    PersonController.php       # Kontakte: index/create/store/edit/update/delete/view
    InteractionController.php  # Interaktionen: store/update/delete/generateAiInteraction (JSON)
    ProfileController.php      # Eigenes Profil: Persona, Passwort, Token-/Kostenanzeige, Datenexport (4.14)
    AdminController.php        # Benutzerverwaltung: Liste, Rolle setzen, löschen
    PageController.php         # Statische Seiten: Impressum, Datenschutz
  Models/
    BaseModel.php               # Abstrakte Basisklasse (Tabellenprüfung)
    Database.php                 # Singleton, mysqli, Prepared Statements, Transaktionen
    User.php
    Person.php
    Interaction.php
    LoginAttempt.php
  Helpers/
    AuthHelper.php               # Session/RBAC/CSRF
    App.php                       # Service-Container (App::set/App::get)
    NavigationHelper.php
    ContactCycleHelper.php        # Ampel-Logik: Fälligkeit aus Kontaktzyklus + letzter Interaktion
  Services/

  Views/
    layouts/
      header.php                  # HTML-Head, Navigation, CSS, CSRF-Token-Bereitstellung
      topbar.php                  # Seitentitel, Flash-Messages, Hilfe-Link
      footer.php                  # JS-Einbindung
    partials/
      flash_messages.php          # Einheitliches Rendering von $_SESSION['success']/['error']/...
    persons/
      list.php                    # Kontaktliste mit Sortierung/Circle-Filter (GET-Parameter)
      create.php
      edit.php
      view.php                    # Detailansicht inkl. Interaktionsliste + „Interaktion hinzufügen“-Formular
    interactions/
      edit.php                    # Formular zum Bearbeiten einer Interaktion
    dashboard/
      index.php
    profile/
      profile.php
      edit-profile.php
    admin/
      users.php
    pages/
      impressum.php
      datenschutz.php
    auth/
      login.php
      register.php
    errors/
      403.php
      404.php
      500.php
resources/
  lang/
    de.php                        # Alle UI-Texte, Format '<modul>.<view>.<label>' => 'Text'
css/
  custom.css
js/
  app.js                          # Formularvalidierung, Bestätigungsdialoge (progressive enhancement)
  persons-list.js                  # DataTables-Initialisierung für die Kontaktliste (siehe Abschnitt 9)
  ai-interaction.js                # Fetch-Aufruf für „KI-Vorschlag generieren“ (JSON-Endpoint)
migrations/
  001_create_users.sql
  002_create_persons.sql
  003_create_interactions.sql
  004_create_login_attempts.sql
logs/
  crm_error.log                   # Applikations-eigenes Error-Log statt Apache-Log
```

## 3. Front Controller & Routing

`index.php` übernimmt unverändert den bukido-Ablauf:

1. Konfiguration laden (`config_environment.php`, `config.php`).
2. Session starten (Sicherheitsflags bereits in `config.php` gesetzt: `httponly`, `samesite=Strict`,
   eigener Session-Name, `secure` nur bei HTTPS/Production).
3. `bootstrap.php` einbinden (Autoloading).
4. Session-Timeout prüfen (Inaktivitäts-Logout).
5. `RateLimitService::check()` — IP-/Bot-Schutz, vor jeglicher Verarbeitung.
6. `page`/`action` aus `$_GET` lesen (Default: `page=login`, `action=index`).
7. Auth-Check: geschützte Seiten (`dashboard`, `persons`, `interactions`, `profile`, `admin`) erfordern
   `AuthHelper::isLoggedIn()`; `login`/`register` sind nur für nicht angemeldete Benutzer erreichbar.
8. Globaler CSRF-Check für jeden POST-Request (`AuthHelper::validateCsrfToken()`).
9. Controller instanziieren, Methode über `$methodMap[page][action]` auflösen, ausführen.
10. Zentrales Exception-Handling: Development zeigt Stacktrace, Production rendert `Views/errors/500.php`.

### Controller-Mapping

```php
$controllers = [
    'login'      => 'AuthController',
    'register'   => 'AuthController',
    'forgot-password' => 'AuthController',
    'reset-password'   => 'AuthController',
    'logout'     => 'AuthController',
    'dashboard'  => 'DashboardController',
    'persons'    => 'PersonController',
    'interactions' => 'InteractionController',
    'profile'    => 'ProfileController',
    'admin'      => 'AdminController',
    'impressum'  => 'PageController',
    'datenschutz'=> 'PageController',
];

$protected_pages = ['dashboard', 'persons', 'interactions', 'profile', 'admin'];
$auth_only_pages = ['login', 'register', 'forgot-password', 'reset-password'];
```

### Methoden-Mapping (Auszug)

```php
$methodMap = [
    'persons' => [
        'index'  => 'index',   // Liste, GET-Parameter: sort, dir, circle
        'create' => 'create',
        'store'  => 'store',
        'view'   => 'view',    // Detailansicht + Interaktionsliste
        'edit'   => 'edit',
        'update' => 'update',
        'delete' => 'delete',
    ],
    'interactions' => [
        'store'                 => 'store',
        'edit'                  => 'edit',
        'update'                => 'update',
        'delete'                => 'delete',
        'generateAiInteraction' => 'generateAiInteraction', // JSON-Antwort für Fetch-Aufruf
        'askAi'                 => 'askAi',                 // JSON-Antwort, freier KI-Prompt
    ],
    'admin' => [
        'index'      => 'users',
        'updateRole' => 'updateRole',
        'delete'     => 'deleteUser',
    ],
    'profile' => [
        'index'          => 'index',
        'edit'           => 'edit',
        'update'         => 'update',
        'changePassword' => 'changePassword',
        'export'         => 'export',        // Datei-Download (SQL-Dump), siehe Abschnitt 11
    ],
];
```

**Architekturentscheidung — kein zentraler `api.php`-Dispatcher mehr**: Das Ist-System bündelte alle
Interaktionen in einem einzigen AJAX-Endpoint mit `switch($action)`. Der Neuaufbau folgt stattdessen
konsequent dem bukido-Muster aus serverseitig gerenderten Seiten pro Aktion (`page`/`action`-Routing), mit
URL-getriebenem Zustand (Sortierung/Filter als GET-Parameter, kein Client-State in JavaScript). Nur die
beiden Aktionen mit spürbarem Bedarf an sofortigem Feedback — „KI-Vorschlag generieren“ und die freie
KI-Anfrage — bleiben schlanke JSON-Endpoints innerhalb von `InteractionController`, analog zu bukidos
`AdminController::creditAdjustPost()` bzw. `DiscussionController::vote()`. Das reduziert die Frontend-Logik
von einer ~1300-Zeilen-`app.js`-Datei auf wenige, gezielte Fetch-Aufrufe.

## 4. Authentifizierung, Sessions & RBAC

`AuthHelper` (statische Klasse, wie in bukido) übernimmt:

- `setSession($user)` — schreibt nach Login `user_id`, `user_name`, `role` sowie `last_activity` in die
  Session (`session_regenerate_id(true)` gegen Session-Fixation).
- `isLoggedIn()`, `getUserId()`, `getUserName()`.
- `isAdmin()` — prüft `$_SESSION['role'] === 'admin'` (im CRM-Datenmodell ist die Rolle ein
  `ENUM('user','admin','inactive')`, nicht wie in bukido einzelne Boolean-Flags; `AuthHelper` bildet das
  1:1 auf die vorhandene Spalte ab, ohne das Schema zu ändern).
- `isActive()` — prüft `$_SESSION['role'] !== 'inactive'`; wird zusätzlich zu `isLoggedIn()` in
  `requireLogin()` geprüft, damit `inactive`-Accounts wie im Fachkonzept beschrieben ausgesperrt bleiben.
- `requireLogin()`, `requireAdmin()`, `requireActive()` — Redirects mit Flash-Message bei fehlender
  Berechtigung.
- `generateCsrfToken()` / `validateCsrfToken()` — Token pro Session, geprüft in `index.php` für jeden POST.
- `checkSessionTimeout()` — Inaktivitäts-Logout (konfigurierbar über `SESSION_TIMEOUT`, Default wie bei
  bukido 8h; für ein persönliches Tool kann ein kürzerer Wert sinnvoll sein — Entscheidung beim
  Produktverantwortlichen).

**Login-Absicherung** (ersetzt die alte, direkt in `login.php` verdrahtete Logik):

- `LoginAttempt`-Model + Migration `004_create_login_attempts.sql`, identisch zum bukido-Muster: Spalten
  `identifier` (E-Mail + IP), `attempts`, `last_attempt`, `locked_until`. `AuthController::handleLogin()`
  prüft vor jedem Versuch `checkLoginAttempts()`, zählt Fehlversuche mit `recordFailedAttempt()`
  (`INSERT ... ON DUPLICATE KEY UPDATE`), sperrt nach 5 Versuchen für 15 Minuten und setzt bei Erfolg
  `resetLoginAttempts()` zurück. Das ist strenger als die alte reine IP-Sperre und schützt zusätzlich gegen
  verteilte Angriffe auf einen einzelnen Account.
- Zusätzlich global `RateLimitService::check()` in `index.php` für generischen Bot-/DDoS-Schutz
  (IP-Request-Raten, verdächtige User-Agents), unabhängig vom Login-Formular. Technisch siehe unten.
- Registrierung: neue Accounts erhalten weiterhin Rolle `inactive` (außer dem allerersten Account →
  `admin`), Freischaltung durch einen Admin über `AdminController::updateRole()`.

**Generischer Rate-Limiter (`RateLimitService`, Phase 6, umgesetzt 02.07.2026)**:
`app/Services/RateLimitService.php`, aufgerufen in `index.php` direkt nach dem Session-Timeout-Check, vor
dem Routing. Übernommen aus `bukido.solutor.de/app/Services/RateLimitService.php`, dabei aber **bewusst
nicht 1:1 kopiert**: Die bukido-Fassung speichert ihre Zähler in `$_SESSION`. Da ein Bot/Skript ohne
Cookie-Unterstützung bei jeder Anfrage eine neue Session erhält, kommt der Zähler dort nie über 1 hinaus
— die Maßnahme wäre gegen genau die Angreifer wirkungslos, gegen die sie schützen soll. Diese Fassung
zählt daher DB-gestützt pro IP:
- `app/Models/RateLimitAttempt.php` nutzt bewusst dieselbe Tabelle `login_attempts` wie `LoginAttempt`
  (Spalten sind generisch genug: `identifier`/`attempts`/`last_attempt`/`locked_until`) statt einer
  eigenen Migration — Identifier-Namensraum `ratelimit|<ip>`, getrennt von `LoginAttempt`s
  `<username>|<ip>` und dem `migrations_tool|<ip>`-Namensraum aus `config/migrations.php` (siehe unten).
  `recordRequest()` erhöht den Zähler, setzt ihn aber auf 1 zurück, wenn der letzte Request länger als
  das Zeitfenster zurückliegt (gleitendes Zeitfenster ohne separate Fensterstart-Spalte).
- Limits: 120 Anfragen/Minute für normale User-Agents, 10 Anfragen/Minute für als verdächtig
  eingestufte User-Agents (statische Musterliste wie bukido: `bot`, `crawler`, `curl`, `python`, ...,
  plus leerer User-Agent). Bei Überschreitung: IP-Sperre für 15 Minuten, HTTP 429 mit `Retry-After`-Header.
- Bewusst vereinfacht gegenüber bukido: kein separates Stunden-Fenster, keine automatische
  Violation-Eskalation über mehrere Sperren hinweg — für das Mengengerüst eines persönlichen CRM
  ausreichend.

**Absicherung von `config/migrations.php` (Phase 6, umgesetzt 02.07.2026)**: Das Web-Tool zur
Migrationsausführung war über ein statisches Passwort geschützt, das als Klartext-Konstante
(`MIGRATION_PASSWORD`) in der **getrackten** `config/config.php` lag und damit über das öffentliche
GitHub-Repo einsehbar war (verifiziert: Commit mit dem Wert liegt auf `origin/main`, Repo ist öffentlich).
Behoben:
- Passwort rotiert und in die gitignored `config_environment.php` verschoben (`MIGRATION_PASSWORD`);
  `config/config.php` enthält den Wert nicht mehr.
- Neue Konstante `MIGRATIONS_TOOL_ENABLED`: `migrations.php` antwortet mit HTTP 404 (statt 403, um die
  Existenz des Tools nicht zu bestätigen), wenn `ENVIRONMENT === 'production'` und die Konstante nicht
  explizit `true` ist. Auf Produktivsystemen soll sie per Default `false` sein und nur für die Dauer
  eines bewussten Migrationslaufs kurz aktiviert werden.
- Derselbe Konto+IP-Brute-Force-Schutz wie beim regulären Login (5 Versuche/15 Minuten Sperre, 2s
  künstliche Verzögerung bei Fehlversuch) wurde auf den Passwort-Check des Tools angewendet (raw-mysqli
  gegen `login_attempts`, Identifier-Namensraum `migrations_tool|<ip>` — das Tool nutzt bewusst keine
  Models/`Database`-Singleton, um unabhängig von der übrigen Anwendung lauffähig zu bleiben).
- `config/config_environment.example.php` neu angelegt — dokumentiert beide neuen Konstanten mit
  Platzhalterwerten.
- **Für den Produktivserver nachzuziehen**: Die dortige `config_environment.php` liegt außerhalb dieses
  Repos und muss manuell um `MIGRATION_PASSWORD` (neuer, zufälliger Wert, nicht der rotierte
  Entwicklungswert) und `MIGRATIONS_TOOL_ENABLED = false` ergänzt werden.

## 5. Ownership-Modell (Berechtigungen auf Datensatzebene)

Zentrales Muster, 1:1 aus bukidos `BookController` übernommen und konsequent auf **jede** Aktion angewendet,
die eine `person_id` oder `interaction_id` entgegennimmt:

```php
// PersonController::edit() / update() / delete() / view()
$person = $this->personModel->findById($id);

if (!$person) {
    throw new Exception(t('person.error.not_found'));
}

if (!AuthHelper::isAdmin() && (int)$person['user_id'] !== AuthHelper::getUserId()) {
    throw new Exception(t('person.error.access_denied'));
}
```

Dieselbe Prüfung gilt in `InteractionController` — dort wird zusätzlich über die referenzierte Person
geprüft (`Interaction::findById()` liefert `person_id`, darüber wird `Person::findById()` und dessen
`user_id` verglichen). Damit ist ausgeschlossen, dass ein Benutzer fremde Datensätze über erratene IDs
lesen oder verändern kann — dieser Schutz existiert unabhängig von der jeweiligen Methode und wird nicht,
wie im Ist-System, nur bei manchen Funktionen vergessen.

`Person::getAllForUser($userId)` und `Interaction::getAllForPerson($personId)` liefern grundsätzlich nur
Daten für den aufrufenden Kontext; die zusätzliche Prüfung im Controller bleibt trotzdem Pflicht (defense
in depth), analog zur bukido-Konvention.

## 6. Datenmodell (unverändert, siehe `concept.md` Abschnitt 7)

Das Schema aus `datamodel.sql` wird 1:1 in nummerierte Migrationsdateien überführt (kein Feld wird
umbenannt oder entfernt):

- `migrations/001_create_users.sql` — Tabelle `users`
- `migrations/002_create_persons.sql` — Tabelle `persons`
- `migrations/003_create_interactions.sql` — Tabelle `interactions`
- `migrations/004_create_login_attempts.sql` — Tabelle `login_attempts` (Schema wie bukido: `identifier`,
  `attempts`, `last_attempt`, `locked_until` — funktional gleichwertig zum alten reinen
  IP-Log, aber mit Sperrzeitpunkt statt Live-Zählung)

Die eigentliche Datenübernahme (bestehende Zeilen aus der Produktivdatenbank) erfolgt separat per
`mysqldump`/Import, nicht Teil der Migrationsdateien.

**Datenzugriffsschicht-Wechsel**: Das Ist-System nutzt PDO. Der Neuaufbau nutzt — wie bukido — ein
mysqli-basiertes `Database`-Singleton mit `fetchOne()`, `query()`, `execute()`, `insert()`, `update()`,
`beginTransaction()`/`commit()`/`rollback()` sowie `tableExists()` für `BaseModel`. Das ist eine reine
Zugriffsschicht-Entscheidung zur Konsistenz mit dem Referenzprojekt und hat keine Auswirkung auf das Schema.

## 7. Model-Schicht

Alle Models erben von `BaseModel` (Tabellenprüfung beim Instanziieren) und kapseln ausschließlich SQL für
ihre Tabelle:

```php
class Person extends BaseModel
{
    protected string $table = 'persons';

    public function getAllForUser(int $userId, string $sort = 'last_name', string $dir = 'ASC'): array { ... }
    // JOIN + MAX(interaction_date) analog zur alten get_all_persons_with_last_interaction()
    public function findById(int $id): ?array { ... }
    public function create(array $data): int { ... }        // Whitelist + leere ENUM/DATE-Felder -> NULL
    public function update(int $id, array $data): bool { ... }
    public function delete(int $id): bool { ... }            // interactions werden über FK CASCADE entfernt
    public function getUniqueCirclesForUser(int $userId): array { ... }
}

class Interaction extends BaseModel
{
    protected string $table = 'interactions';

    public function getAllForPerson(int $personId): array { ... }
    public function getAllForUser(int $userId): array { ... }   // für Datenexport, siehe Abschnitt 11
    public function findById(int $id): ?array { ... }
    public function create(array $data, int $userId): int { ... }
    public function update(int $id, array $data): bool { ... }
    public function delete(int $id): bool { ... }
}
```

`User` ergänzt gegenüber bukido die CRM-spezifischen Felder (`persona`, `tokens_sent`, `tokens_generated`,
`tokens_cost`, `role`) über dieselben `findById()`/`update()`-Konventionen.

`ContactCycleHelper::getStatus(?string $lastInteractionDate, ?string $contactCycle): array` bündelt die
Ampel-Berechnung (grau/rot/gelb/grün, siehe Fachkonzept Abschnitt 4.7) als reine Funktion, testbar ohne
Datenbank — analog zu bukidos kleinen, fokussierten Helpern wie `ColorHelper.php`.

## 8. Dashboard (technisch)

Setzt concept.md Abschnitt 4.12 (D1–D8) um. Reine Lesefunktion, keine neuen Tabellen/Migrationen nötig —
alle Abfragen laufen über die bestehenden `persons`/`interactions`-Tabellen und filtern serverseitig auf
`user_id` (Ownership, siehe Abschnitt 5). `DashboardController::index()` lädt alle Blöcke in einem
Durchlauf und reicht sie unverändert an `Views/dashboard/index.php` durch — kein AJAX, eine
Server-Antwort.

Neue Model-Methoden:

```php
class Person extends BaseModel
{
    // ... bestehende Methoden

    /** D1: Personen mit Ampel gelb/rot, rot vor gelb, längste Überfälligkeit zuerst. */
    public function getDueContacts(int $userId, int $limit = 10): array { ... }

    /** D2: Personen mit Geburtstag (Jahrestag) in den nächsten $days Tagen. */
    public function getUpcomingBirthdays(int $userId, int $days = 14): array { ... }

    /** D3: Personen einer bestimmten Priorität (ersetzt den bisherigen Inline-Filter in DashboardController). */
    public function getByPriority(int $userId, string $priority): array { ... }

    /** D4: Personen mit status = NEW, älteste zuerst. */
    public function getNewContacts(int $userId): array { ... }

    /** D6: Kennzahlen: Anzahl je Ampelfarbe. */
    public function getCountsForUser(int $userId): array { ... }

    /** D7: Personen ohne Kontaktzyklus, letzte Interaktion > $months Monate her oder nie, max $limit. */
    public function getStaleContacts(int $userId, int $months = 6, int $limit = 5): array { ... }

    /** D8: Circle-Name => Anzahl Personen, sortiert nach Name (baut auf getUniqueCirclesForUser() auf). */
    public function getCircleCounts(int $userId): array { ... }
}

class Interaction extends BaseModel
{
    // ... bestehende Methoden

    /** D5: Letzte $limit Interaktionen über alle Personen eines Benutzers, Personenname per JOIN. */
    public function getRecentForUser(int $userId, int $limit = 5): array { ... }
}
```

**Ampel-abhängige Blöcke (D1, D7)**: `ContactCycleHelper::getStatus()` ist reine PHP-Logik ohne
SQL-Äquivalent. `getDueContacts()`/`getStaleContacts()` laden daher — wie die bestehende
`PersonController::index()`-Methode — zunächst alle Personen des Benutzers über `getAllForUser()` und
filtern/sortieren in PHP über `ContactCycleHelper`. Bei den Kontaktzahlen eines persönlichen CRM (siehe
Abschnitt 9) ist das unproblematisch; eine SQL-Nachbildung der Ampel-Logik würde sie duplizieren und ist
nicht gerechtfertigt.

**Geburtstage (D2)**: Vergleich von `MONTH(birthday)`/`DAY(birthday)` gegen ein Fenster von heute +
`$days` Tagen, inkl. Jahreswechsel-Fall (z. B. 25.12. + 14 Tage reicht in den Januar). Das Fenster wird in
PHP als Liste der nächsten `$days` Kalendertage (`m-d`-Strings) gebildet und per
`WHERE DATE_FORMAT(birthday, '%m-%d') IN (...)` abgefragt.

**Circle-Übersicht (D8)**: baut auf `getUniqueCirclesForUser()` auf und zählt zusätzlich pro Circle die
Treffer in PHP (gleiche Kommasplit-Logik wie dort).

`Views/dashboard/index.php` wird um sieben Card-Blöcke ergänzt (D1–D2, D4–D8; D3 bestehend als
Top5-Karte), jeder mit Leerzustand-Hinweistext gemäß concept.md 4.12. Anzeigereihenfolge im
zweispaltigen Raster: D1, D4, D3, D2, D5, D6, D7, D8 (D2/D4 bewusst gegenüber der D-Nummerierung
vertauscht, siehe concept.md 4.12). Die konfigurierbaren Schwellwerte (8 Einträge bei D1/D4, 5 Einträge
bei D2/D3, 14 Tage Fenster bei D2, 3 Einträge bei D5, 6 Monate bei D7 — siehe concept.md Annahme zu 4.12)
werden als
Klassenkonstanten in `DashboardController` deklariert, nicht in den Model-Methoden hartkodiert, damit sie
an einer Stelle änderbar bleiben. Die Obergrenze für D2 (Geburtstage) und D4 (neue Kontakte) wird per
`array_slice()` im Controller auf das Ergebnis der Model-Methoden angewendet, da
`Person::getUpcomingBirthdays()`/`getNewContacts()` bewusst ungekürzt bleiben (andere Aufrufer könnten die
vollständige Liste benötigen); D1 (`getDueContacts()`) und D3 (`getByPriority()` + `array_slice()` im
Controller) folgen demselben Prinzip. D3 filtert weiterhin auf die Priorität `TOP10` (Datenmodell
unverändert, Abschnitt 6) — nur Kachel-Beschriftung und Anzeige-Obergrenze wurden auf "Top 5" angepasst.

## 9. Kontaktliste: DataTables (technisch)

Setzt die Erweiterung von concept.md 4.6 um: clientseitige Sortierung und Pagination (20 Einträge
Default) statt serverseitig gerenderter Sortier-Links.

**Entscheidung**: DataTables rendert clientseitig (kein serverseitiges Processing). Begründung: Das
Mengengerüst eines persönlichen CRM (ein Benutzer pro Instanz, üblicherweise zwei- bis niedrig
dreistellige Kontaktzahl) liegt weit unter der Schwelle von ca. 1.000–2.000 Zeilen, ab der serverseitiges
DataTables-Processing nötig würde. `PersonController::index()` liefert daher weiterhin wie bisher alle
Kontakte des Benutzers in einem Rutsch (siehe auch Abschnitt 13 zur Skalierungsgrenze).

Technische Umsetzung:
- **Bibliothek**: DataTables 2.x mit `dataTables.bootstrap5` (Styling-Integration für Bootstrap 5), per
  CDN eingebunden — analog zum bestehenden Bootstrap-JS-Bundle in `footer.php` (kein neuer Build-Schritt,
  kein npm/Composer-Zwang, siehe Abschnitt 1).
- **Initialisierung**: `js/persons-list.js` (neu) initialisiert DataTables auf `table#persons-table` mit
  deutscher Sprachdatei (`language.url` auf CDN-gehostetes `de-DE.json`), `pageLength: 20`, `order` auf
  die Spalte passend zum aktuellen `$currentSort`/`$currentDir` (serverseitig als `data-order`-Attribut an
  die Tabelle übergeben, damit der erste Render konsistent mit dem zuletzt gewählten Zustand ist).
- **Serverseitige Sortier-Links entfallen**: `persons/list.php` verliert die bisherigen
  `$sortLink()`-Spaltenkopf-Links; Sortierung läuft ausschließlich über Klick auf die DataTables-Spaltenköpfe.
  `PersonController::index()` behält `sort`/`dir`/`circle` als GET-Parameter ausschließlich für den
  Circle-Filter (4.5) und den initialen Sortierzustand beim ersten Laden; die `ALLOWED_SORT`-Whitelist
  (4.6) bleibt bestehen, da sie weiterhin die initiale SQL-`ORDER BY`-Klausel absichert.
- **Ampel-Spalte bleibt sortierbar**: Die erste Spalte enthält nur ein Badge ohne Text; DataTables sortiert
  sie über ein numerisches `data-order`-Attribut je Zeile (rot=0, gelb=1, grün=2, grau=3), konfiguriert
  über `columnDefs`.
- **Circle-Filter-Badges bleiben serverseitig** (GET-Parameter `circle`, unverändert aus 4.5) — DataTables'
  eingebaute Volltextsuche wird zusätzlich angezeigt (Standard-Suchfeld), aber ersetzt die Circle-Badges
  nicht, um keinen zweiten, inkonsistenten Filtermechanismus einzuführen.
- **Volltextsuche über Notizen (concept.md 4.13)**: DataTables' Standard-Suchfeld durchsucht per Default nur
  sichtbare Zellinhalte. Damit auch `notes` durchsucht wird, ohne die Notiz als eigene, sichtbar breite
  Spalte in der Liste anzuzeigen, erhält `persons/list.php` pro Zeile eine zusätzliche `<td>` mit der Notiz,
  die per `columnDefs: [{ targets: <notes-index>, visible: false, searchable: true }]` aus der Anzeige
  ausgeblendet, aber weiterhin durchsucht wird. Damit deckt das ohnehin vorhandene Suchfeld Name, Firma,
  Position (bereits sichtbare Spalten) und Notizen (versteckte Spalte) ab, ganz ohne eigenen Endpoint oder
  Server-Request — `PersonController::index()` muss dafür lediglich `notes` mit in die von `getAllForUser()`
  gelieferten Zeilen aufnehmen, was bereits der Fall ist (Spalte ist Teil von `persons`).
- **Umfang der Änderung**: nur `persons/list.php` (Tabellen-Markup erhält `id="persons-table"`, Klassen
  `table table-hover align-middle` bleiben; DataTables ergänzt Such-/Pagination-Markup automatisch) und
  `footer.php` (CDN-Einbindung DataTables-CSS/JS + `persons-list.js`) werden angepasst.

## 10. KI-Integration (Services)

- `OpenAIResponsesService` kapselt den Aufruf der OpenAI-„Responses“-API (cURL, Fehlerbehandlung,
  Modellwahl über Konstanten wie `OPENAI_NANO_MODEL` in `apikeyconfig.php`) — direktes Pendant zur
  gleichnamigen Klasse in bukido, keine Neuerfindung nötig.
- `PersonPromptBuilder` baut den Prompt für die automatische Follow-up-Mail ausschließlich aus tatsächlich
  vorhandenen `persons`-Spalten und der über `Interaction::getAllForPerson()` geladenen Historie (behebt den
  im Fachkonzept dokumentierten Ist-Bug mit nicht existierenden Feldnamen).
- `InteractionController::generateAiInteraction()` und `askAi()` sind schlanke JSON-Endpoints (siehe
  Abschnitt 3), rufen `PersonPromptBuilder` + `OpenAIResponsesService` auf und schreiben das Ergebnis über
  `Interaction::create()`. `Person::updateConversationId()` schreibt die zurückgelieferte
  `openai_conversation_id` korrekt anhand von `person_id` (nicht `id`) fest.
- Token-/Kostenzähler (`users.tokens_sent/tokens_generated/tokens_cost`) werden nach jedem KI-Aufruf über
  `User::addTokenUsage()` aktualisiert, damit die Anzeige in `profile/profile.php` reale Werte zeigt.

## 11. Datenexport (technisch)

Setzt concept.md 4.14 um: Der angemeldete Benutzer kann seine eigenen Daten (`persons` + `interactions`)
als SQL-Datei herunterladen.

- **Einstiegspunkt**: `GET ?page=profile&action=export`, `ProfileController::export()`. Kein GET-Formular
  nötig (keine Nutzereingaben), daher unkritisch als GET-Route — kein CSRF-Risiko, da die Aktion nur lesend
  ist und keinen Zustand ändert (Abschnitt 3, Schritt 8 gilt ohnehin nur für POST).
- **Datenbeschaffung**: `Person::getAllForUser($userId)` und die neue `Interaction::getAllForUser($userId)`
  (analog zu `getAllForPerson()`, aber ohne Personenfilter, siehe Abschnitt 7) laden ausschließlich
  Datensätze des angemeldeten Benutzers — dieselbe Ownership-Garantie wie überall sonst (Abschnitt 5),
  `$userId` kommt ausschließlich aus `AuthHelper::getUserId()`.
- **Erzeugung des SQL-Dumps — kein Shell-Aufruf**: `mysqldump` wird **nicht** aufgerufen (kein
  `exec()`/`shell_exec()` mit Benutzerbezug, keine Angriffsfläche für Command Injection). Stattdessen baut
  eine neue `ExportService::buildSqlDump(array $persons, array $interactions): string` die `INSERT`-Statements
  programmatisch aus den bereits geladenen, typisierten Arrays zusammen — jeder Wert wird über
  `mysqli::real_escape_string()` (bzw. äquivalent über das `Database`-Singleton) escaped, keine
  String-Konkatenation von Rohwerten. Die interne `person_id`/`user_id` bleibt im Dump enthalten (Re-Import
  in eine leere, kompatible Datenbank funktioniert 1:1), ein Hinweiskommentar am Dateianfang weist darauf hin,
  dass beim Import in eine bestehende Datenbank ID-Kollisionen möglich sind.
- **Download-Response**: `ProfileController::export()` setzt die Header selbst (kein View-Template):
  `Content-Type: application/sql`, `Content-Disposition: attachment; filename="crm-export-<user_id>-<Datum>.sql"`,
  `Content-Length`, gibt den Dump-String aus und beendet die Ausführung (`exit`/`return` vor dem regulären
  View-Rendering in `index.php`) — analog zum bestehenden Muster für JSON-Endpoints in Abschnitt 3.
- **Umfang**: Export ist rein lesend, keine neue Migration, kein neues Datenmodell-Objekt.

## 12. Views, Layout & Frontend

- `layouts/header.php` → `layouts/topbar.php` → Modul-View → `layouts/footer.php`, exakt das bukido-Muster.
  `header.php` erzeugt/prüft das CSRF-Token, lädt Navigation (`NavigationHelper::getNav()`) und optional
  gecachte Anzeigedaten (z. B. Token-Guthaben) aus der Session.
- `partials/flash_messages.php` rendert `$_SESSION['success']/['error']/['info']/['warning']/['hint']`
  einheitlich und leert sie danach — ersetzt die verstreuten `if ($success_message)`-Blöcke im Ist-System.
- Formularseiten pro Modul und Aktion (`persons/list.php`, `create.php`, `edit.php`, `view.php`) statt
  serverseitig zusammengebauter HTML-Strings in JavaScript (wie im Ist-System `renderPersonForm()` etc.) —
  bessere Testbarkeit, kein doppelter Rendering-Pfad zwischen PHP und JS.
- Bootstrap 5 + Bootstrap Icons als UI-Basis (unverändert zum Ist-System, bewährtes Look & Feel).
  Ergänzt um DataTables 2.x (Bootstrap-5-Styling) für die Kontaktliste, siehe Abschnitt 9 — ebenfalls per
  CDN, kein zusätzlicher Build-Schritt.
- `js/app.js` beschränkt sich auf progressive Enhancement (Formularvalidierung, Bestätigungsdialoge), keine
  Rendering-Logik mehr im Client.
- `js/persons-list.js` initialisiert DataTables auf der Kontaktliste (siehe Abschnitt 9) — einzige Stelle
  mit clientseitiger Tabellen-Interaktivität, keine Datenhaltung/Rendering-Logik außerhalb dessen, was
  DataTables selbst übernimmt.
- `js/ai-interaction.js` kapselt ausschließlich die beiden JSON-Fetch-Aufrufe aus Abschnitt 3
  (Lade-Spinner, Fehleranzeige) — direkter Nachfolger von `handleGenerateAiInteraction()`.

## 13. Navigation & Mehrsprachigkeit

`config/navigation.php` liefert ein Array mit `visible`-Closures pro Eintrag (Muster wie bukido):

```php
return [
    ['key' => 'dashboard', 'label' => 'nav.dashboard', 'page' => 'dashboard', 'visible' => fn() => true],
    ['key' => 'persons',   'label' => 'nav.persons',   'page' => 'persons',   'visible' => fn() => true],
    ['key' => 'admin',     'label' => 'nav.admin',     'page' => 'admin',     'visible' => fn() => AuthHelper::isAdmin()],
    ['key' => 'profile',   'label' => 'nav.profile',   'page' => 'profile',   'visible' => fn() => true],
];
```

`resources/lang/de.php` enthält alle UI-Texte im Format `'<modul>.<view>.<label>' => 'Text'`
(z. B. `'person.list.title' => 'Meine Kontakte'`), abgerufen über `t()` (`I18nHelper.php` + `App`-Container,
1:1 aus bukido übernommen). Eine `en.php` ist strukturell vorbereitet, aber für den ersten Release nicht
Pflicht.

## 14. Fehlerbehandlung & Betrieb

- Zentrales try/catch um den Controller-Dispatch in `index.php` (siehe Abschnitt 3, Schritt 10).
- `Views/errors/403.php`/`404.php`/`500.php` für produktionsreife Fehlerseiten.
- Anwendungseigenes Error-Log (`logs/crm_error.log`) statt Verlass auf das Apache-Log, analog `config.php`
  in bukido.
- Umgebungstrennung über `config_environment.php` (`ENVIRONMENT`, `BASE_URL`), gitignored, mit
  `config_environment.example.php` als Vorlage.

## 15. Sicherheitsbausteine des Neuaufbaus (Zusammenfassung)

- Ownership-Check in jeder Controller-Methode, die eine ID entgegennimmt (Abschnitt 5) — kein isolierter
  „vergessener“ Fall wie im Ist-System, da das Muster einheitlich für alle Controller gilt.
- Globaler CSRF-Schutz für alle POST-Requests (statt keinem).
- Prepared Statements ausschließlich über das `Database`-Singleton, kein SQL in Controllern/Views.
- Passwort-Hashing via `password_hash()`/`password_verify()` (unverändert zum Ist-System, weiterhin gut).
- Kombinierter Brute-Force-Schutz: `login_attempts`-basierte Kontosperre + generisches IP-/Bot-Rate-Limiting.
- Session-Hardening: `httponly`, `samesite=Strict`, eigener Session-Name, `secure` in Production,
  Session-Regeneration bei Login, Inaktivitäts-Timeout.
- Secrets (`OPENAI_API_KEY`, DB-Zugang) ausschließlich in gitignored Config-Dateien, mit versionierten
  `*.example.php`-Vorlagen ohne echte Werte — kein Secret mehr direkt im (wenn auch gitignored) Hauptcode.
- Dashboard und DataTables-Kontaktliste (Abschnitt 8/9) sind reine Lesefunktionen über bereits
  ownership-geprüfte Model-Methoden (`$userId` immer aus `AuthHelper::getUserId()`, nie aus dem Request) —
  keine neue Angriffsfläche gegenüber Abschnitt 5.
- Datenexport (Abschnitt 11) baut den SQL-Dump programmatisch mit Escaping über das `Database`-Singleton,
  kein Shell-Aufruf von `mysqldump` — keine Command-Injection-Fläche; `$userId` auch hier ausschließlich aus
  `AuthHelper::getUserId()`.

## 16. Entscheidungen und offene Punkte

- **D1 — Volltextsuche (concept.md 4.13)**: läuft ausschließlich clientseitig über das ohnehin geplante
  DataTables-Suchfeld (Abschnitt 9), inkl. versteckter, aber durchsuchbarer Notizen-Spalte. Kein eigener
  Such-Endpoint, kein zusätzlicher Ownership-Check nötig, da die Liste bereits `user_id`-gefiltert geladen
  wird.
- **D2 — Datenexport-Format (concept.md 4.14)**: nur SQL-Dump (`INSERT`-Statements), kein zusätzliches
  CSV-Format — vom Auftraggeber bestätigt.
- **D3 — LinkedIn-Profildaten (concept.md 4.15, Auftraggeber-Entscheidung A1)**: Status quo, kein
  LinkedIn-API-Client und keine Drittanbieter-Anbindung vorgesehen. `linkedin_profile` bleibt ein reines
  Freitextfeld ohne serverseitige Anreicherungslogik — keine Auswirkung auf Datenmodell oder Architektur.

## 17. Referenzen

- Fachlicher Funktionsumfang, Geschäftsregeln und das beizubehaltende Datenmodell: [`concept.md`](concept.md).
- Architektonisches Vorbild inkl. Konventionen für neue Module: `bukido.solutor.de/claude.md`.
