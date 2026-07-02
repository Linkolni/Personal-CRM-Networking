<?php
/** edit.php - Formular zum Bearbeiten einer Interaktion. Erwartet: $interaction. */
$dateValue = $interaction['interaction_date'] ? date('Y-m-d', strtotime($interaction['interaction_date'])) : '';
?>

<form method="POST" action="<?= BASE_URL ?>/index.php?page=interactions&action=update&id=<?= (int)$interaction['interaction_id'] ?>">
    <input type="hidden" name="interaction_id" value="<?= (int)$interaction['interaction_id'] ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label"><?= t('interaction.field.date') ?> *</label>
            <input type="date" name="interaction_date" class="form-control" value="<?= htmlspecialchars($dateValue) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= t('interaction.field.type') ?> *</label>
            <select name="interaction_type" class="form-select" required>
                <?php foreach (Interaction::TYPES as $type): ?>
                    <option value="<?= $type ?>" <?= $interaction['interaction_type'] === $type ? 'selected' : '' ?>>
                        <?= t('interaction.type.' . $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label"><?= t('interaction.field.memo') ?></label>
            <textarea name="memo" class="form-control" rows="4"><?= htmlspecialchars($interaction['memo'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
        <a href="<?= BASE_URL ?>/index.php?page=persons&action=view&id=<?= (int)$interaction['person_id'] ?>" class="btn btn-outline-secondary"><?= t('common.cancel') ?></a>
    </div>
</form>

<form method="POST" action="<?= BASE_URL ?>/index.php?page=interactions&action=delete&id=<?= (int)$interaction['interaction_id'] ?>"
      class="mt-2" data-confirm-submit="<?= t('common.confirm_delete') ?>">
    <input type="hidden" name="interaction_id" value="<?= (int)$interaction['interaction_id'] ?>">
    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> <?= t('common.delete') ?></button>
</form>
