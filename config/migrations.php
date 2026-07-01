<?php
/**
 * migrations.php - Datenbank-Migrationstool
 * Version: 1.0.0
 * Datum: 2026-06-30
 * Änderung: Initiale Version – passwortgeschütztes Web-Tool zum Ausführen von SQL-Migrationen
 */

// --- Konfiguration laden (ENVIRONMENT muss vor config.php definiert sein) ---
require_once __DIR__ . '/../config/config_environment.php';
require_once __DIR__ . '/../config/config.php';

session_start();

// --- Passwort-Authentifizierung ---
$authenticated = !empty($_SESSION['migration_auth']);
$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        unset($_SESSION['migration_auth']);
        $authenticated = false;
    } elseif (isset($_POST['password'])) {
        if (hash_equals(MIGRATION_PASSWORD, $_POST['password'])) {
            $_SESSION['migration_auth'] = true;
            $authenticated = true;
        } else {
            $authError = 'Falsches Passwort.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset' && $authenticated) {
        // Tracking-Tabelle leeren – nächster Aufruf führt alle Migrationen erneut aus
        $resetDb = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $resetDb->set_charset(DB_CHARSET);
        $resetDb->query("TRUNCATE TABLE `_migrations`");
        $resetDb->close();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?reset=1');
        exit;
    }
}

// --- HTML-Kopf ausgeben ---
function renderHead(string $title): void
{
    echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' – Fire Migrations</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .migration-card { font-family: monospace; font-size: .9rem; }
        .status-icon { font-size: 1.2rem; }
    </style>
</head>
<body>
<div class="container py-5" style="max-width:860px">
    <div class="d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-database-gear fs-2 text-primary"></i>
        <div>
            <h1 class="h4 mb-0">Fire – Datenbank-Migrationen</h1>
            <small class="text-muted">' . APP_NAME . ' · ' . ENVIRONMENT . '</small>
        </div>
    </div>';
}

function renderFoot(): void
{
    echo '</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>';
}

// --- Login-Formular anzeigen ---
if (!$authenticated) {
    renderHead('Login');
    echo '<div class="card shadow-sm" style="max-width:400px;margin:0 auto">
    <div class="card-body p-4">
        <h5 class="card-title mb-3"><i class="bi bi-lock-fill text-warning me-2"></i>Passwort erforderlich</h5>';
    if ($authError) {
        echo '<div class="alert alert-danger py-2">' . htmlspecialchars($authError) . '</div>';
    }
    echo '<form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="pw" class="form-label">Migrations-Passwort</label>
                <input type="password" id="pw" name="password" class="form-control" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-unlock me-1"></i>Zugang freischalten
            </button>
        </form>
    </div>
</div>';
    renderFoot();
    exit;
}

// --- MySQLi-Exceptions deaktivieren – Fehler werden manuell über errno/error geprüft ---
mysqli_report(MYSQLI_REPORT_OFF);

// --- Datenbankverbindung aufbauen ---
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_errno) {
    renderHead('Fehler');
    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>
        DB-Verbindung fehlgeschlagen: ' . htmlspecialchars($db->connect_error) . '</div>';
    renderFoot();
    exit;
}
$db->set_charset(DB_CHARSET);

// --- Tracking-Tabelle sicherstellen ---
$db->query("CREATE TABLE IF NOT EXISTS `_migrations` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `filename`      VARCHAR(255)  NOT NULL,
    `executed_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`        ENUM('success','error') NOT NULL,
    `error_message` TEXT          NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// --- Bereits ausgeführte Migrationen laden ---
$done = [];
$res = $db->query("SELECT filename, status, executed_at FROM `_migrations`");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $done[$row['filename']] = $row;
    }
}

// --- SQL-Dateien einlesen und sortieren ---
$files = glob(__DIR__ . '/../migrations/*.sql');
sort($files);

// --- Migrationen ausführen ---
$results = [];

foreach ($files as $filepath) {
    $filename = basename($filepath);

    // Bereits ausgeführt?
    if (isset($done[$filename])) {
        $prev = $done[$filename];
        $results[] = [
            'file'    => $filename,
            'status'  => 'skipped',
            'message' => 'Bereits ausgeführt am ' . $prev['executed_at'],
        ];
        continue;
    }

    // Datei einlesen – multi_query übergibt das SQL direkt an den MySQL-Parser,
    // der Kommentare, mehrzeilige Strings und mehrere Statements korrekt verarbeitet.
    $sql = file_get_contents($filepath);

    $fileOk    = true;
    $fileError = '';

    if (!$db->multi_query($sql)) {
        // Erstes Statement ist bereits fehlerhaft
        $fileOk    = false;
        $fileError = $db->error;
    } else {
        // Alle Result-Sets konsumieren und auf Fehler prüfen
        do {
            if ($res = $db->store_result()) {
                $res->free();
            }
            if ($db->errno) {
                $fileOk    = false;
                $fileError = $db->error;
                // Restliche Results leeren, damit die Verbindung wieder nutzbar ist
                while ($db->more_results()) {
                    $db->next_result();
                    if ($r = $db->store_result()) {
                        $r->free();
                    }
                }
                break;
            }
        } while ($db->more_results() && $db->next_result());
    }

    // Ergebnis in Tracking-Tabelle speichern
    if ($fileOk) {
        $stmt = $db->prepare("INSERT INTO `_migrations` (filename, status) VALUES (?, 'success')");
        $stmt->bind_param('s', $filename);
        $stmt->execute();
        $stmt->close();
        $results[] = [
            'file'    => $filename,
            'status'  => 'success',
            'message' => 'Erfolgreich ausgeführt',
        ];
    } else {
        $stmt = $db->prepare(
            "INSERT INTO `_migrations` (filename, status, error_message) VALUES (?, 'error', ?)"
        );
        $stmt->bind_param('ss', $filename, $fileError);
        $stmt->execute();
        $stmt->close();
        $results[] = [
            'file'    => $filename,
            'status'  => 'error',
            'message' => $fileError,
        ];
    }
}

$db->close();

// --- Statistik ---
$countTotal   = count($results);
$countSuccess = count(array_filter($results, fn($r) => $r['status'] === 'success'));
$countSkipped = count(array_filter($results, fn($r) => $r['status'] === 'skipped'));
$countError   = count(array_filter($results, fn($r) => $r['status'] === 'error'));

// --- Ausgabe ---
renderHead('Ergebnis');

// Hinweis nach Reset
if (!empty($_GET['reset'])) {
    echo '<div class="alert alert-warning">
        <i class="bi bi-arrow-counterclockwise me-2"></i>
        <strong>Tracking zurückgesetzt.</strong> Alle Migrationen werden jetzt erneut ausgeführt.
    </div>';
}

// Zusammenfassung
$summaryClass = $countError > 0 ? 'danger' : ($countSuccess > 0 ? 'success' : 'info');
echo '<div class="alert alert-' . $summaryClass . ' d-flex gap-3 align-items-center mb-4">
    <i class="bi bi-' . ($countError > 0 ? 'x-circle' : 'check-circle') . '-fill fs-4"></i>
    <div>
        <strong>' . $countTotal . ' Migration(en) geprüft</strong> &mdash;
        <span class="text-success fw-semibold">' . $countSuccess . ' ausgeführt</span> &nbsp;·&nbsp;
        <span class="text-secondary">' . $countSkipped . ' übersprungen</span> &nbsp;·&nbsp;
        <span class="text-danger fw-semibold">' . $countError . ' Fehler</span>
    </div>
</div>';

// Tabelle
echo '<div class="card shadow-sm migration-card mb-4">
<div class="card-body p-0">
<table class="table table-hover mb-0">
<thead class="table-dark">
    <tr>
        <th style="width:2.5rem"></th>
        <th>Datei</th>
        <th>Status</th>
        <th>Details</th>
    </tr>
</thead>
<tbody>';

foreach ($results as $r) {
    [$icon, $badgeClass, $label] = match ($r['status']) {
        'success' => ['<i class="bi bi-check-circle-fill text-success status-icon"></i>', 'success', 'Ausgeführt'],
        'skipped' => ['<i class="bi bi-skip-forward-fill text-info status-icon"></i>',    'info',    'Übersprungen'],
        'error'   => ['<i class="bi bi-x-circle-fill text-danger status-icon"></i>',      'danger',  'Fehler'],
        default   => ['', 'secondary', ''],
    };

    echo '<tr>
        <td class="text-center align-middle">' . $icon . '</td>
        <td class="align-middle fw-semibold">' . htmlspecialchars($r['file']) . '</td>
        <td class="align-middle"><span class="badge text-bg-' . $badgeClass . '">' . $label . '</span></td>
        <td class="align-middle text-muted">' . htmlspecialchars($r['message']) . '</td>
    </tr>';
}

echo '</tbody></table></div></div>';

// Reset-Box (immer sichtbar)
echo '<div class="card border-warning mb-4">
<div class="card-body d-flex justify-content-between align-items-center gap-3">
    <div>
        <strong class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Tracking zurücksetzen</strong><br>
        <small class="text-muted">
            Löscht alle Einträge aus <code>_migrations</code> – beim nächsten Aufruf werden alle SQL-Dateien erneut ausgeführt.<br>
            Sinnvoll wenn Migrationen fälschlich als „Ausgeführt" markiert wurden, aber die Tabellen fehlen.
        </small>
    </div>
    <form method="POST" onsubmit="return confirm(\'Wirklich alle Migration-Einträge löschen und von vorne starten?\')">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-outline-warning btn-sm text-nowrap">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset &amp; Neustart
        </button>
    </form>
</div>
</div>';

// Abmelden
echo '<form method="POST" class="text-end">
    <button type="submit" name="logout" value="1" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Abmelden
    </button>
</form>';

renderFoot();
