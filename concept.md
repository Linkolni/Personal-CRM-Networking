# Fachkonzept: Personal CRM (Networking-Tool)

Status: Ist-Analyse der bestehenden Anwendung (`crm.solutor.de`) als Grundlage für einen vollständigen Neuaufbau.
Das Datenmodell (siehe Abschnitt 7) bleibt beim Neuaufbau **unverändert**. Die Anwendungslogik, Architektur und
Oberfläche werden neu implementiert — perspektivisch nach demselben Bauplan wie im Referenzprojekt
`bukido.solutor.de` (MVC, siehe Abschnitt 9).

---

## 1. Zweck und Zielsetzung

Ein schlankes, selbst gehostetes "Personal CRM" für die bewusste Pflege des eigenen beruflichen/privaten
Netzwerks. Anders als ein klassisches Firmen-CRM verwaltet jeder Benutzer **seine eigenen** Kontakte
("Personen") und protokolliert Interaktionen (Anrufe, E-Mails, Treffen ...) mit ihnen. Ziel ist, keine
wichtige Beziehung durch Priorisierung, Gruppierung ("Circles") und einen fälligkeitsbasierten
Kontaktzyklus zu vernachlässigen.

## 2. Anwenderrollen

| Rolle | Rechte |
|---|---|
| `inactive` | Kann sich registrieren/einloggen, aber keine Anwendungsfunktion nutzen (Login wird blockiert, bis freigeschaltet). Default-Rolle bei Registrierung, sobald bereits ein Benutzer existiert. |
| `user` | Voller Zugriff auf die eigenen Kontakte, Interaktionen, KI-Funktionen und das eigene Profil. |
| `admin` | Wie `user`, zusätzlich Zugriff auf den Adminbereich (Benutzerverwaltung: Rolle ändern, Benutzer löschen). Der **erste** registrierte Benutzer im System wird automatisch `admin`. |

Jeder Benutzer sieht und bearbeitet ausschließlich seine eigenen Personen/Interaktionen (mandantenfähig
auf Benutzerebene, kein Datenaustausch zwischen Benutzern vorgesehen).

## 3. Domänenmodell (fachlich)

### Benutzer (User)
Ein Account mit Zugangsdaten, Rolle, einer frei definierbaren "Persona" (Stilbeschreibung für die
KI-Textgenerierung) sowie kumulierten KI-Nutzungskennzahlen (gesendete/generierte Tokens, Kosten in €).

### Person (Kontakt)
Der zentrale fachliche Gegenstand: ein Netzwerkkontakt mit Stammdaten (Name, Firma, Position, Kontaktwege,
Social/Web-Links, Geburtstag), Klassifizierung (Status, Priorität, Circles, Kontaktzyklus) und Freitext-Notizen.
Jede Person gehört genau einem Benutzer.

### Interaktion
Ein protokollierter Kontaktpunkt mit einer Person (Datum, Art, Memo). Interaktionen bilden die
Kommunikationshistorie, aus der sich u. a. der Fälligkeitsstatus (Ampel) und der KI-Kontext ableiten.

## 4. Funktionsbereiche

### 4.1 Authentifizierung & Zugriffssteuerung
- Login mit Benutzername/Passwort (`password_hash`/`password_verify`).
- Nach Login: Session mit `user_id`, `username`, `role`.
- Zugriffsschutz: Jede Seite prüft `is_user_logged_in()`; Rollen `inactive` haben keinen Zugriff, werden
  nach erfolgreicher Passwortprüfung sofort wieder ausgeloggt mit Hinweis "Account wartet auf Freischaltung".
- **Brute-Force-Schutz**: Fehlgeschlagene Logins werden pro IP protokolliert (`login_attempts`); nach
  5 Fehlversuchen innerhalb von 5 Minuten wird die IP für den Rest dieses Zeitfensters gesperrt. Bei jedem
  Fehlversuch wird künstlich 2 Sekunden verzögert.
- Logout löscht die Session vollständig inkl. Session-Cookie.

### 4.2 Registrierung & Freischaltung
- Öffentliches Registrierungsformular (Benutzername, Passwort) mit einfachem Rechen-Captcha
  (Session-basiert, Addition zweier Zufallszahlen) gegen Bots.
- Neue Accounts erhalten Rolle `inactive` (außer dem allerersten Account im System → `admin`) und müssen
  von einem Admin freigeschaltet werden.

### 4.3 Admin-Benutzerverwaltung
- Nur für Rolle `admin` erreichbar.
- Tabellarische Übersicht aller Benutzer (ID, Name, Rolle, Registrierungsdatum).
- Aktionen je Benutzer: Rolle setzen auf `user`/`admin`/`inactive`, oder Benutzer vollständig löschen
  (kaskadiert über FK auf dessen Personen und Interaktionen).

### 4.4 Kontaktverwaltung (Personen CRUD)
- **Anlegen**: Formular im Detailbereich; einziges Pflichtfeld ist der Nachname. Neue Kontakte erhalten
  Status `NEW`.
- **Lesen**: Klick auf einen Listeneintrag öffnet eine schreibgeschützte Detailansicht (Kontaktdaten,
  Priorität, Status, Notizen, klickbare `mailto:`-Links).
- **Bearbeiten**: Eigenes Formular mit allen Feldern, aus der Detailansicht oder direkt aus der Liste
  erreichbar.
- **Löschen**: Nur aus der Bearbeitungsansicht, mit Sicherheitsabfrage. Löscht kaskadierend alle
  zugehörigen Interaktionen. Serverseitig wird geprüft, dass die Person dem angemeldeten Benutzer gehört.
- Erfasste Felder: Vor-/Nachname, zwei E-Mail- und zwei Telefonfelder, Firma, Position, LinkedIn-Profil,
  Website, Geburtstag, Status (`NEW`/`ACTIVE`/`INACTIVE`), Priorität (`TOP10`/`TOP25`/`TOP50`/`TOP100`),
  Circles (kommaseparierter Freitext), Kontaktzyklus, Notizen.

### 4.5 Circles (Gruppierung/Tags) & Filterung
- Circles sind frei vergebene, kommaseparierte Schlagworte pro Person (kein eigenes Datenmodell-Objekt,
  reiner Textstring in `persons.circles`).
- Aus allen Personen eines Benutzers wird serverseitig eine bereinigte, dedupliziert-sortierte Liste aller
  vorkommenden Circle-Namen ermittelt.
- Diese werden als klickbare Filter-Badges über der Kontaktliste angezeigt (inkl. "Alle"-Badge zum
  Zurücksetzen); ein aktiver Filter reduziert die Liste clientseitig auf Personen, deren Circle-Liste den
  gewählten Begriff enthält.

### 4.6 Kontakt-Sortierung
- Die Kontaktliste ist über Klick auf Spaltenüberschriften sortierbar (Name, Firma, Priorität, Kontaktzyklus,
  letzter Kontakt); erneuter Klick kehrt die Richtung um. Serverseitig gegen eine feste Whitelist erlaubter
  Sortierfelder abgesichert.

### 4.7 Kontaktzyklus & Fälligkeits-Ampel
- Jeder Person kann ein Kontaktzyklus zugewiesen werden (wöchentlich, zweiwöchentlich, monatlich,
  quartalsweise, halbjährlich, jährlich).
- Je Person wird das Datum der letzten Interaktion ermittelt (`MAX(interaction_date)`).
- Daraus berechnet die Oberfläche eine Ampel je Kontakt:
  - **grau**: kein Kontaktzyklus definiert.
  - **rot**: Zyklus definiert, aber noch nie Kontakt gehabt.
  - **grün**: letzter Kontakt liegt innerhalb der Zyklusdauer.
  - **gelb**: überfällig, aber innerhalb der doppelten Zyklusdauer.
  - **rot**: seit mehr als der doppelten Zyklusdauer kein Kontakt ("stark überfällig").

### 4.8 Interaktionsprotokoll (CRUD)
- Je Person beliebig viele Interaktionen mit Datum, Art (Kaffee-Treffen, E-Mail, LinkedIn-Nachricht,
  Telefonat, Mittagessen, Meeting, Konferenz, Sonstiges) und optionalem Freitext-Memo.
- Anlegen/Bearbeiten über ein Formular im Detailbereich; die Liste ist absteigend nach Datum sortiert.
- Löschen einzelner Interaktionen mit Sicherheitsabfrage.
- Serverseitige Berechtigungsprüfung beim Bearbeiten (Interaktion muss über die zugehörige Person dem
  angemeldeten Benutzer gehören).

### 4.9 KI-Funktionen
Integration der OpenAI "Responses"-API (aktuell konfiguriertes Modell: `gpt-5-nano`), pro Benutzer über eine
frei definierbare "Persona" als System-Instruktion personalisierbar.

- **Freitext-Anfrage** (`ask_ai`): Benutzer kann im Kontext einer Person einen beliebigen Prompt an die KI
  stellen und die Antwort erhalten (aktuell nicht in `app.js` mit einem UI-Element verdrahtet, aber
  API-seitig vorhanden).
- **Automatischer Follow-up-Vorschlag** (`generate_ai_interaction`): Aus Personendaten und der kompletten
  Interaktionshistorie wird automatisch ein Prompt gebaut ("Schreibe eine Kontaktaufnahme per E-Mail ..."),
  die KI-Antwort wird direkt als neue Interaktion vom Typ E-Mail mit heutigem Datum gespeichert.
- Konversationskontext: Es wird versucht, eine `openai_conversation_id` je Person zu persistieren, damit
  spätere Anfragen auf den bisherigen Chatverlauf aufbauen (siehe bekannter Bug in Abschnitt 8).
- Token-/Kostenzähler pro Benutzer sind im Datenmodell vorgesehen (`tokens_sent`, `tokens_generated`,
  `tokens_cost`), werden aber aktuell im KI-Ablauf nicht befüllt (nur `update_user_tokens()` existiert als
  Funktion, wird im KI-Flow nicht aufgerufen).

### 4.10 Benutzerprofil
- Eigene Seite `profile.php`: Anzeige von Benutzername sowie Token-/Kosten-Kennzahlen (nur lesend).
- Editierbar: Persona-Freitext (Stil-/Rollenbeschreibung für die KI) sowie Passwortänderung
  (aktuelles Passwort zur Verifikation, neues Passwort ≥ 8 Zeichen, Bestätigungsfeld).

### 4.11 Rechtliches / Sonstiges
- Impressum und Datenschutzerklärung als statische Textseiten, über Konstanten in `config.php` verlinkt.
- Firmenbranding (Name, Logo, Hintergrund-/Schriftfarbe) ist über Konstanten in `config.php` konfigurierbar
  und wird in Header/Login/Footer verwendet (White-Label-fähig für einen Betreiber pro Instanz).
- Dashboard/Startseite (`home_content.php`) zeigt eine Willkommensmeldung sowie eine Liste der
  "Top 10"-Kontakte (Filter auf `priority = TOP10`) — im aktuellen Code allerdings nicht mehr in den
  regulären Seitenaufruf (`index.php`) eingebunden, sondern nur als Template-Fragment vorhanden.
  Soll-Konzept für den Neuaufbau: siehe 4.12.

### 4.12 Dashboard (Soll-Konzept für den Neuaufbau)

Das Dashboard ist die Startseite nach dem Login und beantwortet auf einen Blick die Kernfrage des
Systems: "Wen sollte ich als Nächstes kontaktieren?" Es ist eine rein lesende Aggregation vorhandener
Daten — keine Änderung am Datenmodell (Abschnitt 7), keine eigenen Bearbeitungsfunktionen. Alle Blöcke
zeigen ausschließlich Daten des angemeldeten Benutzers (Ownership gemäß Abschnitt 2); jeder
Personeneintrag verlinkt auf die Detailansicht (4.4). Ein Block ohne Inhalt zeigt einen kurzen
Hinweistext statt einer leeren Tabelle. Anzeigereihenfolge (zweispaltiges Raster): D1, D4, D3, D2, D5, D6,
D7, D8.

| ID | Stufe | Block | Anforderung |
|---|---|---|---|
| D1 | MUSS | Fällige Kontakte | Zeigt Personen mit Ampelstatus gelb oder rot (Berechnung gemäß 4.7), sortiert rot vor gelb, innerhalb gleicher Farbe längste Zeit seit letzter Interaktion zuerst; maximal 8 Einträge. Je Eintrag: Name, Firma, Priorität, Ampel, Datum der letzten Interaktion. |
| D2 | MUSS | Anstehende Geburtstage | Zeigt Personen, deren Geburtstag (Jahrestag) innerhalb der nächsten 14 Tage inkl. heute liegt, sortiert nach Nähe zum heutigen Datum; heutige Geburtstage sind hervorgehoben; maximal 5 Einträge. Je Eintrag: Name, Datum. |
| D3 | MUSS | Wichtigste Kontakte | Zeigt Personen mit Priorität `TOP10` inkl. Ampelstatus (Fortführung der bisherigen Startseiten-Funktion aus 4.11); auf der Oberfläche als "Top 5" beschriftet, maximal 5 Einträge (Kürzung gegenüber der zugrundeliegenden Priorität `TOP10`, die als Klassifizierung in 4.4 unverändert bleibt). |
| D4 | SOLL | Unbearbeitete neue Kontakte | Zeigt Personen mit Status `NEW`, älteste zuerst — als Erinnerung, Priorität, Circles und Kontaktzyklus zu vergeben; maximal 8 Einträge. |
| D5 | SOLL | Letzte Aktivitäten | Zeigt die 3 jüngsten Interaktionen über alle Personen des Benutzers (Datum, Art, Personenname, Memo-Anriss). |
| D6 | SOLL | Netzwerk-Kennzahlen | Zeigt als Kennzahl ausschließlich die Anzahl Kontakte je Ampelfarbe (grün/gelb/rot/grau). |
| D7 | KANN | Aus den Augen verloren | Zeigt maximal 5 Personen ohne Kontaktzyklus, deren letzte Interaktion länger als 6 Monate zurückliegt oder die noch nie eine Interaktion hatten — als Impuls, den "grauen" Bestand zu reaktivieren oder auszusortieren. |
| D8 | KANN | Circle-Übersicht | Zeigt je Circle die Anzahl zugeordneter Personen als klickbares Badge, verlinkt auf die entsprechend vorgefilterte Kontaktliste (4.5). |

**Annahmen** (bei Abnahme zu bestätigen): Die Zahlenwerte in D1-D5 und D7 (8 Einträge bei D1/D4,
5 Einträge bei D2/D3, 14 Tage bei D2, 3 Einträge bei D5, 6 Monate bei D7) sind Vorschlagswerte ohne
fachliche Vorgabe des Auftraggebers und wurden im Zuge mehrerer Rückmeldungen des Auftraggebers
schrittweise nachjustiert, um die Gesamthöhe des Dashboards zu reduzieren.

### 4.13 Volltextsuche über Kontakte

- Ein Suchfeld oberhalb der Kontaktliste durchsucht bei jedem Kontakt des angemeldeten Benutzers die Felder
  Vor-/Nachname, Firma, Position und Notizen auf Teilstring-Treffer (case-insensitive), clientseitig auf der
  bereits geladenen, vollständigen Kontaktliste — Notizen sind Teil von `persons` und ohnehin Teil des beim
  Laden der Liste übertragenen Datensatzes je Person, es ist kein zusätzlicher Serverzugriff nötig.
- Die Liste reduziert sich sofort auf Treffer; kein Treffer zeigt einen Hinweistext statt einer leeren Tabelle.
- Ergänzt, ersetzt aber nicht die Circle-Filter-Badges (4.5); beide Filter sind gleichzeitig kombinierbar
  (UND-Verknüpfung).
- Ownership gilt wie überall: durchsucht wird ausschließlich der Datenbestand des angemeldeten Benutzers
  (die Liste wird bereits serverseitig auf `user_id` gefiltert geladen, die Suche selbst erfordert dadurch
  keinen eigenen Ownership-Check).

### 4.14 Datenexport (Backup)

- Der angemeldete Benutzer kann über sein Profil (4.10) einen Export **seiner eigenen** Daten (Personen und
  zugehörige Interaktionen) als Datei anstoßen und direkt herunterladen.
- Zweck: Datensicherung/Mitnahme, unabhängig vom Hosting-Betreiber.
- Der Export enthält ausschließlich Datensätze, die dem anfragenden Benutzer gehören (`user_id`-Filterung);
  Zugangsdaten anderer Benutzer, Sessions oder `login_attempts` sind nicht Teil des Exports.
- Format: SQL-Datei mit `INSERT`-Anweisungen für die eigenen Zeilen aus `persons` und `interactions`
  (importierbar in eine kompatible Datenbank).

### 4.15 LinkedIn-Profildaten zu einem Kontakt

- Das Feld `linkedin_profile` (4.4) speichert weiterhin nur eine manuell eingetragene URL/ID. Es gibt bewusst
  **keine** automatisierte Beschaffung oder Anreicherung von LinkedIn-Profildaten (z. B. aktuelle Position,
  Foto, gemeinsame Kontakte) zu einer Person — mangels ohne Weiteres nutzbarem, offiziellem
  LinkedIn-API-Zugang für diesen Zweck (siehe Abschnitt 8, A1: vom Auftraggeber bestätigt, Status quo).

## 5. Geschäftsregeln / Validierungen (zusammengefasst)

- Nachname ist das einzige Pflichtfeld einer Person.
- Leere Werte bei `birthday`, `priority`, `contact_cycle`, `openai_conversation_id` werden beim Update zu
  `NULL` normalisiert (wegen ENUM/DATE-Spalten in der DB).
- Update von Personen läuft über eine Feld-Whitelist (Schutz vor Mass Assignment); `user_id` und Zeitstempel
  sind darüber nicht änderbar.
- Löschen einer Person ist nur dem Eigentümer (`user_id`-Abgleich) erlaubt.
- Bearbeiten einer Interaktion ist nur erlaubt, wenn die zugehörige Person dem angemeldeten Benutzer gehört.
- Registrierung: Benutzername muss eindeutig sein; neue Accounts starten `inactive`, außer es ist der
  allererste Account.
- Login: gesperrte IPs (Brute-Force) werden vor jeder Formularverarbeitung abgewiesen; `inactive`-Accounts
  werden nach Passwortprüfung sofort wieder ausgeloggt.
- Datenexport (4.14) filtert serverseitig zwingend auf `user_id` des angemeldeten Benutzers. Die
  Volltextsuche (4.13) läuft rein clientseitig auf der bereits `user_id`-gefiltert geladenen Kontaktliste
  und benötigt daher keinen eigenen serverseitigen Ownership-Check.

## 6. Nicht-funktionale Aspekte (Ist-Zustand)

- **Technologie**: PHP (prozedural, Funktionsbibliotheken statt Klassen) + MySQL/MariaDB über PDO mit
  Prepared Statements; Bootstrap 5 + Bootstrap Icons als UI-Basis; Vanilla-JS-SPA-artiges Frontend
  (`app.js`) mit Fetch-API gegen einen zentralen AJAX-Dispatcher (`api.php`).
- **Architektur**: Kein MVC — feste PHP-Dateien pro Seite (`index.php`, `admin.php`, `profile.php`,
  `login.php`, `register.php`), Funktionsbibliotheken (`persons_functions.php`, `user_functions.php`,
  `ai_functions.php`) statt Models, HTML direkt in PHP bzw. als Template-Strings in JavaScript.
  Geeignet für einfaches Shared-Hosting, nicht für Skalierung/Testbarkeit optimiert.
- **Zielgruppe/Deployment**: Single-Server-Betrieb auf Standard-Webhosting, mandantenfähig nur auf
  Benutzerebene (kein Team-/Org-Konzept).
- **Internationalisierung**: nicht vorhanden, alle Texte fest auf Deutsch codiert.
- **Sicherheit (vorhanden)**: Passwort-Hashing, durchgängig Prepared Statements, Login-Rate-Limiting per IP,
  Rollenmodell mit Sperrzustand `inactive`, htmlspecialchars-Escaping im Frontend gegen XSS.

## 7. Datenmodell (unverändert zu übernehmen)

Quelle: [`datamodel.sql`](datamodel.sql). Wird beim Neuaufbau **1:1 beibehalten**.

- **`users`**: `id`, `username` (unique), `password_hash`, `persona`, `role` (`user`/`admin`/`inactive`),
  `tokens_sent`, `tokens_generated`, `tokens_cost`, `created_at`.
- **`persons`**: `person_id`, `user_id` (FK → `users.id`, `ON DELETE CASCADE`), `first_name`, `last_name`
  (Pflicht), `email1/2`, `phone1/2`, `company`, `position`, `linkedin_profile`, `website`, `birthday`,
  `status` (`NEW`/`ACTIVE`/`INACTIVE`), `priority` (`TOP10`/`TOP25`/`TOP50`/`TOP100`), `circles`
  (Freitext, kommasepariert), `contact_cycle` (`WEEKLY`…`ANNUALLY`), `notes`, `openai_conversation_id`,
  `created_at`, `updated_at`.
- **`interactions`**: `interaction_id`, `person_id` (FK → `persons.person_id`, `CASCADE`), `user_id`
  (FK → `users.id`, `CASCADE`), `interaction_date`, `interaction_type` (`COFFEE_MEETING`, `EMAIL`,
  `LINKEDIN_MESSAGE`, `PHONE_CALL`, `LUNCH`, `MEETING`, `CONFERENCE`, `OTHER`), `memo`, `created_at`.
- **`login_attempts`**: `id`, `ip_address`, `attempt_time` — rein technisch, kein fachliches Objekt.

## 8. Bekannte Probleme (Ist-Zustand) und Annahmen

- **Bekannter Bug** (referenziert aus 4.9): Die Speicherung der `openai_conversation_id` erfolgt im
  Ist-System fehlerhaft anhand von `id` statt `person_id`, wodurch der Konversationskontext für Folgeanfragen
  nicht zuverlässig wiederhergestellt wird. Im Neuaufbau zu beheben (siehe itdesign.md, Abschnitt 10).
- **A1 — LinkedIn-Profildaten (4.15), vom Auftraggeber bestätigt (02.07.2026)**: Es gibt keinen offiziellen,
  ohne Weiteres nutzbaren LinkedIn-API-Zugang, um Profildaten zu einem gespeicherten Kontakt automatisiert
  abzurufen. Entscheidung: Status quo — es bleibt beim bestehenden manuellen Freitextfeld
  `linkedin_profile`, keine Anbindung einer Drittanbieter-API und kein automatisierter Such-Link.

## 9. Ausblick: Ziel-Architektur für den Neuaufbau

Dieses Fachkonzept beschreibt ausschließlich den **fachlichen** Ist-Zustand. Die technische Umsetzung des
Neuaufbaus steht in itdesign.md
