$(document).ready(function () {
    // HTML Editor - Sistema de identificação visual de elementos
    class HtmlEditor {
        constructor() {
            this.overlay = null;
            this.currentElement = null;
            this.currentElementRect = null;
            this.editableElementsMap = []; // Mapa de elementos editáveis com suas coordenadas
            this.isEnabled = true;
            this.isModalActive = false;
            this.init();
        }

        init() {
            this.createOverlay();
            this.mapEditableElements(); // Mapear todos os elementos editáveis na página
            this.bindEvents();
        }

        createOverlay() {
            // Criar overlay visual para destacar elementos
            this.overlay = document.createElement('div');
            this.overlay.id = 'html-editor-overlay';
            this.overlay.style.cssText = `
                position: absolute;
                pointer-events: auto;
                background-color: rgba(67, 67, 67, 0.56);
                border: 2px solid rgba(128, 128, 128, 0.8);
                z-index: 999999;
                display: none;
                box-sizing: border-box;
                cursor: pointer;
                transition: all 0.1s ease;
            `;

            // Overlay clicável - sem botão separado
            this.overlay.title = '';

            // Adicionar evento de click ao overlay (usando event capturing para funcionar com pointer-events: none)
            document.addEventListener('click', (e) => {
                if (e.target.closest('#html-editor-overlay')) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (this.currentElement) {
                        this.handleElementClick(this.currentElement);
                    }
                }
            }, true); // true para event capturing

            document.body.appendChild(this.overlay);
        }

        handleElementClick(element) {
            const tagName = element.tagName.toLowerCase();

            // Atualizar informações do elemento
            const textField = document.getElementById('text-field');
            const imageField = document.getElementById('image-field');
            const textArea = document.getElementById('element-text');
            const srcInput = document.getElementById('element-src');

            if (tagName === 'img') {
                // Para imagens, mostrar campo de src
                textField.style.display = 'none';
                imageField.style.display = 'block';
                srcInput.value = element.src || '';
            } else if (this.isDirectlyTextEditable(element)) {
                // Para elementos de texto, mostrar textarea com HTML
                textField.style.display = 'block';
                imageField.style.display = 'none';
                textArea.value = (element.innerHTML || '').trim();
                textArea.value = textArea.value.replace(/<br\s*\/?>/gi, '\n');
            } else {
                return; // Não é editável diretamente
            }

            // Armazenar referência do elemento atual
            this.editingElement = element;

            // Mostrar modal
            this.modal.modal('show');
        }

        isDirectlyTextEditable(element) {
            const tagName = element.tagName.toLowerCase();

            // Elementos que contêm texto editável
            const textElements = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div', 'a', 'li', 'td', 'th', 'label', 'button'];

            if (!textElements.includes(tagName)) {
                return false;
            }

            // Verificar se tem filhos que são elementos HTML (não texto)
            const childElements = element.querySelectorAll('*');

            // Se não tem filhos HTML, é diretamente editável
            if (childElements.length === 0) {
                return true;
            }

            // Se tem filhos HTML mas o texto próprio é significativo, pode ser editável
            // Mas vamos ser mais rigorosos: só permitir se não há filhos que sejam blocos ou imagens
            const blockChildren = element.querySelectorAll('div, p, h1, h2, h3, h4, h5, h6, section, article, img, ul, ol, table, label');
            return blockChildren.length === 0 && element.textContent.trim().length > 0;
        }

        mapEditableElements() {
            this.editableElementsMap = [];

            // Encontrar todos os elementos que podem ser editáveis
            const allElements = document.querySelectorAll('*');

            for (let element of allElements) {
                if (this.isEditableElement(element) && this.shouldHighlight(element)) {
                    const rect = element.getBoundingClientRect();

                    // Só incluir elementos que têm área visível
                    if (rect.width > 0 && rect.height > 0) {
                        this.editableElementsMap.push({
                            element: element,
                            rect: {
                                left: rect.left,
                                top: rect.top,
                                right: rect.left + rect.width,
                                bottom: rect.top + rect.height,
                                width: rect.width,
                                height: rect.height
                            },
                            depth: this.getElementDepth(element) // Profundidade no DOM para ordenação
                        });
                    }
                }
            }

            // Ordenar por profundidade (elementos mais profundos primeiro) para priorizar filhos sobre pais
            this.editableElementsMap.sort((a, b) => b.depth - a.depth);
        }

        getElementDepth(element) {
            let depth = 0;
            let current = element;
            while (current.parentElement) {
                depth++;
                current = current.parentElement;
            }
            return depth;
        }

        bindEvents() {
            // Configurar eventos do modal
            this.modal = $('#html-editor-modal');
            this.modal.modal({
                closable: true,
                onShow: () => {
                    this.isModalActive = true;
                    this.lastProcessedElement = null;
                    this.hideOverlay();
                },
                onHide: () => {
                    this.isModalActive = false;
                },
                onApprove: () => {
                    this.saveElementChanges();
                }
            });

            // Evento para atualizar mapa de elementos quando a página rolar
            window.addEventListener('scroll', () => {
                if (this.isEnabled) {
                    this.updateElementsMap();
                }
            });

            // Variável para controlar o último elemento processado
            this.lastProcessedElement = null;

            // Eventos de mouse para detectar elementos
            document.addEventListener('mouseover', (e) => {
                if (!this.isEnabled || this.isModalActive) return;

                const element = this.getElementAtPosition(e.clientX, e.clientY);

                // Só processar se encontrou um elemento E ele é diferente do último processado
                if (element && element !== this.lastProcessedElement) {
                    this.lastProcessedElement = element;
                    if (this.shouldHighlight(element)) {
                        this.showOverlayForElement(element);
                    }
                } else if (!element && this.lastProcessedElement) {
                    // Se não encontrou elemento mas tinha um antes, limpar
                    this.lastProcessedElement = null;
                    this.hideOverlay();
                }
            });

            // Usar mousemove para verificar se mouse saiu da área do elemento atual
            document.addEventListener('mousemove', (e) => {
                if (!this.isEnabled) return;

                // Se o modal estiver ativo, garantir que o overlay esteja oculto
                if (this.isModalActive && this.currentElement) {
                    this.lastProcessedElement = null;
                    this.hideOverlay();
                    return; // Não processar mais nada enquanto modal estiver ativo
                }

                // Primeiro, verificar se ainda estamos sobre o elemento atual
                if (!this.isModalActive && this.currentElement && this.currentElementRect) {
                    // Recalcular coordenadas em tempo real para lidar com scroll
                    const rect = this.currentElement.getBoundingClientRect();
                    const currentRect = {
                        left: rect.left,
                        top: rect.top,
                        right: rect.left + rect.width,
                        bottom: rect.top + rect.height
                    };

                    // e.clientX e e.clientY já são relativos à viewport (assim como getBoundingClientRect)
                    const mouseX = e.clientX;
                    const mouseY = e.clientY;

                    // Verificar se mouse ainda está dentro da área do elemento
                    const isInsideElement = mouseX >= currentRect.left &&
                        mouseX <= currentRect.right &&
                        mouseY >= currentRect.top &&
                        mouseY <= currentRect.bottom;

                    if (!isInsideElement) {
                        this.lastProcessedElement = null;
                        this.hideOverlay();
                    }
                }

                // Se não há elemento atual ou mouse saiu, verificar se estamos sobre outro elemento editável
                if (!this.isModalActive && !this.currentElement) {
                    const element = this.getElementAtPosition(e.clientX, e.clientY);

                    if (element && element !== this.lastProcessedElement) {
                        this.lastProcessedElement = element;
                        if (this.shouldHighlight(element)) {
                            this.showOverlayForElement(element);
                        }
                    }
                }
            });
        }

        getElementAtPosition(mouseX, mouseY) {
            // Procurar no mapa de elementos editáveis qual está sob as coordenadas do mouse
            // Como o mapa está ordenado por profundidade (mais profundos primeiro), pegamos o primeiro que encontrar
            for (let item of this.editableElementsMap) {
                if (mouseX >= item.rect.left &&
                    mouseX <= item.rect.right &&
                    mouseY >= item.rect.top &&
                    mouseY <= item.rect.bottom) {
                    return item.element;
                }
            }
            return null;
        }

        isGenericContainer(element) {
            const tagName = element.tagName.toLowerCase();

            // Containers genéricos que geralmente não queremos editar diretamente
            const genericContainers = ['div', 'section', 'article', 'main', 'aside', 'header', 'footer', 'nav'];

            if (!genericContainers.includes(tagName)) return false;

            // Verificar se tem classes que indicam layout (não conteúdo)
            const layoutClasses = ['grid', 'flex', 'container', 'row', 'col', 'bg-', 'py-', 'px-', 'mx-', 'my-', 'mt-', 'mb-', 'ml-', 'mr-', 'pt-', 'pb-', 'pl-', 'pr-'];
            const hasLayoutClasses = layoutClasses.some(cls => element.className.includes(cls));

            // Se tem classes de layout ou não tem conteúdo significativo, é um container genérico
            return hasLayoutClasses || element.textContent.trim().length < 10;
        }

        isEditableElement(element) {
            // Verificar se é um elemento que deve ser destacado
            if (!this.shouldHighlight(element)) return false;

            // Verificar se é editável DIRETAMENTE (não apenas contém elementos editáveis)
            const tagName = element.tagName.toLowerCase();

            // Para imagens: sempre editável diretamente
            if (tagName === 'img') return true;

            // Para texto: só se for editável diretamente (sem filhos HTML significativos)
            return this.isDirectlyTextEditable(element);
        }

        shouldHighlight(element) {
            // Não destacar elementos do próprio editor
            if (element.id === 'html-editor-overlay') return false;
            if (element.closest('#html-editor-overlay')) return false;

            // Não destacar elementos do modal
            if (element.closest('.ui.modal')) return false;
            if (element.closest('#html-editor-modal')) return false;

            // Não destacar o dimmer/background do modal
            if (element.classList.contains('ui') && element.classList.contains('dimmer') && element.classList.contains('modals')) return false;

            // Destacar apenas elementos HTML significativos
            const tagName = element.tagName.toLowerCase();
            const skipTags = ['html', 'body', 'head', 'script', 'style', 'link', 'meta'];

            return !skipTags.includes(tagName);
        }

        showOverlayForElement(element) {
            // Só atualizar se o elemento realmente mudou
            if (!element || element === this.currentElement) return;

            this.currentElement = element;

            // Calcular posição e tamanho do elemento
            const rect = element.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            // Guardar as coordenadas relativas à viewport (getBoundingClientRect já retorna isso)
            this.currentElementRect = {
                left: rect.left,
                top: rect.top,
                right: rect.left + rect.width,
                bottom: rect.top + rect.height
            };

            // Posicionar overlay exatamente sobre o elemento (usando coordenadas absolutas para posicionamento)
            this.overlay.style.top = (rect.top + scrollTop) + 'px';
            this.overlay.style.left = (rect.left + scrollLeft) + 'px';
            this.overlay.style.width = rect.width + 'px';
            this.overlay.style.height = rect.height + 'px';
            this.overlay.style.display = 'block';

            // Adicionar informações do elemento
            this.overlay.setAttribute('data-element', element.tagName.toLowerCase());
            if (element.id) {
                this.overlay.setAttribute('data-id', element.id);
            }
            if (element.className) {
                this.overlay.setAttribute('data-class', element.className);
            }
        }

        updateElementsMap() {
            // Atualizar coordenadas de todos os elementos no mapa baseado na posição de scroll atual
            for (let item of this.editableElementsMap) {
                const rect = item.element.getBoundingClientRect();
                item.rect = {
                    left: rect.left,
                    top: rect.top,
                    right: rect.left + rect.width,
                    bottom: rect.top + rect.height,
                    width: rect.width,
                    height: rect.height
                };
            }
        }

        hideOverlay() {
            this.overlay.style.display = 'none';
            this.currentElement = null;
            this.currentElementRect = null;
        }

        saveElementChanges() {
            const element = this.editingElement;
            if (!element) return;

            const tagName = element.tagName.toLowerCase();

            if (tagName === 'img') {
                const srcInput = document.getElementById('element-src');
                element.src = srcInput.value;
            } else if (this.isDirectlyTextEditable(element)) {
                const textArea = document.getElementById('element-text');
                // Converter quebras de linha (\n) em tags <br> para preservar formatação
                const htmlContent = textArea.value.replace(/\n/g, '<br>');
                element.innerHTML = htmlContent;
            }

            // Limpar referência do elemento sendo editado
            this.editingElement = null;

            // Remapear elementos após mudanças (conteúdo pode ter afetado o mapeamento)
            this.mapEditableElements();
        }
    }

    // Inicializar o editor HTML
    window.htmlEditor = new HtmlEditor();

    window.htmlEditor.overlay.title = html_editor.overlay_title;
});