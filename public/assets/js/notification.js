// notification.js

/**
 * Sistema de notificaciones como componente Vue independiente
 */
export function createNotificationSystem() {
    const { createApp } = Vue;

    return createApp({
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

                    // Buscar el elemento y agregar animación de salida
                    const alertElement = this.$el.querySelector(`[data-alert-id="${alertId}"]`);
                    if (alertElement) {
                        alertElement.style.animation = 'slideOut 0.3s ease-out forwards';

                        setTimeout(() => {
                            this.alerts.splice(index, 1);
                            if (alert.key) {
                                this.deleteMessage(alert.key);
                            }
                        }, 300);
                    } else {
                        this.alerts.splice(index, 1);
                        if (alert.key) {
                            this.deleteMessage(alert.key);
                        }
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
             * Elimina un mensaje del servidor
             */
            async deleteMessage(key) {
                const formData = new FormData();
                formData.append('key', key);

                try {
                    const response = await fetch('notification/', {
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
    });
}

// Export por defecto
export default createNotificationSystem;