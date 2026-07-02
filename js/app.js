/**
 * app.js - Progressive Enhancement (Formularvalidierung, Bestätigungsdialoge).
 * Kein Rendering im Client (siehe itdesign.md Abschnitt 9).
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.getAttribute('data-confirm-submit'))) {
                e.preventDefault();
            }
        });
    });
});
