# Handover: Neuaufbau crm.solutor.de (MVC)

Status: **Phase 0 (Grundgerüst), Phase 1 (Auth + Admin), Phase 2 (Kontaktverwaltung), Phase 3
(Interaktionsprotokoll), Phase 5 (Profil), Phase 7 (Dashboard-Erweiterung D1-D8 + DataTables-Kontaktliste)
und Phase 8 (Volltextsuche 4.13 + Datenexport 4.14) sind implementiert und lokal end-to-end getestet.**
Phase 4 (KI-Integration) ist **auf Wunsch zurückgestellt** (siehe Abschnitt 4). **Phase 6 (Härtung) ist in
Arbeit** (02.07.2026) — dabei wurde ein **kritischer, bereits aktiver Sicherheitsfund behoben**
(Klartext-Passwort für `config/migrations.php` im öffentlichen Git-Repo, siehe Abschnitt 2) sowie
generisches Rate-Limiting und Security-Header ergänzt; offen sind noch Browser-UI-Test und die
Produktivdaten-Übernahme (siehe Abschnitt 4). Fachliche Grundlage: [`concept.md`](concept.md). Architektur:
[`itdesign.md`](itdesign.md).

---

## 1. Was bereits läuft

### Grundgerüst (vorgezogen aus Phase 0, war Voraussetzung für alles Weitere)
### Phase 1 — Auth & Admin
### Phase 2 — Kontaktverwaltung
### Phase 3 — Interaktionsprotokoll
- **Nicht Teil dieser Phase** (bewusst zurückgestellt auf Phase 4): `generateAiInteraction()`/`askAi()` als
  JSON-Endpoints in `InteractionController` — noch nicht implementiert.
### Phase 5 — Profil (Phase 4 auf Benutzerwunsch übersprungen, siehe Abschnitt 4)

### Phase 6 — Härtung (in Arbeit, 02.07.2026)
- **Kritischer Fund, sofort behoben**: `config/migrations.php` war nur durch ein statisches Passwort
  geschützt, das als Klartext-Konstante `MIGRATION_PASSWORD` in der **getrackten** `config/config.php`
  lag. Verifiziert: der Commit mit diesem Wert liegt auf `origin/main`, das GitHub-Repo
  `Linkolni/Personal-CRM-Networking` ist **öffentlich** — das Passwort war damit für jeden einsehbar, der
  das Repo findet. `apikeyconfig.php`/`database.php`/`config_environment.php` waren nie betroffen (schon
  immer korrekt gitignored).
  - Passwort rotiert, aus `config.php` entfernt und in die gitignored `config_environment.php`
    verschoben (auf Wunsch des Benutzers).
  - Neue Konstante `MIGRATIONS_TOOL_ENABLED`: [`config/migrations.php`](config/migrations.php) antwortet
    mit HTTP 404 (statt 403, verrät die Existenz nicht), wenn `ENVIRONMENT === 'production'` und diese
    Konstante nicht explizit `true` ist.
  - Zusätzlich Konto+IP-Brute-Force-Schutz auf den Passwort-Check des Tools (5 Versuche/15 Min. Sperre,
    2s künstliche Verzögerung), analog `AuthController::handleLogin()`.
  - [`config/config_environment.example.php`](config/config_environment.example.php) neu angelegt (fehlte
    trotz Erwähnung in itdesign.md) — Vorlage mit Platzhalterwerten für beide neuen Konstanten.
  - **Noch zu tun (nicht durch mich möglich)**: Die `config_environment.php` auf dem Produktivserver
    liegt außerhalb dieses Repos. Dort muss manuell ein **neuer, eigener** `MIGRATION_PASSWORD`-Wert
    gesetzt werden (nicht der hier rotierte Entwicklungswert) sowie `MIGRATIONS_TOOL_ENABLED = false`.
- **Generisches Rate-Limiting**: [`app/Services/RateLimitService.php`](app/Services/RateLimitService.php)
  (neu) + [`app/Models/RateLimitAttempt.php`](app/Models/RateLimitAttempt.php) (neu), in
  [`index.php`](index.php) direkt nach dem Session-Timeout-Check aufgerufen. Aus
  `bukido.solutor.de/app/Services/RateLimitService.php` übernommen, aber **korrigiert**: Die bukido-Fassung
  zählt in `$_SESSION` — gegen Bots/Skripte ohne Cookie-Unterstützung wäre das wirkungslos, da jede Anfrage
  eine neue Session bekäme. Diese Fassung zählt stattdessen pro IP in der (bereits vorhandenen)
  `login_attempts`-Tabelle, wiederverwendet für einen neuen Identifier-Namensraum `ratelimit|<ip>`.
  120 Anfragen/Minute normal, 10/Minute bei verdächtigem User-Agent, danach 15 Minuten IP-Sperre (HTTP 429).
- **Security-Header**: [`.htaccess`](.htaccess) um `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`,
  `Strict-Transport-Security` sowie `Options -Indexes` ergänzt. **Bewusst kein Content-Security-Policy**:
  die Anwendung lädt Bootstrap/DataTables/jQuery von mehreren CDN-Hosts und nutzt ein Inline-`<script>`
  für die CSRF-Token-Injection (`footer.php`) — eine korrekte CSP müsste alle Quellen exakt allowlisten;
  mangels Browser-Testmöglichkeit in dieser Session nicht risikofrei umsetzbar (siehe Abschnitt 2).
- `config/.htaccess` (Zugriff auf `config/`-Verzeichnis bis auf `migrations.php` gesperrt) war bereits
  vorhanden, keine Änderung nötig.

### Phase 8 — Volltextsuche & Datenexport (concept.md 4.13/4.14, itdesign.md Abschnitt 9/11, 02.07.2026)
- **Volltextsuche (4.13)**: `Views/persons/list.php` erhielt eine zusätzliche, mit `d-none` ausgeblendete
  Spalte mit den Notizen je Zeile; `js/persons-list.js` markiert diese Spalte (Index 6) über
  `columnDefs: { targets: 6, visible: false, searchable: true }` als durchsuchbar. Läuft komplett über das
  bereits vorhandene DataTables-Standardsuchfeld — kein neuer Endpoint, kein Server-Request. Neuer
  i18n-Schlüssel `person.list.col.notes`.
- **Datenexport (4.14)**: `ProfileController::export()` (neu) liefert unter `?page=profile&action=export`
  (GET, kein CSRF nötig da rein lesend) einen SQL-Dump der eigenen `persons`+`interactions` als Download.
  `Interaction::getAllForUser()` (neu, analog `getAllForPerson()` ohne Personenfilter) sowie
  `app/Services/ExportService.php` (neu) — baut `INSERT`-Statements programmatisch über eine
  Spalten-Whitelist (filtert dabei berechnete Felder wie `last_contact` aus `Person::getAllForUser()`
  automatisch heraus) und `Database::escape()` (neu, wrapt `mysqli::real_escape_string()`); kein
  Shell-Aufruf von `mysqldump`. Download-Button in `Views/profile/profile.php`, neuer i18n-Schlüssel
  `profile.index.export`.
- **4.15 LinkedIn-Profildaten**: keine Code-Änderung — laut concept.md Abschnitt 8 (A1) vom Auftraggeber
  bestätigter Status quo (nur weiterhin manuelles Freitextfeld `linkedin_profile`).

## 2. Lokal getestet (Laragon, MySQL)

Lokale Datenbank `web4714_crm` wurde angelegt und alle vier Migrationen über das bestehende
[`config/migrations.php`](config/migrations.php)-Tool ausgeführt (0 Fehler). Per curl (Cookie-Jar, Sessions)
end-to-end verifiziert:

- Registrierung erster Benutzer → automatisch `admin`; zweiter Benutzer → `inactive`.
- Rechen-Captcha wird korrekt geprüft (falsche Antwort → Fehler).
- Login mit `inactive`-Rolle wird abgewiesen ("wartet auf Freischaltung"), ohne Session zu setzen.
- Admin schaltet Benutzer frei (Rolle → `user`) über die UI.
- Kontakt anlegen, Detailansicht, Liste, Circle-Filter (Treffer + Leerfall), Sortierung.
- Update mit Leerstring bei `priority` → Spalte wird echtes `NULL` (nicht `''`), verifiziert per SQL.
- **Ownership-Isolation**: zweiter Benutzer sieht/erreicht den Kontakt des ersten Benutzers nicht
  (Redirect + "Zugriff verweigert", leere eigene Liste).
- **RBAC**: Nicht-Admin bekommt auf `?page=admin` HTTP 403.
- **CSRF-Schutz**: POST ohne gültiges Token wird abgelehnt, kein Datensatz wird angelegt.
- **Admin-Selbstschutz**: Admin kann sich selbst nicht löschen/ändern.
- Löschen eines Kontakts, Logout (Session zerstört, Dashboard danach nicht mehr erreichbar).

**Phase 3 (Interaktionen)**, zusätzlich mit einem eigens registrierten Testbenutzer verifiziert:
- Interaktion anlegen über das inline-Formular in `persons/view.php`, Anzeige in der Liste mit korrektem
  deutschem Label (`PHONE_CALL` → „Telefonat“).
- Bearbeiten (Datum/Art/Memo) und Löschen der eigenen Interaktion.
- Validierung: leeres `interaction_date` bzw. `interaction_type` außerhalb der Whitelist wird mit
  Fehlermeldung abgelehnt, kein Datensatz landet in der DB.
- **Ownership-Isolation**: Zugriff auf die Detailseite einer fremden Person, sowie Bearbeiten/Löschen einer
  fremden Interaktion (auch bei korrekt geratener `interaction_id`) werden mit Redirect abgewiesen, ohne
  dass ein Datensatz verändert wird; ein POST mit fremder `person_id` beim Anlegen wird ebenfalls
  blockiert.
- Testdaten (Testbenutzer, Testkontakt, Testinteraktionen) im Anschluss wieder aus der lokalen DB entfernt.

**Phase 5 (Profil)**, ebenfalls mit einem eigens registrierten Testbenutzer verifiziert:
- Verbotsfall zuerst: `?page=profile` ohne Login → Redirect zum Login.
- Persona speichern über das Edit-Formular, per SQL gegengeprüft.
- Passwortänderung: falsches aktuelles Passwort → Fehlermeldung, Passwort bleibt unverändert (mit
  altem Passwort weiterhin einloggbar); korrektes aktuelles Passwort + neues Passwort → Änderung erfolgreich,
  Login mit dem neuen Passwort funktioniert.
- Validierung: neues Passwort < 8 Zeichen bzw. Bestätigungsfeld weicht ab → jeweils abgelehnt mit
  Fehlermeldung, kein Passwort geändert.
- Token-/Kostenanzeige mit echten Werten (488 / 1132 / 1,50 €) geprüft — korrekte deutsche Zahlenformatierung
  bei den Kosten (Komma statt Punkt).
- Testbenutzer im Anschluss wieder aus der lokalen DB entfernt.

Keine Browser-UI-Prüfung (Bootstrap-Rendering, Responsive, JS-Interaktionen) — nur serverseitige Logik via
curl. **Empfehlung**: vor Abnahme einmal im echten Browser durchklicken.

**Phase 6 (Härtung)**, per curl gegen die lokale DB verifiziert:
- `config/migrations.php`: falsches Passwort erhöht `attempts` in `login_attempts`
  (`migrations_tool|<ip>`); nach 5 Fehlversuchen Sperre für 15 Min., auch das jetzt korrekte (rotierte)
  Passwort wird während der Sperre abgelehnt; nach Ablauf/Reset funktioniert Login mit dem neuen
  Passwort und setzt den Zähler zurück. Produktions-Sperre (`ENVIRONMENT=production` +
  `MIGRATIONS_TOOL_ENABLED` false/fehlend/true) isoliert per PHP-CLI durchgespielt, alle drei Fälle
  korrekt (blockiert/blockiert/durchgelassen).
- `RateLimitService`: normaler Request unauffällig; 11 Requests mit botartigem User-Agent
  (`curl-test-bot/1.0`) innerhalb einer Minute → ab dem 11. Request HTTP 429, Folge-Request mit
  normalem Browser-User-Agent von derselben IP ebenfalls 429 (Sperre wirkt IP-weit, nicht nur UA-weit).
  Nach Zurücksetzen der Sperre: vollständiger Login-Durchlauf (Login → Dashboard → Kontaktliste) weiterhin
  fehlerfrei — keine Regression durch den neuen Check vor dem Routing.
- Security-Header: per `curl -I` bestätigt, dass `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy` und `Strict-Transport-Security` auf jeder Seite gesetzt werden.
- Alle Testdaten (Testbenutzer, `login_attempts`-Testeinträge) im Anschluss entfernt.

**Phase 8 (Volltextsuche & Datenexport)**, mit zwei eigens angelegten Testbenutzern (`exporttest_a`/`_b`,
je ein Testkontakt mit eindeutiger Notiz, einer davon zusätzlich mit einer Testinteraktion) per curl
end-to-end verifiziert:
- Kontaktliste liefert die verdeckte Notizen-Spalte korrekt mit dem eigenen Notiztext; die Notiz des
  jeweils anderen Benutzers taucht nicht auf (Ownership über das bestehende `getAllForUser()` bereits
  gegeben, keine Regression).
- Export von Benutzer A enthält ausschließlich dessen eigene Person (inkl. Interaktion), Export von
  Benutzer B ausschließlich dessen eigene Person — keine Daten des jeweils anderen Benutzers in beiden
  Dumps.
- Verbotsfall: `?page=profile&action=export` ohne Login → Redirect zum Login, kein Dump wird ausgeliefert.
- Der erzeugte SQL-Dump wurde gegen eine frische, migrierte Testdatenbank importiert und lieferte die
  exakt exportierten Zeilen zurück — Dump ist syntaktisch korrekt und reimportierbar. **Beobachtung dabei**:
  Der Import scheitert an der FK-Constraint auf `persons.user_id`, wenn die Zieldatenbank noch keine
  passende `users`-Zeile hat (Export enthält bewusst keine `users`-Tabelle, siehe concept.md 4.14) — das
  ist jetzt im Hinweiskommentar am Dump-Anfang dokumentiert.
- Alle Testdaten (Testbenutzer, Testkontakte, Testinteraktion, `login_attempts`-Einträge, Test-DB
  `export_import_test`) im Anschluss entfernt.

## 4. Nächste Schritte

### Phase 4 — KI-Integration (auf Benutzerwunsch zurückgestellt, 02.07.2026: "bringt nicht viel")
- `app/Services/OpenAIResponsesService.php` (aus bukido übernehmbar), `app/Services/PersonPromptBuilder.php`
  (neu, nur reale `persons`-Spalten), `InteractionController::generateAiInteraction()`/`askAi()` als
  JSON-Endpoints, `Person::updateConversationId()`, `User::addTokenUsage()` nach jedem KI-Call.
- Kein konkreter Termin — nächster natürlicher Schritt ist Phase 6, falls Phase 4 dauerhaft zurückgestellt
  bleibt.

### Phase 6 — Härtung & Abnahme (Rest, siehe Abschnitt 1 für bereits Erledigtes)
- **Produktivserver-Config nachziehen** (kann nicht von hier aus erledigt werden): dortige
  `config_environment.php` um einen **neuen, eigenen** `MIGRATION_PASSWORD`-Wert und
  `MIGRATIONS_TOOL_ENABLED = false` ergänzen (siehe Abschnitt 1, Phase 6).
- Browser-UI-Testdurchlauf (siehe Abschnitt 2), inkl. DataTables-Interaktion (Sortier-Klick, Live-Suche,
  Pagination) auf der Kontaktliste — bislang nur serverseitig via curl geprüft.
- Content-Security-Policy nachrüsten (bewusst zurückgestellt, siehe Abschnitt 1, Phase 6) — braucht
  einen echten Browser-Testdurchlauf, um die CDN-/Inline-Script-Allowlist ohne Funktionsbruch zu
  kalibrieren.
- Datenübernahme aus Produktivdatenbank per `mysqldump`/Import — Schema-Abgleich bereits erledigt,
  verbleibt nur noch der eigentliche Datenimport kurz vor Go-Live.
- **Empfehlung, nicht umgesetzt**: den GitHub-Commit-Verlauf mit dem alten Klartext-Passwort per
  History-Rewrite bereinigen (z. B. `git filter-repo`) — auf Wunsch des Benutzers zurückgestellt, da
  reines Rotieren des Passworts als ausreichend bewertet wurde. Bei Bedarf später nachholbar, erfordert
  dann Force-Push und Neuklonen für alle Mitentwickler.

## 5. Weiterhin offene Entscheidungen

- `SESSION_TIMEOUT` steht auf 8h (28800s, bukido-Standard) — für ein persönliches Tool ggf. bewusst
  kürzer wählen.
- `documentation/`-Ordner: löschen oder neu schreiben (siehe Abschnitt 3, letzter Punkt).
- Passwort-vergessen-Flow: einführen (erfordert Mail-Versand-Entscheidung) oder dauerhaft weglassen
  (Admin setzt bei Bedarf manuell ein neues Passwort)?

## 6. Nicht im Scope

- Kein Composer/Framework-Unterbau.
- Keine `en.php`-Übersetzung für den ersten Release.
- Kein Team-/Org-Datenmodell.
