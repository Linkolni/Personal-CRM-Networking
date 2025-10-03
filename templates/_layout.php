<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titel aus der Firmenkonstante -->
    <title><?= COMPANY_NAME ?? 'CRM' ?></title>

    <!-- ======================================================= -->
    <!-- FAVICON-DEKLARATION (HIER EINFÜGEN) -->
    <!-- ======================================================= -->
    <!-- Standard-Favicon für ältere Browser -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Moderne PNG-Favicons in verschiedenen Größen -->
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">

    <!-- Apple Touch Icon (für Homescreen auf iPhones/iPads) -->
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
    <!-- ======================================================= -->
    <!-- ENDE FAVICON-DEKLARATION -->
    <!-- ======================================================= -->


    <!-- Bootstrap & Bootstrap Icons laden -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Eigene Styles nach Bootstrap laden, damit sie überschreiben können -->
    <link rel="stylesheet" href="css/custom.css">
</head>

<!--
    Der Body wird als Flexbox-Spalte angelegt.
    d-flex flex-column: Der Body wird von oben nach unten ausgerichtet.
    min-vh-100: Body füllt stets mindestens die ganze Fensterhöhe (viewport height).
    Dadurch befinden sich Header am oberen Rand und Footer am unteren Rand,
    selbst wenn der Inhalt zwischendrin sehr wenig ist (Sticky Footer).
-->

<body class="d-flex flex-column min-vh-100">

    <!-- =========================================
         HEADERBEREICH
         Zeigt Logo + Firmenname + Login-Info
         Farben und Logo sind über config.php variabel.
         ========================================= -->
    <header class="p-3" style="background-color: <?= COMPANY_BACKGROUNDCOLOR ?>; color: <?= COMPANY_COLOR ?>;">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <!-- Linke Kopfzeile: Logo und App-Name -->
            <a href="index.php" class="d-flex align-items-center text-decoration-none"
                style="color: <?= COMPANY_COLOR ?>;">
                <!-- Firmenlogo (fixierte Höhe, Breite automatisch!) -->
                <img src="<?= COMPANY_LOGO ?>" alt="Logo" class="me-2"
                    style="height: 35px; width: auto; max-width: 80px;">
                <!-- Anwendungsname -->
                <span class="h3 mb-0"><?= COMPANY_NAME ?></span>
            </a>

            <!-- Rechte Kopfzeile: Login-Status und Logout-Link, Admins mit Admin-Link -->
            <div>
                <span>Angemeldet als: <?= htmlspecialchars($username) ?></span>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-warning btn-sm ms-2">Admin</a>
                <?php endif; ?>


                <a href="logout.php" class="btn btn-sm ms-3"
                    style="border-color: <?= COMPANY_COLOR ?>; color: <?= COMPANY_COLOR ?>;">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- =========================================
         HAUPTINHALT
         flex-grow-1: Nimmt im Flexbody automatisch alle verbleibenden Pixel zwischen Header und Footer ein.
         Dadurch bleibt der Footer unten am Bildschirm (Sticky-Footer-Prinzip).
         ========================================= -->
    <main class="container-fluid mt-4 flex-grow-1">
        <div class="row main-content-row h-100">

            <!-- Linkes Panel (Personen-Liste, z. B. 50% breit) -->
            <div id="list-panel" class="col-md-6 border-end">
                <?php require_once 'persons_list.php'; ?>
            </div>

            <!-- Rechtes Panel (Detailbereich, 50% breit) -->
            <div id="details-panel" class="col-md-6">
                <!-- Inhalt wird via JavaScript dynamisch eingefügt -->
            </div>
        </div>
    </main>

    <!-- =========================================
         FOOTERBEREICH
         Farben analog zu Header.
         mt-auto: Schiebt Footer im Flexcontainer ans untere Ende.
         ========================================= -->
    <footer class="text-center p-3 mt-auto"
        style="background-color: <?= COMPANY_BACKGROUNDCOLOR ?>; color: <?= COMPANY_COLOR ?>;">
        <!-- Angepasste Schriftgröße (z. B. via Bootstrap-Klasse, falls erwünscht) -->
        <span class="fs-5">
            &copy; <?= date('Y') ?> <?= COMPANY_NAME ?>
        </span>
    </footer>

    <!-- Bootstrap JS und eigene App-Logik -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>

</html>