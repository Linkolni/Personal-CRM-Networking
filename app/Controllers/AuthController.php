<?php
/**
 * AuthController.php - Login, Logout, Registrierung (siehe concept.md 4.1/4.2).
 */
class AuthController
{
    private User $userModel;
    private LoginAttempt $loginAttemptModel;

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct()
    {
        $this->userModel = new User();
        $this->loginAttemptModel = new LoginAttempt();
    }

    // ========================================================================
    // LOGIN
    // ========================================================================

    public function showLogin(): void
    {
        if (AuthHelper::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }

        $pageTitle = t('login.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/auth/login.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function handleLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $identifier = $username . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        try {
            if ($username === '' || $password === '') {
                throw new Exception(t('auth.error.missing_credentials'));
            }

            $this->checkLoginAttempts($identifier);

            $user = $this->userModel->findByUsername($username);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($identifier);
                sleep(2); // künstliche Verzögerung gegen Brute-Force (concept.md 4.1)
                throw new Exception(t('auth.error.invalid_credentials'));
            }

            // Passwort korrekt -> Fehlversuch-Zähler zurücksetzen
            $this->loginAttemptModel->resetAttempts($identifier);

            if ($user['role'] === 'inactive') {
                $_SESSION['error'] = t('auth.error.account_inactive');
                header('Location: ' . BASE_URL . '/index.php?page=login');
                exit;
            }

            AuthHelper::setSession($user);

            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                $parsed = parse_url($redirect);
                $isSafe = !isset($parsed['scheme']) && !isset($parsed['host']) && strpos($redirect, '/') === 0;
                header('Location: ' . ($isSafe ? $redirect : BASE_URL . '/index.php?page=dashboard'));
            } else {
                header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            }
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
    }

    private function checkLoginAttempts(string $identifier): void
    {
        $result = $this->loginAttemptModel->findByIdentifier($identifier);

        if ($result && $result['locked_until'] && strtotime($result['locked_until']) > time()) {
            throw new Exception(t('auth.error.account_locked'));
        }
    }

    private function recordFailedAttempt(string $identifier): void
    {
        $this->loginAttemptModel->recordFailedAttempt($identifier);

        $result = $this->loginAttemptModel->findByIdentifier($identifier);
        if ($result && (int)$result['attempts'] >= self::MAX_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
            $this->loginAttemptModel->lock($identifier, $lockUntil);
        }
    }

    // ========================================================================
    // REGISTRIERUNG
    // ========================================================================

    public function showRegister(): void
    {
        if (AuthHelper::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }

        // Rechen-Captcha: zwei Zufallszahlen, Ergebnis in der Session (concept.md 4.2)
        $captchaA = random_int(1, 10);
        $captchaB = random_int(1, 10);
        $_SESSION['captcha_answer'] = $captchaA + $captchaB;
        $captchaQuestion = "$captchaA + $captchaB";

        $pageTitle = t('register.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/auth/register.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function handleRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=register');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $captchaAnswer = (int)($_POST['captcha_answer'] ?? -1);
        $expectedAnswer = $_SESSION['captcha_answer'] ?? null;
        unset($_SESSION['captcha_answer']);

        try {
            if ($username === '' || $password === '' || $passwordConfirm === '') {
                throw new Exception(t('auth.error.register_missing_fields'));
            }

            if ($expectedAnswer === null || $captchaAnswer !== $expectedAnswer) {
                throw new Exception(t('auth.error.captcha_invalid'));
            }

            if (strlen($password) < 8) {
                throw new Exception(t('auth.error.password_length'));
            }

            if ($password !== $passwordConfirm) {
                throw new Exception(t('auth.error.password_mismatch'));
            }

            if ($this->userModel->findByUsername($username)) {
                throw new Exception(t('auth.error.username_taken'));
            }

            $isFirstUser = $this->userModel->count() === 0;
            $this->userModel->create($username, $password);

            $_SESSION['success'] = $isFirstUser
                ? t('auth.success.register_first_admin')
                : t('auth.success.register');

            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?page=register');
            exit;
        }
    }

    // ========================================================================
    // LOGOUT
    // ========================================================================

    public function logout(): void
    {
        AuthHelper::destroySession();
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}
