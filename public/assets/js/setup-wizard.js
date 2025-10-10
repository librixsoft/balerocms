// setup-wizard.js
import NotificationSystem from './notification.js';

const { createApp } = Vue;

createApp({
    data() {
        return {
            currentStep: 0,
            steps: [
                {
                    id: 'db-config',
                    label: window.BLOCK_CONFIG.step1_label,
                    icon: 'fas fa-database'
                },
                {
                    id: 'site-info',
                    label: window.BLOCK_CONFIG.step2_label,
                    icon: 'fas fa-info-circle'
                },
                {
                    id: 'admin-config',
                    label: window.BLOCK_CONFIG.step3_label,
                    icon: 'fas fa-user-shield'
                }
            ],
            tooltipElement: null,
            notificationSystem: null
        };
    },

    mounted() {
        // Inicializar sistema de notificaciones
        this.notificationSystem = new NotificationSystem({
            container: '#notifications',
            autoInit: true
        });

        // Cargar alertas del servidor
        this.notificationSystem.loadServerAlerts();

        // Crear el elemento tooltip una sola vez
        this.tooltipElement = document.createElement('div');
        this.tooltipElement.className = 'custom-tooltip';
        document.body.appendChild(this.tooltipElement);
    },

    methods: {
        // Gestión de alertas
        addAlert(type, message, key = null, dismissible = true) {
            this.notificationSystem.addAlert(type, message, key, dismissible);
        },

        dismissAlert(alertId) {
            this.notificationSystem.dismissAlert(alertId);
        },

        // Navegación entre pasos
        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        goToStep(stepIndex) {
            if (stepIndex >= 0 && stepIndex < this.steps.length) {
                this.currentStep = stepIndex;
            }
        },

        // Gestión de tooltips
        showTooltip(event, message) {
            if (!this.tooltipElement) return;

            const icon = event.target;
            const rect = icon.getBoundingClientRect();

            // Configurar contenido y mostrar
            this.tooltipElement.innerHTML = message;
            this.tooltipElement.style.display = 'block';
            this.tooltipElement.style.opacity = '1';

            // Esperar un frame para obtener dimensiones correctas
            this.$nextTick(() => {
                const tooltipRect = this.tooltipElement.getBoundingClientRect();
                let left = rect.left + window.scrollX - 12;
                let top = rect.bottom + window.scrollY + 8;

                // Ajustar si se sale por la derecha
                if (left + tooltipRect.width > window.innerWidth) {
                    left = window.innerWidth - tooltipRect.width - 20 + window.scrollX;
                }

                // Ajustar si se sale por la izquierda
                if (left < 0) {
                    left = 10 + window.scrollX;
                }

                this.tooltipElement.style.left = `${left}px`;
                this.tooltipElement.style.top = `${top}px`;
            });
        },

        hideTooltip() {
            if (this.tooltipElement) {
                this.tooltipElement.style.display = 'none';
                this.tooltipElement.style.opacity = '0';
            }
        },

        // Cambio de idioma
        submitLanguageForm() {
            this.$refs.languageForm.submit();
        }
    }
}).mount('#app');