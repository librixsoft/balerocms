// admin-theme.js - Funcionalidad común del tema
// Este objeto se puede reutilizar en diferentes vistas

class AdminTheme {
    constructor() {
        window.AdminTheme = {
            data() {
                return {
                    isDark: false
                }
            },
            methods: {
                toggleTheme() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                },
                loadTheme() {
                    const savedTheme = localStorage.getItem('theme');
                    if (savedTheme === 'dark') {
                        this.isDark = true;
                    }
                }
            },
            computed: {
                themeClasses() {
                    return {
                        'dark-theme': this.isDark
                    };
                },
                navbarClasses() {
                    return {
                        'navbar-dark': this.isDark,
                        'bg-dark': this.isDark,
                        'navbar-light': !this.isDark,
                        'bg-light': !this.isDark
                    };
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
    }
}

new AdminTheme();