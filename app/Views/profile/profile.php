<?php
/** profile.php - Schreibgeschützte Profilansicht. Erwartet: $user. */
?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= t('profile.index.account_title') ?></h5>
                <dl class="row mb-0">
                    <dt class="col-sm-5"><?= t('profile.field.username') ?></dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($user['username']) ?></dd>

                    <dt class="col-sm-5"><?= t('profile.field.role') ?></dt>
                    <dd class="col-sm-7"><?= t('admin.users.role.' . $user['role']) ?></dd>

                    <dt class="col-sm-5"><?= t('profile.field.created_at') ?></dt>
                    <dd class="col-sm-7"><?= htmlspecialchars(date('d.m.Y', strtotime($user['created_at']))) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= t('profile.index.tokens_title') ?></h5>
                <dl class="row mb-0">
                    <dt class="col-sm-7"><?= t('profile.field.tokens_sent') ?></dt>
                    <dd class="col-sm-5"><?= (int)$user['tokens_sent'] ?></dd>

                    <dt class="col-sm-7"><?= t('profile.field.tokens_generated') ?></dt>
                    <dd class="col-sm-5"><?= (int)$user['tokens_generated'] ?></dd>

                    <dt class="col-sm-7"><?= t('profile.field.tokens_cost') ?></dt>
                    <dd class="col-sm-5"><?= htmlspecialchars(number_format((float)$user['tokens_cost'], 2, ',', '.')) ?> &euro;</dd>
                </dl>
            </div>
        </div>
    </div>

    <?php if (!empty($user['persona'])): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= t('profile.field.persona') ?></h5>
                    <p class="card-text mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($user['persona']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <a href="<?= BASE_URL ?>/index.php?page=profile&action=edit" class="btn btn-primary">
        <i class="bi bi-pencil"></i> <?= t('common.edit') ?>
    </a>
    <a href="<?= BASE_URL ?>/index.php?page=profile&action=export" class="btn btn-outline-secondary">
        <i class="bi bi-download"></i> <?= t('profile.index.export') ?>
    </a>
</div>
