// blocks-editor.js - Editor de bloques con Quill
// Combina la funcionalidad del tema con el editor

const { createApp } = Vue;

createApp({
    data() {
        return {
            // Datos del tema (heredados de AdminTheme)
            ...window.AdminTheme.data(),

            // Datos del editor de bloques
            blockName: '',
            sortOrder: window.BLOCK_CONFIG?.nextSortOrder || 0,
            quill: null,
            isHtmlMode: false,
            htmlContent: ''
        }
    },
    mounted() {
        // Cargar tema primero
        window.AdminTheme.mounted.call(this);

        // Inicializar Quill editor
        this.initQuillEditor();
    },
    methods: {
        // Importar métodos del tema
        ...window.AdminTheme.methods,

        // ========== Métodos del editor Quill ==========
        initQuillEditor() {
            // Crear el icono SVG personalizado para el botón HTML
            const icons = Quill.import('ui/icons');
            icons['html'] = '<svg viewBox="0 0 18 18"><polyline class="ql-stroke" points="5 7 3 9 5 11"></polyline><polyline class="ql-stroke" points="13 7 15 9 13 11"></polyline><line class="ql-stroke" x1="10" y1="5" x2="8" y2="13"></line></svg>';

            // Agregar el botón HTML al toolbar
            const toolbarOptions = [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean'],
                ['html']
            ];

            this.quill = new Quill('#quill-editor', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            'html': this.toggleHtmlMode
                        }
                    }
                }
            });
        },

        toggleHtmlMode() {
            const editorContainer = document.getElementById('quill-editor');
            const editor = this.quill.root;

            if (!this.isHtmlMode) {
                // Cambiar a modo HTML
                this.htmlContent = editor.innerHTML;
                editor.innerText = this.htmlContent;
                editorContainer.classList.add('html-mode');
                editor.contentEditable = true;
                this.isHtmlMode = true;
            } else {
                // Volver a modo visual
                const htmlText = editor.innerText;
                editor.innerHTML = htmlText;
                editorContainer.classList.remove('html-mode');
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
    }
}).mount('#app');