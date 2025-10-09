// admin-theme.js - Funcionalidad común del tema
// Este objeto se puede reutilizar en diferentes vistas

window.AdminTheme = {
    data() {
        return {
            isDark: false
        }
    },
    methods: {
        toggleTheme() {
            this.isDark = !this.isDark;
            if (this.isDark) {
                document.body.classList.add('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-light', 'bg-light');
                document.querySelector('.navbar').classList.add('navbar-dark', 'bg-dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-dark', 'bg-dark');
                document.querySelector('.navbar').classList.add('navbar-light', 'bg-light');
                localStorage.setItem('theme', 'light');
            }
        },
        loadTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                this.isDark = true;
                document.body.classList.add('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-light', 'bg-light');
                document.querySelector('.navbar').classList.add('navbar-dark', 'bg-dark');
            }
        }
    },
    mounted() {
        this.loadTheme();
    }
};

// Para vistas que SOLO necesitan el tema (como settings.html)
// Montar automáticamente si no hay otro código que lo necesite
if (document.getElementById('app') && typeof Quill === 'undefined') {
    const { createApp } = Vue;
    createApp(window.AdminTheme).mount('#app');
}