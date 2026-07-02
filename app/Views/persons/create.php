<form method="POST" action="<?= BASE_URL ?>/index.php?page=persons&action=store">
    <?php $person = []; include __DIR__ . '/_form.php'; ?>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
        <a href="<?= BASE_URL ?>/index.php?page=persons" class="btn btn-outline-secondary"><?= t('common.cancel') ?></a>
    </div>
</form>
