// login.js
import NotificationSystem from './notification.js';

/**
 * Sistema de gestión de Login
 */
class LoginSystem {
    constructor() {
        this.notifications = null;
        this.init();
    }

    /**
     * Inicializa el sistema de login
     */
    init() {
        // Inicializar sistema de notificaciones
        this.notifications = new NotificationSystem({
            container: '#notifications',
            autoInit: true
        });

        // Cargar alertas del servidor
        this.notifications.loadServerAlerts();

        // Enfocar el campo de usuario
        this.focusUsername();
    }

    /**
     * Enfoca el campo de usuario
     */
    focusUsername() {
        const usernameInput = document.querySelector('#username');
        if (usernameInput) {
            usernameInput.focus();
        }
    }
}

// Auto-instanciar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.loginSystem = new LoginSystem();
    });
} else {
    window.loginSystem = new LoginSystem();
}

export default LoginSystem;