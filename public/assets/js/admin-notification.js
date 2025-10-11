// admin-notification.js
// Sistema de notificaciones integrado en la instancia Vue principal

class AdminNotificationSystem {
    constructor() {
        this.vueInstance = null;
        this.init();
    }

    /**
     * Inicializa el sistema de notificaciones
     */
    init() {
        const container = document.querySelector('#admin-notifications');
        if (!container) {
            console.warn('Admin notification container #admin-notifications not found');
            return;
        }

        // Esperar a que Vue esté listo
        setTimeout(() => {
            this.attachToVueInstance();
        }, 100);
    }

    /**
     * Se adjunta a la instancia Vue existente
     */
    attachToVueInstance() {
        this.vueInstance = window.vueInstance;

        if (!this.vueInstance) {
            console.error('Vue instance not found');
            return;
        }

        // Agregar datos de notificaciones a la instancia Vue
        if (!this.vueInstance.alerts) {
            this.vueInstance.$data.alerts = [];
        }

        // Agregar métodos a la instancia Vue
        Object.assign(this.vueInstance, {
            addAlert: this.addAlert.bind(this),
            dismissAlert: this.dismissAlert.bind(this),
            clearAll: this.clearAll.bind(this),
            loadServerAlerts: this.loadServerAlerts.bind(this),
            deleteMessage: this.deleteMessage.bind(this)
        });

        // Montar el componente de notificaciones
        this.mountNotificationComponent();

        // Cargar alertas del servidor
        this.loadServerAlerts();

        console.log('Admin notification system initialized');
    }

    /**
     * Monta el componente de notificaciones en el contenedor
     */
    mountNotificationComponent() {
        const container = document.querySelector('#admin-notifications');
        const { createApp } = Vue;

        const vueInstance = this.vueInstance;

        const notificationApp = createApp({
            data() {
                return {
                    alerts: vueInstance.$data.alerts || []
                };
            },
            watch: {
                'alerts': {
                    handler(newVal) {
                        if (vueInstance.$data.alerts) {
                            vueInstance.$data.alerts = newVal;
                        }
                    },
                    deep: true
                }
            },
            methods: {
                dismissAlert: this.dismissAlert.bind(this),
            },
            template: `
                <div class="admin-notifications-container">
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
        }).mount(container);
    }

    /**
     * Añade una nueva alerta
     */
    addAlert(type, message, key = null, dismissible = true, duration = null) {
        const id = key || `alert-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const icons = {
            danger: 'fas fa-exclamation-triangle',
            error: 'fas fa-exclamation-triangle',
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

        if (this.vueInstance && this.vueInstance.$data.alerts) {
            this.vueInstance.$data.alerts.push(alert);
        }

        if (duration && duration > 0) {
            setTimeout(() => {
                this.dismissAlert(id);
            }, duration);
        }

        return alert;
    }

    /**
     * Descarta una alerta con animación
     */
    dismissAlert(alertId) {
        if (!this.vueInstance || !this.vueInstance.$data.alerts) return;

        const index = this.vueInstance.$data.alerts.findIndex(a => a.id === alertId);
        if (index > -1) {
            const alert = this.vueInstance.$data.alerts[index];

            const alertElement = document.querySelector(`[data-alert-id="${alertId}"]`);
            if (alertElement) {
                alertElement.style.animation = 'slideOut 0.3s ease-out forwards';

                setTimeout(() => {
                    this.vueInstance.$data.alerts.splice(index, 1);
                    if (alert.key) {
                        this.deleteMessage(alert.key);
                    }
                }, 300);
            } else {
                this.vueInstance.$data.alerts.splice(index, 1);
                if (alert.key) {
                    this.deleteMessage(alert.key);
                }
            }
        }
    }

    /**
     * Limpia todas las alertas
     */
    clearAll() {
        if (this.vueInstance && this.vueInstance.$data.alerts) {
            this.vueInstance.$data.alerts = [];
        }
    }

    /**
     * Elimina un mensaje del servidor
     */
    async deleteMessage(key) {
        const formData = new FormData();
        formData.append('key', key);

        try {
            const response = await fetch('/admin/notification/', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            console.log(data.status, data.message);
            return data;
        } catch (err) {
            console.error('Error deleting flash message:', err);
            throw err;
        }
    }

    /**
     * Carga alertas desde el servidor
     */
    async loadServerAlerts() {
        try {
            const response = await fetch('/admin/notification/');
            const json = await response.json();

            if (json.status !== 'ok' && json.status !== 'empty') return;

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
}

export { AdminNotificationSystem };
export default AdminNotificationSystem;