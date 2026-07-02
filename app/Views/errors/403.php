<?php
/**
 * 403.php - Zugriff verweigert
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Zugriff verweigert</title>
    <link href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 text-center">
                <i class="bi bi-shield-exclamation text-danger" style="font-size: 6rem;"></i>
                <h1 class="display-1 fw-bold text-danger">403</h1>
                <h2 class="mb-4">Zugriff verweigert</h2>
                <p class="lead text-muted mb-4">Sie haben keine Berechtigung, auf diese Seite zuzugreifen.</p>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/index.php?page=dashboard" class="btn btn-primary btn-lg">
                    <i class="bi bi-house"></i> Zum Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
