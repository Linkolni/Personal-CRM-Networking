<?php
/**
 * 404.php - Seite nicht gefunden
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Seite nicht gefunden</title>
    <link href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 text-center">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 6rem;"></i>
                <h1 class="display-1 fw-bold text-warning">404</h1>
                <h2 class="mb-4">Seite nicht gefunden</h2>
                <p class="lead text-muted mb-4">Die angeforderte Seite konnte nicht gefunden werden.</p>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-house"></i> Zur Startseite
                </a>
            </div>
        </div>
    </div>
</body>
</html>
