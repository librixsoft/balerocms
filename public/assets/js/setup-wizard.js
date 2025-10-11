// setup-wizard.js

import createNotificationSystem from './notification.js';

/**
 * Sistema de gestión del asistente de configuración
 */
class SetupWizard {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.tooltipElement = null;
        this.notificationApp = null;
        this.vueApp = null;

        // Esperar a que BLOCK_CONFIG esté disponible
        this.waitForConfig();
    }

    /**
     * Espera a que la configuración esté lista
     */
    async waitForConfig() {
        // Intentar obtener configuración de un elemento data attribute
        const configElement = document.querySelector('[data-wizard-config]');

        if (configElement) {
            try {
                const config = JSON.parse(configElement.dataset.wizardConfig);
                this.loadConfig(config);
            } catch (e) {
                console.error('Error parsing config:', e);
                this.loadConfig();
            }
        } else if (window.BLOCK_CONFIG) {
            // Fallback a window.BLOCK_CONFIG si existe
            this.loadConfig(window.BLOCK_CONFIG);
        } else {
            // Usar valores por defecto
            this.loadConfig();
        }

        this.init();
    }

    /**
     * Carga la configuración de los pasos
     */
    loadConfig(config = {}) {
        this.steps = [
            {
                id: 'db-config',
                label: config.step1_label || 'Database Config',
                icon: 'fas fa-database'
            },
            {
                id: 'site-info',
                label: config.step2_label || 'Site Info',
                icon: 'fas fa-info-circle'
            },
            {
                id: 'admin-config',
                label: config.step3_label || 'Admin Config',
                icon: 'fas fa-user-shield'
            }
        ];
    }

    /**
     * Inicializa el sistema del asistente
     */
    init() {
        // Inicializar sistema de notificaciones como app Vue independiente
        const notificationContainer = document.querySelector('#notifications');
        if (notificationContainer) {
            this.notificationApp = createNotificationSystem().mount('#notifications');

            // Cargar alertas del servidor si existe el endpoint
            this.notificationApp.loadServerAlerts();
        }

        // Crear el elemento tooltip
        this.tooltipElement = document.createElement('div');
        this.tooltipElement.className = 'custom-tooltip';
        document.body.appendChild(this.tooltipElement);

        // Crear la aplicación Vue principal
        this.createVueApp();
    }

    /**
     * Crea y monta la aplicación Vue principal
     */
    createVueApp() {
        const { createApp } = Vue;
        const appContainer = document.querySelector('#app');

        if (!appContainer) {
            console.error('Container #app not found');
            return;
        }

        this.vueApp = createApp({
            data: () => ({
                currentStep: this.currentStep,
                steps: this.steps,
                wizard: this
            }),

            methods: {
                // Gestión de alertas - delegadas al sistema de notificaciones
                addAlert(type, message, key = null, dismissible = true) {
                    if (this.wizard.notificationApp) {
                        return this.wizard.notificationApp.addAlert(type, message, key, dismissible);
                    }
                    console.warn('Notification system not initialized');
                },

                dismissAlert(alertId) {
                    if (this.wizard.notificationApp) {
                        this.wizard.notificationApp.dismissAlert(alertId);
                    }
                },

                clearAlerts() {
                    if (this.wizard.notificationApp) {
                        this.wizard.notificationApp.clearAll();
                    }
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
                },

                // Validación de formularios con notificaciones
                async validateAndSubmit(formId, endpoint) {
                    const form = document.getElementById(formId);
                    if (!form) {
                        console.error(`Form #${formId} not found`);
                        return;
                    }

                    const formData = new FormData(form);

                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.addAlert('success', result.message || 'Operation successful');
                            if (result.nextStep) {
                                setTimeout(() => this.nextStep(), 1000);
                            }
                        } else {
                            this.addAlert('danger', result.message || 'Operation failed');
                        }

                        return result;
                    } catch (error) {
                        this.addAlert('danger', 'Connection error with server');
                        console.error('Error:', error);
                    }
                }
            }
        }).mount('#app');
    }

    /**
     * API pública para añadir alertas
     */
    addAlert(type, message, key = null, dismissible = true) {
        if (this.notificationApp) {
            return this.notificationApp.addAlert(type, message, key, dismissible);
        }
    }

    /**
     * API pública para descartar alertas
     */
    dismissAlert(alertId) {
        if (this.notificationApp) {
            this.notificationApp.dismissAlert(alertId);
        }
    }

    /**
     * Muestra un tooltip
     */
    showTooltip(event, message) {
        if (!this.tooltipElement) return;

        const icon = event.target;
        const rect = icon.getBoundingClientRect();

        this.tooltipElement.innerHTML = message;
        this.tooltipElement.style.display = 'block';
        this.tooltipElement.style.opacity = '1';

        requestAnimationFrame(() => {
            const tooltipRect = this.tooltipElement.getBoundingClientRect();
            let left = rect.left + window.scrollX - 12;
            let top = rect.bottom + window.scrollY + 8;

            if (left + tooltipRect.width > window.innerWidth) {
                left = window.innerWidth - tooltipRect.width - 20 + window.scrollX;
            }

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