/**
 * auth.js - Session-Handling und Auto-Logout
 * 
 * Beschreibung:
 * - Session-Timeout-Prüfung
 * - Auto-Logout nach Inaktivität
 * - Activity-Tracking
 */

const Auth = {
    
    // Timeout in Minuten
    TIMEOUT_MINUTES: 30,
    
    // Interval-ID für Timeout-Check
    timeoutInterval: null,
    
    // Letzte Aktivität (Timestamp)
    lastActivity: Date.now(),
    
    /**
     * Initialisierung
     */
    init: function() {
        // Nur wenn eingeloggt
        if (this.isLoggedIn()) {
            this.startTimeoutCheck();
            this.trackActivity();
        }
    },
    
    /**
     * Prüft ob User eingeloggt ist
     */
    isLoggedIn: function() {
        // Einfache Prüfung: Gibt es ein Dashboard-Element?
        return document.body.classList.contains('logged-in') || 
               document.querySelector('[data-user-id]') !== null;
    },
    
    /**
     * Startet Timeout-Überwachung
     */
    startTimeoutCheck: function() {
        // Alle 60 Sekunden prüfen
        this.timeoutInterval = setInterval(() => {
            this.checkTimeout();
        }, 60000);
    },
    
    /**
     * Prüft ob Session abgelaufen ist
     */
    checkTimeout: function() {
        const elapsed = (Date.now() - this.lastActivity) / 1000 / 60; // Minuten
        
        if (elapsed >= this.TIMEOUT_MINUTES) {
            this.logout('Ihre Sitzung ist abgelaufen (Inaktivität)');
        } else if (elapsed >= this.TIMEOUT_MINUTES - 5) {
            // 5 Minuten vor Ablauf warnen
            UIComponents.showNotification(
                `Ihre Sitzung läuft in ${Math.ceil(this.TIMEOUT_MINUTES - elapsed)} Minuten ab`,
                'warning'
            );
        }
    },
    
    /**
     * Aktivitäts-Tracking
     */
    trackActivity: function() {
        // Bei jeder Benutzer-Interaktion Timestamp aktualisieren
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
            }, { passive: true });
        });
    },
    
    /**
     * Logout
     */
    logout: function(message = null) {
        if (message) {
            sessionStorage.setItem('logout_message', message);
        }
        
        window.location.href = '/index.php?page=logout';
    }
};

// Bei Laden initialisieren
document.addEventListener('DOMContentLoaded', () => {
    Auth.init();
    
    // Logout-Nachricht anzeigen falls vorhanden
    const logoutMessage = sessionStorage.getItem('logout_message');
    if (logoutMessage) {
        UIComponents.showNotification(logoutMessage, 'warning');
        sessionStorage.removeItem('logout_message');
    }
});
