// notification.js

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 * Sistema de notificaciones plug and play como componente Vue independiente
 *
 */
class NotificationSystem {
    constructor() {
        this.vueApp = null;
        this.init();
    }

    /**
     * Inicializa el sistema de notificaciones
     */
    init() {
        const container = document.querySelector('#notifications');
        if (!container) {
            console.warn('Notification container #notifications not found');
            return;
        }

        this.createVueApp();
    }

    /**
     * Crea y monta la aplicación Vue
     */
    createVueApp() {
        const { createApp } = Vue;

        this.vueApp = createApp({
            data() {
                return {
                    alerts: []
                };
            },

            methods: {
                /**
                 * Añade una nueva alerta
                 */
                addAlert(type, message, key = null, dismissible = true) {
                    const id = key || `alert-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                    const icons = {
                        danger: 'fas fa-exclamation-triangle',
                        warning: 'fas fa-exclamation-triangle',
                        success: 'fas fa-check-circle',
                        info: 'fas fa-info-circle'
                    };

                    const alert = {
                        id,
                        type,
                        icon: icons[type] || 'fas fa-info-circle',
                        message,
                        key,
                        dismissible
                    };

                    this.alerts.push(alert);
                    return alert;
                },

                /**
                 * Descarta una alerta con animación
                 */
                dismissAlert(alertId) {
                    const index = this.alerts.findIndex(a => a.id === alertId);
                    if (index > -1) {
                        const alert = this.alerts[index];

                        const alertElement = this.$el.querySelector(`[data-alert-id="${alertId}"]`);
                        if (alertElement) {
                            alertElement.style.animation = 'slideOut 0.3s ease-out forwards';

                            setTimeout(() => {
                                this.alerts.splice(index, 1);
                            }, 300);
                        } else {
                            this.alerts.splice(index, 1);
                        }
                    }
                },

                /**
                 * Limpia todas las alertas
                 */
                clearAll() {
                    this.alerts = [];
                },

                /**
                 * Carga alertas desde el servidor
                 */
                async loadServerAlerts() {
                    try {
                        const response = await fetch('notification/');
                        const json = await response.json();

                        if (json.status !== 'ok') return;

                        const serverData = json.data || {};

                        for (const [key, value] of Object.entries(serverData)) {
                            let type = 'info';
                            if (key === 'errors') type = 'danger';
                            if (key === 'success') type = 'success';
                            if (key === 'warnings') type = 'warning';

                            if (value && typeof value === 'object') {
                                for (const [subKey, msg] of Object.entries(value)) {
                                    this.addAlert(type, msg, `${key}.${subKey}`);
                                }
                            } else {
                                this.addAlert(type, String(value), key);
                            }
                        }
                    } catch (err) {
                        console.error('Error loading server alerts:', err);
                    }
                }
            },

            template: `
                <div class="notifications-container">
                    <transition-group name="alert" tag="div">
                        <div 
                            v-for="alert in alerts" 
                            :key="alert.id"
                            :class="['vue-alert', 'vue-alert-' + alert.type]"
                            :data-alert-id="alert.id"
                            role="alert"
                        >
                            <div class="vue-alert-icon">
                                <i :class="alert.icon"></i>
                            </div>
                            <div class="vue-alert-content" v-html="alert.message"></div>
                            <button 
                                v-if="alert.dismissible"
                                class="vue-alert-close"
                                aria-label="Close"
                                @click="dismissAlert(alert.id)"
                            >
                                ×
                            </button>
                        </div>
                    </transition-group>
                </div>
            `
        }).mount('#notifications');

        // Cargar alertas del servidor automáticamente
        this.vueApp.loadServerAlerts();
    }

    /**
     * API pública: Añade una alerta
     */
    addAlert(type, message, key = null, dismissible = true) {
        if (this.vueApp) {
            return this.vueApp.addAlert(type, message, key, dismissible);
        }
        console.warn('Vue app not initialized');
        return null;
    }

    /**
     * API pública: Descarta una alerta
     */
    dismissAlert(alertId) {
        if (this.vueApp) {
            this.vueApp.dismissAlert(alertId);
        }
    }

    /**
     * API pública: Limpia todas las alertas
     */
    clearAll() {
        if (this.vueApp) {
            this.vueApp.clearAll();
        }
    }

    /**
     * API pública: Carga alertas del servidor
     */
    loadServerAlerts() {
        if (this.vueApp) {
            this.vueApp.loadServerAlerts();
        }
    }
}

/**
 * Auto-instanciar el sistema de notificaciones si existe el contenedor
 */
function initNotificationSystem() {
    const container = document.querySelector('#notifications');
    if (container) {
        const notificationSystem = new NotificationSystem();

        // Exponer globalmente para acceso desde otros módulos
        window.notificationSystem = notificationSystem;

        console.log('Notification system initialized');
        return notificationSystem;
    }
    return null;
}

// Auto-inicialización cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationSystem);
} else {
    initNotificationSystem();
}

// Export para uso como módulo
export { NotificationSystem, initNotificationSystem };
export default NotificationSystem;