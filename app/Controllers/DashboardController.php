<?php
/**
 * DashboardController.php - Startseite: Begrüßung + Dashboard-Blöcke D1-D8
 * (concept.md 4.12, itdesign.md Abschnitt 8).
 */
class DashboardController
{
    private Person $personModel;
    private Interaction $interactionModel;

    /** Schwellwerte der Dashboard-Blöcke (concept.md 4.12, dort als Annahme gekennzeichnet). */
    private const DUE_CONTACTS_LIMIT = 5;
    private const TOP_PRIORITY_LIMIT = 5;
    private const NEW_CONTACTS_LIMIT = 5;
    private const BIRTHDAY_WINDOW_DAYS = 14;
    private const BIRTHDAY_LIMIT = 5;
    private const RECENT_INTERACTIONS_LIMIT = 3;
    private const STALE_CONTACTS_MONTHS = 6;
    private const STALE_CONTACTS_LIMIT = 5;

    public function __construct()
    {
        AuthHelper::requireLogin();
        $this->personModel = new Person();
        $this->interactionModel = new Interaction();
    }

    public function index(): void
    {
        $userId = AuthHelper::getUserId();

        // D3: Top10-Kontakte, auf TOP_PRIORITY_LIMIT gekürzt, ergänzt um Ampelstatus für die Anzeige
        $topPersons = array_slice($this->personModel->getByPriority($userId, 'TOP10'), 0, self::TOP_PRIORITY_LIMIT);
        foreach ($topPersons as &$person) {
            $person['cycle_status'] = ContactCycleHelper::getStatus($person['last_contact'] ?? null, $person['contact_cycle'] ?? null);
        }
        unset($person);

        $dueContacts = $this->personModel->getDueContacts($userId, self::DUE_CONTACTS_LIMIT); // D1
        $upcomingBirthdays = array_slice($this->personModel->getUpcomingBirthdays($userId, self::BIRTHDAY_WINDOW_DAYS), 0, self::BIRTHDAY_LIMIT); // D2
        $newContacts = array_slice($this->personModel->getNewContacts($userId), 0, self::NEW_CONTACTS_LIMIT); // D4
        $recentInteractions = $this->interactionModel->getRecentForUser($userId, self::RECENT_INTERACTIONS_LIMIT); // D5
        $counts = $this->personModel->getCountsForUser($userId); // D6
        $staleContacts = $this->personModel->getStaleContacts($userId, self::STALE_CONTACTS_MONTHS, self::STALE_CONTACTS_LIMIT); // D7
        $circleCounts = $this->personModel->getCircleCounts($userId); // D8

        $pageTitle = t('dashboard.title');

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/topbar.php';
        require_once __DIR__ . '/../Views/dashboard/index.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }
}
