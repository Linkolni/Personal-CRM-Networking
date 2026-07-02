<?php
/**
 * AuthHelper.php - Session-Handling und RBAC.
 *
 * Bildet das CRM-Rollenmodell (ENUM 'user'/'admin'/'inactive' auf users.role)
 * 1:1 ab, ohne das Schema zu ändern (siehe itdesign.md Abschnitt 4).
 */
class AuthHelper
{
    /**
     * Setzt Session nach erfolgreichem Login.
     * $user muss mindestens id, username, role enthalten.
     */
    public static function setSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function getUserName(): string
    {
        return $_SESSION['user_name'] ?? 'Gast';
    }

    public static function getRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && ($_SESSION['role'] ?? null) === 'admin';
    }

    /**
     * Rolle 'inactive' bedeutet: Account wartet auf Freischaltung durch einen Admin.
     */
    public static function isActive(): bool
    {
        return self::isLoggedIn() && ($_SESSION['role'] ?? null) !== 'inactive';
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        self::requireActive();
    }

    public static function requireActive(): void
    {
        if (!self::isActive()) {
            self::destroySession();
            $_SESSION['error'] = 'Ihr Account wartet noch auf Freischaltung durch einen Administrator.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            $_SESSION['error'] = 'Zugriff verweigert. Admin-Rechte erforderlich.';
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }
    }

    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function checkSessionTimeout(): void
    {
        $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 28800;

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            self::destroySession();
            header('Location: ' . BASE_URL . '/index.php?page=login&timeout=1');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    public static function destroySession(): void
    {
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }
}
