// setup-wizard.js

const { createApp } = Vue;

createApp({
    data() {
        const config = window.BLOCK_CONFIG || {};
        return {
            currentStep: 0,
            steps: [
                { id: 'dbconfig', label: config.step1_label || '', icon: 'fas fa-database' },
                { id: 'siteinfo', label: config.step2_label || '', icon: 'fas fa-globe' },
                { id: 'adminconfig', label: config.step3_label || '', icon: 'fas fa-user-shield' }
            ],
            formData: {
                dbhost: config.dbhost || '', // dbhost: "{txt_dbhost}"
            },
            tooltip: null
        };
    },
    mounted() {
        // Inicializa alertas ya existentes
        if (window.NotificationHelper) {
            window.NotificationHelper.init();
        }
    },
    methods: {
        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
            }
        },
        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },
        goToStep(index) {
            this.currentStep = index;
        },
        submitLanguageForm() {
            this.$refs.languageForm.submit();
        },
        handleSubmit(event) {

        },
        showTooltip(event, text) {
            if (this.tooltip) this.tooltip.dispose();
            this.tooltip = new bootstrap.Tooltip(event.target, {
                title: text,
                trigger: 'manual'
            });
            this.tooltip.show();
        },
        hideTooltip() {
            if (this.tooltip) {
                this.tooltip.hide();
                setTimeout(() => {
                    if (this.tooltip) {
                        this.tooltip.dispose();
                        this.tooltip = null;
                    }
                }, 200);
            }
        },
        dismissAlert(key, event) {
            // Llama al NotificationHelper
            if (window.NotificationHelper) {
                window.NotificationHelper.dismissAlert(key, event);
            }
        }
    },
    beforeUnmount() {
        if (this.tooltip) {
            this.tooltip.dispose();
        }
    }
}).mount('#app');
