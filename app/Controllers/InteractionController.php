<?php
/**
 * InteractionController.php - Interaktionsprotokoll (siehe concept.md 4.8).
 * Ownership-Check über die referenzierte Person (siehe itdesign.md Abschnitt 5):
 * eine Interaktion gehört dem Benutzer, dem die zugehörige Person gehört.
 */
class InteractionController
{
    private Interaction $interactionModel;
    private Person $personModel;

    public function __construct()
    {
        AuthHelper::requireLogin();
        $this->interactionModel = new Interaction();
        $this->personModel = new Person();
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=persons');
            exit;
        }

        $personId = (int)($_POST['person_id'] ?? 0);
        $this->loadOwnedPerson($personId);

        try {
            $this->validate($_POST);
            $this->interactionModel->create($personId, AuthHelper::getUserId(), $_POST);
            $_SESSION['success'] = t('interaction.success.created');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?page=persons&action=view&id=' . $personId);
        exit;
    }

    public function edit(): void
    {
        $interaction = $this->loadOwnedInteraction((int)($_GET['id'] ?? 0));

        $pageTitle = t('interaction.edit.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/interactions/edit.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function update(): void
    {
        $id = (int)($_POST['interaction_id'] ?? $_GET['id'] ?? 0);
        $interaction = $this->loadOwnedInteraction($id);
        $personId = (int)$interaction['person_id'];

        try {
            $this->validate($_POST);
            $this->interactionModel->update($id, $_POST);
            $_SESSION['success'] = t('interaction.success.updated');
            header('Location: ' . BASE_URL . '/index.php?page=persons&action=view&id=' . $personId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?page=interactions&action=edit&id=' . $id);
        }
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_POST['interaction_id'] ?? $_GET['id'] ?? 0);
        $interaction = $this->loadOwnedInteraction($id);
        $personId = (int)$interaction['person_id'];

        $this->interactionModel->delete($id);
        $_SESSION['success'] = t('interaction.success.deleted');
        header('Location: ' . BASE_URL . '/index.php?page=persons&action=view&id=' . $personId);
        exit;
    }

    private function validate(array $data): void
    {
        if (trim($data['interaction_date'] ?? '') === '') {
            throw new Exception(t('interaction.error.date_required'));
        }

        if (!in_array($data['interaction_type'] ?? '', Interaction::TYPES, true)) {
            throw new Exception(t('interaction.error.type_invalid'));
        }
    }

    /**
     * Lädt eine Person und stellt sicher, dass sie dem angemeldeten Benutzer gehört.
     */
    private function loadOwnedPerson(int $id): array
    {
        $person = $this->personModel->findById($id);

        if (!$person) {
            $_SESSION['error'] = t('person.error.not_found');
            header('Location: ' . BASE_URL . '/index.php?page=persons');
            exit;
        }

        if ((int)$person['user_id'] !== AuthHelper::getUserId()) {
            $_SESSION['error'] = t('person.error.access_denied');
            header('Location: ' . BASE_URL . '/index.php?page=persons');
            exit;
        }

        return $person;
    }

    /**
     * Lädt eine Interaktion und stellt über die referenzierte Person sicher, dass sie
     * dem angemeldeten Benutzer gehört.
     */
    private function loadOwnedInteraction(int $id): array
    {
        $interaction = $this->interactionModel->findById($id);

        if (!$interaction) {
            $_SESSION['error'] = t('interaction.error.not_found');
            header('Location: ' . BASE_URL . '/index.php?page=persons');
            exit;
        }

        $this->loadOwnedPerson((int)$interaction['person_id']);

        return $interaction;
    }
}
