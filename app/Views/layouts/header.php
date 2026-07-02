<?php

/**
 * header.php - Layout-Header mit Navigation.
 * Optionale Variable vor require_once im Controller setzen: $pageTitle
 */

if (AuthHelper::isLoggedIn()) {
    AuthHelper::checkSessionTimeout();
}

$csrfToken  = AuthHelper::generateCsrfToken();
$isLoggedIn = AuthHelper::isLoggedIn();
$userName   = AuthHelper::getUserName();
$nav        = $isLoggedIn ? NavigationHelper::getNav() : [];
$v          = defined('ASSET_VERSION') ? ASSET_VERSION : '1';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">

    <title><?= htmlspecialchars(APP_NAME) ?> - <?= htmlspecialchars($pageTitle ?? t('app.tagline')) ?></title>

    <link href="<?= BASE_URL ?>/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/custom.css?v=<?= htmlspecialchars($v) ?>">

    <link rel="icon" href="<?= BASE_URL ?>/favicon.ico" sizes="32x32">
    <link rel="icon" href="<?= BASE_URL ?>/images/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="icon" href="<?= BASE_URL ?>/images/favicon-32x32.png" type="image/png" sizes="32x32">
</head>
<body class="d-flex flex-column vh-100 <?= $isLoggedIn ? 'logged-in' : '' ?>" data-user-id="<?= (int)AuthHelper::getUserId() ?>">

<?php if ($isLoggedIn): ?>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: <?= defined('APP_BG_COLOR') ? APP_BG_COLOR : '#1a1a1a' ?>;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php?page=dashboard">
                <img src="<?= BASE_URL ?>/<?= defined('APP_LOGO') ? APP_LOGO : '' ?>" width="28" height="28" alt="">
                <span><?= htmlspecialchars(APP_NAME) ?></span>
            </a>

            <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
                <span class="badge bg-warning text-dark ms-1">DEV</span>
            <?php endif; ?>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Navigation öffnen">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($nav as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-1 <?= NavigationHelper::isActive($item) ? 'active' : '' ?>"
                               href="<?= NavigationHelper::url($item) ?>">
                                <?php if (!empty($item['icon'])): ?><i class="bi <?= $item['icon'] ?>"></i><?php endif; ?>
                                <?= t($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?= BASE_URL ?>/index.php?page=logout">
                                    <i class="bi bi-box-arrow-right"></i> <?= t('nav.logout') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="<?= $containerClass ?? 'container' ?> mt-4 main-content flex-grow-1 overflow-auto">
