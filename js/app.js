/**
 * app.js
 *
 * Haupt-JavaScript-Datei für die CRM-Anwendung.
 * Regelt die gesamte Benutzerinteraktion und Kommunikation mit dem Server (API).
 */

// =========================================================================
// GLOBALE VARIABLEN
// =========================================================================
// Speichern den Zustand der Anwendung, z.B. die aktuelle Sortierung oder die ausgewählte Person.
let currentSortField = 'last_name';
let currentSortDir = 'ASC';
let currentPersonId = null;
let currentCircleFilter = null; // NEU: Speichert den aktiven Filter

// =========================================================================
// HAUPT-INITIALISIERUNG
// =========================================================================
// Stellt sicher, dass das Skript erst ausgeführt wird, wenn das HTML-Dokument vollständig geladen ist.
document.addEventListener('DOMContentLoaded', () => {

    // Referenzen auf die wichtigsten Layout-Elemente.
    const listPanel = document.getElementById('list-panel');
    const detailsPanel = document.getElementById('details-panel');

    // =========================================================================
    // EVENT LISTENER (Die "Schaltzentrale" für alle Klicks)
    // =========================================================================

    // 1. Event-Listener für das linke Panel (Tabelle, Sortierung, "Neu"-Button).
    listPanel.addEventListener('click', (event) => {
        const clickedElement = event.target;

        // --- NEU: Klick auf ein Filter-Badge abfangen ---
        const badge = clickedElement.closest('.filter-badge');
        if (badge) {
            event.preventDefault(); // Verhindert, dass die Seite nach oben springt

            const circle = badge.dataset.circle;
            // Setze den globalen Filter oder hebe ihn auf, wenn "Alle" geklickt wird
            currentCircleFilter = (circle === 'all') ? null : circle;

            // Lade die gesamte Liste neu. Die Funktionen wenden den Filter dann automatisch an.
            refreshPersonList();
            return; // Wichtig, um die weitere Ausführung zu stoppen
        }

        // --- ENDE NEUER TEIL ---
        // FALL 1: Klick auf eine sortierbare Tabellenüberschrift.
        const sortableHeader = clickedElement.closest('th.sortable');
        if (sortableHeader) {
            handleSort(sortableHeader.dataset.sort);
            return;
        }

        // FALL 2: Klick auf einen Button.
        const button = clickedElement.closest('button');
        if (button) {
            if (button.id === 'btn-add-person') {
                handleAddNewPerson();
                return;
            }

            const personId = button.dataset.personId;
            if (personId) {
                if (button.classList.contains('btn-edit-person')) {
                    handleEditPerson(personId);
                } else if (button.classList.contains('btn-show-interactions')) {
                    handleShowInteractions(personId);
                }
            }
        }
    });

    // 2. Event-Listener für dynamische Buttons im rechten Detail-Panel.
    detailsPanel.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        // Klick auf "Bearbeiten" einer Interaktion wird hier separat behandelt.
        if (button.classList.contains('btn-edit-interaction')) {
            // Holt die Daten aus dem data-Attribut und füllt das Formular.
            const interactionData = JSON.parse(button.dataset.interaction);
            populateInteractionForm(interactionData);
            return;
        }

        // Behandelt alle anderen Buttons im Detail-Panel über ihre ID.
        switch (button.id) {
            // Personen-Formular
            case 'btn-delete-person':
                // Zeigt die native Browser-Sicherheitsabfrage
                if (confirm('Sind Sie sicher, dass Sie diesen Kontakt endgültig löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
                    handleDeletePerson();
                }
                break; // Wichtig!

            case 'btn-save-person':
                handleSavePerson();
                break;
            case 'btn-cancel-edit':
                showWelcomeMessage();
                break;

            // Interaktions-Ansicht / Formular
            case 'btn-show-new-interaction-form':
                populateInteractionForm(); // Aufruf ohne Daten -> leeres Formular für "Neu"
                break;
            case 'btn-save-interaction':
                handleSaveInteraction();
                break;
            case 'btn-cancel-interaction':
                document.getElementById('new-interaction-form-container').classList.add('d-none');
                document.getElementById('btn-show-new-interaction-form').classList.remove('d-none');
                break;
        }
    });

    // =========================================================================
    // AKTIONEN (HANDLER-FUNKTIONEN)
    // =========================================================================

    /** Steuert die Sortierlogik und stößt das Neuladen der Tabelle an. */
    function handleSort(sortField) {
        if (sortField === currentSortField) {
            currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSortField = sortField;
            currentSortDir = 'ASC';
        }
        refreshPersonList();
    }

    /** Holt eine Person von der API und zeigt das gefüllte Bearbeitungsformular an. */
    async function handleEditPerson(personId) {
        try {
            currentPersonId = personId;
            const response = await fetch(`api.php?action=get_person&id=${personId}`, { cache: 'no-cache' });
            const result = await response.json();
            if (result.success) {
                renderPersonForm(result.data);
            } else {
                alert('Fehler: ' + result.message);
            }
        } catch (error) {
            console.error('Netzwerkfehler:', error);
            alert('Ein Netzwerkfehler ist aufgetreten.');
        }
    }

    /** Zeigt das leere Formular zum Erstellen einer neuen Person. */
    function handleAddNewPerson() {
        renderPersonForm({});
    }

    /** Holt die Interaktionen einer Person von der API und zeigt sie im Detailbereich an. */
    async function handleShowInteractions(personId) {
        try {
            currentPersonId = personId; // WICHTIG: Merkt sich die ID der Person für diese Ansicht.
            const response = await fetch(`api.php?action=get_interactions&id=${personId}`, { cache: 'no-cache' });
            const result = await response.json();
            if (result.success) {
                renderInteractions(result.data);
            } else {
                alert('Fehler: ' + result.message);
            }
        } catch (error) {
            console.error('Netzwerkfehler:', error);
            alert('Ein Netzwerkfehler ist aufgetreten.');
        }
    }

    /** Liest die Daten aus dem Personen-Formular und sendet sie zum Speichern an die API. */
    async function handleSavePerson() {
        const form = document.getElementById('person-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api.php?action=save_person', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            // --- HIER IST DIE ÄNDERUNG ---
            // Gib die Debug-Infos immer in der Konsole aus, wenn sie vorhanden sind.
            // Das hilft enorm bei der Fehlersuche, ohne den Nutzer zu stören.
            if (result.debug_sql) {
                console.group("SQL Debug Information (save_person)");
                console.log("Backend hat folgende SQL-Informationen zurückgegeben:");
                console.log(result.debug_sql);
                console.groupEnd();
            }
            // --- ENDE DER ÄNDERUNG ---

            if (result.success) {
                await refreshPersonList();
                showWelcomeMessage();
            } else {
                alert('Fehler beim Speichern: ' + (result.message || 'Unbekannter Fehler'));
            }
        } catch (error) {
            console.error('Netzwerkfehler:', error);
            alert('Ein Netzwerkfehler ist aufgetreten.');
        }
    }

    /** Liest die Daten aus dem Personen-Formular und sendet sie zum Speichern an die API. */
    async function handleSavePerson() {
        const form = document.getElementById('person-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api.php?action=save_person', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            // --- VERBESSERTE FEHLERBEHANDLUNG START ---

            // Prüfen, ob die Antwort erfolgreich war (Status 200-299).
            if (!response.ok) {
                // Wenn nicht, lies die Antwort als Text, um die PHP-Fehlermeldung zu sehen.
                const errorText = await response.text();
                // Werfe einen neuen, aussagekräftigen Fehler.
                throw new Error(`Server-Fehler (Status ${response.status}): ${errorText}`);
            }

            // Nur wenn die Antwort ok ist, versuche, sie als JSON zu parsen.
            const result = await response.json();

            // --- VERBESSERTE FEHLERBEHANDLUNG ENDE ---

            if (result.debug_sql) {
                console.group("SQL Debug Information (save_person)");
                console.log("Backend hat folgende SQL-Informationen zurückgegeben:");
                console.log(result.debug_sql);
                console.groupEnd();
            }

            if (result.success) {
                await refreshPersonList();
                showWelcomeMessage();
            } else {
                alert('Fehler beim Speichern: ' + (result.message || 'Unbekannter Fehler'));
            }

        } catch (error) {
            // Dieser catch-Block fängt jetzt sowohl echte Netzwerkfehler als auch den Server-Fehler auf.
            console.error('Fehler beim Speichern der Person:', error);

            // Zeige die detaillierte Fehlermeldung in einem Alert an.
            alert('Ein Fehler ist aufgetreten. Details:\n\n' + error.message);
        }
    }
    /** Speichert eine neue ODER aktualisiert eine bestehende Interaktion. */
    async function handleSaveInteraction() {
        const form = document.getElementById('new-interaction-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        data.person_id = currentPersonId;

        if (!data.interaction_date || !data.interaction_type) {
            alert("Bitte Datum und Art der Interaktion ausfüllen.");
            return;
        }

        const isUpdate = data.interaction_id && data.interaction_id > 0;
        const action = isUpdate ? 'update_interaction' : 'add_interaction';

        try {
            // KORREKTUR: Es müssen Backticks (`) anstelle von einfachen Anführungszeichen (') verwendet werden,
            // damit die Variable ${action} korrekt eingesetzt wird.
            const response = await fetch(`api.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                handleShowInteractions(currentPersonId);
            } else {
                alert('Fehler beim Speichern der Interaktion: ' + (result.message || 'Unbekannter Fehler'));
            }
        } catch (error) {
            console.error('Netzwerkfehler beim Speichern der Interaktion:', error);
            alert('Ein Netzwerkfehler ist aufgetreten.');
        }
    }

    // =========================================================================
    // DARSTELLUNG (RENDER-FUNKTIONEN)
    // =========================================================================

    /**
     * Haupt-Aktualisierungsfunktion: Holt Personen UND Circles von der API.
     * Ruft dann die entsprechenden Render-Funktionen auf.
     */
    async function refreshPersonList() {
        try {
            // NEU: Startet zwei Anfragen an die API parallel, um Zeit zu sparen.
            const [personsResponse, circlesResponse] = await Promise.all([
                fetch(`api.php?action=get_persons&sort=${currentSortField}&dir=${currentSortDir}`, { cache: 'no-cache' }),
                fetch(`api.php?action=get_circles`, { cache: 'no-cache' })
            ]);

            // Verarbeitet die Antworten
            const personsResult = await personsResponse.json();
            const circlesResult = await circlesResponse.json();

            // Ruft die Funktion zum Rendern der Filter-Badges auf.
            if (circlesResult.success) {
                renderCircleFilters(circlesResult.data);
            }

            // Ruft die neue Funktion zum Rendern der Tabelle auf.
            if (personsResult.success) {
                renderPersonsTable(personsResult.data);
            }

        } catch (error) {
            console.error('Fehler beim Aktualisieren der Liste:', error);
        }
    }


    /**
 * Baut die HTML-Tabelle mit der Personenliste und wendet den Circle-Filter an.
 * @param {Array} persons - Die komplette, ungefilterte Personenliste von der API.
 */
    function renderPersonsTable(persons) {
        const container = document.getElementById('persons-table-container');
        if (!container) return;

        // --- NEU: FILTERLOGIK START ---
        let filteredPersons = persons;
        // Wenn ein Filter in der globalen Variable gesetzt ist...
        if (currentCircleFilter) {
            // ...wende den Array.filter auf die Personenliste an.
            filteredPersons = persons.filter(person => {
                // Prüfe, ob die Person überhaupt Circles-Einträge hat.
                if (!person.circles) return false;
                // Zerlege den String der Person in ein Array von Circles.
                const personCircles = person.circles.split(',').map(c => c.trim());
                // Gibt `true` zurück (und behält die Person), wenn der Filter im Array enthalten ist.
                return personCircles.includes(currentCircleFilter);
            });
        }
        // --- NEU: FILTERLOGIK ENDE ---

        // Prüfe, ob nach dem Filtern noch Personen übrig sind.
        if (filteredPersons.length === 0) {
            container.innerHTML = '<div class="alert alert-info mt-3">Keine Kontakte für den aktuellen Filter gefunden.</div>';
            return;
        }

        // Ab hier ist der Code identisch zu Ihrem alten Code,
        // verwendet aber die potenziell gefilterte Liste `filteredPersons`.
        let tableHtml = `
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th></th>
                        <th data-sort="last_name" class="sortable" style="cursor: pointer;">Name <i class="bi"></i></th>
                        <th data-sort="company" class="sortable" style="cursor: pointer;">Firma & Position <i class="bi"></i></th>
                        <th data-sort="priority" class="sortable" style="cursor: pointer;">Status & Prio <i class="bi"></i></th>
                        <th data-sort="contact_cycle" class="sortable" style="cursor: pointer;">Zyklus <i class="bi"></i></th>
                        <th data-sort="last_interaction" class="sortable" style="cursor: pointer;">Letzter Kontakt <i class="bi"></i></th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>`;

        filteredPersons.forEach(person => {
            let circlesHtml = '';
            if (person.circles) {
                const circlesArray = person.circles.split(',').map(s => s.trim()).filter(Boolean);
                circlesHtml = circlesArray.map(circle =>
                    `<span class="badge bg-purple me-1">${htmlspecialchars(circle)}</span>`
                ).join(' ');
            }

            tableHtml += `
            <tr>
                <td>${getTrafficLightIndicator(person.last_interaction, person.contact_cycle)}</td>
                <td>${htmlspecialchars(person.first_name || '')} <strong>${htmlspecialchars(person.last_name)}</strong>
                    <div class="mt-1">${circlesHtml}</div>
                </td>
                <td>
                    ${htmlspecialchars(person.company || '')}
                    <small class="d-block text-muted">${htmlspecialchars(person.position || '')}</small>
                </td>
                <td>
                    <span class="badge bg-secondary">${htmlspecialchars(person.status || '-')}</span>
                    <span class="badge bg-info text-dark">${htmlspecialchars(person.priority || '-')}</span>
                </td>
                <td>${htmlspecialchars(person.contact_cycle || '-')}</td>
                <td>${person.last_interaction ? new Date(person.last_interaction).toLocaleDateString('de-DE') : '-'}</td>
                <td>
                    <button class="btn btn-secondary btn-sm btn-show-interactions" title="Interaktionen" data-person-id="${person.person_id}"><i class="bi bi-chat-left-text"></i></button>
                    <button class="btn btn-outline-primary btn-sm btn-edit-person" title="Bearbeiten" data-person-id="${person.person_id}"><i class="bi bi-pencil"></i></button>
                </td>
            </tr>`;
        });

        tableHtml += `</tbody></table></div>`;
        container.innerHTML = tableHtml;

        // Setzt das Sortier-Icon in der richtigen Spalte
        const activeHeader = container.querySelector(`th[data-sort='${currentSortField}'] i`);
        if (activeHeader) {
            activeHeader.className = currentSortDir === 'ASC' ? 'bi bi-sort-up' : 'bi bi-sort-down';
        }
    }


    /**
     * NEUE FUNKTION
     * Baut das HTML für die klickbaren Filter-Badges und fügt es ein.
     * Hebt den aktuell aktiven Filter visuell hervor.
     */
    function renderCircleFilters(circles) {
        const container = document.getElementById('circles-filter-container');
        if (!container) return;

        if (circles.length === 0) {
            container.innerHTML = ''; // Keine Circles, kein Filter
            return;
        }

        let html = '';

        // "Alle"-Button zum Zurücksetzen des Filters
        const allClass = !currentCircleFilter ? 'bg-primary text-white' : 'bg-secondary';
        html += `<a href="#" class="badge ${allClass} me-1 filter-badge" data-circle="all">Alle</a>`;

        // Baue ein Badge für jeden Circle
        circles.forEach(circle => {
            const activeClass = (circle === currentCircleFilter) ? 'bg-primary text-white' : 'bg-purple';
            html += `<a href="#" class="badge ${activeClass} me-1 filter-badge" data-circle="${htmlspecialchars(circle)}">${htmlspecialchars(circle)}</a>`;
        });

        container.innerHTML = html;
    }


    /** Baut das HTML für das Personen-Formular und fügt es in den Detailbereich ein. */
    function renderPersonForm(person) {
        const html = `
            <h3>${person.person_id ? 'Kontakt bearbeiten' : 'Neuen Kontakt erstellen'}</h3>
            <form id="person-form" novalidate>
                <input type="hidden" name="person_id" value="${person.person_id || ''}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Vorname</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="${htmlspecialchars(person.first_name || '')}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Nachname</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="${htmlspecialchars(person.last_name || '')}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email1" class="form-label">E-Mail 1</label>
                        <input type="email" class="form-control" id="email1" name="email1" value="${htmlspecialchars(person.email1 || '')}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email2" class="form-label">E-Mail 2</label>
                        <input type="email" class="form-control" id="email2" name="email2" value="${htmlspecialchars(person.email2 || '')}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone1" class="form-label">Telefon 1</label>
                        <input type="tel" class="form-control" id="phone1" name="phone1" value="${htmlspecialchars(person.phone1 || '')}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone2" class="form-label">Telefon 2</label>
                        <input type="tel" class="form-control" id="phone2" name="phone2" value="${htmlspecialchars(person.phone2 || '')}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company" class="form-label">Firma</label>
                        <input type="text" class="form-control" id="company" name="company" value="${htmlspecialchars(person.company || '')}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position" value="${htmlspecialchars(person.position || '')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="circles" class="form-label">Circles (kommasepariert)</label>
                    <input type="text" class="form-control" id="circles" name="circles" 
                        value="${htmlspecialchars(person.circles || '')}" 
                        placeholder="z.B. Freund, Firma1, IT-Architekten">
                    <div class="form-text">
                        Mehrere Begriffe einfach mit einem Komma trennen.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="linkedin_profile" class="form-label">LinkedIn Profil</label>
                    <input type="url" class="form-control" id="linkedin_profile" name="linkedin_profile" placeholder="https://www.linkedin.com/in/..." value="${htmlspecialchars(person.linkedin_profile || '')}">
                </div>
                <div class="mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" placeholder="https://..." value="${htmlspecialchars(person.website || '')}">
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="birthday" class="form-label">Geburtstag</label>
                        <input type="date" class="form-control" id="birthday" name="birthday" value="${person.birthday || ''}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="NEW" ${person.status === 'NEW' || !person.status ? 'selected' : ''}>NEU</option>
                            <option value="ACTIVE" ${person.status === 'ACTIVE' ? 'selected' : ''}>Aktiv</option>
                            <option value="INACTIVE" ${person.status === 'INACTIVE' ? 'selected' : ''}>Inaktiv</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priorität</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="TOP10" ${person.priority === 'TOP10' ? 'selected' : ''}>Top 10</option>
                            <option value="TOP25" ${person.priority === 'TOP25' ? 'selected' : ''}>Top 25</option>
                            <option value="TOP50" ${person.priority === 'TOP50' ? 'selected' : ''}>Top 50</option>
                            <option value="TOP100" ${!person.priority || person.priority === 'TOP100' ? 'selected' : ''}>Top 100</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="contact_cycle" class="form-label">Kontaktzyklus</label>
                    <select class="form-select" id="contact_cycle" name="contact_cycle">
                        <option value="">(kein Zyklus)</option>
                        <option value="WEEKLY" ${person.contact_cycle === 'WEEKLY' ? 'selected' : ''}>Wöchentlich</option>
                        <option value="BIWEEKLY" ${person.contact_cycle === 'BIWEEKLY' ? 'selected' : ''}>Zweiwöchentlich</option>
                        <option value="MONTHLY" ${person.contact_cycle === 'MONTHLY' ? 'selected' : ''}>Monatlich</option>
                        <option value="QUARTERLY" ${person.contact_cycle === 'QUARTERLY' ? 'selected' : ''}>Quartalsweise</option>
                        <option value="SEMI_ANNUALLY" ${person.contact_cycle === 'SEMI_ANNUALLY' ? 'selected' : ''}>Halbjährlich</option>
                        <option value="ANNUALLY" ${person.contact_cycle === 'ANNUALLY' ? 'selected' : ''}>Jährlich</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notizen</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4">${htmlspecialchars(person.notes || '')}</textarea>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        ${person.person_id ? `<button type="button" id="btn-delete-person" class="btn btn-outline-danger">Löschen</button>` : ''}
                    </div>
                    <button type="button" id="btn-cancel-edit" class="btn btn-secondary me-2">Abbrechen</button>
                    <button type="button" id="btn-save-person" class="btn btn-primary">Speichern</button>
                </div>
            </form>`;
        detailsPanel.innerHTML = html;
    }

    /** Baut das HTML für die Interaktionsansicht, inklusive des Formulars zum Hinzufügen/Bearbeiten. */
    function renderInteractions(interactions) {
        const today = new Date().toISOString().split('T')[0];
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Interaktionen</h3>
                <button id="btn-show-new-interaction-form" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Neue Interaktion</button>
            </div>
            <div id="new-interaction-form-container" class="card card-body mb-4 d-none">
                <h5 class="card-title">Neue Interaktion hinzufügen</h5>
                <form id="new-interaction-form">
                    <div class="mb-3">
                        <label for="interaction_date" class="form-label">Datum</label>
                        <input type="date" class="form-control" id="interaction_date" name="interaction_date" value="${today}" required>
                    </div>
                    <div class="mb-3">
                        <label for="interaction_type" class="form-label">Art der Interaktion</label>
                        <select class="form-select" id="interaction_type" name="interaction_type" required>
                            <option value="" disabled selected>Bitte auswählen...</option>
                            <option value="PHONE_CALL">Telefonat</option>
                            <option value="EMAIL">E-Mail</option>
                            <option value="LINKEDIN_MESSAGE">LinkedIn Nachricht</option>
                            <option value="MEETING">Meeting</option>
                            <option value="LUNCH">Mittagessen</option>
                            <option value="COFFEE_MEETING">Kaffee-Treffen</option>
                            <option value="CONFERENCE">Konferenz</option>
                            <option value="OTHER">Sonstiges</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="memo" class="form-label">Notizen</label>
                        <textarea class="form-control" id="memo" name="memo" rows="3"></textarea>
                    </div>
                    <div>
                        <button type="button" id="btn-save-interaction" class="btn btn-primary">Speichern</button>
                        <button type="button" id="btn-cancel-interaction" class="btn btn-secondary">Abbrechen</button>
                    </div>
                </form>
            </div>
            <div id="interaction-list-container">`;
        if (interactions.length === 0) {
            html += '<p class="text-muted">Keine Interaktionen für diesen Kontakt vorhanden.</p>';
        } else {
            html += '<div class="list-group">';
            interactions.forEach(interaction => {
                const interactionData = JSON.stringify(interaction); // Ohne htmlspecialchars!
                const interactionTypeDisplay = {
                    'COFFEE_MEETING': 'Kaffee-Treffen', 'EMAIL': 'E-Mail', 'LINKEDIN_MESSAGE': 'LinkedIn Nachricht',
                    'PHONE_CALL': 'Telefonat', 'LUNCH': 'Mittagessen', 'MEETING': 'Meeting',
                    'CONFERENCE': 'Konferenz', 'OTHER': 'Sonstiges'
                };
                html += `


                <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${new Date(interaction.interaction_date).toLocaleDateString('de-DE')} 
                            <small>${htmlspecialchars(interactionTypeDisplay[interaction.interaction_type] || interaction.interaction_type)}</small></h5>
                            <div>
                                <button class="btn btn-outline-primary btn-sm btn-edit-interaction" title="Bearbeiten" data-interaction='${interactionData}'><i class="bi bi-pencil"></i></button>
                            </div>
                        </div>
                        <p class="mb-1">${htmlspecialchars(interaction.memo || '')}</p>
                   
                    </div>`;
            });
            html += '</div>';
        }
        html += `</div>`;
        detailsPanel.innerHTML = html;
    }

    /** Füllt das Interaktionsformular (entweder leer für "Neu" oder mit Daten für "Bearbeiten"). */
    function populateInteractionForm(interaction = null) {
        const formContainer = document.getElementById('new-interaction-form-container');
        const form = document.getElementById('new-interaction-form');
        const title = formContainer.querySelector('h5');

        const oldHiddenInput = form.querySelector('input[name="interaction_id"]');
        if (oldHiddenInput) oldHiddenInput.remove();

        if (interaction) {
            title.textContent = 'Interaktion bearbeiten';
            form.interaction_date.value = interaction.interaction_date;
            form.interaction_type.value = interaction.interaction_type;
            form.memo.value = interaction.memo;

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'interaction_id';
            hiddenInput.value = interaction.interaction_id;
            form.appendChild(hiddenInput);
        } else {
            title.textContent = 'Neue Interaktion hinzufügen';
            form.reset();
            form.interaction_date.value = new Date().toISOString().split('T')[0];
        }

        formContainer.classList.remove('d-none');
        document.getElementById('btn-show-new-interaction-form').classList.add('d-none');
    }

    /** Zeigt die Willkommensnachricht im Detailbereich an. */
    function showWelcomeMessage() {
        detailsPanel.innerHTML = `
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="text-center text-muted">
                    <i class="bi bi-arrow-left-square" style="font-size: 3rem;"></i>
                    <p class="mt-2">Wählen Sie einen Kontakt aus oder erstellen Sie einen neuen.</p>
                </div>
            </div>`;
    }

    /** Hilfsfunktion zum Schutz vor XSS in der HTML-Ausgabe. */
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, (match) => {
            const replacements = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return replacements[match];
        });
    }

    /** Erzeugt das HTML für ein Ampel-Icon basierend auf dem Kontaktzyklus. */
    function getTrafficLightIndicator(lastInteractionDate, contactCycle) {
        if (!contactCycle) {
            return '<i class="bi bi-circle-fill text-muted" title="Kein Kontaktzyklus definiert"></i>';
        }
        const cycleDaysMap = {
            'WEEKLY': 7, 'BIWEEKLY': 14, 'MONTHLY': 30, 'QUARTERLY': 90,
            'SEMI_ANNUALLY': 180, 'ANNUALLY': 365
        };
        const cycleDuration = cycleDaysMap[contactCycle];
        if (!lastInteractionDate) {
            return `<i class="bi bi-circle-fill text-danger" title="Erster Kontakt für Zyklus '${contactCycle}' überfällig"></i>`;
        }
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const lastContact = new Date(lastInteractionDate);
        const diffTime = today - lastContact;
        const daysSinceLastContact = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (daysSinceLastContact < cycleDuration) {
            const daysLeft = cycleDuration - daysSinceLastContact;
            return `<i class="bi bi-circle-fill text-success" title="Nächster Kontakt in ca. ${daysLeft} Tagen fällig"></i>`;
        } else if (daysSinceLastContact < cycleDuration * 2) {
            const daysOverdue = daysSinceLastContact - cycleDuration;
            return `<i class="bi bi-circle-fill text-warning" title="Kontakt seit ca. ${daysOverdue} Tagen überfällig"></i>`;
        } else {
            return `<i class="bi bi-circle-fill text-danger" title="Kontakt stark überfällig!"></i>`;
        }
    }

    /**
     * 
     * Sendet die Löschanfrage für die aktuell ausgewählte Person an die API.
     */
    async function handleDeletePerson() {
        // Die ID der zu löschenden Person ist in `currentPersonId` gespeichert
        if (!currentPersonId) {
            alert('Fehler: Keine Person zum Löschen ausgewählt.');
            return;
        }

        try {
            // Ruft den neuen 'delete_person' API-Endpunkt auf
            const response = await fetch(`api.php?action=delete_person&id=${currentPersonId}`, {
                method: 'POST' // POST ist für löschende Aktionen besser als GET
            });

            // Wir nutzen wieder die robuste Fehlerbehandlung
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server-Fehler (Status ${response.status}): ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                alert('Kontakt wurde erfolgreich gelöscht.');
                await refreshPersonList(); // Liste aktualisieren
                showWelcomeMessage(); // Detailansicht zurücksetzen
            } else {
                alert('Fehler beim Löschen: ' + (result.message || 'Unbekannter Fehler.'));
            }

        } catch (error) {
            console.error('Netzwerkfehler beim Löschen:', error);
            alert('Ein Fehler ist aufgetreten. Details:\n\n' + error.message);
        }
    }

    // =========================================================================
    // INITIALER AUFRUF
    // =========================================================================
    refreshPersonList();
});
