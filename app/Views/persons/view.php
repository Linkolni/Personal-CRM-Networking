<?php
$cycleColorClass = [
    'gray'   => 'secondary',
    'red'    => 'danger',
    'yellow' => 'warning',
    'green'  => 'success',
][$person['cycle_status']['color']];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <h3>
        <?= htmlspecialchars(trim($person['first_name'] . ' ' . $person['last_name'])) ?>
        <span class="badge bg-<?= $cycleColorClass ?>" title="<?= htmlspecialchars($person['cycle_status']['label']) ?>">&nbsp;</span>
    </h3>
    <a href="<?= BASE_URL ?>/index.php?page=persons&action=edit&id=<?= (int)$person['person_id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-pencil"></i> <?= t('common.edit') ?>
    </a>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Kontaktdaten</h5>
                <dl class="row mb-0">
                    <?php if (!empty($person['company'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.company') ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($person['company']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['position'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.position') ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($person['position']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['email1'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.email1') ?></dt>
                        <dd class="col-sm-8"><a href="mailto:<?= htmlspecialchars($person['email1']) ?>"><?= htmlspecialchars($person['email1']) ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['email2'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.email2') ?></dt>
                        <dd class="col-sm-8"><a href="mailto:<?= htmlspecialchars($person['email2']) ?>"><?= htmlspecialchars($person['email2']) ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['phone1'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.phone1') ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($person['phone1']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['phone2'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.phone2') ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($person['phone2']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['linkedin_profile'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.linkedin') ?></dt>
                        <dd class="col-sm-8"><a href="<?= htmlspecialchars($person['linkedin_profile']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($person['linkedin_profile']) ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['website'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.website') ?></dt>
                        <dd class="col-sm-8"><a href="<?= htmlspecialchars($person['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($person['website']) ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($person['birthday'])): ?>
                        <dt class="col-sm-4"><?= t('person.field.birthday') ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars(date('d.m.Y', strtotime($person['birthday']))) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Klassifizierung</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4"><?= t('person.field.status') ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($person['status']) ?></dd>

                    <dt class="col-sm-4"><?= t('person.field.priority') ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($person['priority'] ?? '-') ?></dd>

                    <dt class="col-sm-4"><?= t('person.field.contact_cycle') ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($person['contact_cycle'] ?? '-') ?></dd>

                    <dt class="col-sm-4"><?= t('person.field.circles') ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($person['circles'] ?? '-') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <?php if (!empty($person['notes'])): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= t('person.field.notes') ?></h5>
                    <p class="card-text" style="white-space: pre-wrap;"><?= htmlspecialchars($person['notes']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title"><?= t('interaction.list.title') ?></h5>

        <h6><?= t('interaction.create.title') ?></h6>
        <form method="POST" action="<?= BASE_URL ?>/index.php?page=interactions&action=store">
            <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= t('interaction.field.date') ?> *</label>
                    <input type="date" name="interaction_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('interaction.field.type') ?> *</label>
                    <select name="interaction_type" class="form-select" required>
                        <?php foreach (Interaction::TYPES as $type): ?>
                            <option value="<?= $type ?>"><?= t('interaction.type.' . $type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label"><?= t('interaction.field.memo') ?></label>
                    <textarea name="memo" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>

        <hr class="my-4">

        <?php if (empty($interactions)): ?>
            <p class="text-muted"><?= t('interaction.list.empty') ?></p>
        <?php else: ?>
            <ul class="list-group list-group-flush mb-3">
                <?php foreach ($interactions as $interaction): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars(date('d.m.Y', strtotime($interaction['interaction_date']))) ?></strong>
                            &mdash; <?= t('interaction.type.' . $interaction['interaction_type']) ?>
                            <?php if (!empty($interaction['memo'])): ?>
                                <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($interaction['memo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>/index.php?page=interactions&action=edit&id=<?= (int)$interaction['interaction_id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4">
    <a href="<?= BASE_URL ?>/index.php?page=persons" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> <?= t('common.back') ?>
    </a>
</div>
