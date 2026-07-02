<?php
/**
 * flash_messages.php - Rendert $_SESSION['success']/['error']/['info']/['warning']/['hint']
 * einheitlich und leert sie danach (siehe itdesign.md Abschnitt 9).
 */
$flashTypes = [
    'success' => ['class' => 'success', 'icon' => 'bi-check-circle'],
    'error'   => ['class' => 'danger',  'icon' => 'bi-exclamation-triangle'],
    'info'    => ['class' => 'info',    'icon' => 'bi-info-circle'],
    'warning' => ['class' => 'warning', 'icon' => 'bi-exclamation-triangle'],
    'hint'    => ['class' => 'light',   'icon' => 'bi-lightbulb'],
];

foreach ($flashTypes as $key => $config):
    if (isset($_SESSION[$key])): ?>
        <div class="alert alert-<?= $config['class'] ?> alert-dismissible fade show border" role="alert">
            <i class="bi <?= $config['icon'] ?> me-2"></i>
            <?= htmlspecialchars($_SESSION[$key]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
        <?php unset($_SESSION[$key]); ?>
    <?php endif;
endforeach; ?>
