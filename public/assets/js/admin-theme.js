// admin-theme.js - Funcionalidad común del tema + editor
// Proporciona una base reutilizable para todas las vistas admin

import Editor from './editor.js';

console.log('admin-theme.js cargado');

class AdminTheme {
    constructor() {
        // Definir el objeto Vue reutilizable base
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
                isDark() {
                    this.applyThemeToBody();
                }
            }
        };

        // Auto-montar solo si es una vista simple (sin Quill)
        this.autoMount();
    }

    autoMount() {
        if (document.getElementById('app')) {
            // Si no hay Quill, montar con AdminTheme solo
            if (typeof Quill === 'undefined') {
                const { createApp } = Vue;
                const instance = createApp(window.AdminTheme).mount('#app');
                window.vueInstance = instance;
            }
            // Si hay Quill, se montará desde Editor usando createExtendedApp
        }
    }

    /**
     * Crear aplicación Vue con funcionalidad extendida
     * @param {Object} extensionMethods - Métodos adicionales para la instancia Vue
     * @param {Object} extensionData - Datos adicionales para la instancia Vue
     */
    static createExtendedApp(extensionMethods = {}, extensionData = {}) {
        const { createApp } = Vue;

        const appConfig = {
            data() {
                return {
                    ...window.AdminTheme.data.call(this),
                    ...extensionData
                }
            },
            methods: {
                ...window.AdminTheme.methods,
                ...extensionMethods
            },
            computed: window.AdminTheme.computed,
            mounted: function() {
                window.AdminTheme.mounted.call(this);
            },
            watch: window.AdminTheme.watch
        };

        const app = createApp(appConfig);
        const instance = app.mount('#app');

        // Guardar la instancia globalmente para que Editor pueda accederla
        window.vueInstance = instance;

        return instance;
    }
}

// Exponer el método estático globalmente para que Editor pueda usarlo
window.AdminThemeClass = AdminTheme;

// Auto-instanciar
new AdminTheme();

// Si Quill está disponible, instanciar el editor DESPUÉS de que AdminTheme esté listo
if (typeof Quill !== 'undefined') {
    new Editor();
}