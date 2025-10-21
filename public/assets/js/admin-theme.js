// admin-theme.js - Funcionalidad común del tema + Editor integrado
// Versión actualizada para Quill v2.0

import AdminNotificationSystem from './admin-notification.js';

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
                // Métodos del editor Quill v2.0
                initQuillEditor() {
                    console.log('Inicializando Quill v2.0');

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
                        placeholder: 'Escribe tu contenido aquí...',
                        modules: {
                            toolbar: {
                                container: toolbarOptions,
                                handlers: {
                                    'html': this.toggleHtmlMode.bind(this)
                                }
                            }
                        }
                    });

                    // --- Contenido inicial desde window ---
                    if (window.INITIAL_QUILL_CONTENT) {
                        this.quill.root.innerHTML = window.INITIAL_QUILL_CONTENT;
                    }

                    console.log('Quill inicializado correctamente');
                }
,

                // Método auxiliar para cargar contenido HTML de forma segura
                loadHtmlContent(html) {
                    if (!this.quill) {
                        console.error('Quill no está inicializado');
                        return;
                    }

                    // Método seguro: establecer innerHTML directamente
                    this.quill.root.innerHTML = html;

                    // Opcional: dar foco al editor después de cargar
                    this.quill.focus();

                    console.log('Contenido HTML cargado');
                },

                toggleHtmlMode() {
                    const editorContainer = document.getElementById('quill-editor');
                    const editor = this.quill.root;

                    if (!this.isHtmlMode) {
                        // --- Activar modo HTML ---
                        // Deshabilitar Quill temporalmente
                        this.quill.disable();

                        this.htmlContent = editor.innerHTML;

                        // Crear un <textarea> temporal para mostrar el HTML
                        const textarea = document.createElement('textarea');
                        textarea.id = 'html-editor';
                        textarea.value = this.htmlContent;
                        textarea.style.width = '100%';
                        textarea.style.height = editorContainer.offsetHeight + 'px';
                        textarea.style.fontFamily = 'monospace';
                        textarea.style.fontSize = '14px';
                        textarea.style.padding = '10px';
                        textarea.style.border = '1px solid #ccc';
                        textarea.style.borderRadius = '4px';
                        textarea.style.resize = 'vertical';

                        // Ocultar el editor visual y añadir el textarea
                        editorContainer.style.display = 'none';
                        editorContainer.parentNode.insertBefore(textarea, editorContainer.nextSibling);

                        this.isHtmlMode = true;
                        console.log('Modo HTML activado');
                    } else {
                        // --- Volver a modo visual ---
                        const textarea = document.getElementById('html-editor');
                        if (textarea) {
                            const htmlText = textarea.value;

                            // Remover el textarea primero
                            textarea.remove();

                            // Mostrar el editor
                            editorContainer.style.display = '';

                            // Usar setTimeout para asegurar que el DOM esté actualizado
                            setTimeout(() => {
                                // Establecer el contenido directamente en el root
                                this.quill.root.innerHTML = htmlText;

                                // Re-habilitar Quill
                                this.quill.enable();

                                // Dar foco al editor
                                this.quill.focus();
                            }, 0);
                        } else {
                            // Si no hay textarea, simplemente mostrar el editor y habilitarlo
                            editorContainer.style.display = '';
                            this.quill.enable();
                        }

                        this.isHtmlMode = false;
                        console.log('Modo visual activado');
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
                            this.notificationSystem.showNotification('Bloque creado exitosamente', 'success');
                            setTimeout(() => {
                                window.location.href = '/admin/blocks';
                            }, 1000);
                        } else {
                            this.notificationSystem.showNotification('Error al crear el bloque', 'error');
                            console.error('Error al crear el bloque');
                        }
                    } catch (err) {
                        this.notificationSystem.showNotification('Error de conexión', 'error');
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