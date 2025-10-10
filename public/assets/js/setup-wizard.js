// setup-wizard.js
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
            alerts: [],
            tooltipElement: null
        };
    },

    mounted() {
        // Leer alertas desde el contenedor oculto del servidor
        this.loadServerAlerts();
    },

    methods: {
        // Cargar alertas desde el HTML del servidor
        loadServerAlerts() {
            const serverAlertsContainer = document.getElementById('server-alerts');
            if (!serverAlertsContainer) return;

            const alertElements = serverAlertsContainer.querySelectorAll('div[data-type]');
            alertElements.forEach(element => {
                const alert = {
                    id: element.dataset.id,
                    type: element.dataset.type,
                    icon: element.dataset.icon,
                    message: element.dataset.message,
                    key: element.dataset.key || null,
                    dismissible: element.dataset.dismissible !== 'false'
                };
                this.alerts.push(alert);
            });

            // Limpiar el contenedor después de leer
            serverAlertsContainer.remove();
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

        // Gestión de alertas
        addAlert(type, message, key = null, dismissible = true) {
            const id = key || `alert-${Date.now()}`;
            const icons = {
                danger: 'fas fa-exclamation-triangle',
                warning: 'fas fa-exclamation-triangle',
                success: 'fas fa-check-circle',
                info: 'fas fa-info-circle'
            };

            this.alerts.push({
                id,
                type,
                icon: icons[type] || 'fas fa-info-circle',
                message,
                key,
                dismissible
            });
        },

        dismissAlert(alertId) {
            const index = this.alerts.findIndex(a => a.id === alertId);
            if (index > -1) {
                const alert = this.alerts[index];
                this.alerts.splice(index, 1);

                // Si la alerta tiene una key, notificar al servidor
                if (alert.key) {
                    this.deleteMessage(alert.key);
                }
            }
        },

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
            }
        },

        // Gestión de tooltips
        showTooltip(event, message) {
            const icon = event.target;
            const rect = icon.getBoundingClientRect();

            if (!this.tooltipElement) {
                this.tooltipElement = document.createElement('div');
                this.tooltipElement.className = 'custom-tooltip';
                document.body.appendChild(this.tooltipElement);
            }

            this.tooltipElement.textContent = message;
            this.tooltipElement.style.display = 'block';
            this.tooltipElement.style.left = `${rect.left + window.scrollX}px`;
            this.tooltipElement.style.top = `${rect.bottom + window.scrollY + 5}px`;
        },

        hideTooltip() {
            if (this.tooltipElement) {
                this.tooltipElement.style.display = 'none';
            }
        },

        // Cambio de idioma
        submitLanguageForm() {
            this.$refs.languageForm.submit();
        }
    }
}).mount('#app');