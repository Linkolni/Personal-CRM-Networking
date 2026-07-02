<form method="POST" action="<?= BASE_URL ?>/index.php?page=persons&action=update&id=<?= (int)$person['person_id'] ?>">
    <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
    <?php include __DIR__ . '/_form.php'; ?>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
        <a href="<?= BASE_URL ?>/index.php?page=persons&action=view&id=<?= (int)$person['person_id'] ?>" class="btn btn-outline-secondary"><?= t('common.cancel') ?></a>
    </div>
</form>

<form method="POST" action="<?= BASE_URL ?>/index.php?page=persons&action=delete&id=<?= (int)$person['person_id'] ?>"
      class="mt-2" data-confirm-submit="<?= t('common.confirm_delete') ?>">
    <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> <?= t('common.delete') ?></button>
</form>
