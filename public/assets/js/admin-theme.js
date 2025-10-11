// admin-theme.js - Funcionalidad común del tema + módulos
// Carga directa de módulos propios

// Importar solo los módulos que necesitas
// Descomentar según disponibilidad en tu proyecto
import Editor from './editor.js';
import AdminNotificationSystem from './admin-notification.js';
// import Modal from './modal.js';
// import Table from './table.js';
// import Validator from './validator.js';
// import FileUpload from './file-upload.js';
// import Analytics from './analytics.js';

console.log('admin-theme.js cargado');

class AdminTheme {
    constructor() {
        // Instanciar el sistema de notificaciones primero
        this.notificationSystem = new AdminNotificationSystem();

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

        // Auto-montar si no hay módulos que lo requieran
        this.autoMount();
    }

    autoMount() {
        if (document.getElementById('app')) {
            // Si no hay módulos especiales, montar con AdminTheme solo
            const hasSpecialModules = typeof Quill !== 'undefined';

            if (!hasSpecialModules) {
                const { createApp } = Vue;
                const instance = createApp(window.AdminTheme).mount('#app');
                window.vueInstance = instance;
            }
        }
    }

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

        // Guardar la instancia globalmente
        window.vueInstance = instance;

        return instance;
    }
}

// Exponer la clase globalmente
window.AdminThemeClass = AdminTheme;

// Auto-instanciar AdminTheme
new AdminTheme();

// Instanciar módulos
if (typeof Quill !== 'undefined') {
    new Editor();
}
