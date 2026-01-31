$(document).ready(function () {
    /**
     * HTML Editor - Sistema de identificação e edição visual de elementos
     * 
     * Funcionalidades:
     * - Edição de texto simples (p, h1-h6, span, a, etc.)
     * - Edição de imagens (src)
     * - Edição de código HTML (SVG, input, iframe, etc.)
     * 
     * Tipos de edição:
     * - 'text': Elementos com texto editável diretamente
     * - 'image': Elementos <img> (edita src)
     * - 'code': Elementos que requerem edição de HTML (SVG, input, etc.)
     */
    class HtmlEditor {
        constructor() {
            // Estado do editor
            this.overlay = null;
            this.currentElement = null;
            this.editingElement = null;
            this.editingType = null; // 'text', 'image', 'code'
            this.isEnabled = true;
            this.isModalActive = false;

            // Cache para performance
            this.editableElementsCache = new WeakSet();
            this.lastMousePosition = { x: 0, y: 0 };

            // Configurações
            this.config = {
                // Tags que são editáveis diretamente como texto
                textEditableTags: [
                    'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'span', 'a', 'li', 'td', 'th',
                    'label', 'button', 'blockquote',
                    'summary', 'figcaption', 'caption',
                    'dt', 'dd', 'cite', 'q', 'abbr',
                    'article', 'div' // Serão editáveis se tiverem texto direto
                ],
                // Tags que devem ser completamente ignoradas (não atravessa)
                ignoredTags: ['html', 'body', 'head', 'script', 'style', 'link', 'meta', 'noscript'],
                // Tags que são editáveis como código HTML (outerHTML)
                codeEditableTags: ['svg', 'input', 'textarea', 'select', 'iframe', 'video', 'audio', 'canvas', 'object', 'embed', 'section', 'header', 'footer', 'nav', 'aside', 'main', 'figure', 'details', 'form', 'table', 'ul', 'ol', 'dl'],
                // Tags filhas de SVG que devem redirecionar para o SVG pai
                svgChildTags: ['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'g', 'text', 'tspan', 'defs', 'use', 'symbol', 'clippath', 'mask', 'pattern', 'lineargradient', 'radialgradient', 'stop', 'image', 'foreignobject'],
                // Tags que bloqueiam edição de texto (filhos estruturais)
                blockingChildTags: ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'section', 'article', 'img', 'ul', 'ol', 'table', 'form', 'details', 'blockquote'],
                // Seletores para elementos do editor (não editáveis)
                editorSelectors: ['#html-editor-overlay', '#html-editor-modal', '.ui.dimmer.modals']
            };

            this.init();
        }

        /**
         * Inicialização do editor
         */
        init() {
            this.createOverlay();
            this.bindEvents();
            this.cacheEditableElements();
        }

        /**
         * Cria o overlay visual que destaca elementos
         */
        createOverlay() {
            this.overlay = document.createElement('div');
            this.overlay.id = 'html-editor-overlay';
            this.overlay.style.cssText = `
                position: absolute;
                pointer-events: auto;
                background-color: rgba(59, 130, 246, 0.15);
                border: 2px solid rgba(59, 130, 246, 0.8);
                z-index: 999999;
                display: none;
                box-sizing: border-box;
                cursor: pointer;
                transition: all 0.15s ease-out;
                border-radius: 4px;
            `;

            // Click handler para abrir modal de edição
            this.overlay.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.currentElement) {
                    this.openEditModal(this.currentElement);
                }
            });

            document.body.appendChild(this.overlay);
        }

        /**
         * Cache de elementos editáveis para melhor performance
         */
        cacheEditableElements() {
            const allElements = document.querySelectorAll('*');
            for (const element of allElements) {
                if (this.isEditableElement(element)) {
                    this.editableElementsCache.add(element);
                }
            }
        }

        /**
         * Vincula todos os eventos necessários
         */
        bindEvents() {
            // Configurar modal do Fomantic UI
            this.modal = $('#html-editor-modal');
            this.modal.modal({
                closable: true,
                onShow: () => {
                    this.isModalActive = true;
                    this.hideOverlay();
                },
                onHide: () => {
                    this.isModalActive = false;
                    this.editingElement = null;
                },
                onApprove: () => {
                    this.saveChanges();
                }
            });

            // Throttle para mousemove (performance)
            let throttleTimer = null;
            const throttleDelay = 16; // ~60fps

            document.addEventListener('mousemove', (e) => {
                if (!this.isEnabled || this.isModalActive) return;

                // Salvar posição do mouse
                this.lastMousePosition = { x: e.clientX, y: e.clientY };

                // Throttle para evitar processamento excessivo
                if (throttleTimer) return;

                throttleTimer = setTimeout(() => {
                    throttleTimer = null;
                    this.handleMouseMove(e.clientX, e.clientY);
                }, throttleDelay);
            });

            // Mouseleave do documento
            document.addEventListener('mouseleave', () => {
                if (!this.isModalActive) {
                    this.hideOverlay();
                }
            });

            // Atualizar cache quando DOM mudar (MutationObserver)
            const observer = new MutationObserver(() => {
                this.cacheEditableElements();
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Atualizar overlay em scroll
            window.addEventListener('scroll', () => {
                if (this.currentElement && this.overlay.style.display !== 'none') {
                    this.updateOverlayPosition();
                }
            }, { passive: true });

            // Atualizar overlay em resize
            window.addEventListener('resize', () => {
                if (this.currentElement && this.overlay.style.display !== 'none') {
                    this.updateOverlayPosition();
                }
            }, { passive: true });
        }

        /**
         * Handler principal de movimento do mouse
         * Usa elementsFromPoint para pegar o elemento mais profundo na hierarquia
         */
        handleMouseMove(mouseX, mouseY) {
            // Usar elementsFromPoint que retorna elementos do mais profundo ao mais superficial
            const elementsAtPoint = document.elementsFromPoint(mouseX, mouseY);

            // Encontrar o primeiro elemento editável (mais profundo na hierarquia)
            const editableElement = this.findDeepestEditableElement(elementsAtPoint);

            if (editableElement) {
                if (editableElement !== this.currentElement) {
                    this.showOverlay(editableElement);
                }
            } else {
                this.hideOverlay();
            }
        }

        /**
         * Encontra o elemento editável mais profundo na lista de elementos
         * @param {Element[]} elements - Lista de elementos sob o cursor (ordenada do mais profundo ao mais superficial)
         * @returns {Element|null}
         */
        findDeepestEditableElement(elements) {
            for (const element of elements) {
                // Pular elementos do próprio editor
                if (this.isEditorElement(element)) continue;

                const tagName = element.tagName.toLowerCase();

                // Se for um filho de SVG, redirecionar para o SVG pai
                if (this.config.svgChildTags.includes(tagName)) {
                    const svgParent = element.closest('svg');
                    if (svgParent && !this.isEditorElement(svgParent)) {
                        return svgParent;
                    }
                    continue;
                }

                // Verificar se é editável (usando cache para performance)
                if (this.editableElementsCache.has(element) || this.isEditableElement(element)) {
                    return element;
                }
            }
            return null;
        }

        /**
         * Verifica se o elemento pertence ao próprio editor
         */
        isEditorElement(element) {
            if (element.id === 'html-editor-overlay') return true;
            if (element.closest('#html-editor-overlay')) return true;
            if (element.closest('#html-editor-modal')) return true;
            if (element.closest('.ui.dimmer.modals')) return true;
            return false;
        }

        /**
         * Verifica se um elemento é editável
         * @returns {Object|false} Retorna objeto com tipo de edição ou false
         */
        isEditableElement(element) {
            if (!element || !element.tagName) return false;

            const tagName = element.tagName.toLowerCase();

            // Ignorar tags não editáveis
            if (this.config.ignoredTags.includes(tagName)) return false;

            // Ignorar elementos do editor
            if (this.isEditorElement(element)) return false;

            // Ignorar filhos de SVG (serão redirecionados para o SVG pai)
            if (this.config.svgChildTags.includes(tagName)) return false;

            // SVG e outros elementos de código são editáveis como código
            if (this.config.codeEditableTags.includes(tagName)) return true;

            // Imagens são sempre editáveis
            if (tagName === 'img') return true;

            // Para outros elementos, verificar se contém texto editável diretamente
            return this.isDirectlyTextEditable(element);
        }

        /**
         * Determina o tipo de edição para um elemento
         * @param {Element} element
         * @returns {'text'|'image'|'code'}
         */
        getEditType(element) {
            if (!element || !element.tagName) return 'text';

            const tagName = element.tagName.toLowerCase();

            if (tagName === 'img') return 'image';
            if (this.config.codeEditableTags.includes(tagName)) return 'code';

            return 'text';
        }

        /**
         * Verifica se um elemento contém texto editável diretamente
         * (sem filhos estruturais que contenham seu próprio conteúdo)
         */
        isDirectlyTextEditable(element) {
            const tagName = element.tagName.toLowerCase();

            // Deve ser uma tag de texto válida
            if (!this.config.textEditableTags.includes(tagName)) return false;

            // Verificar se tem conteúdo de texto direto (não apenas em filhos)
            const hasDirectTextContent = this.hasDirectTextContent(element);
            if (!hasDirectTextContent) return false;

            // Verificar se tem filhos estruturais que bloqueiam edição direta
            // Exceto para elementos específicos como summary, span, etc.
            const simpleTextTags = ['span', 'a', 'label', 'button', 'summary', 'cite', 'q', 'abbr'];
            if (simpleTextTags.includes(tagName)) {
                // Para tags simples, só bloqueia se tiver divs/parágrafos dentro
                const hasBlockingChildren = element.querySelector('div, p, h1, h2, h3, h4, h5, h6');
                return !hasBlockingChildren;
            }

            // Para tags estruturais (article, div, etc.), verificar filhos bloqueantes
            const hasBlockingChildren = element.querySelector(
                this.config.blockingChildTags.join(', ')
            );

            return !hasBlockingChildren;
        }

        /**
         * Verifica se o elemento tem texto direto (não apenas em filhos)
         * @param {Element} element
         * @returns {boolean}
         */
        hasDirectTextContent(element) {
            // Iterar pelos nós filhos diretos
            for (const node of element.childNodes) {
                // Nó de texto com conteúdo significativo
                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.textContent.trim();
                    if (text.length > 0) {
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * Mostra o overlay sobre o elemento
         */
        showOverlay(element) {
            if (!element) return;

            this.currentElement = element;
            this.updateOverlayPosition();
            this.overlay.style.display = 'block';

            // Determinar tipo de edição
            const editType = this.getEditType(element);

            // Adicionar dados para debug/inspeção
            this.overlay.dataset.element = element.tagName.toLowerCase();
            this.overlay.dataset.editable = editType;

            // Mudar cor do overlay baseado no tipo
            if (editType === 'code') {
                this.overlay.style.backgroundColor = 'rgba(139, 92, 246, 0.15)';
                this.overlay.style.borderColor = 'rgba(139, 92, 246, 0.8)';
            } else if (editType === 'image') {
                this.overlay.style.backgroundColor = 'rgba(34, 197, 94, 0.15)';
                this.overlay.style.borderColor = 'rgba(34, 197, 94, 0.8)';
            } else {
                this.overlay.style.backgroundColor = 'rgba(59, 130, 246, 0.15)';
                this.overlay.style.borderColor = 'rgba(59, 130, 246, 0.8)';
            }
        }

        /**
         * Atualiza a posição do overlay baseado no elemento atual
         */
        updateOverlayPosition() {
            if (!this.currentElement) return;

            const rect = this.currentElement.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            // Pequeno padding visual
            const padding = 2;

            this.overlay.style.top = (rect.top + scrollTop - padding) + 'px';
            this.overlay.style.left = (rect.left + scrollLeft - padding) + 'px';
            this.overlay.style.width = (rect.width + padding * 2) + 'px';
            this.overlay.style.height = (rect.height + padding * 2) + 'px';
        }

        /**
         * Esconde o overlay
         */
        hideOverlay() {
            this.overlay.style.display = 'none';
            this.currentElement = null;
        }

        /**
         * Abre o modal de edição para o elemento
         */
        openEditModal(element) {
            if (!element) return;

            // Determinar tipo de edição
            this.editingType = this.getEditType(element);

            // Obter referências dos campos
            const textField = document.getElementById('text-field');
            const imageField = document.getElementById('image-field');
            const codeField = document.getElementById('code-field');
            const textArea = document.getElementById('element-text');
            const srcInput = document.getElementById('element-src');
            const codeArea = document.getElementById('element-code');

            if (!textField || !imageField) return;

            // Esconder todos os campos primeiro
            textField.style.display = 'none';
            imageField.style.display = 'none';
            if (codeField) codeField.style.display = 'none';

            // Configurar campos baseado no tipo de edição
            switch (this.editingType) {
                case 'image':
                    imageField.style.display = 'block';
                    if (srcInput) srcInput.value = element.src || '';

                    // ===== ImagePicker State Management =====
                    // Limpar dados anteriores do imagepicker
                    window._imagepickerData = null;

                    // Verificar se a imagem tem atributos data-imagepicker-*
                    const imagepickerUrl = element.getAttribute('data-imagepicker-url');
                    const imagepickerNome = element.getAttribute('data-imagepicker-nome');
                    const imagepickerTipo = element.getAttribute('data-imagepicker-tipo');

                    const previewWidget = document.querySelector('._html-editor-imagepick-preview');
                    const previewImage = document.querySelector('._html-editor-imagepick-image');
                    const previewNome = document.querySelector('._html-editor-imagepick-nome .content');
                    const previewTipo = document.querySelector('._html-editor-imagepick-tipo .content');

                    if (imagepickerUrl && previewWidget) {
                        // A imagem foi selecionada via imagepicker - mostrar preview
                        if (previewImage) previewImage.src = imagepickerUrl;
                        if (previewNome) previewNome.textContent = imagepickerNome || '';
                        if (previewTipo) previewTipo.textContent = imagepickerTipo || '';
                        previewWidget.style.display = 'block';
                    } else if (previewWidget) {
                        // Imagem sem dados do imagepicker - esconder preview
                        previewWidget.style.display = 'none';
                        if (previewImage) previewImage.src = '';
                        if (previewNome) previewNome.textContent = '';
                        if (previewTipo) previewTipo.textContent = '';
                    }
                    break;

                case 'code':
                    if (codeField) {
                        codeField.style.display = 'block';
                        // Formatar o outerHTML para melhor visualização
                        const formattedHtml = this.formatHtml(element.outerHTML);

                        // Usar CodeMirror se disponível
                        if (window.CodeMirrorHtmlEditor) {
                            window.CodeMirrorHtmlEditor.setValue(formattedHtml);
                            // Refresh necessário após mostrar o modal
                            setTimeout(() => {
                                window.CodeMirrorHtmlEditor.refresh();
                            }, 100);
                        } else if (codeArea) {
                            codeArea.value = formattedHtml;
                        }
                    } else {
                        // Fallback para campo de texto se code-field não existir
                        textField.style.display = 'block';
                        if (textArea) {
                            textArea.value = element.outerHTML;
                        }
                    }
                    break;

                default: // 'text'
                    textField.style.display = 'block';
                    if (textArea) {
                        // Converter <br> para quebras de linha no textarea
                        let content = (element.innerHTML || '').trim();
                        content = content.replace(/<br\s*\/?>/gi, '\n');
                        textArea.value = content;
                    }
                    break;
            }

            // Salvar referência do elemento sendo editado
            this.editingElement = element;

            // Mostrar modal
            this.modal.modal('show');
        }

        /**
         * Formata HTML para melhor visualização
         * Usa a função global cleanCodeString se disponível
         * @param {string} html
         * @returns {string}
         */
        formatHtml(html) {
            if (!html || typeof html !== 'string') return '';

            // Usar função global cleanCodeString se disponível (mais robusta)
            if (typeof window.cleanCodeString === 'function') {
                return window.cleanCodeString(html, 'html');
            }

            // Fallback simples caso cleanCodeString não esteja disponível
            return html.trim();
        }

        /**
         * Salva as alterações feitas no modal
         */
        saveChanges() {
            const element = this.editingElement;
            if (!element) return;

            switch (this.editingType) {
                case 'image':
                    const srcInput = document.getElementById('element-src');
                    if (srcInput && srcInput.value) {
                        element.src = srcInput.value;

                        // ===== ImagePicker Data Persistence =====
                        // Se há dados do imagepicker armazenados, salvar como atributos data-*
                        if (window._imagepickerData) {
                            element.setAttribute('data-imagepicker-url', window._imagepickerData.url || '');
                            element.setAttribute('data-imagepicker-nome', window._imagepickerData.nome || '');
                            element.setAttribute('data-imagepicker-tipo', window._imagepickerData.tipo || '');
                            // Limpar dados após salvar
                            window._imagepickerData = null;
                        }
                    }
                    break;

                case 'code':
                    // Obter valor do CodeMirror ou textarea fallback
                    let newHtml = '';
                    if (window.CodeMirrorHtmlEditor) {
                        newHtml = window.CodeMirrorHtmlEditor.getValue();
                    } else {
                        const codeArea = document.getElementById('element-code');
                        const textAreaFallback = document.getElementById('element-text');
                        newHtml = codeArea?.value || textAreaFallback?.value || '';
                    }

                    if (newHtml) {
                        try {
                            // Criar um elemento temporário para validar o HTML
                            const temp = document.createElement('div');
                            temp.innerHTML = newHtml.trim();

                            if (temp.firstElementChild) {
                                // Substituir o elemento original pelo novo
                                element.outerHTML = newHtml.trim();
                            }
                        } catch (e) {
                            console.error('Erro ao processar HTML:', e);
                        }
                    }
                    break;

                default: // 'text'
                    const textArea = document.getElementById('element-text');
                    if (textArea) {
                        // Converter quebras de linha para <br>
                        const htmlContent = textArea.value.replace(/\n/g, '<br>');
                        element.innerHTML = htmlContent;
                    }
                    break;
            }

            // Atualizar cache após alteração
            this.cacheEditableElements();

            // Limpar referências
            this.editingElement = null;
            this.editingType = null;
        }

        /**
         * Habilita o editor
         */
        enable() {
            this.isEnabled = true;
        }

        /**
         * Desabilita o editor
         */
        disable() {
            this.isEnabled = false;
            this.hideOverlay();
        }

        /**
         * Atualiza a configuração do editor
         * @param {Object} newConfig - Nova configuração parcial
         */
        updateConfig(newConfig) {
            this.config = { ...this.config, ...newConfig };
            this.cacheEditableElements();
        }
    }

    // Inicializar o editor
    window.htmlEditor = new HtmlEditor();

    // Configurar título do overlay se disponível
    if (typeof html_editor !== 'undefined' && html_editor.overlay_title) {
        window.htmlEditor.overlay.title = html_editor.overlay_title;
    }
});
