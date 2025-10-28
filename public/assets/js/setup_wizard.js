class SetupWizard {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.tooltipElement = null;
        this.vueApp = null;

        // Esperar a que BLOCK_CONFIG esté disponible
        this.waitForConfig();
    }

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

    loadConfig(config = {}) {
        const translations = window.WIZARD_TRANSLATIONS || {};
        this.steps = [
            {
                id: 'db-config',
                label: translations.dbconfig || 'Database Configuration',
                icon: 'fas fa-database'
            },
            {
                id: 'site-info',
                label: translations.siteinfo || 'Site Information',
                icon: 'fas fa-info-circle'
            },
            {
                id: 'admin-config',
                label: translations.adminconfig || 'Admin Configuration',
                icon: 'fas fa-user-shield'
            }
        ];
    }

    init() {
        // Crear el elemento tooltip
        this.tooltipElement = document.createElement('div');
        this.tooltipElement.className = 'custom-tooltip';
        document.body.appendChild(this.tooltipElement);

        // Crear la aplicación Vue principal
        this.createVueApp();
    }

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

                showTooltip(event, message) {
                    this.wizard.showTooltip(event, message);
                },

                hideTooltip() {
                    this.wizard.hideTooltip();
                },

            }
        }).mount('#app');
    }

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

    hideTooltip() {
        if (this.tooltipElement) {
            this.tooltipElement.style.display = 'none';
            this.tooltipElement.style.opacity = '0';
        }
    }

    getCurrentStep() {
        return this.steps[this.currentStep];
    }

    getSteps() {
        return this.steps;
    }
}

window.setupWizard = new SetupWizard();
console.log('Setup Wizard initialized');