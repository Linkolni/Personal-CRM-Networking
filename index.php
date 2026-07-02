<?php

/**
 * index.php - Front Controller (Single Entry Point)
 *
 * Alle Requests laufen durch diese Datei und werden über ?page=...&action=...
 * an Controller/Methoden geroutet (siehe itdesign.md Abschnitt 3).
 *
 * Hinweis: Nur die bereits implementierten Module (Auth, Dashboard, Persons, Interactions, Profile,
 * Admin) sind hier verdrahtet. Statische Seiten (Impressum/Datenschutz) bleiben bewusst externe Links
 * (siehe config.php IMPRESSUM_URL/DATENSCHUTZ_URL), kein eigener PageController (siehe handover.md).
 */

// ============================================================================
// 1. KONFIGURATION LADEN
// ============================================================================
require_once __DIR__ . '/config/config_environment.php';
require_once __DIR__ . '/config/config.php';

// ============================================================================
// 2. SESSION STARTEN
// ============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// 3. BOOTSTRAP - AUTOLOADING
// ============================================================================
require_once __DIR__ . '/bootstrap.php';

App::set('translator', new TranslationService('de'));

// ============================================================================
// 4. SESSION-TIMEOUT PRÜFEN (Inaktivitäts-Check)
// ============================================================================
if (isset($_SESSION['user_id'])) {
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 28800;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?page=login&timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

// ============================================================================
// 4b. RATE-LIMITING (generischer IP-/Bot-Schutz, siehe itdesign.md Abschnitt 4/14)
// ============================================================================
RateLimitService::check();

// ============================================================================
// 5. REQUEST-ROUTING
// ============================================================================
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';

// ============================================================================
// 6. CONTROLLER-MAPPING
// ============================================================================
$controllers = [
    'login'         => 'AuthController',
    'register'      => 'AuthController',
    'logout'        => 'AuthController',
    'dashboard'     => 'DashboardController',
    'persons'       => 'PersonController',
    'interactions'  => 'InteractionController',
    'profile'       => 'ProfileController',
    'admin'         => 'AdminController',
];

$protected_pages = ['dashboard', 'persons', 'interactions', 'profile', 'admin'];
$auth_only_pages = ['login', 'register'];

// ============================================================================
// 7. AUTH-CHECK
// ============================================================================
if (in_array($page, $protected_pages) && !AuthHelper::isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

if (in_array($page, $auth_only_pages) && AuthHelper::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    exit;
}

if ($page === 'admin' && !AuthHelper::isAdmin()) {
    http_response_code(403);
    require_once __DIR__ . '/app/Views/errors/403.php';
    exit;
}

// ============================================================================
// 7b. CSRF-SCHUTZ FÜR ALLE POST-REQUESTS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfSubmitted = $_POST['csrf_token'] ?? '';
    if (!AuthHelper::validateCsrfToken($csrfSubmitted)) {
        http_response_code(403);
        $_SESSION['error'] = 'Ungültige Anfrage (CSRF-Schutz). Bitte die Seite neu laden und erneut versuchen.';
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';
        $parsed = parse_url($referer);
        $safeReferer = (!isset($parsed['host']) || $parsed['host'] === ($_SERVER['HTTP_HOST'] ?? ''))
            ? $referer
            : BASE_URL . '/index.php';
        header('Location: ' . $safeReferer);
        exit;
    }
}

// ============================================================================
// 8. CONTROLLER LADEN
// ============================================================================
if (!isset($controllers[$page])) {
    $page = 'login';
}

$controllerName = $controllers[$page];

if (!class_exists($controllerName)) {
    throw new Exception("Controller-Klasse nicht gefunden: $controllerName");
}

// ============================================================================
// 9. CONTROLLER AUSFÜHREN
// ============================================================================
try {
    $controller = new $controllerName();

    $methodMap = [
        'login' => [
            'index'       => 'showLogin',
            'handleLogin' => 'handleLogin',
        ],
        'register' => [
            'index'          => 'showRegister',
            'handleRegister' => 'handleRegister',
        ],
        'logout' => [
            'index' => 'logout',
        ],
        'dashboard' => [
            'index' => 'index',
        ],
        'persons' => [
            'index'  => 'index',
            'create' => 'create',
            'store'  => 'store',
            'view'   => 'view',
            'edit'   => 'edit',
            'update' => 'update',
            'delete' => 'delete',
        ],
        'interactions' => [
            'store'  => 'store',
            'edit'   => 'edit',
            'update' => 'update',
            'delete' => 'delete',
        ],
        'profile' => [
            'index'          => 'index',
            'edit'           => 'edit',
            'update'         => 'update',
            'changePassword' => 'changePassword',
            'export'         => 'export',
        ],
        'admin' => [
            'index'      => 'users',
            'updateRole' => 'updateRole',
            'delete'     => 'deleteUser',
        ],
    ];

    $method = $methodMap[$page][$action] ?? 'index';

    if (!method_exists($controller, $method)) {
        throw new Exception("Methode nicht gefunden: $controllerName::$method");
    }

    $controller->$method();
} catch (Exception $e) {
    http_response_code(500);

    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<!DOCTYPE html><html lang='de'><head><meta charset='UTF-8'>
        <title>Fehler 500 - Internal Server Error</title>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; padding: 50px; background: #f5f5f5; margin: 0; }
            .error-box { background: white; padding: 30px; border-left: 5px solid #dc3545;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
            h1 { color: #dc3545; margin-top: 0; }
            pre { background: #f8f9fa; padding: 15px; overflow-x: auto; border: 1px solid #dee2e6;
                border-radius: 4px; font-size: 13px; line-height: 1.4; }
        </style></head><body>
            <div class='error-box'>
                <h1>Fehler 500 - Internal Server Error</h1>
                <p><strong>Meldung:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>Datei:</strong> " . htmlspecialchars($e->getFile()) . "</p>
                <p><strong>Zeile:</strong> " . $e->getLine() . "</p>
                <h3>Stack-Trace:</h3>
                <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
                <a href='" . BASE_URL . "/index.php?page=dashboard'>&larr; Zurück zum Dashboard</a>
            </div>
        </body></html>";
    } else {
        require_once __DIR__ . '/app/Views/errors/500.php';
    }
}
