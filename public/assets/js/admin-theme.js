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

                    // --- System Update ---
                    isUpdating: false,
                    updateProgress: false,
                    updateStatus: 'Preparing update...',
                    progressPercent: 0,
                    updateResult: false,
                    updateSuccess: false,
                    updateMessage: '',
                    updateBackupFile: '',
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
                handleSlugInput(event) {
                    // Convertir automáticamente a formato slug válido
                    let value = event.target.value;

                    // Convertir a minúsculas
                    value = value.toLowerCase();

                    // Reemplazar espacios y caracteres especiales con guiones
                    value = value.replace(/\s+/g, '-')           // espacios a guiones
                                 .replace(/[áàäâã]/g, 'a')       // vocales con acentos
                                 .replace(/[éèëê]/g, 'e')
                                 .replace(/[íìïî]/g, 'i')
                                 .replace(/[óòöôõ]/g, 'o')
                                 .replace(/[úùüû]/g, 'u')
                                 .replace(/ñ/g, 'n')
                                 .replace(/[^a-z0-9-]/g, '-')    // cualquier otro caracter a guion
                                 .replace(/-+/g, '-')            // múltiples guiones a uno solo
                                 .replace(/^-|-$/g, '');         // eliminar guiones al inicio/fin

                    event.target.value = value;
                    this.staticUrl = value;
                },

                // ── System Update ──────────────────────────────────────────
                setProgress(percent, status) {
                    this.progressPercent = percent;
                    this.updateStatus   = status;
                },

                async performUpdate() {
                    if (!confirm('This will update your BaleroCMS installation. Please, create a backup before updating. Continue?')) {
                        return;
                    }

                    this.isUpdating     = true;
                    this.updateProgress = true;
                    this.updateResult   = false;

                    try {
                        // Step 0: Self-update UpdateService.php
                        this.setProgress(15, 'Updating core service...');
                        const selfUpdateResponse = await fetch('/admin/update/self-update', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });

                        const selfUpdateData = await selfUpdateResponse.json();
                        if (!selfUpdateData.success) {
                            throw new Error(selfUpdateData.message);
                        }

                        // Step 1-4: Perform the actual update (new request = new PHP load)
                        this.setProgress(40, 'Downloading update...');
                        const response = await fetch('/admin/update/perform', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });

                        const data = await response.json();
                        this.setProgress(100, 'Update complete!');

                        await new Promise(resolve => setTimeout(resolve, 500));

                        this.updateProgress   = false;
                        this.updateSuccess    = data.success;
                        this.updateMessage    = data.message;
                        this.updateBackupFile = data.backup_file || '';
                        this.updateResult     = true;

                        if (!data.success) {
                            this.isUpdating = false;
                        }

                    } catch (error) {
                        this.setProgress(0, 'Error occurred');

                        await new Promise(resolve => setTimeout(resolve, 300));

                        this.updateProgress   = false;
                        this.updateSuccess    = false;
                        this.updateMessage    = `An error occurred: ${error.message}`;
                        this.updateBackupFile = '';
                        this.updateResult     = true;
                        this.isUpdating       = false;
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

                        // Metadatos adicionales que se persisten junto a la imagen
                        formData.append('meta_original_name', file.name);
                        formData.append('meta_size',          file.size);
                        formData.append('meta_mime',          file.type);
                        formData.append('meta_uploaded_at',   new Date().toISOString());

                        // Detectar sección desde la URL actual (pages, blocks, etc.)
                        const pathParts = window.location.pathname.replace(/\/+$/, '').split('/');
                        const context   = pathParts.find(p => ['pages', 'blocks'].includes(p)) ?? 'unknown';
                        formData.append('meta_context', context);

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
