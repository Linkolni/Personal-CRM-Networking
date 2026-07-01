/**
 * validation.js - Formularvalidierung
 * 
 * Beschreibung:
 * - Clientseitige Validierung
 * - Echtzeit-Feedback
 * - Bootstrap-Integration
 */

const Validation = {
    
    /**
     * Initialisierung
     */
    init: function() {
        // Alle Formulare mit Validierung
        document.querySelectorAll('form[data-validate="true"]').forEach(form => {
            this.attachValidation(form);
        });
        
        // Spezifische Felder
        this.initEmailValidation();
        this.initPasswordValidation();
        this.initPriceValidation();
    },
    
    /**
     * Validierung an Formular anhängen
     */
    attachValidation: function(form) {
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    },
    
    /**
     * Komplettes Formular validieren
     */
    validateForm: function(form) {
        let isValid = true;
        
        // Alle required Felder prüfen
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                this.showError(field, 'Dieses Feld ist erforderlich');
                isValid = false;
            }
        });
        
        // E-Mail-Felder
        form.querySelectorAll('input[type="email"]').forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showError(field, 'Ungültige E-Mail-Adresse');
                isValid = false;
            }
        });
        
        // Passwort-Bestätigung
        const password = form.querySelector('input[name="password"]');
        const passwordConfirm = form.querySelector('input[name="password_confirm"]');
        
        if (password && passwordConfirm) {
            if (password.value !== passwordConfirm.value) {
                this.showError(passwordConfirm, 'Passwörter stimmen nicht überein');
                isValid = false;
            }
        }
        
        return isValid;
    },
    
    /**
     * E-Mail-Validierung
     */
    initEmailValidation: function() {
        document.querySelectorAll('input[type="email"]').forEach(field => {
            field.addEventListener('blur', () => {
                if (field.value && !this.isValidEmail(field.value)) {
                    this.showError(field, 'Ungültige E-Mail-Adresse');
                } else {
                    this.clearError(field);
                }
            });
        });
    },
    
    /**
     * Passwort-Validierung (min. 8 Zeichen)
     */
    initPasswordValidation: function() {
        document.querySelectorAll('input[type="password"][name="password"]').forEach(field => {
            field.addEventListener('input', () => {
                const length = field.value.length;
                const feedback = field.parentElement.querySelector('.password-strength');
                
                if (feedback) {
                    if (length === 0) {
                        feedback.textContent = '';
                    } else if (length < 8) {
                        feedback.textContent = 'Zu kurz (min. 8 Zeichen)';
                        feedback.className = 'password-strength text-danger';
                    } else if (length < 12) {
                        feedback.textContent = 'Akzeptabel';
                        feedback.className = 'password-strength text-warning';
                    } else {
                        feedback.textContent = 'Stark';
                        feedback.className = 'password-strength text-success';
                    }
                }
            });
        });
    },
    
    /**
     * Preis/Reward-Validierung (0-999.99)
     */
    initPriceValidation: function() {
        document.querySelectorAll('input[name="price"], input[name="reward"]').forEach(field => {
            field.addEventListener('blur', () => {
                const value = parseFloat(field.value);
                
                if (isNaN(value) || value < 0 || value > 999.99) {
                    this.showError(field, 'Wert muss zwischen 0 und 999.99 liegen');
                } else {
                    this.clearError(field);
                }
            });
        });
    },
    
    /**
     * E-Mail-Format prüfen
     */
    isValidEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    /**
     * Fehler anzeigen
     */
    showError: function(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        // Feedback-Element erstellen oder aktualisieren
        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    },
    
    /**
     * Fehler entfernen
     */
    clearError: function(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
};

// Bei Laden initialisieren
document.addEventListener('DOMContentLoaded', () => {
    Validation.init();
});
