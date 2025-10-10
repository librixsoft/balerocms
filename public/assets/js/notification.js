// notification.js

/**
 * Sistema de notificaciones independiente
 */
class NotificationSystem {
    constructor(options = {}) {
        this.alerts = [];
        this.container = options.container || null;
        this.autoInit = options.autoInit !== false;

        if (this.autoInit && this.container) {
            this.init();
        }
    }

    init() {
        if (typeof this.container === 'string') {
            this.container = document.querySelector(this.container);
        }

        if (this.container) {
            this.render();
        }
    }

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

        if (this.container) {
            this.render();
        }

        return alert;
    }

    dismissAlert(alertId) {
        const index = this.alerts.findIndex(a => a.id === alertId);
        if (index > -1) {
            const alert = this.alerts[index];
            this.alerts.splice(index, 1);

            if (alert.key) {
                this.deleteMessage(alert.key);
            }

            if (this.container) {
                this.render();
            }
        }
    }

    clearAll() {
        this.alerts = [];
        if (this.container) {
            this.render();
        }
    }

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
    }

    render() {
        if (!this.container) return;

        this.container.innerHTML = '';

        this.alerts.forEach(alert => {
            const alertElement = this.createAlertElement(alert);
            this.container.appendChild(alertElement);
        });
    }

    createAlertElement(alert) {
        const div = document.createElement('div');
        div.className = `alert alert-${alert.type} alert-dismissible fade show`;
        div.setAttribute('role', 'alert');
        div.dataset.alertId = alert.id;

        const icon = document.createElement('i');
        icon.className = alert.icon;

        const span = document.createElement('span');
        span.innerHTML = alert.message;

        div.appendChild(icon);
        div.appendChild(document.createTextNode(' '));
        div.appendChild(span);

        if (alert.dismissible) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn-close';
            button.setAttribute('aria-label', 'Close');
            button.addEventListener('click', () => {
                this.dismissAlert(alert.id);
            });
            div.appendChild(button);
        }

        return div;
    }

    loadServerAlerts(containerId = 'server-alerts') {
        const serverAlertsContainer = document.getElementById(containerId);
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

        serverAlertsContainer.remove();

        if (this.container) {
            this.render();
        }
    }
}

export default NotificationSystem;