<?php
/**
 * AdminController.php - Benutzerverwaltung (siehe concept.md 4.3).
 * Nur für Rolle 'admin' erreichbar.
 */
class AdminController
{
    private User $userModel;

    private const VALID_ROLES = ['user', 'admin', 'inactive'];

    public function __construct()
    {
        AuthHelper::requireAdmin();
        $this->userModel = new User();
    }

    public function users(): void
    {
        $users = $this->userModel->getAll();
        $pageTitle = t('admin.users.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/admin/users.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function updateRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=admin');
            exit;
        }

        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        try {
            if ($targetUserId === AuthHelper::getUserId()) {
                throw new Exception(t('admin.error.self_action'));
            }

            if (!in_array($role, self::VALID_ROLES, true)) {
                throw new Exception('Ungültige Rolle.');
            }

            $target = $this->userModel->findById($targetUserId);
            if (!$target) {
                throw new Exception(t('person.error.not_found'));
            }

            $this->userModel->updateRole($targetUserId, $role);
            $_SESSION['success'] = t('admin.success.role_updated');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?page=admin');
        exit;
    }

    public function deleteUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=admin');
            exit;
        }

        $targetUserId = (int)($_POST['user_id'] ?? 0);

        try {
            if ($targetUserId === AuthHelper::getUserId()) {
                throw new Exception(t('admin.error.self_action'));
            }

            $target = $this->userModel->findById($targetUserId);
            if (!$target) {
                throw new Exception(t('person.error.not_found'));
            }

            // Kaskadiert über FK ON DELETE CASCADE auf persons/interactions (siehe concept.md 4.3)
            $this->userModel->delete($targetUserId);
            $_SESSION['success'] = t('admin.success.user_deleted');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?page=admin');
        exit;
    }
}
