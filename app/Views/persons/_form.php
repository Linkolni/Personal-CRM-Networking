<?php
/**
 * _form.php - Gemeinsames Formular für create.php/edit.php.
 * Erwartet: $person (array, ggf. leer bei Neuanlage).
 */
$p = $person ?? [];
$val = fn($field) => htmlspecialchars($p[$field] ?? '');
?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.first_name') ?></label>
        <input type="text" name="first_name" class="form-control" value="<?= $val('first_name') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.last_name') ?> *</label>
        <input type="text" name="last_name" class="form-control" value="<?= $val('last_name') ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.email1') ?></label>
        <input type="email" name="email1" class="form-control" value="<?= $val('email1') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.email2') ?></label>
        <input type="email" name="email2" class="form-control" value="<?= $val('email2') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.phone1') ?></label>
        <input type="text" name="phone1" class="form-control" value="<?= $val('phone1') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.phone2') ?></label>
        <input type="text" name="phone2" class="form-control" value="<?= $val('phone2') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.company') ?></label>
        <input type="text" name="company" class="form-control" value="<?= $val('company') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.position') ?></label>
        <input type="text" name="position" class="form-control" value="<?= $val('position') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.linkedin') ?></label>
        <input type="url" name="linkedin_profile" class="form-control" value="<?= $val('linkedin_profile') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.website') ?></label>
        <input type="url" name="website" class="form-control" value="<?= $val('website') ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label"><?= t('person.field.birthday') ?></label>
        <input type="date" name="birthday" class="form-control" value="<?= $val('birthday') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label"><?= t('person.field.priority') ?></label>
        <select name="priority" class="form-select">
            <option value="">-</option>
            <?php foreach (['TOP10', 'TOP25', 'TOP50', 'TOP100'] as $opt): ?>
                <option value="<?= $opt ?>" <?= ($p['priority'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label"><?= t('person.field.contact_cycle') ?></label>
        <select name="contact_cycle" class="form-select">
            <option value="">-</option>
            <?php foreach (['WEEKLY', 'BIWEEKLY', 'MONTHLY', 'QUARTERLY', 'SEMI_ANNUALLY', 'ANNUALLY'] as $opt): ?>
                <option value="<?= $opt ?>" <?= ($p['contact_cycle'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (isset($p['person_id'])): ?>
        <div class="col-md-6">
            <label class="form-label"><?= t('person.field.status') ?></label>
            <select name="status" class="form-select">
                <?php foreach (['NEW', 'ACTIVE', 'INACTIVE'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($p['status'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="col-md-6">
        <label class="form-label"><?= t('person.field.circles') ?></label>
        <input type="text" name="circles" class="form-control" value="<?= $val('circles') ?>" placeholder="z. B. Uni, Sport, Business">
    </div>

    <div class="col-12">
        <label class="form-label"><?= t('person.field.notes') ?></label>
        <textarea name="notes" class="form-control" rows="4"><?= $val('notes') ?></textarea>
    </div>
</div>
