$(document).ready(function () {
    /**
     * HTML Editor - Editor visual de elementos (roda DENTRO do iframe de preview).
     *
     * req-034 (BATCH-034) — reformulação profunda:
     *  - Tags editáveis permissivas (qualquer tag não-ignorada): texto, imagem ou código.
     *  - Duplo overlay: hover (transitório) + seleção (persistente).
     *  - Barra de ferramentas flutuante: arrastar, duplicar, editar, deletar.
     *  - Editor rápido de classes Tailwind + breadcrumb de navegação do DOM.
     *  - Drag and Drop (DnD) com linha de placeholder.
     *  - Inclusão de novos elementos e widgets (modo de inserção).
     *  - Histórico Undo/Redo (config.undoLimit, padrão 30).
     *  - Wrappers virtuais atômicos para widgets (<!-- widgets#... -->).
     *
     * Comunicação com a janela pai (html-editor-visual-controls.js) via postMessage,
     * namespace de ações `c2f-he:*`.
     */
    class HtmlEditor {
        constructor() {
            // ===== Estado
            this.hoverOverlay = null;
            this.selectionOverlay = null;
            this.toolbar = null;
            this.breadcrumb = null;
            this.styler = null;
            this.placeholder = null;

            this.hoveredElement = null;   // elemento sob o mouse (hover)
            this.selectedElement = null;  // elemento selecionado (persistente)
            this.editingElement = null;   // elemento em edição no modal
            this.editingType = null;      // 'text' | 'image' | 'code' | 'widget'

            this.isEnabled = true;
            this.isModalActive = false;
            this.suppressClick = false;   // suprime o clique residual após um arraste

            // DnD
            this.dragging = false;
            this.dragElement = null;
            this.dropTarget = null;       // { element, position: 'before'|'after'|'inside' }

            // Inserção
            this.insertMode = false;
            this.insertPayload = null;    // { kind:'element'|'widget', ... }

            // Histórico
            this.undoStack = [];
            this.redoStack = [];
            this.lastMousePosition = { x: 0, y: 0 };

            // ===== Configurações
            this.config = {
                // Tags completamente ignoradas (não atravessa nem seleciona).
                ignoredTags: ['html', 'body', 'head', 'script', 'style', 'link', 'meta', 'noscript', 'title'],
                // Tags filhas de SVG que redirecionam para o SVG pai.
                svgChildTags: ['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'g', 'text', 'tspan', 'defs', 'use', 'symbol', 'clippath', 'mask', 'pattern', 'lineargradient', 'radialgradient', 'stop', 'image', 'foreignobject'],
                // Tags inline simples que NÃO bloqueiam edição de texto direto.
                inlineTextTags: ['strong', 'em', 'b', 'i', 'u', 'span', 'a', 'br', 'small', 'code', 'mark', 'sub', 'sup', 'q', 'cite', 'abbr', 'time', 'label', 'wbr', 's', 'del', 'ins'],
                // Containers vazios candidatos a receber inserção "dentro".
                containerTags: ['div', 'section', 'article', 'main', 'header', 'footer', 'nav', 'aside', 'ul', 'ol', 'figure', 'form'],
                // Limite do histórico Undo/Redo.
                undoLimit: 30
            };

            this.init();
        }

        // ===================================================================
        // Inicialização
        // ===================================================================
        init() {
            this.injectStyles();
            this.createOverlays();
            this.createToolbar();
            this.createPlaceholder();
            this.bindModal();
            this.bindEvents();
            this.bindMessageBus();
            this.convertWidgetCommentsToWrappers();
            // Estado inicial do histórico.
            this.undoStack = [this.extractUserHtml(false)];
            this.redoStack = [];
            this.notifyHistory();
        }

        injectStyles() {
            if (document.getElementById('html-editor-visual-styles')) return;
            const css = `
                #html-editor-hover-overlay,#html-editor-selection-overlay{
                    position:absolute;pointer-events:none;box-sizing:border-box;display:none;
                    z-index:999990;border-radius:3px;transition:top .05s,left .05s,width .05s,height .05s;}
                #html-editor-hover-overlay{border:1px dashed rgba(59,130,246,0.9);
                    background:rgba(59,130,246,0.08);z-index:999989;}
                #html-editor-selection-overlay{border:2px solid rgba(124,58,237,0.95);
                    background:rgba(124,58,237,0.10);z-index:999990;}
                #html-editor-floating-toolbar{position:absolute;display:none;z-index:999999;
                    background:#1f2937;border-radius:6px;box-shadow:0 2px 10px rgba(0,0,0,0.3);
                    padding:3px;gap:2px;align-items:center;}
                #html-editor-floating-toolbar .he-tb-btn{display:inline-flex;align-items:center;justify-content:center;
                    width:30px;height:30px;border:none;background:transparent;color:#fff;cursor:pointer;border-radius:4px;}
                #html-editor-floating-toolbar .he-tb-btn:hover{background:rgba(255,255,255,0.18);}
                #html-editor-floating-toolbar .he-tb-btn.he-tb-drag{cursor:move;}
                #html-editor-floating-toolbar .he-tb-btn.he-tb-del:hover{background:rgba(220,38,38,0.85);}
                #html-editor-selection-breadcrumb{position:absolute;display:none;z-index:999998;
                    background:#111827;color:#e5e7eb;font:11px/1.4 monospace;padding:2px 6px;border-radius:0 0 4px 4px;
                    max-width:90vw;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
                #html-editor-selection-breadcrumb .he-crumb{cursor:pointer;color:#93c5fd;}
                #html-editor-selection-breadcrumb .he-crumb:hover{color:#fff;text-decoration:underline;}
                #html-editor-selection-breadcrumb .he-crumb-sep{color:#6b7280;margin:0 3px;}
                #html-editor-tailwind-styler{position:absolute;display:none;z-index:999998;background:#fff;
                    border:1px solid #d1d5db;border-radius:6px;box-shadow:0 2px 10px rgba(0,0,0,0.2);
                    padding:6px;max-width:420px;}
                #html-editor-tailwind-styler .he-tw-tags{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;}
                #html-editor-tailwind-styler .he-tw-tag{display:inline-flex;align-items:center;gap:4px;
                    background:#eef2ff;color:#3730a3;border-radius:10px;padding:1px 6px;font:11px monospace;}
                #html-editor-tailwind-styler .he-tw-tag b{cursor:pointer;color:#9333ea;}
                #html-editor-tailwind-styler input{width:100%;border:1px solid #d1d5db;border-radius:4px;
                    padding:3px 6px;font:12px monospace;outline:none;}
                .conn2flow-dnd-placeholder{height:0;border-top:3px dashed #f59e0b;margin:0;padding:0;
                    pointer-events:none;position:relative;z-index:999985;box-shadow:0 0 4px rgba(245,158,11,0.6);}
                .conn2flow-widget-wrapper{position:relative;border:2px dashed #f59e0b;
                    background:rgba(245,158,11,0.06);border-radius:4px;padding:18px 4px 4px;margin:4px 0;}
                .conn2flow-widget-wrapper>.conn2flow-widget-label{position:absolute;top:0;left:0;
                    background:#f59e0b;color:#1f2937;font:10px/1.4 sans-serif;font-weight:bold;
                    padding:1px 6px;border-radius:4px 0 4px 0;}
                .conn2flow-widget-wrapper>.conn2flow-widget-inner{pointer-events:none;}
                html.he-inserting,html.he-inserting *{cursor:copy !important;}
                html.he-dragging,html.he-dragging *{cursor:move !important;}
            `;
            const style = document.createElement('style');
            style.id = 'html-editor-visual-styles';
            style.textContent = css;
            document.head.appendChild(style);

            // Datalist de classes Tailwind para autocomplete simples.
            if (!document.getElementById('html-editor-tw-classes')) {
                const dl = document.createElement('datalist');
                dl.id = 'html-editor-tw-classes';
                this.tailwindSuggestions().forEach((c) => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    dl.appendChild(opt);
                });
                document.body.appendChild(dl);
            }
        }

        createOverlays() {
            this.hoverOverlay = document.createElement('div');
            this.hoverOverlay.id = 'html-editor-hover-overlay';
            document.body.appendChild(this.hoverOverlay);

            this.selectionOverlay = document.createElement('div');
            this.selectionOverlay.id = 'html-editor-selection-overlay';
            document.body.appendChild(this.selectionOverlay);

            this.breadcrumb = document.createElement('div');
            this.breadcrumb.id = 'html-editor-selection-breadcrumb';
            document.body.appendChild(this.breadcrumb);
        }

        createToolbar() {
            const tb = document.createElement('div');
            tb.id = 'html-editor-floating-toolbar';
            tb.innerHTML = `
                <button class="he-tb-btn he-tb-drag" type="button" title="Arrastar / Mover">
                    <i class="arrows alternate icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-dup" type="button" title="Duplicar">
                    <i class="copy icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-edit" type="button" title="Editar">
                    <i class="pencil icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-del" type="button" title="Deletar">
                    <i class="trash icon" style="margin:0"></i></button>
            `;
            document.body.appendChild(tb);
            this.toolbar = tb;

            // Editor rápido de classes Tailwind (acoplado ao overlay de seleção).
            const styler = document.createElement('div');
            styler.id = 'html-editor-tailwind-styler';
            styler.innerHTML = `
                <div class="he-tw-tags"></div>
                <input type="text" list="html-editor-tw-classes" placeholder="Adicionar classes (espaço/Enter)..." />
            `;
            document.body.appendChild(styler);
            this.styler = styler;

            // ===== Ações da toolbar
            tb.querySelector('.he-tb-dup').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.duplicateSelected();
            });
            tb.querySelector('.he-tb-edit').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.editSelected();
            });
            tb.querySelector('.he-tb-del').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.deleteSelected();
            });
            // Drag handle inicia o DnD.
            tb.querySelector('.he-tb-drag').addEventListener('mousedown', (e) => {
                e.preventDefault(); e.stopPropagation(); this.startDrag(e);
            });

            // ===== Tailwind styler: input
            const input = styler.querySelector('input');
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); this.applyClassesFromInput(); }
            });
            input.addEventListener('blur', () => { this.applyClassesFromInput(); });
            // Remover classe ao clicar no "x".
            styler.querySelector('.he-tw-tags').addEventListener('click', (e) => {
                const x = e.target.closest('b[data-class]');
                if (x) { this.removeClass(x.getAttribute('data-class')); }
            });
        }

        createPlaceholder() {
            this.placeholder = document.createElement('div');
            this.placeholder.className = 'conn2flow-dnd-placeholder';
            this.placeholder.style.display = 'none';
        }

        // ===================================================================
        // Detecção de elementos
        // ===================================================================
        isEditorOwned(element) {
            if (!element || element.nodeType !== Node.ELEMENT_NODE) return false;
            if (element.id && (
                element.id === 'html-editor-hover-overlay' ||
                element.id === 'html-editor-selection-overlay' ||
                element.id === 'html-editor-floating-toolbar' ||
                element.id === 'html-editor-selection-breadcrumb' ||
                element.id === 'html-editor-tailwind-styler' ||
                element.id === 'html-editor-modal')) return true;
            if (typeof element.closest === 'function') {
                if (element.closest('#html-editor-floating-toolbar')) return true;
                if (element.closest('#html-editor-selection-breadcrumb')) return true;
                if (element.closest('#html-editor-tailwind-styler')) return true;
                if (element.closest('#html-editor-modal')) return true;
                if (element.closest('.html-editor-container')) return true;
                if (element.closest('.ui.dimmer.modals')) return true;
                if (element.classList && element.classList.contains('conn2flow-dnd-placeholder')) return true;
            }
            return false;
        }

        /**
         * Resolve o elemento editável "alvo" a partir de um nó qualquer:
         * - widgets são tratados como bloco atômico (retorna o wrapper).
         * - filhos de SVG redirecionam para o <svg> pai.
         */
        resolveEditable(element) {
            if (!element || element.nodeType !== Node.ELEMENT_NODE) return null;
            if (this.isEditorOwned(element)) return null;

            // Bloco atômico de widget.
            const wrapper = element.closest ? element.closest('.conn2flow-widget-wrapper') : null;
            if (wrapper) return wrapper;

            const tag = element.tagName.toLowerCase();
            if (this.config.ignoredTags.includes(tag)) return null;

            // Filho de SVG -> redireciona para o SVG.
            if (this.config.svgChildTags.includes(tag)) {
                const svg = element.closest('svg');
                return (svg && !this.isEditorOwned(svg)) ? svg : null;
            }
            return element;
        }

        findEditableFromPoint(x, y) {
            const list = document.elementsFromPoint(x, y);
            for (const el of list) {
                if (this.isEditorOwned(el)) continue;
                const resolved = this.resolveEditable(el);
                if (resolved) return resolved;
            }
            return null;
        }

        getEditType(element) {
            if (!element || !element.tagName) return 'text';
            if (element.classList && element.classList.contains('conn2flow-widget-wrapper')) return 'widget';
            const tag = element.tagName.toLowerCase();
            if (tag === 'img') return 'image';
            if (this.isDirectlyTextEditable(element)) return 'text';
            return 'code';
        }

        isDirectlyTextEditable(element) {
            const tag = element.tagName.toLowerCase();
            if (tag === 'img' || tag === 'svg') return false;
            if (!this.hasDirectTextContent(element)) return false;
            // Todos os filhos-elemento devem ser inline simples (sem estrutura bloqueante).
            for (const child of element.children) {
                const ct = child.tagName.toLowerCase();
                if (!this.config.inlineTextTags.includes(ct)) return false;
            }
            return true;
        }

        hasDirectTextContent(element) {
            for (const node of element.childNodes) {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0) return true;
            }
            return false;
        }

        // ===================================================================
        // Eventos globais
        // ===================================================================
        bindEvents() {
            let throttle = null;
            document.addEventListener('mousemove', (e) => {
                this.lastMousePosition = { x: e.clientX, y: e.clientY };
                if (!this.isEnabled || this.isModalActive) return;
                if (this.dragging) { this.onDragMove(e); return; }
                if (this.insertMode) { this.onInsertMove(e); return; }
                if (throttle) return;
                throttle = setTimeout(() => {
                    throttle = null;
                    this.onHoverMove(e.clientX, e.clientY);
                }, 16);
            });

            document.addEventListener('mouseleave', () => { this.hideHover(); });

            // Clique: seleção persistente / clique fora limpa / clique em modo inserção insere.
            document.addEventListener('click', (e) => {
                if (!this.isEnabled || this.isModalActive) return;
                // Suprimir o clique residual gerado logo após um arraste (DnD).
                if (this.suppressClick) { this.suppressClick = false; e.preventDefault(); e.stopPropagation(); return; }
                if (this.isEditorOwned(e.target)) return; // toolbar/breadcrumb/styler/modal tratam sozinhos
                if (this.insertMode) { this.onInsertClick(e); return; }

                const el = this.findEditableFromPoint(e.clientX, e.clientY);
                if (el) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.selectElement(el);
                } else {
                    this.clearSelection();
                }
            }, true);

            // Reposicionar overlays em scroll/resize.
            const reposition = () => {
                if (this.hoveredElement) this.positionOverlay(this.hoverOverlay, this.hoveredElement);
                if (this.selectedElement) this.updateSelectionUI();
            };
            window.addEventListener('scroll', reposition, { passive: true });
            window.addEventListener('resize', reposition, { passive: true });

            // Atalhos de teclado no iframe (quando o foco está no preview).
            document.addEventListener('keydown', (e) => {
                if (this.isModalActive) return;
                const key = (e.key || '').toLowerCase();
                if ((e.ctrlKey || e.metaKey) && key === 'z' && !e.shiftKey) {
                    e.preventDefault(); this.undo();
                } else if ((e.ctrlKey || e.metaKey) && (key === 'y' || (key === 'z' && e.shiftKey))) {
                    e.preventDefault(); this.redo();
                } else if (key === 'escape') {
                    if (this.insertMode) this.exitInsertMode();
                    else this.clearSelection();
                } else if ((key === 'delete' || key === 'backspace') && this.selectedElement && !this.isTypingTarget(e.target)) {
                    e.preventDefault(); this.deleteSelected();
                }
            });
        }

        isTypingTarget(t) {
            if (!t) return false;
            const tag = (t.tagName || '').toLowerCase();
            return tag === 'input' || tag === 'textarea' || t.isContentEditable;
        }

        bindMessageBus() {
            window.addEventListener('message', (e) => {
                let data;
                try { data = JSON.parse(e.data); } catch (err) { return; }
                if (!data || !data.action) return;
                switch (data.action) {
                    case 'c2f-he:undo': this.undo(); break;
                    case 'c2f-he:redo': this.redo(); break;
                    case 'c2f-he:insert-element':
                        this.enterInsertMode({ kind: 'element', elementType: data.elementType }); break;
                    case 'c2f-he:insert-widget':
                        this.enterInsertMode({
                            kind: 'widget', widgetModule: data.widgetModule,
                            widgetSlug: data.widgetSlug, widgetName: data.widgetName
                        }); break;
                    case 'c2f-he:cancel-insert': this.exitInsertMode(); break;
                }
            });
        }

        // ===================================================================
        // Hover
        // ===================================================================
        onHoverMove(x, y) {
            const el = this.findEditableFromPoint(x, y);
            if (!el) { this.hideHover(); return; }
            if (el === this.selectedElement) { this.hideHover(); return; }
            this.hoveredElement = el;
            this.positionOverlay(this.hoverOverlay, el);
            this.hoverOverlay.style.display = 'block';
        }

        hideHover() {
            this.hoveredElement = null;
            if (this.hoverOverlay) this.hoverOverlay.style.display = 'none';
        }

        positionOverlay(overlay, element) {
            const rect = element.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            overlay.style.top = (rect.top + scrollTop) + 'px';
            overlay.style.left = (rect.left + scrollLeft) + 'px';
            overlay.style.width = rect.width + 'px';
            overlay.style.height = rect.height + 'px';
        }

        // ===================================================================
        // Seleção persistente
        // ===================================================================
        selectElement(element) {
            if (!element) return;
            this.selectedElement = element;
            this.hideHover();
            this.updateSelectionUI();
            this.renderStyler(element);
        }

        clearSelection() {
            this.selectedElement = null;
            if (this.selectionOverlay) this.selectionOverlay.style.display = 'none';
            if (this.toolbar) this.toolbar.style.display = 'none';
            if (this.breadcrumb) this.breadcrumb.style.display = 'none';
            if (this.styler) this.styler.style.display = 'none';
        }

        updateSelectionUI() {
            const element = this.selectedElement;
            if (!element || !document.body.contains(element)) { this.clearSelection(); return; }

            this.positionOverlay(this.selectionOverlay, element);
            this.selectionOverlay.style.display = 'block';

            const rect = element.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            // Toolbar acoplada logo acima do topo do overlay de seleção.
            this.toolbar.style.display = 'flex';
            const tbTop = rect.top + scrollTop - this.toolbar.offsetHeight - 6;
            this.toolbar.style.top = (tbTop < scrollTop ? rect.bottom + scrollTop + 6 : tbTop) + 'px';
            this.toolbar.style.left = (rect.left + scrollLeft) + 'px';

            // Breadcrumb na borda inferior do overlay de seleção.
            this.renderBreadcrumb(element);
            this.breadcrumb.style.display = 'block';
            this.breadcrumb.style.top = (rect.bottom + scrollTop) + 'px';
            this.breadcrumb.style.left = (rect.left + scrollLeft) + 'px';

            // Tailwind styler logo abaixo do breadcrumb.
            if (this.styler.style.display === 'block') {
                this.styler.style.top = (rect.bottom + scrollTop + this.breadcrumb.offsetHeight + 2) + 'px';
                this.styler.style.left = (rect.left + scrollLeft) + 'px';
            }
        }

        // ===================================================================
        // Breadcrumb
        // ===================================================================
        renderBreadcrumb(element) {
            const path = [];
            let node = element;
            let guard = 0;
            while (node && node.nodeType === Node.ELEMENT_NODE && guard < 40) {
                const tag = node.tagName.toLowerCase();
                if (tag === 'body' || tag === 'html') break;
                if (this.isEditorOwned(node)) break;
                path.unshift(node);
                node = node.parentElement;
                guard++;
            }
            this.breadcrumb.innerHTML = '';
            path.forEach((el, idx) => {
                if (idx > 0) {
                    const sep = document.createElement('span');
                    sep.className = 'he-crumb-sep';
                    sep.textContent = '>';
                    this.breadcrumb.appendChild(sep);
                }
                const crumb = document.createElement('span');
                crumb.className = 'he-crumb';
                let label = el.tagName.toLowerCase();
                if (el.id) label += '#' + el.id;
                else if (el.classList && el.classList.length) label += '.' + el.classList[0];
                crumb.textContent = label;
                crumb.addEventListener('mouseenter', () => {
                    this.hoveredElement = el;
                    this.positionOverlay(this.hoverOverlay, el);
                    this.hoverOverlay.style.display = 'block';
                });
                crumb.addEventListener('mouseleave', () => { this.hideHover(); });
                crumb.addEventListener('click', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    this.selectElement(el);
                });
                this.breadcrumb.appendChild(crumb);
            });
        }

        // ===================================================================
        // Tailwind styler
        // ===================================================================
        renderStyler(element) {
            if (!element || (element.classList && element.classList.contains('conn2flow-widget-wrapper'))) {
                this.styler.style.display = 'none';
                return;
            }
            const tagsBox = this.styler.querySelector('.he-tw-tags');
            tagsBox.innerHTML = '';
            const classes = Array.from(element.classList || []);
            classes.forEach((cls) => {
                const tag = document.createElement('span');
                tag.className = 'he-tw-tag';
                tag.innerHTML = '<span></span> <b data-class="" title="Remover">&times;</b>';
                tag.querySelector('span').textContent = cls;
                tag.querySelector('b').setAttribute('data-class', cls);
                tagsBox.appendChild(tag);
            });
            this.styler.querySelector('input').value = '';
            this.styler.style.display = 'block';
            this.updateSelectionUI();
        }

        applyClassesFromInput() {
            const input = this.styler.querySelector('input');
            const value = (input.value || '').trim();
            if (!value || !this.selectedElement) { input.value = ''; return; }
            value.split(/\s+/).forEach((cls) => {
                if (cls) this.selectedElement.classList.add(cls);
            });
            input.value = '';
            this.renderStyler(this.selectedElement);
            this.afterDomMutation();
        }

        removeClass(cls) {
            if (!this.selectedElement) return;
            this.selectedElement.classList.remove(cls);
            this.renderStyler(this.selectedElement);
            this.afterDomMutation();
        }

        tailwindSuggestions() {
            const out = [];
            const scale = ['0', '1', '2', '3', '4', '5', '6', '8', '10', '12', '16', '20', '24'];
            ['p', 'px', 'py', 'pt', 'pb', 'pl', 'pr', 'm', 'mx', 'my', 'mt', 'mb', 'ml', 'mr', 'gap'].forEach((p) => {
                scale.forEach((s) => out.push(p + '-' + s));
            });
            ['w', 'h'].forEach((p) => { out.push(p + '-full', p + '-screen', p + '-auto', p + '-1/2', p + '-1/3', p + '-2/3'); });
            ['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl'].forEach((s) => out.push('text-' + s));
            const colors = ['gray', 'red', 'blue', 'green', 'yellow', 'indigo', 'purple', 'pink'];
            const shades = ['100', '200', '300', '400', '500', '600', '700', '800', '900'];
            colors.forEach((c) => shades.forEach((sh) => { out.push('bg-' + c + '-' + sh); out.push('text-' + c + '-' + sh); }));
            ['flex', 'inline-flex', 'grid', 'block', 'inline-block', 'hidden', 'flex-row', 'flex-col', 'flex-wrap',
                'items-center', 'items-start', 'items-end', 'justify-center', 'justify-between', 'justify-start', 'justify-end',
                'font-bold', 'font-semibold', 'font-medium', 'text-center', 'text-left', 'text-right',
                'relative', 'absolute', 'fixed', 'sticky', 'rounded', 'rounded-lg', 'rounded-full',
                'shadow', 'shadow-md', 'shadow-lg', 'border', 'border-2', 'overflow-hidden', 'cursor-pointer',
                'container', 'mx-auto', 'transition'].forEach((c) => out.push(c));
            return out;
        }

        // ===================================================================
        // Ações da toolbar: Duplicar / Editar / Deletar
        // ===================================================================
        duplicateSelected() {
            const el = this.selectedElement;
            if (!el || !el.parentNode) return;
            const clone = el.cloneNode(true);
            el.parentNode.insertBefore(clone, el.nextSibling);
            this.selectElement(clone);
            this.afterDomMutation();
        }

        deleteSelected() {
            const el = this.selectedElement;
            if (!el || !el.parentNode) return;
            if (!window.confirm('Deseja realmente excluir este elemento?')) return;
            el.parentNode.removeChild(el);
            this.clearSelection();
            this.afterDomMutation();
        }

        editSelected() {
            const el = this.selectedElement;
            if (!el) return;
            // Widgets: editam o slug do registro (não abrem o CodeMirror — req-034 §6.5).
            if (el.classList && el.classList.contains('conn2flow-widget-wrapper')) {
                this.editWidgetWrapper(el);
                return;
            }
            this.openEditModal(el);
        }

        editWidgetWrapper(wrapper) {
            const slugAtual = wrapper.getAttribute('data-widget-slug') || '';
            const novo = window.prompt('Slug do widget (registro do banco):', slugAtual);
            if (novo === null) return;
            const slug = novo.trim();
            const type = wrapper.getAttribute('data-widget-type') || '';
            wrapper.setAttribute('data-widget-slug', slug);
            wrapper.setAttribute('data-widget-signature', type + '->render({"grupo_slug": "' + slug + '"})');
            const label = wrapper.querySelector('.conn2flow-widget-label');
            if (label) label.textContent = 'Widget: ' + type + ' - ' + slug;
            this.updateSelectionUI();
            this.afterDomMutation();
        }

        // ===================================================================
        // Modal de edição (texto / imagem / código)
        // ===================================================================
        bindModal() {
            this.modal = $('#html-editor-modal');
            if (!this.modal.length) return;
            this.modal.modal({
                closable: true,
                onShow: () => { this.isModalActive = true; this.hideHover(); this.hideChrome(); },
                onHide: () => { this.isModalActive = false; this.restoreChrome(); },
                onApprove: () => { this.saveChanges(); }
            });
        }

        // Esconde overlays/toolbar/breadcrumb/styler (sem perder a seleção) — usado enquanto o
        // modal de edição do Fomantic está aberto, para não cobri-lo (z-index).
        hideChrome() {
            this.hideHover();
            if (this.selectionOverlay) this.selectionOverlay.style.display = 'none';
            if (this.toolbar) this.toolbar.style.display = 'none';
            if (this.breadcrumb) this.breadcrumb.style.display = 'none';
            if (this.styler) this.styler.style.display = 'none';
        }

        restoreChrome() {
            if (this.selectedElement && document.body.contains(this.selectedElement)) {
                this.updateSelectionUI();
                this.renderStyler(this.selectedElement);
            }
        }

        openEditModal(element) {
            if (!element) return;
            this.editingType = this.getEditType(element);

            const textField = document.getElementById('text-field');
            const imageField = document.getElementById('image-field');
            const codeField = document.getElementById('code-field');
            const textArea = document.getElementById('element-text');
            const srcInput = document.getElementById('element-src');
            const codeArea = document.getElementById('element-code');
            if (!textField || !imageField) return;

            textField.style.display = 'none';
            imageField.style.display = 'none';
            if (codeField) codeField.style.display = 'none';

            switch (this.editingType) {
                case 'image':
                    imageField.style.display = 'block';
                    if (srcInput) srcInput.value = element.getAttribute('src') || '';
                    window._imagepickerData = null;
                    this.syncImagepickPreview(element);
                    break;
                case 'code': {
                    if (codeField) {
                        codeField.style.display = 'block';
                        const formatted = this.formatHtml(element.outerHTML);
                        if (window.CodeMirrorHtmlEditor) {
                            window.CodeMirrorHtmlEditor.setValue(formatted);
                            setTimeout(() => { window.CodeMirrorHtmlEditor.refresh(); }, 100);
                        } else if (codeArea) {
                            codeArea.value = formatted;
                        }
                    } else {
                        textField.style.display = 'block';
                        if (textArea) textArea.value = element.outerHTML;
                    }
                    break;
                }
                default: {
                    textField.style.display = 'block';
                    if (textArea) {
                        let content = (element.innerHTML || '').trim().replace(/<br\s*\/?>/gi, '\n');
                        textArea.value = content;
                    }
                }
            }

            this.editingElement = element;
            this.modal.modal('show');
        }

        syncImagepickPreview(element) {
            const url = element.getAttribute('data-imagepicker-url');
            const nome = element.getAttribute('data-imagepicker-nome');
            const tipo = element.getAttribute('data-imagepicker-tipo');
            const previewWidget = document.querySelector('._html-editor-imagepick-preview');
            const previewImage = document.querySelector('._html-editor-imagepick-image');
            const previewNome = document.querySelector('._html-editor-imagepick-nome .content');
            const previewTipo = document.querySelector('._html-editor-imagepick-tipo .content');
            if (url && previewWidget) {
                if (previewImage) previewImage.src = url;
                if (previewNome) previewNome.textContent = nome || '';
                if (previewTipo) previewTipo.textContent = tipo || '';
                previewWidget.style.display = 'block';
            } else if (previewWidget) {
                previewWidget.style.display = 'none';
                if (previewImage) previewImage.src = '';
                if (previewNome) previewNome.textContent = '';
                if (previewTipo) previewTipo.textContent = '';
            }
        }

        formatHtml(html) {
            if (!html || typeof html !== 'string') return '';
            if (typeof window.cleanCodeString === 'function') return window.cleanCodeString(html, 'html');
            return html.trim();
        }

        saveChanges() {
            const element = this.editingElement;
            if (!element) return;

            switch (this.editingType) {
                case 'image': {
                    const srcInput = document.getElementById('element-src');
                    if (srcInput && srcInput.value) {
                        element.setAttribute('src', srcInput.value);
                        if (window._imagepickerData) {
                            element.setAttribute('data-imagepicker-url', window._imagepickerData.url || '');
                            element.setAttribute('data-imagepicker-nome', window._imagepickerData.nome || '');
                            element.setAttribute('data-imagepicker-tipo', window._imagepickerData.tipo || '');
                            window._imagepickerData = null;
                        }
                    }
                    break;
                }
                case 'code': {
                    let newHtml = '';
                    if (window.CodeMirrorHtmlEditor) {
                        newHtml = window.CodeMirrorHtmlEditor.getValue();
                    } else {
                        const codeArea = document.getElementById('element-code');
                        const textAreaFallback = document.getElementById('element-text');
                        newHtml = (codeArea && codeArea.value) || (textAreaFallback && textAreaFallback.value) || '';
                    }
                    if (newHtml) {
                        try {
                            const temp = document.createElement('div');
                            temp.innerHTML = newHtml.trim();
                            if (temp.firstElementChild) {
                                const novo = temp.firstElementChild;
                                element.parentNode.replaceChild(novo, element);
                                this.selectedElement = novo;
                            }
                        } catch (e) { console.error('Erro ao processar HTML:', e); }
                    }
                    break;
                }
                default: {
                    const textArea = document.getElementById('element-text');
                    if (textArea) element.innerHTML = textArea.value.replace(/\n/g, '<br>');
                }
            }

            this.editingElement = null;
            this.editingType = null;
            this.updateSelectionUI();
            this.afterDomMutation();
        }

        // ===================================================================
        // Drag and Drop (DnD) — req-034 §3
        // ===================================================================
        startDrag(e) {
            if (!this.selectedElement) return;
            this.dragging = true;
            this.dragElement = this.selectedElement;
            document.documentElement.classList.add('he-dragging');
            this.hideHover();
            this.toolbar.style.display = 'none';
            this.breadcrumb.style.display = 'none';
            this.styler.style.display = 'none';

            const onUp = (ev) => {
                document.removeEventListener('mouseup', onUp, true);
                this.endDrag(ev);
            };
            document.addEventListener('mouseup', onUp, true);
        }

        onDragMove(e) {
            const target = this.computeDropTarget(e.clientX, e.clientY);
            this.dropTarget = target;
            if (target) this.positionPlaceholder(target);
            else this.removePlaceholder();
        }

        computeDropTarget(x, y) {
            const list = document.elementsFromPoint(x, y);
            for (const el of list) {
                if (this.isEditorOwned(el)) continue;
                if (el === this.dragElement || (this.dragElement && this.dragElement.contains(el))) continue;
                const resolved = this.resolveEditable(el);
                if (!resolved || resolved === this.dragElement) continue;
                if (this.dragElement && this.dragElement.contains(resolved)) continue;

                const rect = resolved.getBoundingClientRect();
                const tag = resolved.tagName.toLowerCase();
                const isEmptyContainer = this.config.containerTags.includes(tag) && resolved.children.length === 0;
                if (isEmptyContainer) return { element: resolved, position: 'inside' };
                const before = (y - rect.top) < (rect.height / 2);
                return { element: resolved, position: before ? 'before' : 'after' };
            }
            return null;
        }

        positionPlaceholder(target) {
            const ph = this.placeholder;
            if (target.position === 'inside') {
                target.element.appendChild(ph);
            } else if (target.position === 'before') {
                target.element.parentNode.insertBefore(ph, target.element);
            } else {
                target.element.parentNode.insertBefore(ph, target.element.nextSibling);
            }
            ph.style.display = 'block';
        }

        removePlaceholder() {
            if (this.placeholder && this.placeholder.parentNode) {
                this.placeholder.parentNode.removeChild(this.placeholder);
            }
            if (this.placeholder) this.placeholder.style.display = 'none';
        }

        endDrag(e) {
            document.documentElement.classList.remove('he-dragging');
            const target = this.dropTarget;
            const el = this.dragElement;
            this.dragging = false;
            this.dragElement = null;
            this.dropTarget = null;

            if (el && target && this.placeholder.parentNode) {
                this.placeholder.parentNode.insertBefore(el, this.placeholder);
            }
            this.removePlaceholder();
            // Evitar que o clique residual do mouseup re-selecione outro elemento.
            this.suppressClick = true;

            if (el) { this.selectElement(el); this.afterDomMutation(); }
        }

        // ===================================================================
        // Modo de inserção (novos elementos / widgets) — req-034 §4
        // ===================================================================
        enterInsertMode(payload) {
            if (!payload) return;
            this.clearSelection();
            this.hideHover();
            this.insertMode = true;
            this.insertPayload = payload;
            document.documentElement.classList.add('he-inserting');
        }

        exitInsertMode() {
            this.insertMode = false;
            this.insertPayload = null;
            this.removePlaceholder();
            document.documentElement.classList.remove('he-inserting');
        }

        onInsertMove(e) {
            const target = this.computeInsertTarget(e.clientX, e.clientY);
            this.dropTarget = target;
            if (target) this.positionPlaceholder(target);
            else this.removePlaceholder();
        }

        computeInsertTarget(x, y) {
            const list = document.elementsFromPoint(x, y);
            for (const el of list) {
                if (this.isEditorOwned(el)) continue;
                const resolved = this.resolveEditable(el);
                if (!resolved) continue;
                const rect = resolved.getBoundingClientRect();
                const tag = resolved.tagName.toLowerCase();
                const isEmptyContainer = this.config.containerTags.includes(tag) && resolved.children.length === 0;
                if (isEmptyContainer) return { element: resolved, position: 'inside' };
                const before = (y - rect.top) < (rect.height / 2);
                return { element: resolved, position: before ? 'before' : 'after' };
            }
            // Sem alvo: inserir no fim do body (conteúdo do usuário).
            return null;
        }

        onInsertClick(e) {
            e.preventDefault();
            e.stopPropagation();
            const payload = this.insertPayload;
            const target = this.dropTarget;
            const node = (payload.kind === 'widget')
                ? this.buildWidgetWrapper(payload)
                : this.buildElement(payload.elementType);
            if (!node) { this.exitInsertMode(); return; }

            if (target && this.placeholder.parentNode) {
                this.placeholder.parentNode.insertBefore(node, this.placeholder);
            } else {
                // fallback (clique sem alvo): inserir antes da UI do editor no body.
                const ref = document.getElementById('html-editor-modal') ||
                    document.getElementById('html-editor-hover-overlay');
                document.body.insertBefore(node, ref || null);
            }

            this.exitInsertMode();
            this.selectElement(node);
            this.afterDomMutation();

            // Inserção de imagem abre o ImagePicker imediatamente.
            if (payload.kind === 'element' && payload.elementType === 'img') {
                this.openEditModal(node);
            }
        }

        buildElement(type) {
            const t = (type || 'p').toLowerCase();
            let el;
            switch (t) {
                case 'h1': el = document.createElement('h1'); el.textContent = 'Novo título'; break;
                case 'h2': el = document.createElement('h2'); el.textContent = 'Novo título'; break;
                case 'h3': el = document.createElement('h3'); el.textContent = 'Novo título'; break;
                case 'img':
                    el = document.createElement('img');
                    el.setAttribute('src', (typeof html_editor !== 'undefined' && html_editor.raiz ? html_editor.raiz : '') + 'images/imagem-padrao.png');
                    el.setAttribute('alt', '');
                    break;
                case 'a': el = document.createElement('a'); el.setAttribute('href', '#'); el.textContent = 'Novo link'; break;
                case 'button': el = document.createElement('button'); el.setAttribute('type', 'button'); el.textContent = 'Novo botão'; break;
                case 'div': el = document.createElement('div'); el.textContent = 'Novo bloco'; break;
                case 'section': el = document.createElement('section'); el.textContent = 'Nova seção'; break;
                default: el = document.createElement('p'); el.textContent = 'Novo parágrafo';
            }
            return el;
        }

        buildWidgetWrapper(payload) {
            const type = payload.widgetModule;
            const slug = payload.widgetSlug || '';
            const signature = type + '->render({"grupo_slug": "' + slug + '"})';
            return this.createWrapperEl({
                type: type,
                slug: slug,
                name: payload.widgetName || slug,
                signature: signature,
                innerHtml: ''
            });
        }

        // ===================================================================
        // Wrappers virtuais de widget — req-034 §6.5
        // ===================================================================
        createWrapperEl(opts) {
            const wrapper = document.createElement('div');
            wrapper.className = 'conn2flow-widget-wrapper';
            wrapper.setAttribute('data-widget-type', opts.type);
            wrapper.setAttribute('data-widget-slug', opts.slug);
            wrapper.setAttribute('data-widget-signature', opts.signature);

            const label = document.createElement('div');
            label.className = 'conn2flow-widget-label';
            label.textContent = 'Widget: ' + opts.type + ' - ' + (opts.slug || '(novo)');
            wrapper.appendChild(label);

            const inner = document.createElement('div');
            inner.className = 'conn2flow-widget-inner';
            inner.innerHTML = opts.innerHtml || '';
            wrapper.appendChild(inner);

            return wrapper;
        }

        /**
         * Converte comentários de widget (<!-- widgets#X->render({...}) < --> ... > -->)
         * em divs .conn2flow-widget-wrapper. Operação cirúrgica via varredura de nós COMMENT.
         */
        convertWidgetCommentsToWrappers() {
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT, null);
            const comments = [];
            let n;
            while ((n = walker.nextNode())) comments.push(n);

            const openRe = /^\s*widgets#(.+?)\s*<\s*$/i;
            const closeRe = /^\s*widgets#\s*(.+?)\s*>\s*$/i;

            for (let i = 0; i < comments.length; i++) {
                const c = comments[i];
                if (!c.parentNode) continue;
                const mo = c.data.match(openRe);
                if (!mo) continue;
                const signature = mo[1].trim();

                // Procurar o fechamento correspondente.
                let close = null;
                for (let j = i + 1; j < comments.length; j++) {
                    const mc = comments[j].data.match(closeRe);
                    if (mc && mc[1].trim() === signature) { close = comments[j]; break; }
                }
                if (!close || close.parentNode !== c.parentNode) continue;

                const parsed = this.parseWidgetSignature(signature);
                const wrapper = this.createWrapperEl({
                    type: parsed.type, slug: parsed.slug, name: parsed.slug,
                    signature: signature, innerHtml: ''
                });
                const inner = wrapper.querySelector('.conn2flow-widget-inner');

                // Mover os nós entre os comentários para dentro do wrapper.
                let node = c.nextSibling;
                while (node && node !== close) {
                    const next = node.nextSibling;
                    inner.appendChild(node);
                    node = next;
                }
                c.parentNode.insertBefore(wrapper, c);
                c.parentNode.removeChild(c);
                if (close.parentNode) close.parentNode.removeChild(close);
            }
        }

        parseWidgetSignature(signature) {
            const m = signature.match(/^(.+?)->(\w+)\((.*)\)$/);
            let type = signature, slug = '';
            if (m) {
                type = m[1].trim();
                try {
                    const params = JSON.parse(m[3]);
                    slug = params.grupo_slug || '';
                } catch (e) { /* params não-JSON: mantém slug vazio */ }
            }
            return { type: type, slug: slug };
        }

        // ===================================================================
        // Histórico Undo / Redo — req-034 §6.1
        // ===================================================================
        afterDomMutation() {
            this.updateSelectionUI();
            this.pushUndo();
            // Notificar o pai para re-sincronizar o CodeMirror, se aplicável.
            try {
                window.parent.postMessage(JSON.stringify({ action: 'c2f-he:dom-changed' }), '*');
            } catch (e) { /* noop */ }
        }

        pushUndo() {
            const snapshot = this.extractUserHtml(false);
            if (this.undoStack.length && this.undoStack[this.undoStack.length - 1] === snapshot) return;
            this.undoStack.push(snapshot);
            if (this.undoStack.length > this.config.undoLimit + 1) this.undoStack.shift();
            this.redoStack = [];
            this.notifyHistory();
        }

        undo() {
            if (this.undoStack.length <= 1) return;
            const current = this.undoStack.pop();
            this.redoStack.push(current);
            const prev = this.undoStack[this.undoStack.length - 1];
            this.applyState(prev);
            this.notifyHistory();
        }

        redo() {
            if (!this.redoStack.length) return;
            const next = this.redoStack.pop();
            this.undoStack.push(next);
            this.applyState(next);
            this.notifyHistory();
        }

        notifyHistory() {
            try {
                window.parent.postMessage(JSON.stringify({
                    action: 'c2f-he:history',
                    canUndo: this.undoStack.length > 1,
                    canRedo: this.redoStack.length > 0
                }), '*');
            } catch (e) { /* noop */ }
        }

        applyState(html) {
            this.clearSelection();
            this.hideHover();
            this.removePlaceholder();
            // Remover o conteúdo do usuário atual (preservando UI e container do editor).
            this.getUserContentNodes().forEach((node) => node.parentNode && node.parentNode.removeChild(node));
            // Inserir o estado no topo do body (antes da UI/container).
            const tpl = document.createElement('template');
            tpl.innerHTML = html;
            const ref = document.body.firstChild;
            document.body.insertBefore(tpl.content, ref);
            try {
                window.parent.postMessage(JSON.stringify({ action: 'c2f-he:dom-changed' }), '*');
            } catch (e) { /* noop */ }
        }

        // ===================================================================
        // Extração de HTML (snapshots e save)
        // ===================================================================
        isUserContentNode(node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                if (this.isEditorOwned(node)) return false;
                if (node.id && node.id.indexOf('html-editor-') === 0) return false;
                if (node.classList && (node.classList.contains('html-editor-container') ||
                    node.classList.contains('conn2flow-dnd-placeholder'))) return false;
                // Dimmer/modais que o Fomantic injeta no body ao abrir o modal de edição.
                if (node.matches && node.matches('.ui.dimmer.modals')) return false;
                const tag = node.tagName.toLowerCase();
                if (tag === 'datalist' && node.id === 'html-editor-tw-classes') return false;
                if (tag === 'script' || tag === 'style') return false;
                return true;
            }
            // Comentários e textos: conteúdo do usuário.
            return node.nodeType === Node.COMMENT_NODE || node.nodeType === Node.TEXT_NODE;
        }

        getUserContentNodes() {
            return Array.from(document.body.childNodes).filter((n) => this.isUserContentNode(n));
        }

        /**
         * Extrai o HTML do conteúdo do usuário (sem UI do editor).
         * @param {boolean} widgetsToComments  reconverte wrappers virtuais em comentários.
         */
        extractUserHtml(widgetsToComments) {
            const container = document.createElement('div');
            this.getUserContentNodes().forEach((n) => container.appendChild(n.cloneNode(true)));

            // Limpar quaisquer resíduos de UI no clone.
            container.querySelectorAll('#html-editor-floating-toolbar,#html-editor-hover-overlay,' +
                '#html-editor-selection-overlay,#html-editor-selection-breadcrumb,#html-editor-tailwind-styler,' +
                '#html-editor-modal,.conn2flow-dnd-placeholder,.html-editor-container,.ui.dimmer.modals')
                .forEach((el) => el.remove());

            if (widgetsToComments) {
                container.querySelectorAll('.conn2flow-widget-wrapper').forEach((wrapper) => {
                    const signature = wrapper.getAttribute('data-widget-signature') ||
                        ((wrapper.getAttribute('data-widget-type') || '') +
                            '->render({"grupo_slug": "' + (wrapper.getAttribute('data-widget-slug') || '') + '"})');
                    const inner = wrapper.querySelector('.conn2flow-widget-inner');
                    const innerHtml = inner ? inner.innerHTML : '';
                    const open = document.createComment(' widgets#' + signature + ' < ');
                    const close = document.createComment(' widgets#' + signature + ' > ');
                    const frag = document.createDocumentFragment();
                    frag.appendChild(open);
                    const tmp = document.createElement('div');
                    tmp.innerHTML = innerHtml;
                    while (tmp.firstChild) frag.appendChild(tmp.firstChild);
                    frag.appendChild(close);
                    wrapper.parentNode.replaceChild(frag, wrapper);
                });
            }
            return container.innerHTML.trim();
        }

        getCleanHtml() {
            return this.extractUserHtml(true);
        }

        // ===================================================================
        // API pública
        // ===================================================================
        enable() { this.isEnabled = true; }
        disable() { this.isEnabled = false; this.hideHover(); this.clearSelection(); }
        updateConfig(newConfig) { this.config = Object.assign({}, this.config, newConfig); }
    }

    // ===== Inicializar o editor visual
    window.htmlEditor = new HtmlEditor();

    // Expor o HTML limpo para a janela pai (save / sincronização do CodeMirror).
    window.htmlEditorGetCleanHtml = function () {
        return window.htmlEditor ? window.htmlEditor.getCleanHtml() : '';
    };
});
