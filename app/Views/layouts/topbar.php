<!-- Topbar gibt den Seitentitel und Flash-Messages aus -->

<?php include __DIR__ . '/../partials/flash_messages.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h2 class="mb-0">
        <?php if (!empty($pageIcon)): ?><i class="bi <?= $pageIcon ?> me-2"></i><?php endif; ?>
        <?= htmlspecialchars($pageTitle ?? '') ?>
    </h2>
</div>
