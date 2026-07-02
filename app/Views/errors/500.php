<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fehler 500 - Server Error</title>
    <link href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 5rem;"></i>
                        <h1 class="mt-4">500 - Interner Server-Fehler</h1>
                        <p class="lead text-muted">
                            Es ist ein technisches Problem aufgetreten. Bitte versuchen Sie es später erneut.
                        </p>
                        <hr>
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/index.php?page=dashboard" class="btn btn-primary">
                            <i class="bi bi-house"></i> Zurück zum Dashboard
                        </a>
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/index.php?page=login" class="btn btn-secondary">
                            <i class="bi bi-box-arrow-in-right"></i> Zum Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
