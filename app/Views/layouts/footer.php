<?php
/**
 * footer.php - Gemeinsamer HTML-Footer.
 */
?>
</div> <!-- Ende Container (aus header.php geöffnet) -->

<footer class="mt-auto py-4 border-top">
    <div class="container">
        <p class="mb-1 text-muted text-center text-md-start">
            &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> - <?= t('footer.copyright') ?>
        </p>
        <small class="d-block text-center text-md-start">
            <a href="<?= defined('IMPRESSUM_URL') ? IMPRESSUM_URL : '#' ?>" class="text-decoration-none text-muted me-2" target="_blank" rel="noopener">
                <?= t('footer.imprint') ?>
            </a>
            &bull;
            <a href="<?= defined('DATENSCHUTZ_URL') ? DATENSCHUTZ_URL : '#' ?>" class="text-decoration-none text-muted ms-2" target="_blank" rel="noopener">
                <?= t('footer.privacy') ?>
            </a>
        </small>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) return;
        document.querySelectorAll('form').forEach(function (form) {
            if (form.method.toLowerCase() === 'post' && !form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = token;
                form.appendChild(input);
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<script src="<?= BASE_URL ?>/js/persons-list.js"></script>
</body>
</html>
