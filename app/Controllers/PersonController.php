<?php
/**
 * PersonController.php - Kontakte-CRUD (siehe concept.md 4.4-4.7).
 * Ownership-Check in jeder Methode, die eine person_id entgegennimmt
 * (siehe itdesign.md Abschnitt 5).
 */
class PersonController
{
    private Person $personModel;
    private Interaction $interactionModel;

    private const ALLOWED_SORT = ['last_name', 'company', 'priority', 'contact_cycle', 'last_contact'];

    public function __construct()
    {
        AuthHelper::requireLogin();
        $this->personModel = new Person();
        $this->interactionModel = new Interaction();
    }

    public function index(): void
    {
        $userId = AuthHelper::getUserId();

        $sort = in_array($_GET['sort'] ?? '', self::ALLOWED_SORT, true) ? $_GET['sort'] : 'last_name';
        $dir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $circle = trim($_GET['circle'] ?? '');

        $persons = $this->personModel->getAllForUser($userId, $sort, $dir);

        if ($circle !== '') {
            $persons = array_values(array_filter($persons, function ($person) use ($circle) {
                $personCircles = array_map('trim', explode(',', $person['circles'] ?? ''));
                return in_array($circle, $personCircles, true);
            }));
        }

        foreach ($persons as &$person) {
            $person['cycle_status'] = ContactCycleHelper::getStatus($person['last_contact'] ?? null, $person['contact_cycle'] ?? null);
        }
        unset($person);

        $circles = $this->personModel->getUniqueCirclesForUser($userId);

        $pageTitle = t('person.list.title');
        $currentSort = $sort;
        $currentDir = $dir;
        $currentCircle = $circle;

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/persons/list.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function create(): void
    {
        $pageTitle = t('person.create.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/persons/create.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=persons');
            exit;
        }

        try {
            $lastName = trim($_POST['last_name'] ?? '');
            if ($lastName === '') {
                throw new Exception(t('person.error.last_name_required'));
            }

            $id = $this->personModel->create(AuthHelper::getUserId(), $_POST);
            $_SESSION['success'] = t('person.success.created');
            header('Location: ' . BASE_URL . '/index.php?page=persons&action=view&id=' . $id);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?page=persons&action=create');
        }
        exit;
    }

    public function view(): void
    {
        $person = $this->loadOwnedPerson((int)($_GET['id'] ?? 0));

        $person['cycle_status'] = ContactCycleHelper::getStatus($person['last_contact'] ?? null, $person['contact_cycle'] ?? null);
        $interactions = $this->interactionModel->getAllForPerson((int)$person['person_id']);

        $pageTitle = t('person.view.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/persons/view.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function edit(): void
    {
        $person = $this->loadOwnedPerson((int)($_GET['id'] ?? 0));

        $pageTitle = t('person.edit.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/persons/edit.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    public function update(): void
    {
        $id = (int)($_POST['person_id'] ?? $_GET['id'] ?? 0);
        $person = $this->loadOwnedPerson($id);

        try {
            $lastName = trim($_POST['last_name'] ?? '');
            if ($lastName === '') {
                throw new Exception(t('person.error.last_name_required'));
            }

            $this->personModel->update($id, $_POST);
            $_SESSION['success'] = t('person.success.updated');
            header('Location: ' . BASE_URL . '/index.php?page=persons&action=view&id=' . $id);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?page=persons&action=edit&id=' . $id);
        }
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_POST['person_id'] ?? $_GET['id'] ?? 0);
        $this->loadOwnedPerson($id);

        $this->personModel->delete($id);
        $_SESSION['success'] = t('person.success.deleted');
        header('Location: ' . BASE_URL . '/index.php?page=persons');
        exit;
    }

    /**
     * Lädt eine Person und stellt sicher, dass sie dem angemeldeten Benutzer gehört
     * (Ownership-Check, siehe itdesign.md Abschnitt 5).
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
}
