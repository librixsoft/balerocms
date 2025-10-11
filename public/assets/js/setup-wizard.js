import NotificationSystem from './notification.js';

/**
 * Sistema de gestión del asistente de configuración
 */
class SetupWizard {
    constructor() {
        this.currentStep = 0;
        this.steps = [
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
        ];
        this.tooltipElement = null;
        this.notificationSystem = null;
        this.vueApp = null;
        this.init();
    }

    /**
     * Inicializa el sistema del asistente
     */
    init() {
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

        // Crear la aplicación Vue
        this.createVueApp();
    }

    /**
     * Crea y monta la aplicación Vue
     */
    createVueApp() {
        const { createApp } = Vue;

        this.vueApp = createApp({
            data: () => ({
                currentStep: this.currentStep,
                steps: this.steps,
                wizard: this
            }),

            methods: {
                // Gestión de alertas
                addAlert(type, message, key = null, dismissible = true) {
                    this.wizard.addAlert(type, message, key, dismissible);
                },

                dismissAlert(alertId) {
                    this.wizard.dismissAlert(alertId);
                },

                // Navegación entre pasos
                nextStep() {
                    if (this.currentStep < this.steps.length - 1) {
                        this.currentStep++;
                        this.wizard.currentStep = this.currentStep;
                    }
                },

                prevStep() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        this.wizard.currentStep = this.currentStep;
                    }
                },

                goToStep(stepIndex) {
                    if (stepIndex >= 0 && stepIndex < this.steps.length) {
                        this.currentStep = stepIndex;
                        this.wizard.currentStep = stepIndex;
                    }
                },

                // Gestión de tooltips
                showTooltip(event, message) {
                    this.wizard.showTooltip(event, message);
                },

                hideTooltip() {
                    this.wizard.hideTooltip();
                },

                // Cambio de idioma
                submitLanguageForm() {
                    const form = document.querySelector('.lang-form');
                    if (form) {
                        form.submit();
                    }
                }
            }
        }).mount('#app');
    }

    /**
     * Gestión de alertas - Añadir alerta
     */
    addAlert(type, message, key = null, dismissible = true) {
        this.notificationSystem.addAlert(type, message, key, dismissible);
    }

    /**
     * Gestión de alertas - Descartar alerta
     */
    dismissAlert(alertId) {
        this.notificationSystem.dismissAlert(alertId);
    }

    /**
     * Obtiene el paso actual
     */
    getCurrentStep() {
        return this.steps[this.currentStep];
    }

    /**
     * Obtiene todos los pasos
     */
    getSteps() {
        return this.steps;
    }

    /**
     * Muestra un tooltip
     */
    showTooltip(event, message) {
        if (!this.tooltipElement) return;

        const icon = event.target;
        const rect = icon.getBoundingClientRect();

        // Configurar contenido y mostrar
        this.tooltipElement.innerHTML = message;
        this.tooltipElement.style.display = 'block';
        this.tooltipElement.style.opacity = '1';

        // Esperar un frame para obtener dimensiones correctas
        requestAnimationFrame(() => {
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
    }

    /**
     * Oculta el tooltip
     */
    hideTooltip() {
        if (this.tooltipElement) {
            this.tooltipElement.style.display = 'none';
            this.tooltipElement.style.opacity = '0';
        }
    }
}

// Auto-instanciar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.setupWizard = new SetupWizard();
    });
} else {
    window.setupWizard = new SetupWizard();
}

export default SetupWizard;