/**
 * persons-list.js - DataTables-Initialisierung für die Kontaktliste (page=persons).
 * Clientseitige Sortierung/Pagination (20 Einträge Default), siehe itdesign.md Abschnitt 9.
 * Läuft nur, wenn die Tabelle #persons-table auf der Seite vorhanden ist.
 *
 * Versionshistorie:
 * - Notizen-Spalte (Index 6) bleibt unsichtbar, aber durchsuchbar, damit die Standard-
 *   Volltextsuche auch Notizen erfasst (concept.md 4.13, itdesign.md Abschnitt 9).
 */
document.addEventListener('DOMContentLoaded', function () {
    var table = document.getElementById('persons-table');

    if (!table || typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) {
        return;
    }

    var orderColumn = parseInt(table.getAttribute('data-order-column') || '1', 10);
    var orderDir = table.getAttribute('data-order-dir') === 'DESC' ? 'desc' : 'asc';

    jQuery(table).DataTable({
        pageLength: 20,
        order: [[orderColumn, orderDir]],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/de-DE.json'
        },
        columnDefs: [
            { targets: 0, searchable: false },
            { targets: 6, visible: false, searchable: true },
            { targets: -1, orderable: false, searchable: false }
        ]
    });
});
