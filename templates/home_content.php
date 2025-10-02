<?php // Zeige eine Fehlermeldung, falls die DB nicht erreichbar war ?>
<?php if (isset($db_error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($db_error_message) ?></div>
<?php endif; ?>

<h1 class="mb-4">Willkommen zurück, <?= htmlspecialchars($username) ?>!</h1>

<div class="card">
    <div class="card-header">
        Deine Top 10 Kontakte
    </div>
    <div class="card-body">
        <?php if (empty($top10_persons)): ?>
            <p>Du hast noch keine Top-10-Kontakte markiert.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($top10_persons as $person): ?>
                    <li class="list-group-item">
                        <?= htmlspecialchars($person['first_name'] . ' ' . $person['last_name']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
