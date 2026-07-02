<?php
/**
 * dashboard/index.php - Dashboard mit den Blöcken D1-D8 (concept.md 4.12, itdesign.md Abschnitt 8).
 * Reine Anzeige, keine DB-Zugriffe/Berechtigungsentscheidungen - alle Daten kommen vom Controller.
 */
$cycleColorClass = [
    'gray'   => 'secondary',
    'red'    => 'danger',
    'yellow' => 'warning',
    'green'  => 'success',
];

$personLink = function (array $person) {
    $name = htmlspecialchars(trim($person['first_name'] . ' ' . $person['last_name']));
    return '<a href="' . BASE_URL . '/index.php?page=persons&action=view&id=' . (int)$person['person_id'] . '" class="text-decoration-none">' . $name . '</a>';
};
?>

<div class="row g-4 mt-1">
    <!-- D1: Fällige Kontakte -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.due.title') ?></div>
            <div class="card-body">
                <?php if (empty($dueContacts)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.due.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($dueContacts as $person): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge bg-<?= $cycleColorClass[$person['cycle_status']['color']] ?>" title="<?= htmlspecialchars($person['cycle_status']['label']) ?>">&nbsp;</span>
                                    <?= $personLink($person) ?>
                                    <span class="text-muted small"><?= htmlspecialchars($person['company'] ?? '') ?></span>
                                </span>
                                <span class="text-muted small"><?= $person['last_contact'] ? htmlspecialchars(date('d.m.Y', strtotime($person['last_contact']))) : '-' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D4: Unbearbeitete neue Kontakte -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.new.title') ?></div>
            <div class="card-body">
                <?php if (empty($newContacts)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.new.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($newContacts as $person): ?>
                            <li class="list-group-item"><?= $personLink($person) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D3: Wichtigste Kontakte (Top5) -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.top10.title') ?></div>
            <div class="card-body">
                <?php if (empty($topPersons)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.top10.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topPersons as $person): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge bg-<?= $cycleColorClass[$person['cycle_status']['color']] ?>" title="<?= htmlspecialchars($person['cycle_status']['label']) ?>">&nbsp;</span>
                                    <?= $personLink($person) ?>
                                </span>
                                <span class="text-muted small"><?= htmlspecialchars($person['company'] ?? '') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D2: Anstehende Geburtstage -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.birthdays.title') ?></div>
            <div class="card-body">
                <?php if (empty($upcomingBirthdays)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.birthdays.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingBirthdays as $person): ?>
                            <?php $isToday = $person['birthday_md'] === date('m-d'); ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= $personLink($person) ?>
                                <span class="<?= $isToday ? 'badge bg-primary' : 'text-muted small' ?>">
                                    <?= $isToday ? t('dashboard.birthdays.today') : htmlspecialchars(date('d.m.', strtotime($person['birthday']))) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D5: Letzte Aktivitäten -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.recent.title') ?></div>
            <div class="card-body">
                <?php if (empty($recentInteractions)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.recent.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentInteractions as $interaction): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <?= $personLink($interaction) ?>
                                    <span class="text-muted small"><?= htmlspecialchars(date('d.m.Y', strtotime($interaction['interaction_date']))) ?></span>
                                </div>
                                <div class="small text-muted">
                                    <?= t('interaction.type.' . $interaction['interaction_type']) ?>
                                    <?php if (!empty($interaction['memo'])): ?>
                                        &ndash; <?= htmlspecialchars(mb_strimwidth($interaction['memo'], 0, 60, '…')) ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D6: Netzwerk-Kennzahlen -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.stats.title') ?></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-8"><span class="badge bg-danger">&nbsp;</span> <?= t('dashboard.stats.color.red') ?></dt>
                    <dd class="col-4 text-end"><?= (int)$counts['byColor']['red'] ?></dd>
                    <dt class="col-8"><span class="badge bg-warning">&nbsp;</span> <?= t('dashboard.stats.color.yellow') ?></dt>
                    <dd class="col-4 text-end"><?= (int)$counts['byColor']['yellow'] ?></dd>
                    <dt class="col-8"><span class="badge bg-success">&nbsp;</span> <?= t('dashboard.stats.color.green') ?></dt>
                    <dd class="col-4 text-end"><?= (int)$counts['byColor']['green'] ?></dd>
                    <dt class="col-8"><span class="badge bg-secondary">&nbsp;</span> <?= t('dashboard.stats.color.gray') ?></dt>
                    <dd class="col-4 text-end"><?= (int)$counts['byColor']['gray'] ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- D7: Aus den Augen verloren -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.stale.title') ?></div>
            <div class="card-body">
                <?php if (empty($staleContacts)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.stale.empty') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($staleContacts as $person): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= $personLink($person) ?>
                                <span class="text-muted small"><?= $person['last_contact'] ? htmlspecialchars(date('d.m.Y', strtotime($person['last_contact']))) : '-' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- D8: Circle-Übersicht -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><?= t('dashboard.circles.title') ?></div>
            <div class="card-body">
                <?php if (empty($circleCounts)): ?>
                    <p class="text-muted mb-0"><?= t('dashboard.circles.empty') ?></p>
                <?php else: ?>
                    <?php foreach ($circleCounts as $circle => $count): ?>
                        <a href="<?= BASE_URL ?>/index.php?page=persons&circle=<?= urlencode($circle) ?>" class="badge bg-secondary text-decoration-none me-1 mb-1">
                            <?= htmlspecialchars($circle) ?> (<?= (int)$count ?>)
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
