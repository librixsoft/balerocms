// notification.js
export default {
    data() {
        return {
            alerts: []
        };
    },

    methods: {
        addAlert(type, message, key = null) {
            const id = key || `alert-${Date.now()}`;
            this.alerts.push({
                id,
                type,
                message,
                key
            });
        },

        dismissAlert(alertId, key = null) {
            const index = this.alerts.findIndex(a => a.id === alertId);
            if (index > -1) {
                this.alerts.splice(index, 1);
                if (key) {
                    this.deleteMessage(key);
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

        getAlertIcon(type) {
            const icons = {
                danger: 'fas fa-exclamation-triangle',
                warning: 'fas fa-exclamation-triangle',
                success: 'fas fa-check-circle',
                info: 'fas fa-info-circle'
            };
            return icons[type] || 'fas fa-info-circle';
        },

        getAlertClass(type) {
            return `alert alert-${type} alert-dismissible fade show`;
        }
    },

    template: `
        <transition-group name="alert" tag="div">
            <div 
                v-for="alert in alerts" 
                :key="alert.id"
                :class="getAlertClass(alert.type)"
                role="alert"
            >
                <i :class="getAlertIcon(alert.type)"></i>
                <span v-html="alert.message"></span>
                <button 
                    type="button" 
                    class="btn-close" 
                    @click="dismissAlert(alert.id, alert.key)"
                    aria-label="Close"
                ></button>
            </div>
        </transition-group>
    `
};