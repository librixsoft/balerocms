// notification.js
window.NotificationHelper = {
    dismissAlert(key, event) {
        const alert = event.target.closest('.alert');
        if (!alert) return;

        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();

        this.deleteMessage(key);
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

    init() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            alert.addEventListener('closed.bs.alert', () => {
                const key = alert.getAttribute('data-key');
                if (key) this.deleteMessage(key);
            });
        });
    }
};
