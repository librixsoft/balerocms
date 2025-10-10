// admin-theme.js - Funcionalidad común del tema
// Este objeto se puede reutilizar en diferentes vistas

class AdminTheme {
    constructor() {
        // Definir el objeto Vue reutilizable
        window.AdminTheme = {
            data() {
                return {
                    isDark: false
                }
            },
            methods: {
                toggleTheme() {
                    this.isDark = !this.isDark;
                    this.applyThemeToBody();
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                },
                loadTheme() {
                    const savedTheme = localStorage.getItem('theme');
                    if (savedTheme === 'dark') {
                        this.isDark = true;
                    }
                    this.applyThemeToBody();
                },
                applyThemeToBody() {
                    // Manipular el body directamente (fuera de Vue)
                    if (this.isDark) {
                        document.body.classList.add('dark-theme');
                    } else {
                        document.body.classList.remove('dark-theme');
                    }
                }
            },
            computed: {
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
            },
            watch: {
                // Observar cambios en isDark
                isDark() {
                    this.applyThemeToBody();
                }
            }
        };

        // Auto-montar en vistas simples (como settings.html)
        if (document.getElementById('app') && typeof Quill === 'undefined') {
            const { createApp } = Vue;
            createApp(window.AdminTheme).mount('#app');
        }
    }
}

// Auto-instanciar
new AdminTheme();