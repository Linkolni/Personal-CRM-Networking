<?php
/**
 * persons/list.php - Kontaktliste mit Circle-Filter (concept.md 4.5) und clientseitiger
 * DataTables-Sortierung/-Pagination (concept.md 4.6, itdesign.md Abschnitt 9).
 */
$cycleColorClass = [
    'gray'   => 'secondary',
    'red'    => 'danger',
    'yellow' => 'warning',
    'green'  => 'success',
];

// Ampel-Sortierwert für data-order (rot=0 ... grau=3, siehe itdesign.md Abschnitt 9)
$ampelOrder = ['red' => 0, 'yellow' => 1, 'green' => 2, 'gray' => 3];

// Bildet den aktuellen Sortierzustand (aus PersonController::index()) auf den
// DataTables-Spaltenindex ab, damit der erste Render konsistent bleibt.
$sortColumnIndex = [
    'last_name'     => 1,
    'company'       => 2,
    'priority'      => 3,
    'contact_cycle' => 4,
    'last_contact'  => 5,
][$currentSort] ?? 1;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <?php if (!empty($circles)): ?>
            <a href="<?= BASE_URL ?>/index.php?page=persons&sort=<?= urlencode($currentSort) ?>&dir=<?= urlencode($currentDir) ?>"
               class="badge text-decoration-none <?= $currentCircle === '' ? 'bg-primary' : 'bg-secondary' ?>">
                <?= t('common.all') ?>
            </a>
            <?php foreach ($circles as $circle): ?>
                <a href="<?= BASE_URL ?>/index.php?page=persons&sort=<?= urlencode($currentSort) ?>&dir=<?= urlencode($currentDir) ?>&circle=<?= urlencode($circle) ?>"
                   class="badge text-decoration-none <?= $currentCircle === $circle ? 'bg-primary' : 'bg-secondary' ?>">
                    <?= htmlspecialchars($circle) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <a href="<?= BASE_URL ?>/index.php?page=persons&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> <?= t('person.list.new') ?>
    </a>
</div>

<?php if (empty($persons)): ?>
    <p class="text-muted"><?= t('person.list.empty') ?></p>
<?php else: ?>
    <div class="table-responsive">
        <table id="persons-table" class="table table-hover align-middle w-100"
               data-order-column="<?= (int)$sortColumnIndex ?>" data-order-dir="<?= htmlspecialchars($currentDir) ?>">
            <thead>
                <tr>
                    <th></th>
                    <th><?= t('person.list.col.name') ?></th>
                    <th><?= t('person.list.col.company') ?></th>
                    <th><?= t('person.list.col.priority') ?></th>
                    <th><?= t('person.list.col.cycle') ?></th>
                    <th><?= t('person.list.col.last_contact') ?></th>
                    <th class="d-none"><?= t('person.list.col.notes') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td data-order="<?= $ampelOrder[$person['cycle_status']['color']] ?>">
                            <span class="badge bg-<?= $cycleColorClass[$person['cycle_status']['color']] ?>" title="<?= htmlspecialchars($person['cycle_status']['label']) ?>">&nbsp;</span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/index.php?page=persons&action=view&id=<?= (int)$person['person_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars(trim($person['first_name'] . ' ' . $person['last_name'])) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($person['company'] ?? '') ?></td>
                        <td><?= htmlspecialchars($person['priority'] ?? '') ?></td>
                        <td><?= htmlspecialchars($person['contact_cycle'] ?? '') ?></td>
                        <td><?= $person['last_contact'] ? htmlspecialchars(date('d.m.Y', strtotime($person['last_contact']))) : '-' ?></td>
                        <td class="d-none"><?= htmlspecialchars($person['notes'] ?? '') ?></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/index.php?page=persons&action=edit&id=<?= (int)$person['person_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
