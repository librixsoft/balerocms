// admin-theme.js - Funcionalidad común del tema + Editor integrado (Summernote)

console.log('admin-theme.js cargado');

class AdminTheme {
    constructor() {

        // Definir el objeto Vue reutilizable base
        window.AdminTheme = {
            data() {
                return {
                    isDark: false,
                    mobileMenuOpen: false,
                    previewOpen: false,
                    userMenuOpen: false,
                    langOpen: false,
                    blockName: '',
                    virtualTitle: window.PAGE_DATA?.virtualTitle || '',
                    staticUrl: window.PAGE_DATA?.staticUrl || '',
                    visible: window.PAGE_DATA?.visible || '1',
                    sortOrder: window.BLOCK_CONFIG?.nextSortOrder || 0,
                }
            },
            methods: {
                // ... (tus métodos toggleTheme, loadTheme, applyThemeToBody, closeDropdowns, etc.) ...
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
                closeDropdowns(event) {
                    if (!event.target.closest('.nav-dropdown')) {
                        this.previewOpen = false;
                        this.userMenuOpen = false;
                        this.langOpen = false; // Agregué el langOpen
                    }
                },
            },
            computed: {
                // ... (tus computed properties) ...
                navbarClasses() {
                    return {
                        'navbar-dark': this.isDark,
                        'navbar-light': !this.isDark
                    };
                },
                visibleToggle: {
                    get() {
                        return this.visible === '1';
                    },
                    set(value) {
                        this.visible = value ? '1' : '0';
                    }
                }
            },
            mounted() {
                this.loadTheme();
                document.addEventListener('click', this.closeDropdowns);

            },
            beforeUnmount() {
                document.removeEventListener('click', this.closeDropdowns);
                // Destruir Summernote al desmontar la app Vue
                if (document.getElementById('summernote') && typeof $ !== 'undefined') {
                    // Destruimos la instancia creada por $(document).ready()
                    $('#summernote').summernote('destroy');
                }
            },
            watch: {
                isDark() {
                    this.applyThemeToBody();
                }
            }
        };

        this.autoMount();
    }

    // ... (El resto de la clase autoMount, createExtendedApp, etc., se mantiene igual) ...

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
                // Llamamos al mounted original (solo para tema y dropdowns)
                window.AdminTheme.mounted.call(this);


                // Opcional: Adjuntar el submit del formulario si es la página de edición/nueva
                const form = document.querySelector('form[action*="blocks/"]');
                if (form && document.getElementById('summernote')) {
                    form.addEventListener('submit', this.submitForm.bind(this));
                }
            },
            beforeUnmount: window.AdminTheme.beforeUnmount,
            watch: window.AdminTheme.watch
        };

        const app = createApp(appConfig);
        const instance = app.mount('#app');
        window.vueInstance = instance;
        return instance;
    }
}

window.AdminThemeClass = AdminTheme;
new AdminTheme();
export default AdminTheme;

if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
    $(document).ready(function() {
        if (document.getElementById('summernote')) {
            console.log('Inicializando Summernote con jQuery Ready (Punto Único)');
            $('#summernote').summernote({
                height: 200,
                focus: true,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline']],
                    ['font', ['strikethrough']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['codeview']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        if (files.length === 0) return;
                        const file = files[0];
                        const formData = new FormData();
                        formData.append('file', file);

                        fetch('/admin/uploader', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.url) {
                                    $('#summernote').summernote('insertImage', data.url);
                                } else {
                                    console.error('Error al subir la imagen:', data);
                                }
                            })
                            .catch(err => {
                                console.error('Error de conexión:', err);
                            });
                    }
                }
            });
        }
    });
}
