// editor.js - Editor de bloques con Quill
// Se integra con AdminTheme para usar una única instancia de Vue

console.log('editor.js cargado');

class Editor {
    constructor() {
        console.log('Editor constructor called');

        // Llamar a createExtendedApp de AdminTheme
        window.AdminThemeClass.createExtendedApp(
            // extensionMethods
            {
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
            },
            // extensionData
            {
                blockName: '',
                sortOrder: window.BLOCK_CONFIG?.nextSortOrder || 0,
                quill: null,
                isHtmlMode: false,
                htmlContent: ''
            }
        );

        // Inicializar Quill después de que Vue esté montado
        setTimeout(() => {
            if (window.vueInstance && window.vueInstance.initQuillEditor) {
                window.vueInstance.initQuillEditor();
            }
        }, 100);
    }
}

export default Editor;