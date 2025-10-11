// setup-wizard.js

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 * Sistema de gestión del asistente de configuración
 */
class SetupWizard {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.tooltipElement = null;
        this.vueApp = null;

        // Esperar a que BLOCK_CONFIG esté disponible
        this.waitForConfig();
    }

    /**
     * Espera a que la configuración esté lista
     */
    async waitForConfig() {
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
            this.loadConfig(window.BLOCK_CONFIG);
        } else {
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
                /**
                 * Navega al siguiente paso
                 */
                nextStep() {
                    if (this.currentStep < this.steps.length - 1) {
                        this.currentStep++;
                        this.wizard.currentStep = this.currentStep;
                    }
                },

                /**
                 * Navega al paso anterior
                 */
                prevStep() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        this.wizard.currentStep = this.currentStep;
                    }
                },

                /**
                 * Navega a un paso específico
                 */
                goToStep(stepIndex) {
                    if (stepIndex >= 0 && stepIndex < this.steps.length) {
                        this.currentStep = stepIndex;
                        this.wizard.currentStep = stepIndex;
                    }
                },

                /**
                 * Muestra un tooltip
                 */
                showTooltip(event, message) {
                    this.wizard.showTooltip(event, message);
                },

                /**
                 * Oculta el tooltip
                 */
                hideTooltip() {
                    this.wizard.hideTooltip();
                },

            }
        }).mount('#app');
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

/**
 * Auto-instanciar cuando el DOM esté listo si existe el contenedor #app
 */
function initSetupWizard() {
    const appContainer = document.querySelector('#app');
    if (appContainer) {
        window.setupWizard = new SetupWizard();
        console.log('Setup Wizard initialized');
        return window.setupWizard;
    }
    return null;
}

// Auto-inicialización
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSetupWizard);
} else {
    initSetupWizard();
}

// Export para uso como módulo
export { SetupWizard, initSetupWizard };
export default SetupWizard;