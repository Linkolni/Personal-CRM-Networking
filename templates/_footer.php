<?php
// templates/_footer.php
?>
    <footer class="text-center p-3 mt-auto"
        style="background-color: <?= COMPANY_BACKGROUNDCOLOR ?>; color: <?= COMPANY_COLOR ?>;">
        <!-- Angepasste Schriftgröße (z. B. via Bootstrap-Klasse, falls erwünscht) -->
        <span class="fs-5">
            &copy; <?= date('Y') ?> <?= COMPANY_NAME ?>
        </span>
    </footer>
