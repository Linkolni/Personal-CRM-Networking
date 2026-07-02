<?php
/**
 * ProfileController.php - Eigenes Benutzerprofil (siehe concept.md 4.10).
 * Operiert ausschließlich auf dem eingeloggten Benutzer selbst (AuthHelper::getUserId());
 * es wird nirgends eine fremde User-ID aus dem Request entgegengenommen, daher entfällt
 * hier ein zusätzlicher Ownership-Check wie bei Person/Interaction.
 *
 * Versionshistorie:
 * - export() ergänzt: Datenexport als SQL-Datei (concept.md 4.14, itdesign.md Abschnitt 11).
 */
class ProfileController
{
    private User $userModel;
    private Person $personModel;
    private Interaction $interactionModel;

    public function __construct()
    {
        AuthHelper::requireLogin();
        $this->userModel = new User();
        $this->personModel = new Person();
        $this->interactionModel = new Interaction();
    }

    public function index(): void
    {
        $user = $this->loadCurrentUser();
        $pageTitle = t('profile.index.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/profile/profile.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function edit(): void
    {
        $user = $this->loadCurrentUser();
        $pageTitle = t('profile.edit.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/profile/edit-profile.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=profile');
            exit;
        }

        $persona = trim($_POST['persona'] ?? '');
        $this->userModel->updatePersona(AuthHelper::getUserId(), $persona);

        $_SESSION['success'] = t('profile.success.persona_updated');
        header('Location: ' . BASE_URL . '/index.php?page=profile');
        exit;
    }

    public function changePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=profile&action=edit');
            exit;
        }

        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

            $user = $this->loadCurrentUser();

            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception(t('profile.error.current_password_wrong'));
            }

            if (strlen($newPassword) < 8) {
                throw new Exception(t('auth.error.password_length'));
            }

            if ($newPassword !== $newPasswordConfirm) {
                throw new Exception(t('auth.error.password_mismatch'));
            }

            $this->userModel->updatePassword((int)$user['id'], $newPassword);
            $_SESSION['success'] = t('profile.success.password_changed');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?page=profile&action=edit');
        exit;
    }

    /**
     * Datenexport (concept.md 4.14): liefert die eigenen Personen + Interaktionen des
     * angemeldeten Benutzers als SQL-Datei zum Download. Rein lesend, daher als GET-Route
     * ohne CSRF-Prüfung (itdesign.md Abschnitt 11); user_id kommt ausschließlich aus
     * AuthHelper::getUserId(), nie aus dem Request - Ownership ist damit strukturell gegeben.
     */
    public function export(): void
    {
        $userId = AuthHelper::getUserId();

        $persons = $this->personModel->getAllForUser($userId);
        $interactions = $this->interactionModel->getAllForUser($userId);

        $exportService = new ExportService();
        $dump = $exportService->buildSqlDump($persons, $interactions);

        $filename = 'crm-export-' . $userId . '-' . date('Y-m-d') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($dump));
        header('Cache-Control: no-store');
        echo $dump;
        exit;
    }

    /**
     * Lädt den eingeloggten Benutzer. Fail closed: existiert der Benutzer aus der Session
     * nicht mehr in der DB (z.B. zwischenzeitlich gelöscht), wird die Session sofort beendet.
     */
    private function loadCurrentUser(): array
    {
        $user = $this->userModel->findById(AuthHelper::getUserId());

        if (!$user) {
            AuthHelper::destroySession();
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        return $user;
    }
}
