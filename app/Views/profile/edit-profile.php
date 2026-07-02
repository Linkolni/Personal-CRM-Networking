<?php
/** edit-profile.php - Persona bearbeiten + Passwort ändern. Erwartet: $user. */
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= t('profile.edit.persona_title') ?></h5>
        <form method="POST" action="<?= BASE_URL ?>/index.php?page=profile&action=update">
            <div class="mb-3">
                <label class="form-label"><?= t('profile.field.persona') ?></label>
                <textarea name="persona" class="form-control" rows="4"><?= htmlspecialchars($user['persona'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title"><?= t('profile.edit.password_title') ?></h5>
        <form method="POST" action="<?= BASE_URL ?>/index.php?page=profile&action=changePassword">
            <div class="mb-3">
                <label class="form-label"><?= t('profile.field.current_password') ?></label>
                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label"><?= t('profile.field.new_password') ?></label>
                <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label"><?= t('profile.field.new_password_confirm') ?></label>
                <input type="password" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
        </form>
    </div>
</div>

<div class="mt-4">
    <a href="<?= BASE_URL ?>/index.php?page=profile" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> <?= t('common.back') ?>
    </a>
</div>
