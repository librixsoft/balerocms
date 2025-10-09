/**
 * Balero CMS Notification Flash Messages JS Lib
 * @param key SESSION key
 */
// notification.js
function deleteMessage(key) {
    const formData = new FormData();
    formData.append('key', key);

    fetch('notification/', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            console.log(data.status, data.message);
        })
        .catch(err => console.error('Error deleting flash message:', err));
}

document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        alert.addEventListener('closed.bs.alert', () => {
            const key = alert.id.replace('alert-', '');
            deleteMessage(key);
        });
    });
});