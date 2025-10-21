// admin-theme.js - Funcionalidad común del tema + Editor integrado
// Versión unificada sin módulos separados

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
                    isDark: false,
                    mobileMenuOpen: false,
                    previewOpen: false,
                    userMenuOpen: false,
                    langOpen: false,
                    // Datos del editor
                    blockName: '',
                    sortOrder: window.BLOCK_CONFIG?.nextSortOrder || 0,
                    quill: null,
                    isHtmlMode: false,
                    htmlContent: ''
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
                    document.documentElement.setAttribute('data-bs-theme', this.isDark ? 'dark' : 'light');
                    if (this.isDark) {
                        document.body.classList.add('dark-theme');
                    } else {
                        document.body.classList.remove('dark-theme');
                    }
                },
                // Métodos para los dropdowns
                closeDropdowns(event) {
                    // Si el clic no es dentro de un dropdown, cerrar todos
                    if (!event.target.closest('.nav-dropdown')) {
                        this.previewOpen = false;
                        this.userMenuOpen = false;
                    }
                },
                // Métodos del editor Quill
                initQuillEditor() {
                    console.log('Inicializando Quill');

                    // Crear el icono SVG personalizado para el botón HTML
                    const icons = Quill.import('ui/icons');
                    icons['html'] = '<svg viewBox="0 0 18 18"><polyline class="ql-stroke" points="5 7 3 9 5 11"></polyline><polyline class="ql-stroke" points="13 7 15 9 13 11"></polyline><line class="ql-stroke" x1="10" y1="5" x2="8" y2="13"></line></svg>';

                    const toolbarOptions = [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image'],
                        ['html']
                    ];

                    this.quill = new Quill('#quill-editor', {
                        theme: 'snow',
                        modules: {
                            toolbar: {
                                container: toolbarOptions,
                                handlers: {
                                    'html': this.toggleHtmlMode.bind(this)
                                }
                            }
                        }
                    });

                    // 👇 Hola mundo avanzado como HTML
                    const holaMundoHtml = `
            <h1>Hola <em>mundo</em> </h1>
            <p>Este es un contenido de <strong>prueba avanzada</strong> con HTML incrustado directamente.</p>
    `;

                    // Insertar HTML usando clipboard.convert() → Delta
                    const delta = this.quill.clipboard.convert(holaMundoHtml, 'silent');
                    this.quill.setContents(delta);
                }
                ,
                toggleHtmlMode() {
                    const editorContainer = document.getElementById('quill-editor');
                    const editor = this.quill.root;

                    if (!this.isHtmlMode) {
                        // --- Activar modo HTML ---
                        this.htmlContent = editor.innerHTML;

                        // Crear un <textarea> temporal para mostrar el HTML
                        const textarea = document.createElement('textarea');
                        textarea.id = 'html-editor';
                        textarea.value = this.htmlContent;
                        textarea.style.width = '100%';
                        textarea.style.height = editorContainer.offsetHeight + 'px';
                        textarea.style.fontFamily = 'monospace';
                        textarea.style.fontSize = '14px';

                        // Ocultar el editor visual y añadir el textarea
                        editorContainer.style.display = 'none';
                        editorContainer.parentNode.insertBefore(textarea, editorContainer.nextSibling);

                        this.isHtmlMode = true;
                    } else {
                        // --- Volver a modo visual ---
                        const textarea = document.getElementById('html-editor');
                        if (textarea) {
                            const htmlText = textarea.value;
                            this.quill.root.innerHTML = htmlText;
                            textarea.remove();
                        }

                        editorContainer.style.display = '';
                        this.isHtmlMode = false;
                    }
                },
                async submitForm() {
                    // Asegurarse de estar en modo visual antes de enviar
                    if (this.isHtmlMode) {
                        this.toggleHtmlMode();
                    }

                    const content = this.quill.root.innerHTML;

                    const payload = new FormData();
                    payload.append('name', this.blockName);
                    payload.append('sort_order', this.sortOrder);
                    payload.append('content', content);

                    try {
                        const response = await fetch('/admin/blocks/new', {
                            method: 'POST',
                            body: payload
                        });

                        if (response.ok) {
                            window.location.href = '/admin/blocks';
                        } else {
                            console.error('Error al crear el bloque');
                        }
                    } catch (err) {
                        console.error('Fetch error:', err);
                    }
                }
            },
            computed: {
                navbarClasses() {
                    return {
                        'navbar-dark': this.isDark,
                        'navbar-light': !this.isDark
                    };
                }
            },
            mounted() {
                this.loadTheme();
                // Cerrar dropdowns al hacer clic fuera
                document.addEventListener('click', this.closeDropdowns);

                // Inicializar Quill si existe el elemento
                if (document.getElementById('quill-editor') && typeof Quill !== 'undefined') {
                    this.initQuillEditor();
                }
            },
            beforeUnmount() {
                document.removeEventListener('click', this.closeDropdowns);
            },
            watch: {
                isDark() {
                    this.applyThemeToBody();
                }
            }
        };

        // Auto-montar la aplicación Vue
        this.autoMount();
    }

    autoMount() {
        if (document.getElementById('app')) {
            const { createApp } = Vue;
            const instance = createApp(window.AdminTheme).mount('#app');
            window.vueInstance = instance;
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
            beforeUnmount: window.AdminTheme.beforeUnmount,
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

export default AdminTheme;