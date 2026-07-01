/**
 * upload.js - Screenshot-Upload mit Vorschau
 * 
 * Beschreibung:
 * - Datei-Validierung
 * - Bildvorschau
 * - Upload-Progress
 */

const Upload = {
    
    // Max. Dateigröße (5 MB)
    MAX_SIZE: 5 * 1024 * 1024,
    
    // Erlaubte Dateitypen
    ALLOWED_TYPES: ['image/jpeg', 'image/jpg', 'image/png'],
    
    /**
     * Initialisierung
     */
    init: function() {
        const fileInput = document.querySelector('input[type="file"][name="screenshot"]');
        
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileSelect(e.target);
            });
        }
    },
    
    /**
     * Dateiauswahl behandeln
     */
    handleFileSelect: function(input) {
        const file = input.files[0];
        
        if (!file) {
            return;
        }
        
        // Validierung
        if (!this.validateFile(file)) {
            input.value = ''; // Reset
            return;
        }
        
        // Vorschau anzeigen
        this.showPreview(file);
    },
    
    /**
     * Datei validieren
     */
    validateFile: function(file) {
        // Dateityp prüfen
        if (!this.ALLOWED_TYPES.includes(file.type)) {
            UIComponents.showNotification(
                'Ungültiger Dateityp. Nur JPG und PNG erlaubt.', 
                'error'
            );
            return false;
        }
        
        // Dateigröße prüfen
        if (file.size > this.MAX_SIZE) {
            UIComponents.showNotification(
                'Datei zu groß (max. 5 MB)', 
                'error'
            );
            return false;
        }
        
        return true;
    },
    
    /**
     * Bildvorschau anzeigen
     */
    showPreview: function(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Preview-Container finden oder erstellen
            let preview = document.getElementById('screenshot-preview');
            
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'screenshot-preview';
                preview.className = 'mt-3';
                
                const fileInput = document.querySelector('input[name="screenshot"]');
                fileInput.parentElement.appendChild(preview);
            }
            
            // Bild anzeigen
            preview.innerHTML = `
                <p class="text-muted">Vorschau:</p>
                <img src="${e.target.result}" class="img-thumbnail" 
                     style="max-width: 400px; max-height: 400px;" 
                     alt="Screenshot-Vorschau">
                <p class="mt-2 text-success">
                    <i class="bi bi-check-circle"></i> Datei bereit zum Upload
                </p>
            `;
        };
        
        reader.readAsDataURL(file);
    }
};

// Bei Laden initialisieren
document.addEventListener('DOMContentLoaded', () => {
    Upload.init();
});
