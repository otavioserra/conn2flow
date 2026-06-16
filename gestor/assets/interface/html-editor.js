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
            this.childrenBar = null;            // seletor de filhos (req-035)
            this.breadcrumbHoverOverlay = null; // hover roxo dos breadcrumbs (req-035)
            this.styler = null;
            this.placeholder = null;
            this.wrapMenu = null;               // popup de tags para embrulhar (req-036)
            this.clipboardElement = null;       // área de transferência interna (req-036)
            this.imagePickerTarget = null;      // alvo do ImagePicker: 'background' (req-039)
            this.parentHighlightOverlay = null; // destaque de contêiner alvo (append) (req-039)
            this.insertGhost = null;            // elemento fantasma no modo de inserção (req-039)
            this.widgetSeq = 0;                 // contador de ids de wrapper de widget (req-039)
            this.widgetCounter = 0;             // contador de ids únicos de widget (req-044 §1)
            this.widgetsMap = {};               // mapa data-widget-id → {signature,isVariable,type,slug} (req-044 §1)

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
            this.undoStack = [this.captureSnapshot()];
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
                #html-editor-floating-toolbar .he-tb-btn.he-tb-deselect{color:#fca5a5;margin-left:4px;
                    border-left:1px solid rgba(255,255,255,0.18);border-radius:0 4px 4px 0;}
                #html-editor-floating-toolbar .he-tb-btn.he-tb-deselect:hover{background:rgba(220,38,38,0.85);color:#fff;}
                .he-wrap-menu{position:absolute;display:none;z-index:1000000;background:#1f2937;border-radius:6px;
                    box-shadow:0 2px 10px rgba(0,0,0,0.3);padding:4px;min-width:120px;}
                .he-wrap-menu .he-wrap-item{padding:5px 10px;color:#e5e7eb;cursor:pointer;border-radius:4px;
                    font:12px monospace;}
                .he-wrap-menu .he-wrap-item:hover{background:rgba(255,255,255,0.18);}
                #html-editor-selection-breadcrumb{position:absolute;display:none;z-index:999998;
                    background:#111827;color:#e5e7eb;font:11px/1.4 monospace;padding:2px 6px;border-radius:0 0 4px 4px;
                    max-width:96vw;white-space:normal;flex-wrap:wrap;align-items:center;}
                #html-editor-selection-breadcrumb .he-crumb{cursor:pointer;color:#93c5fd;}
                #html-editor-selection-breadcrumb .he-crumb:hover{color:#fff;text-decoration:underline;}
                #html-editor-selection-breadcrumb .he-crumb-sep{color:#6b7280;margin:0 3px;}
                #html-editor-selection-breadcrumb .he-crumb-label,
                #html-editor-selection-children .he-crumb-label{color:#9ca3af;font-weight:bold;margin-right:5px;}
                #html-editor-selection-children{position:absolute;display:none;z-index:999998;
                    background:#1f2937;color:#e5e7eb;font:11px/1.4 monospace;padding:2px 6px;border-radius:0 0 4px 4px;
                    max-width:96vw;white-space:normal;flex-wrap:wrap;align-items:center;}
                #html-editor-selection-children .he-crumb-child{cursor:pointer;color:#fcd34d;}
                #html-editor-selection-children .he-crumb-child:hover{color:#fff;text-decoration:underline;}
                #html-editor-selection-children .he-child-sep{color:#6b7280;margin:0 3px;}
                #html-editor-breadcrumb-hover-overlay{position:absolute;pointer-events:none;box-sizing:border-box;
                    display:none;z-index:999991;border-radius:3px;border:2px dashed rgba(124,58,237,0.95);
                    background:rgba(124,58,237,0.06);}
                #html-editor-tailwind-styler{position:absolute;display:none;z-index:999998;background:#fff;
                    border:1px solid #d1d5db;border-radius:6px;box-shadow:0 2px 10px rgba(0,0,0,0.2);
                    padding:6px;max-width:560px;max-height:72vh;overflow-y:auto;}
                #html-editor-tailwind-styler .he-styler-cols{display:flex;gap:10px;align-items:flex-start;}
                #html-editor-tailwind-styler.he-styler-stacked{max-width:320px;}
                #html-editor-tailwind-styler.he-styler-stacked .he-styler-cols{flex-direction:column;gap:6px;}
                #html-editor-tailwind-styler .he-styler-col-visual{flex:0 0 auto;}
                #html-editor-tailwind-styler .he-styler-col-classes{flex:1 1 190px;min-width:0;
                    border-left:1px solid #e5e7eb;padding-left:10px;align-self:stretch;}
                #html-editor-tailwind-styler.he-styler-stacked .he-styler-col-classes{border-left:none;
                    border-top:1px solid #e5e7eb;padding-left:0;padding-top:6px;width:100%;}
                #html-editor-tailwind-styler .he-helper-section{font:bold 10px sans-serif;color:#1f2937;
                    text-transform:uppercase;letter-spacing:.6px;margin:4px 0 0;padding:5px 6px;cursor:pointer;
                    user-select:none;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;
                    display:flex;align-items:center;gap:5px;}
                #html-editor-tailwind-styler .he-helper-section:first-child{margin-top:0;}
                #html-editor-tailwind-styler .he-helper-section:hover{background:#f3f4f6;}
                #html-editor-tailwind-styler .he-helper-section.active{background:#eff6ff;color:#1e40af;
                    border-color:#bfdbfe;}
                #html-editor-tailwind-styler .he-helper-section i.dropdown.icon{margin:0;font-size:11px;
                    transition:transform .15s;}
                #html-editor-tailwind-styler .he-helper-section.active i.dropdown.icon{transform:rotate(90deg);}
                #html-editor-tailwind-styler .he-helper-section-body{display:none;padding:6px 2px 2px;}
                #html-editor-tailwind-styler .he-helper-section-body.active{display:block;}
                #html-editor-tailwind-styler .he-tw-tags{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;}
                #html-editor-tailwind-styler .he-tw-tag{display:inline-flex;align-items:center;gap:4px;
                    background:#eef2ff;color:#3730a3;border-radius:10px;padding:1px 6px;font:11px monospace;}
                #html-editor-tailwind-styler .he-tw-tag b{cursor:pointer;color:#9333ea;}
                #html-editor-tailwind-styler input{width:100%;border:1px solid #d1d5db;border-radius:4px;
                    padding:3px 6px;font:12px monospace;outline:none;}
                #html-editor-tailwind-styler .he-helper-group{margin-bottom:6px;}
                #html-editor-tailwind-styler .he-helper-title{font:bold 9px sans-serif;color:#6b7280;
                    text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;}
                #html-editor-tailwind-styler .he-helper-row{display:flex;gap:4px;flex-wrap:wrap;}
                #html-editor-tailwind-styler .he-helper-btn{min-width:24px;height:24px;display:inline-flex;
                    align-items:center;justify-content:center;border:1px solid #d1d5db;border-radius:4px;background:#fff;
                    cursor:pointer;font:11px sans-serif;color:#374151;padding:0 5px;}
                #html-editor-tailwind-styler .he-helper-btn:hover{background:#f3f4f6;}
                #html-editor-tailwind-styler .he-helper-btn.active{border-color:#2563eb;background:#dbeafe;color:#1e40af;}
                #html-editor-tailwind-styler .he-helper-btn i.icon{margin:0;}
                #html-editor-tailwind-styler .he-helper-color{width:20px;height:20px;border-radius:50%;
                    border:1px solid rgba(0,0,0,0.25);cursor:pointer;padding:0;background-clip:padding-box;}
                #html-editor-tailwind-styler .he-helper-color.he-color-transparent{
                    background-image:linear-gradient(45deg,#ccc 25%,transparent 25%,transparent 75%,#ccc 75%),
                    linear-gradient(45deg,#ccc 25%,#fff 25%,#fff 75%,#ccc 75%);
                    background-size:8px 8px;background-position:0 0,4px 4px;}
                #html-editor-tailwind-styler .he-helper-color.active{outline:2px solid #2563eb;outline-offset:1px;}
                #html-editor-tailwind-styler .he-helper-bordercolor{background:#fff !important;border-width:3px;
                    border-style:solid;}
                #html-editor-tailwind-styler .he-bgimage-actions{display:flex;gap:4px;align-items:center;}
                #html-editor-tailwind-styler .he-bgimage-preview{margin-top:4px;}
                #html-editor-tailwind-styler .he-bgimage-preview img{max-width:120px;max-height:70px;
                    border:1px solid #d1d5db;border-radius:4px;display:block;}
                .conn2flow-dnd-placeholder{height:0;border-top:3px dashed #f59e0b;margin:0;padding:0;
                    pointer-events:none;position:relative;z-index:999985;box-shadow:0 0 4px rgba(245,158,11,0.6);}
                #html-editor-parent-highlight-overlay{position:absolute;pointer-events:none;box-sizing:border-box;
                    display:none;z-index:999986;border:3px dashed #f59e0b;background:rgba(245,158,11,0.08);
                    border-radius:4px;}
                #html-editor-insert-ghost{position:absolute;pointer-events:none;display:none;z-index:1000001;
                    opacity:0.85;border:1px solid #7c3aed;background:rgba(255,255,255,0.95);border-radius:6px;
                    padding:6px;max-width:420px;max-height:60vh;overflow:hidden;
                    box-shadow:0 4px 12px rgba(0,0,0,0.15);}
                #html-editor-insert-ghost *{pointer-events:none !important;}
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

            // req-035: seletor de filhos diretos (abaixo do breadcrumb de ancestrais).
            this.childrenBar = document.createElement('div');
            this.childrenBar.id = 'html-editor-selection-children';
            document.body.appendChild(this.childrenBar);

            // req-035: overlay roxo tracejado para o hover sobre itens dos breadcrumbs.
            this.breadcrumbHoverOverlay = document.createElement('div');
            this.breadcrumbHoverOverlay.id = 'html-editor-breadcrumb-hover-overlay';
            document.body.appendChild(this.breadcrumbHoverOverlay);

            // req-039: destaque amarelo tracejado de 4 lados para o contêiner alvo (append).
            this.parentHighlightOverlay = document.createElement('div');
            this.parentHighlightOverlay.id = 'html-editor-parent-highlight-overlay';
            document.body.appendChild(this.parentHighlightOverlay);
        }

        createToolbar() {
            const tb = document.createElement('div');
            tb.id = 'html-editor-floating-toolbar';
            tb.innerHTML = `
                <button class="he-tb-btn he-tb-drag" type="button" title="Arrastar / Mover">
                    <i class="arrows alternate icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-dup" type="button" title="Duplicar">
                    <i class="clone icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-copy" type="button" title="Copiar (Ctrl+C)">
                    <i class="copy icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-paste" type="button" title="Colar (Ctrl+V)" style="display:none">
                    <i class="paste icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-wrap" type="button" title="Embrulhar">
                    <i class="box icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-edit" type="button" title="Editar">
                    <i class="pencil icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-del" type="button" title="Deletar">
                    <i class="trash icon" style="margin:0"></i></button>
                <button class="he-tb-btn he-tb-deselect" type="button" title="Deselecionar (Esc)">
                    <i class="times circle icon" style="margin:0"></i></button>
            `;
            document.body.appendChild(tb);
            this.toolbar = tb;

            // req-036: menu de tags para embrulhar (wrap) o elemento selecionado.
            const wrapMenu = document.createElement('div');
            wrapMenu.id = 'html-editor-wrap-menu';
            wrapMenu.className = 'he-wrap-menu';
            ['div', 'section', 'a', 'p', 'article', 'aside'].forEach((tag) => {
                const item = document.createElement('div');
                item.className = 'he-wrap-item';
                item.setAttribute('data-tag', tag);
                item.textContent = '<' + tag + '>';
                wrapMenu.appendChild(item);
            });
            document.body.appendChild(wrapMenu);
            this.wrapMenu = wrapMenu;

            // Editor rápido de classes Tailwind (acoplado ao overlay de seleção).
            // req-037/req-038: duas colunas — ESQUERDA (painel visual) e DIREITA (tags + input).
            const styler = document.createElement('div');
            styler.id = 'html-editor-tailwind-styler';
            styler.innerHTML = `
                <div class="he-styler-cols">
                    <div class="he-styler-col-visual">${this.buildHelperPanelHtml()}</div>
                    <div class="he-styler-col-classes">
                        <div class="he-tw-tags"></div>
                        <input type="text" list="html-editor-tw-classes" placeholder="Adicionar classes (espaço/Enter)..." />
                    </div>
                </div>
            `;
            document.body.appendChild(styler);
            this.styler = styler;

            // req-037/req-038: cliques no painel visual — accordion de seções + aplicação de classes.
            styler.querySelector('.he-styler-col-visual').addEventListener('click', (e) => {
                const sectionHeader = e.target.closest('.he-helper-section');
                if (sectionHeader) {
                    e.preventDefault(); e.stopPropagation();
                    this.toggleHelperSection(sectionHeader);
                    return;
                }
                // req-039: controles de imagem de fundo (ImagePicker + limpar).
                if (e.target.closest('.he-bgimage-pick')) {
                    e.preventDefault(); e.stopPropagation(); this.requestBackgroundImage(); return;
                }
                if (e.target.closest('.he-bgimage-clear')) {
                    e.preventDefault(); e.stopPropagation(); this.clearBackgroundImage(); return;
                }
                const btn = e.target.closest('[data-helper-group]');
                if (!btn) return;
                e.preventDefault(); e.stopPropagation();
                this.applyHelperClass(btn.getAttribute('data-helper-group'), btn.getAttribute('data-helper-class'));
            });

            // ===== Ações da toolbar
            tb.querySelector('.he-tb-dup').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.duplicateSelected();
            });
            tb.querySelector('.he-tb-copy').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.copySelected();
            });
            tb.querySelector('.he-tb-paste').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.pasteSelected();
            });
            tb.querySelector('.he-tb-wrap').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.toggleWrapMenu();
            });
            tb.querySelector('.he-tb-edit').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.editSelected();
            });
            tb.querySelector('.he-tb-del').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.deleteSelected();
            });
            tb.querySelector('.he-tb-deselect').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation(); this.clearSelection();
            });
            // Itens do menu de embrulhar.
            wrapMenu.addEventListener('click', (e) => {
                const item = e.target.closest('.he-wrap-item');
                if (!item) return;
                e.preventDefault(); e.stopPropagation();
                this.wrapSelected(item.getAttribute('data-tag'));
                this.closeWrapMenu();
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
                element.id === 'html-editor-selection-children' ||
                element.id === 'html-editor-breadcrumb-hover-overlay' ||
                element.id === 'html-editor-parent-highlight-overlay' ||
                element.id === 'html-editor-insert-ghost' ||
                element.id === 'html-editor-tailwind-styler' ||
                element.id === 'html-editor-wrap-menu' ||
                element.id === 'html-editor-modal')) return true;
            if (typeof element.closest === 'function') {
                if (element.closest('#html-editor-floating-toolbar')) return true;
                if (element.closest('#html-editor-selection-breadcrumb')) return true;
                if (element.closest('#html-editor-selection-children')) return true;
                if (element.closest('#html-editor-wrap-menu')) return true;
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
                    // req-039: clicar no elemento já selecionado funciona como alternador (deseleciona).
                    if (el === this.selectedElement) {
                        this.clearSelection();
                    } else {
                        this.selectElement(el);
                    }
                } else {
                    this.clearSelection();
                }
            }, true);

            // Fechar o menu de embrulhar ao clicar fora dele (e fora do botão que o abre).
            document.addEventListener('mousedown', (e) => {
                if (!this.wrapMenu || this.wrapMenu.style.display !== 'block') return;
                const t = e.target;
                if (t && typeof t.closest === 'function' &&
                    (t.closest('#html-editor-wrap-menu') || t.closest('.he-tb-wrap'))) return;
                this.closeWrapMenu();
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
                    else { this.closeWrapMenu(); this.clearSelection(); }
                } else if ((key === 'delete' || key === 'backspace') && this.selectedElement && !this.isTypingTarget(e.target)) {
                    e.preventDefault(); this.deleteSelected();
                } else if ((e.ctrlKey || e.metaKey) && key === 'c' && this.selectedElement &&
                    !this.isTypingTarget(e.target) && this.isTextSelectionCollapsed()) {
                    // Copiar o elemento só quando não há seleção de texto ativa (preserva a cópia nativa).
                    e.preventDefault(); this.copySelected();
                } else if ((e.ctrlKey || e.metaKey) && key === 'v' && this.clipboardElement &&
                    this.selectedElement && !this.isTypingTarget(e.target)) {
                    e.preventDefault(); this.pasteSelected();
                }
            });
        }

        isTextSelectionCollapsed() {
            const sel = window.getSelection ? window.getSelection() : null;
            return !sel || sel.isCollapsed || String(sel).length === 0;
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
                    case 'c2f-he:copy': this.copySelected(); break;
                    case 'c2f-he:paste': this.pasteSelected(); break;
                    case 'c2f-he:insert-element':
                        this.enterInsertMode({ kind: 'element', elementType: data.elementType }); break;
                    case 'c2f-he:insert-widget':
                        this.enterInsertMode({
                            kind: 'widget', widgetModule: data.widgetModule,
                            widgetSlug: data.widgetSlug, widgetName: data.widgetName
                        }); break;
                    case 'c2f-he:cancel-insert': this.exitInsertMode(); break;
                    case 'c2f-he:widget-rendered':
                        this.applyWidgetRender(data.wrapperId, data.html); break;
                    case 'html-editor-imagepick-selected':
                        // req-039: quando o alvo do ImagePicker é a imagem de fundo, aplicar no elemento.
                        if (this.imagePickerTarget === 'background' && data.imageData) {
                            this.imagePickerTarget = null;
                            const raiz = (typeof html_editor !== 'undefined' && html_editor.raiz) ? html_editor.raiz : '';
                            const caminho = data.imageData.caminho || '';
                            const url = /^https?:\/\//i.test(caminho) ? caminho : (raiz + caminho);
                            this.applyBackgroundImage(url);
                        }
                        break;
                }
            });
        }

        // ===================================================================
        // Hover
        // ===================================================================
        onHoverMove(x, y) {
            // Se o cursor está sobre o próprio chrome do editor (toolbar/breadcrumbs/styler),
            // não desenhar o hover azul — o hover roxo dos breadcrumbs é tratado à parte.
            const top = document.elementFromPoint(x, y);
            if (top && this.isEditorOwned(top)) { this.hideHover(); return; }
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
            this.closeWrapMenu();
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
            if (this.childrenBar) this.childrenBar.style.display = 'none';
            if (this.styler) this.styler.style.display = 'none';
            this.closeWrapMenu();
            this.hideBreadcrumbHover();
        }

        updateSelectionUI() {
            const element = this.selectedElement;
            if (!element || !document.body.contains(element)) { this.clearSelection(); return; }

            this.positionOverlay(this.selectionOverlay, element);
            this.selectionOverlay.style.display = 'block';

            const rect = element.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            const left = rect.left + scrollLeft;

            // Toolbar acoplada acima do topo do overlay de seleção (ou abaixo, se não couber),
            // ancorada à BORDA DIREITA do elemento (req-035 §1.1).
            this.toolbar.style.display = 'flex';
            this.updatePasteButton(); // req-036: o botão Colar entra/sai antes de medir a largura
            const tbTop = rect.top + scrollTop - this.toolbar.offsetHeight - 6;
            const toolbarEmbaixo = tbTop < scrollTop; // sem espaço no topo: vai para baixo do elemento
            this.toolbar.style.top = (toolbarEmbaixo ? rect.bottom + scrollTop + 6 : tbTop) + 'px';
            let tbLeft = rect.right + scrollLeft - this.toolbar.offsetWidth;
            if (tbLeft < scrollLeft) tbLeft = scrollLeft; // não estourar a margem esquerda
            this.toolbar.style.left = tbLeft + 'px';

            // Empilhamento abaixo do elemento (req-035 §1.2): ancestrais -> filhos -> classes Tailwind.
            // Cada bloco soma sua altura ao topo cumulativo do próximo.
            // req-038: se a toolbar foi desenhada na borda inferior, empurrar os painéis para baixo dela.
            let stackTop = rect.bottom + scrollTop;
            if (toolbarEmbaixo) stackTop += this.toolbar.offsetHeight + 12;

            // 1) Ancestrais (breadcrumb legado, sempre presente quando há seleção).
            this.renderBreadcrumb(element);
            this.breadcrumb.style.display = 'flex';
            this.breadcrumb.style.top = stackTop + 'px';
            this.breadcrumb.style.left = this.clampLeft(this.breadcrumb, left) + 'px';
            stackTop += this.breadcrumb.offsetHeight;

            // 2) Filhos (novo seletor; oculta-se sozinho se não houver filhos editáveis).
            this.renderChildren(element);
            if (this.childrenBar.style.display !== 'none') {
                this.childrenBar.style.top = stackTop + 'px';
                this.childrenBar.style.left = this.clampLeft(this.childrenBar, left) + 'px';
                stackTop += this.childrenBar.offsetHeight;
            }

            // 3) Tailwind styler (se visível para o elemento atual).
            if (this.styler.style.display === 'block') {
                // req-037: empilhar as duas colunas verticalmente em elementos estreitos (<400px).
                this.styler.classList.toggle('he-styler-stacked', rect.width < 400);
                this.styler.style.top = (stackTop + 2) + 'px';
                this.styler.style.left = this.clampLeft(this.styler, left) + 'px';
            }
        }

        // req-039: mantém o painel dentro da largura visível do iframe (clamp horizontal).
        clampLeft(el, leftPx) {
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;
            const w = el.offsetWidth || 0;
            const minLeft = scrollLeft + 4;
            const maxLeft = scrollLeft + window.innerWidth - w - 4;
            if (maxLeft < minLeft) return minLeft;
            return Math.max(minLeft, Math.min(leftPx, maxLeft));
        }

        // ===================================================================
        // Breadcrumb de ancestrais + seletor de filhos (req-035)
        // ===================================================================
        formatCrumbLabel(el) {
            let label = el.tagName.toLowerCase();
            if (el.id) label += '#' + el.id;
            else if (el.classList && el.classList.length) label += '.' + el.classList[0];
            return label;
        }

        // Hover roxo tracejado (overlay dedicado) acionado ao passar o mouse nos breadcrumbs.
        showBreadcrumbHover(el) {
            if (!el || !this.breadcrumbHoverOverlay) return;
            this.positionOverlay(this.breadcrumbHoverOverlay, el);
            this.breadcrumbHoverOverlay.style.display = 'block';
        }

        hideBreadcrumbHover() {
            if (this.breadcrumbHoverOverlay) this.breadcrumbHoverOverlay.style.display = 'none';
        }

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
            const lbl = document.createElement('span');
            lbl.className = 'he-crumb-label';
            lbl.textContent = 'Ancestrais:';
            this.breadcrumb.appendChild(lbl);
            path.forEach((el, idx) => {
                if (idx > 0) {
                    const sep = document.createElement('span');
                    sep.className = 'he-crumb-sep';
                    sep.textContent = '>';
                    this.breadcrumb.appendChild(sep);
                }
                const crumb = document.createElement('span');
                crumb.className = 'he-crumb';
                crumb.textContent = this.formatCrumbLabel(el);
                crumb.addEventListener('mouseenter', () => { this.showBreadcrumbHover(el); });
                crumb.addEventListener('mouseleave', () => { this.hideBreadcrumbHover(); });
                crumb.addEventListener('click', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    this.selectElement(el);
                });
                this.breadcrumb.appendChild(crumb);
            });
        }

        renderChildren(element) {
            this.childrenBar.innerHTML = '';
            // Widgets são blocos atômicos: não expor seus filhos internos.
            if (element.classList && element.classList.contains('conn2flow-widget-wrapper')) {
                this.childrenBar.style.display = 'none';
                return;
            }
            const children = Array.from(element.children || []).filter((c) => {
                if (this.isEditorOwned(c)) return false;
                return !this.config.ignoredTags.includes(c.tagName.toLowerCase());
            });
            if (!children.length) {
                this.childrenBar.style.display = 'none';
                return;
            }
            const lbl = document.createElement('span');
            lbl.className = 'he-crumb-label';
            lbl.textContent = 'Filhos:';
            this.childrenBar.appendChild(lbl);
            children.forEach((el, idx) => {
                if (idx > 0) {
                    const sep = document.createElement('span');
                    sep.className = 'he-child-sep';
                    sep.textContent = '|';
                    this.childrenBar.appendChild(sep);
                }
                const crumb = document.createElement('span');
                crumb.className = 'he-crumb-child';
                crumb.textContent = this.formatCrumbLabel(el);
                crumb.addEventListener('mouseenter', () => { this.showBreadcrumbHover(el); });
                crumb.addEventListener('mouseleave', () => { this.hideBreadcrumbHover(); });
                crumb.addEventListener('click', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    this.selectElement(el);
                });
                this.childrenBar.appendChild(crumb);
            });
            this.childrenBar.style.display = 'flex';
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
            this.syncHelperButtons(element);
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

        // ===================================================================
        // Painel visual de formatação (Tailwind UI Helper) — req-037 / req-038
        // ===================================================================
        tailwindHelperConfig() {
            if (this._helperConfig) return this._helperConfig;
            // Nomes de cor do Tailwind para detectar/limpar classes de cor por regex,
            // preservando alinhamento (`text-left`), tamanhos (`text-lg`) e utilitários (`bg-cover`).
            const cn = 'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose';
            const textColorRe = new RegExp('^text-(?:' + cn + ')-\\d{2,3}$|^text-(?:white|black)$');
            const bgColorRe = new RegExp('^bg-(?:' + cn + ')-\\d{2,3}$|^bg-(?:white|black|transparent)$');
            const borderColorRe = new RegExp('^border-(?:' + cn + ')-\\d{2,3}$|^border-(?:white|black|transparent)$');
            const fontSizeRe = /^text-(?:xs|sm|base|lg|xl|[2-9]xl)$/;
            const fontWeightRe = /^font-(?:thin|extralight|light|normal|medium|semibold|bold|extrabold|black)$/;
            const marginRe = /^m[xytblr]?-\d+$/;            // m-/mx-/my-/mt-/mb-/ml-/mr- + número
            const borderWidthRe = /^border-\d+$/;            // border-0/2/4/8 (o 'border' fica na lista)
            const opacityRe = /^opacity-\d+$/;
            const gapRe = /^gap(?:-[xy])?-\d+$/;

            // Listas estendidas para grupos cujas classes são palavras isoladas (sem número).
            const displayList = ['block', 'inline-block', 'inline', 'flex', 'inline-flex', 'grid', 'inline-grid', 'hidden', 'table', 'flow-root', 'contents'];
            const flexDirList = ['flex-row', 'flex-col', 'flex-row-reverse', 'flex-col-reverse'];
            const justifyList = ['justify-start', 'justify-center', 'justify-end', 'justify-between', 'justify-around', 'justify-evenly'];
            const itemsList = ['items-start', 'items-center', 'items-end', 'items-stretch', 'items-baseline'];
            const shadowList = ['shadow-none', 'shadow-sm', 'shadow', 'shadow-md', 'shadow-lg', 'shadow-xl', 'shadow-2xl', 'shadow-inner'];
            const transformList = ['uppercase', 'lowercase', 'capitalize', 'normal-case'];
            const decorationList = ['underline', 'line-through', 'no-underline', 'overline'];
            const widthList = ['w-auto', 'w-full', 'w-screen', 'w-fit', 'w-min', 'w-max', 'w-1/2', 'w-1/3', 'w-2/3', 'w-1/4', 'w-3/4'];

            this._helperConfig = [
                // ===== Seção: TEXTO
                {
                    key: 'align', section: 'Texto', title: 'Alinhamento', kind: 'icon', default: 'text-left',
                    buttons: [
                        { cls: 'text-left', icon: 'align left', title: 'Esquerda' },
                        { cls: 'text-center', icon: 'align center', title: 'Centro' },
                        { cls: 'text-right', icon: 'align right', title: 'Direita' },
                        { cls: 'text-justify', icon: 'align justify', title: 'Justificado' }
                    ]
                },
                {
                    key: 'fontSize', section: 'Texto', title: 'Tamanho', kind: 'text', default: 'text-base', cleanRe: fontSizeRe,
                    buttons: [
                        { cls: 'text-sm', label: 'P', title: 'Pequeno' },
                        { cls: 'text-base', label: 'N', title: 'Normal' },
                        { cls: 'text-lg', label: 'G', title: 'Grande' },
                        { cls: 'text-xl', label: 'XG', title: 'Extra grande' }
                    ]
                },
                {
                    key: 'fontWeight', section: 'Texto', title: 'Peso', kind: 'text', default: 'font-normal', cleanRe: fontWeightRe,
                    buttons: [
                        { cls: 'font-normal', label: 'N', title: 'Normal' },
                        { cls: 'font-medium', label: 'M', title: 'Médio' },
                        { cls: 'font-bold', label: 'B', title: 'Negrito' }
                    ]
                },
                {
                    key: 'textTransform', section: 'Texto', title: 'Caixa', kind: 'text', cleanList: transformList,
                    buttons: [
                        { cls: 'normal-case', label: 'Aa', title: 'Normal' },
                        { cls: 'uppercase', label: 'AA', title: 'Maiúsculas' },
                        { cls: 'lowercase', label: 'aa', title: 'Minúsculas' },
                        { cls: 'capitalize', label: 'Ab', title: 'Capitalizar' }
                    ]
                },
                {
                    key: 'textDecoration', section: 'Texto', title: 'Decoração', kind: 'icon', cleanList: decorationList,
                    buttons: [
                        { cls: 'no-underline', icon: 'ban', title: 'Nenhuma' },
                        { cls: 'underline', icon: 'underline', title: 'Sublinhado' },
                        { cls: 'line-through', icon: 'strikethrough', title: 'Riscado' }
                    ]
                },
                {
                    key: 'textColor', section: 'Texto', title: 'Cor do texto', kind: 'color', cleanRe: textColorRe,
                    buttons: [
                        { cls: 'text-gray-900', color: '#111827', title: 'Preto' },
                        { cls: 'text-gray-500', color: '#6b7280', title: 'Cinza' },
                        { cls: 'text-red-600', color: '#dc2626', title: 'Vermelho' },
                        { cls: 'text-blue-600', color: '#2563eb', title: 'Azul' },
                        { cls: 'text-green-600', color: '#16a34a', title: 'Verde' },
                        { cls: 'text-yellow-500', color: '#eab308', title: 'Amarelo' },
                        { cls: 'text-purple-600', color: '#9333ea', title: 'Roxo' },
                        { cls: 'text-white', color: '#ffffff', title: 'Branco' }
                    ]
                },
                // ===== Seção: LAYOUT
                {
                    key: 'display', section: 'Layout', title: 'Exibição', kind: 'text', cleanList: displayList,
                    buttons: [
                        { cls: 'block', label: 'Bloco', title: 'Block' },
                        { cls: 'inline-block', label: 'Inline', title: 'Inline-block' },
                        { cls: 'flex', label: 'Flex', title: 'Flex' },
                        { cls: 'grid', label: 'Grid', title: 'Grid' }
                    ]
                },
                {
                    key: 'flexDirection', section: 'Layout', title: 'Direção', kind: 'icon', cleanList: flexDirList,
                    buttons: [
                        { cls: 'flex-row', icon: 'arrows alternate horizontal', title: 'Linha' },
                        { cls: 'flex-col', icon: 'arrows alternate vertical', title: 'Coluna' }
                    ]
                },
                {
                    key: 'justify', section: 'Layout', title: 'Justificar', kind: 'icon', cleanList: justifyList,
                    buttons: [
                        { cls: 'justify-start', icon: 'align left', title: 'Início' },
                        { cls: 'justify-center', icon: 'align center', title: 'Centro' },
                        { cls: 'justify-between', icon: 'align justify', title: 'Entre' },
                        { cls: 'justify-end', icon: 'align right', title: 'Fim' }
                    ]
                },
                {
                    key: 'items', section: 'Layout', title: 'Alinhar itens', kind: 'text', cleanList: itemsList,
                    buttons: [
                        { cls: 'items-start', label: 'Topo', title: 'Início' },
                        { cls: 'items-center', label: 'Meio', title: 'Centro' },
                        { cls: 'items-end', label: 'Base', title: 'Fim' },
                        { cls: 'items-stretch', label: 'Esticar', title: 'Esticar' }
                    ]
                },
                {
                    key: 'gap', section: 'Layout', title: 'Espaço (gap)', kind: 'text', cleanRe: gapRe,
                    buttons: [
                        { cls: 'gap-0', label: '0', title: 'Nenhum' },
                        { cls: 'gap-2', label: 'P', title: 'Pequeno' },
                        { cls: 'gap-4', label: 'M', title: 'Médio' },
                        { cls: 'gap-8', label: 'G', title: 'Grande' }
                    ]
                },
                // ===== Seção: CAIXA
                {
                    key: 'width', section: 'Caixa', title: 'Largura', kind: 'text', cleanList: widthList,
                    buttons: [
                        { cls: 'w-auto', label: 'Auto', title: 'Automática' },
                        { cls: 'w-1/2', label: '½', title: 'Metade' },
                        { cls: 'w-full', label: '100%', title: 'Total' }
                    ]
                },
                {
                    key: 'padding', section: 'Caixa', title: 'Padding', kind: 'text', cleanRe: /^p-\d+$/,
                    buttons: [
                        { cls: 'p-0', label: '0', title: 'Nenhum' },
                        { cls: 'p-2', label: 'P', title: 'Pequeno' },
                        { cls: 'p-4', label: 'M', title: 'Médio' },
                        { cls: 'p-8', label: 'G', title: 'Grande' }
                    ]
                },
                {
                    key: 'margin', section: 'Caixa', title: 'Margem', kind: 'text', cleanRe: marginRe,
                    buttons: [
                        { cls: 'm-0', label: '0', title: 'Nenhuma' },
                        { cls: 'm-2', label: 'P', title: 'Pequena' },
                        { cls: 'm-4', label: 'M', title: 'Média' },
                        { cls: 'm-8', label: 'G', title: 'Grande' }
                    ]
                },
                // ===== Seção: APARÊNCIA
                {
                    key: 'rounded', section: 'Aparência', title: 'Cantos', kind: 'text', default: 'rounded-none', cleanRe: /^rounded(-.+)?$/,
                    buttons: [
                        { cls: 'rounded-none', label: 'Reto', title: 'Reto' },
                        { cls: 'rounded-sm', label: 'Leve', title: 'Leve' },
                        { cls: 'rounded-lg', label: 'Médio', title: 'Médio' },
                        { cls: 'rounded-full', label: '●', title: 'Redondo' }
                    ]
                },
                {
                    key: 'borderWidth', section: 'Aparência', title: 'Borda', kind: 'text', default: 'border-0', cleanRe: borderWidthRe,
                    buttons: [
                        { cls: 'border-0', label: '0', title: 'Nenhuma' },
                        { cls: 'border', label: '1', title: 'Fina' },
                        { cls: 'border-2', label: '2', title: 'Média' },
                        { cls: 'border-4', label: '4', title: 'Grossa' }
                    ]
                },
                {
                    key: 'borderColor', section: 'Aparência', title: 'Cor da borda', kind: 'color', colorStyle: 'border',
                    default: 'border-transparent', cleanRe: borderColorRe,
                    buttons: [
                        { cls: 'border-transparent', color: 'transparent', title: 'Transparente' },
                        { cls: 'border-gray-300', color: '#d1d5db', title: 'Cinza' },
                        { cls: 'border-red-500', color: '#ef4444', title: 'Vermelho' },
                        { cls: 'border-blue-500', color: '#3b82f6', title: 'Azul' },
                        { cls: 'border-green-500', color: '#22c55e', title: 'Verde' },
                        { cls: 'border-yellow-400', color: '#facc15', title: 'Amarelo' },
                        { cls: 'border-purple-500', color: '#a855f7', title: 'Roxo' },
                        { cls: 'border-black', color: '#000000', title: 'Preto' }
                    ]
                },
                {
                    key: 'shadow', section: 'Aparência', title: 'Sombra', kind: 'text', default: 'shadow-none', cleanList: shadowList,
                    buttons: [
                        { cls: 'shadow-none', label: '0', title: 'Nenhuma' },
                        { cls: 'shadow-sm', label: 'P', title: 'Pequena' },
                        { cls: 'shadow', label: 'M', title: 'Média' },
                        { cls: 'shadow-lg', label: 'G', title: 'Grande' },
                        { cls: 'shadow-xl', label: 'XG', title: 'Extra grande' }
                    ]
                },
                {
                    key: 'opacity', section: 'Aparência', title: 'Opacidade', kind: 'text', default: 'opacity-100', cleanRe: opacityRe,
                    buttons: [
                        { cls: 'opacity-100', label: '100', title: '100%' },
                        { cls: 'opacity-75', label: '75', title: '75%' },
                        { cls: 'opacity-50', label: '50', title: '50%' },
                        { cls: 'opacity-25', label: '25', title: '25%' }
                    ]
                },
                // ===== Seção: FUNDO (req-039) — cor de fundo migrada + imagem de fundo
                {
                    key: 'bgColor', section: 'Fundo', title: 'Cor de fundo', kind: 'color', default: 'bg-transparent', cleanRe: bgColorRe,
                    buttons: [
                        { cls: 'bg-transparent', color: 'transparent', title: 'Transparente' },
                        { cls: 'bg-gray-100', color: '#f3f4f6', title: 'Cinza claro' },
                        { cls: 'bg-gray-800', color: '#1f2937', title: 'Cinza escuro' },
                        { cls: 'bg-red-500', color: '#ef4444', title: 'Vermelho' },
                        { cls: 'bg-blue-500', color: '#3b82f6', title: 'Azul' },
                        { cls: 'bg-green-500', color: '#22c55e', title: 'Verde' },
                        { cls: 'bg-yellow-400', color: '#facc15', title: 'Amarelo' },
                        { cls: 'bg-purple-500', color: '#a855f7', title: 'Roxo' }
                    ]
                },
                { key: 'bgImage', section: 'Fundo', title: 'Imagem de fundo', kind: 'bgimage', buttons: [] },
                {
                    key: 'bgRepeat', section: 'Fundo', title: 'Repetição', kind: 'text',
                    cleanList: ['bg-repeat', 'bg-no-repeat', 'bg-repeat-x', 'bg-repeat-y', 'bg-repeat-round', 'bg-repeat-space'],
                    buttons: [
                        { cls: 'bg-repeat', label: 'Tile', title: 'Repetir' },
                        { cls: 'bg-no-repeat', label: 'Não', title: 'Não repetir' },
                        { cls: 'bg-repeat-x', label: 'X', title: 'Repetir horizontal' },
                        { cls: 'bg-repeat-y', label: 'Y', title: 'Repetir vertical' }
                    ]
                },
                {
                    key: 'bgSize', section: 'Fundo', title: 'Tamanho', kind: 'text',
                    cleanList: ['bg-auto', 'bg-cover', 'bg-contain'],
                    buttons: [
                        { cls: 'bg-auto', label: 'Auto', title: 'Automático' },
                        { cls: 'bg-cover', label: 'Cobrir', title: 'Cover' },
                        { cls: 'bg-contain', label: 'Conter', title: 'Contain' }
                    ]
                },
                {
                    key: 'bgPosition', section: 'Fundo', title: 'Posição', kind: 'text',
                    cleanList: ['bg-center', 'bg-top', 'bg-bottom', 'bg-left', 'bg-right',
                        'bg-left-top', 'bg-left-bottom', 'bg-right-top', 'bg-right-bottom'],
                    buttons: [
                        { cls: 'bg-center', label: 'Centro', title: 'Centro' },
                        { cls: 'bg-top', label: 'Topo', title: 'Topo' },
                        { cls: 'bg-bottom', label: 'Base', title: 'Base' },
                        { cls: 'bg-left', label: 'Esq', title: 'Esquerda' },
                        { cls: 'bg-right', label: 'Dir', title: 'Direita' }
                    ]
                }
            ];
            // Deriva a lista fechada de classes de cada grupo a partir dos botões.
            this._helperConfig.forEach((g) => { g.classes = g.buttons.map((b) => b.cls); });
            return this._helperConfig;
        }

        buildHelperPanelHtml() {
            const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            // Agrupar a config por seção (preservando a ordem) para montar o accordion.
            const sections = [];
            this.tailwindHelperConfig().forEach((g) => {
                const name = g.section || 'Geral';
                let sec = sections.find((s) => s.name === name);
                if (!sec) { sec = { name: name, groups: [] }; sections.push(sec); }
                sec.groups.push(g);
            });

            let html = '';
            sections.forEach((sec, idx) => {
                const active = idx === 0 ? ' active' : ''; // primeira seção aberta por padrão
                html += '<div class="he-helper-section' + active + '" data-section="' + esc(sec.name) + '">' +
                    '<i class="dropdown icon"></i>' + esc(sec.name) + '</div>';
                html += '<div class="he-helper-section-body' + active + '">';
                sec.groups.forEach((g) => { html += this.buildHelperGroupHtml(g, esc); });
                html += '</div>';
            });
            return html;
        }

        buildHelperGroupHtml(g, esc) {
            let html = '<div class="he-helper-group" data-group="' + g.key + '">';
            html += '<div class="he-helper-title">' + esc(g.title) + '</div>';
            // req-039: controle especial de imagem de fundo (ImagePicker + preview).
            if (g.kind === 'bgimage') {
                html += '<div class="he-bgimage">' +
                    '<div class="he-bgimage-actions">' +
                    '<button type="button" class="he-helper-btn he-bgimage-pick" title="Selecionar imagem do servidor">' +
                    '<i class="folder open icon"></i> Imagem</button>' +
                    '<button type="button" class="he-helper-btn he-bgimage-clear" title="Remover imagem de fundo">' +
                    '<i class="trash icon"></i></button>' +
                    '</div>' +
                    '<div class="he-bgimage-preview" style="display:none"><img alt="" /></div>' +
                    '</div></div>';
                return html;
            }
            html += '<div class="he-helper-row">';
            g.buttons.forEach((b) => {
                if (g.kind === 'color') {
                    const isBorder = g.colorStyle === 'border';
                    const transp = (b.color === 'transparent');
                    let cls = 'he-helper-color';
                    if (isBorder) cls += ' he-helper-bordercolor';
                    else if (transp) cls += ' he-color-transparent';
                    const styleAttr = isBorder
                        ? ('border-color:' + (transp ? '#9ca3af' : b.color))
                        : ('background-color:' + (transp ? 'transparent' : b.color));
                    html += '<button type="button" class="' + cls + '" data-helper-group="' + g.key +
                        '" data-helper-class="' + b.cls + '" title="' + esc(b.title) + '" style="' + styleAttr + '"></button>';
                } else if (g.kind === 'icon') {
                    html += '<button type="button" class="he-helper-btn" data-helper-group="' + g.key +
                        '" data-helper-class="' + b.cls + '" title="' + esc(b.title) + '"><i class="' + b.icon + ' icon"></i></button>';
                } else {
                    html += '<button type="button" class="he-helper-btn" data-helper-group="' + g.key +
                        '" data-helper-class="' + b.cls + '" title="' + esc(b.title) + '">' + esc(b.label) + '</button>';
                }
            });
            html += '</div></div>';
            return html;
        }

        // Accordion: abre a seção clicada e fecha as demais (clicar na ativa fecha todas).
        toggleHelperSection(header) {
            if (!header || !this.styler) return;
            const visual = this.styler.querySelector('.he-styler-col-visual');
            if (!visual) return;
            const body = header.nextElementSibling; // .he-helper-section-body
            const wasActive = header.classList.contains('active');
            visual.querySelectorAll('.he-helper-section.active, .he-helper-section-body.active')
                .forEach((el) => el.classList.remove('active'));
            if (!wasActive) {
                header.classList.add('active');
                if (body && body.classList.contains('he-helper-section-body')) body.classList.add('active');
            }
            // A altura do painel mudou — reposicionar os overlays de suporte.
            this.updateSelectionUI();
        }

        applyHelperClass(groupKey, cls) {
            const el = this.selectedElement;
            if (!el || !cls) return;
            const g = this.tailwindHelperConfig().find((x) => x.key === groupKey);
            if (!g) return;
            // 1) Remover as classes da lista fechada (os próprios botões do grupo).
            g.classes.forEach((c) => el.classList.remove(c));
            // 2) Remover variantes estendidas (palavras isoladas: displays, sombras, etc.).
            if (g.cleanList) g.cleanList.forEach((c) => el.classList.remove(c));
            // 3) Remover por regex (cores, p-/m-/gap-/opacity-/border-width, etc.).
            if (g.cleanRe) {
                Array.from(el.classList).forEach((c) => { if (g.cleanRe.test(c)) el.classList.remove(c); });
            }
            el.classList.add(cls);
            this.renderStyler(el);
            this.afterDomMutation();
        }

        syncHelperButtons(element) {
            if (!this.styler) return;
            const visual = this.styler.querySelector('.he-styler-col-visual');
            if (!visual) return;
            const present = new Set(Array.from(element.classList || []));
            this.tailwindHelperConfig().forEach((g) => {
                let activeCls = g.classes.find((c) => present.has(c)) || null;
                if (!activeCls && g.default) activeCls = g.default; // destaca a opção padrão
                g.buttons.forEach((b) => {
                    const btn = visual.querySelector('[data-helper-group="' + g.key + '"][data-helper-class="' + b.cls + '"]');
                    if (btn) btn.classList.toggle('active', b.cls === activeCls);
                });
            });
            this.syncBgImagePreview(element);
        }

        // ===== Imagem de fundo (ImagePicker) — req-039
        requestBackgroundImage() {
            if (!this.selectedElement) return;
            const cfg = (typeof html_editor !== 'undefined' && html_editor.imagepick) ? html_editor.imagepick : null;
            this.imagePickerTarget = 'background';
            try {
                window.parent.postMessage(JSON.stringify({ action: 'html-editor-imagepick-open', config: cfg }), '*');
            } catch (e) { /* noop */ }
        }

        applyBackgroundImage(url) {
            const el = this.selectedElement;
            if (!el || !url) return;
            el.style.backgroundImage = "url('" + String(url).replace(/'/g, "\\'") + "')";
            this.syncBgImagePreview(el);
            this.afterDomMutation();
        }

        clearBackgroundImage() {
            const el = this.selectedElement;
            if (!el) return;
            el.style.backgroundImage = '';
            if (el.getAttribute('style') === '') el.removeAttribute('style');
            this.syncBgImagePreview(el);
            this.afterDomMutation();
        }

        currentBackgroundImageUrl(element) {
            const bg = element && element.style ? element.style.backgroundImage : '';
            if (!bg || bg === 'none') return '';
            const m = bg.match(/url\((['"]?)(.*?)\1\)/);
            return m ? m[2] : '';
        }

        syncBgImagePreview(element) {
            if (!this.styler) return;
            const box = this.styler.querySelector('.he-bgimage-preview');
            if (!box) return;
            const url = this.currentBackgroundImageUrl(element);
            const img = box.querySelector('img');
            if (url) {
                if (img) img.src = url;
                box.style.display = 'block';
            } else {
                if (img) img.removeAttribute('src');
                box.style.display = 'none';
            }
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
        // Ações da toolbar: Duplicar / Copiar / Colar / Embrulhar / Editar / Deletar
        // ===================================================================
        duplicateSelected() {
            const el = this.selectedElement;
            if (!el || !el.parentNode) return;
            const clone = el.cloneNode(true);
            el.parentNode.insertBefore(clone, el.nextSibling);
            this.selectElement(clone);
            this.afterDomMutation();
        }

        // ===== Copiar / Colar (req-036)
        copySelected() {
            const el = this.selectedElement;
            if (!el) return;
            this.clipboardElement = el.cloneNode(true);
            // Re-exibe o botão Colar e reposiciona a toolbar (ancorada à direita).
            if (this.selectedElement) this.updateSelectionUI();
            else this.updatePasteButton();
        }

        pasteSelected() {
            const el = this.selectedElement;
            if (!this.clipboardElement || !el || !el.parentNode) return;
            const clone = this.clipboardElement.cloneNode(true);
            el.parentNode.insertBefore(clone, el.nextSibling);
            this.selectElement(clone);
            this.afterDomMutation();
        }

        updatePasteButton() {
            if (!this.toolbar) return;
            const btn = this.toolbar.querySelector('.he-tb-paste');
            if (btn) btn.style.display = this.clipboardElement ? 'inline-flex' : 'none';
        }

        // ===== Embrulhar (wrap) (req-036)
        toggleWrapMenu() {
            if (this.wrapMenu && this.wrapMenu.style.display === 'block') this.closeWrapMenu();
            else this.openWrapMenu();
        }

        openWrapMenu() {
            if (!this.wrapMenu || !this.selectedElement) return;
            const wrapBtn = this.toolbar.querySelector('.he-tb-wrap');
            const rect = wrapBtn.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            this.wrapMenu.style.display = 'block';
            this.wrapMenu.style.top = (rect.bottom + scrollTop + 4) + 'px';
            this.wrapMenu.style.left = (rect.left + scrollLeft) + 'px';
        }

        closeWrapMenu() {
            if (this.wrapMenu) this.wrapMenu.style.display = 'none';
        }

        wrapSelected(tag) {
            const el = this.selectedElement;
            const allowed = ['div', 'section', 'a', 'p', 'article', 'aside'];
            if (!el || !el.parentNode || allowed.indexOf(tag) === -1) return;
            const wrapper = document.createElement(tag);
            el.parentNode.replaceChild(wrapper, el);
            wrapper.appendChild(el);
            // Mantém a seleção no elemento original (agora filho do novo contêiner).
            this.selectElement(el);
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
            // req-044 §1: gera um NOVO id exclusivo e copia os metadados anteriores do mapa,
            // evitando conflito caso o widget editado seja clone de outro na tela.
            const oldId = wrapper.getAttribute('data-widget-id');
            const oldMeta = (oldId && this.widgetsMap[oldId]) ? this.widgetsMap[oldId] : {};
            const newId = this.nextWidgetId();
            this.widgetsMap[newId] = {
                signature: type + '->render({"grupo_slug": "' + slug + '"})',
                isVariable: !!oldMeta.isVariable,
                type: type,
                slug: slug
            };
            wrapper.setAttribute('data-widget-id', newId);
            wrapper.setAttribute('data-widget-slug', slug);
            const label = wrapper.querySelector('.conn2flow-widget-label');
            if (label) label.textContent = 'Widget: ' + type + ' - ' + slug;
            // req-039: o mockup é descartado ao trocar o slug; re-renderiza o esqueleto.
            wrapper.setAttribute('data-widget-mockup', '');
            const inner = wrapper.querySelector('.conn2flow-widget-inner');
            if (inner) inner.innerHTML = '';
            this.updateSelectionUI();
            this.afterDomMutation();
            this.requestWidgetRender(wrapper);
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
            this.hideBreadcrumbHover();
            this.closeWrapMenu();
            if (this.selectionOverlay) this.selectionOverlay.style.display = 'none';
            if (this.toolbar) this.toolbar.style.display = 'none';
            if (this.breadcrumb) this.breadcrumb.style.display = 'none';
            if (this.childrenBar) this.childrenBar.style.display = 'none';
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
            this.showDropIndicator(target);
        }

        // req-039: 'inside' (contêiner) destaca o pai com borda amarela de 4 lados;
        // 'before'/'after' usam a linha de placeholder.
        showDropIndicator(target) {
            if (!target) { this.hideDropIndicators(); return; }
            if (target.position === 'inside') {
                this.removePlaceholder();
                this.showParentHighlight(target.element);
            } else {
                this.hideParentHighlight();
                this.positionPlaceholder(target);
            }
        }

        hideDropIndicators() {
            this.removePlaceholder();
            this.hideParentHighlight();
        }

        showParentHighlight(element) {
            if (!this.parentHighlightOverlay || !element) return;
            this.positionOverlay(this.parentHighlightOverlay, element);
            this.parentHighlightOverlay.style.display = 'block';
        }

        hideParentHighlight() {
            if (this.parentHighlightOverlay) this.parentHighlightOverlay.style.display = 'none';
        }

        // Insere um nó conforme o alvo computado (inside/before/after); retorna sucesso.
        insertAtTarget(node, target) {
            if (!node || !target || !target.element) return false;
            if (target.position === 'inside') {
                target.element.appendChild(node);
            } else if (target.position === 'before') {
                if (!target.element.parentNode) return false;
                target.element.parentNode.insertBefore(node, target.element);
            } else {
                if (!target.element.parentNode) return false;
                target.element.parentNode.insertBefore(node, target.element.nextSibling);
            }
            return true;
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

            if (el && target) {
                this.insertAtTarget(el, target);
            }
            this.hideDropIndicators();
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
            this.createInsertGhost(payload);
        }

        exitInsertMode() {
            this.insertMode = false;
            this.insertPayload = null;
            this.hideDropIndicators();
            this.removeInsertGhost();
            document.documentElement.classList.remove('he-inserting');
        }

        // req-039: elemento fantasma que segue o cursor representando o item a inserir.
        createInsertGhost(payload) {
            this.removeInsertGhost();
            const ghost = document.createElement('div');
            ghost.id = 'html-editor-insert-ghost';
            // req-040: o fantasma mostra o ELEMENTO/WIDGET real a ser inserido (não um rótulo sintético).
            let node;
            if (payload.kind === 'widget') {
                node = this.buildWidgetWrapper(payload);
            } else {
                node = this.buildElement(payload.elementType);
            }
            ghost.appendChild(node);
            document.body.appendChild(ghost);
            this.insertGhost = ghost;
            // Widget: pedir a renderização do esqueleto para o preview seguir o cursor já renderizado.
            if (payload.kind === 'widget') {
                this.requestWidgetRender(node);
            }
        }

        removeInsertGhost() {
            if (this.insertGhost && this.insertGhost.parentNode) {
                this.insertGhost.parentNode.removeChild(this.insertGhost);
            }
            this.insertGhost = null;
        }

        moveInsertGhost(x, y) {
            if (!this.insertGhost) return;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;
            this.insertGhost.style.display = 'block';
            this.insertGhost.style.top = (y + scrollTop + 15) + 'px';
            this.insertGhost.style.left = (x + scrollLeft + 15) + 'px';
        }

        onInsertMove(e) {
            this.moveInsertGhost(e.clientX, e.clientY);
            const target = this.computeInsertTarget(e.clientX, e.clientY);
            this.dropTarget = target;
            this.showDropIndicator(target);
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

            if (!this.insertAtTarget(node, target)) {
                // fallback (clique sem alvo): inserir antes da UI do editor no body.
                const ref = document.getElementById('html-editor-modal') ||
                    document.getElementById('html-editor-hover-overlay');
                document.body.insertBefore(node, ref || null);
            }

            this.exitInsertMode();
            this.selectElement(node);
            this.afterDomMutation();

            // req-039: renderizar o esqueleto do widget recém-inserido.
            if (payload.kind === 'widget') {
                this.requestWidgetRender(node);
            }

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
        // Wrappers virtuais de widget — req-034 §6.5 / req-044 §1
        // ===================================================================

        // req-044 §1: gera um identificador de widget único e limpo (widget-0, widget-1, …).
        nextWidgetId() { return 'widget-' + (this.widgetCounter++); }

        // req-044 §1.2/§2.1: descarrega entidades HTML (&gt; → >, &quot; → ", &amp; → &) que o
        // navegador injeta ao serializar a assinatura no DOM (incl. o caso de duplo escape
        // &amp;gt;). Usa <textarea> (RCDATA: decodifica entidades sem interpretar markup).
        htmlUnescape(str) {
            if (!str || str.indexOf('&') === -1) return str || '';
            const ta = document.createElement('textarea');
            let out = str, prev, guard = 0;
            do {
                prev = out;
                ta.innerHTML = out;
                out = ta.value;
                guard++;
            } while (out !== prev && out.indexOf('&') !== -1 && guard < 3);
            return out;
        }

        // req-044 §1: a assinatura real (com -> e aspas) vive SÓ no mapa em memória, nunca como
        // atributo no DOM. Resolve pelo data-widget-id; fallback reconstrói de type/slug limpos.
        getWidgetSignature(wrapper) {
            const id = wrapper.getAttribute('data-widget-id');
            const meta = id ? this.widgetsMap[id] : null;
            if (meta && meta.signature) return meta.signature;
            const type = wrapper.getAttribute('data-widget-type') || '';
            const slug = wrapper.getAttribute('data-widget-slug') || '';
            return type + '->render({"grupo_slug": "' + slug + '"})';
        }

        createWrapperEl(opts) {
            const id = opts.id || this.nextWidgetId();
            const isVariable = !!opts.isVariable;
            // req-044 §1: a assinatura (caracteres especiais) é guardada apenas no mapa em memória.
            this.widgetsMap[id] = {
                signature: opts.signature,
                isVariable: isVariable,
                type: opts.type,
                slug: opts.slug
            };

            const wrapper = document.createElement('div');
            wrapper.className = 'conn2flow-widget-wrapper';
            wrapper.setAttribute('data-widget-id', id);
            // req-044 §1: somente atributos limpos e alfanuméricos no DOM (sem -> nem aspas).
            wrapper.setAttribute('data-widget-type', opts.type);
            wrapper.setAttribute('data-widget-slug', opts.slug);
            wrapper.setAttribute('data-widget-variable', isVariable ? 'true' : 'false');
            // req-039: o mockup original é preservado à parte; o inner pode receber o preview
            // renderizado (que NÃO deve vazar no save — o save usa o mockup).
            wrapper.setAttribute('data-widget-mockup', opts.innerHtml || '');

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

        // req-039: pede ao pai o HTML renderizado do widget para preencher o wrapper.
        requestWidgetRender(wrapper) {
            if (!wrapper) return;
            // req-044 §1: assinatura resolvida pelo mapa (nunca lida crua do DOM).
            const signature = this.getWidgetSignature(wrapper);
            const slug = wrapper.getAttribute('data-widget-slug');
            if (!signature || !slug) return; // sem slug não há o que renderizar
            let wid = wrapper.getAttribute('data-widget-id');
            if (!wid) { wid = this.nextWidgetId(); wrapper.setAttribute('data-widget-id', wid); }
            const inner = wrapper.querySelector('.conn2flow-widget-inner');
            if (inner && !inner.innerHTML.trim()) {
                inner.innerHTML = '<div style="padding:8px;color:#92400e;font:12px sans-serif">Carregando widget…</div>';
            }
            try {
                window.parent.postMessage(JSON.stringify({
                    action: 'c2f-he:widget-render', signature: signature, wrapperId: wid
                }), '*');
            } catch (e) { /* noop */ }
        }

        applyWidgetRender(wrapperId, html) {
            if (!wrapperId) return;
            const wrapper = document.querySelector('.conn2flow-widget-wrapper[data-widget-id="' + wrapperId + '"]');
            if (!wrapper) return;
            const inner = wrapper.querySelector('.conn2flow-widget-inner');
            if (!inner) return;
            inner.innerHTML = html || '<div style="padding:8px;color:#9ca3af;font:12px sans-serif">(widget sem conteúdo)</div>';
        }

        /**
         * req-045 (correção): converte variáveis de widget inline ([[widgets#...]] / @[[widgets#...]]@)
         * em pares de comentários `widgets-var#` de forma CIRÚRGICA, percorrendo apenas os text nodes
         * do conteúdo do usuário. NÃO reescreve document.body.innerHTML — fazer isso destruiria os
         * overlays/toolbar/styler do editor (anexados ao body em createOverlays/createToolbar/
         * createPlaceholder), quebrando a seleção de elementos quando a página tinha um widget em
         * formato de variável. (Com widget em comentário o innerHTML não era reescrito, por isso só
         * o caso de variável falhava.)
         */
        convertWidgetVariablesToComments() {
            const varRe = /@?\[\[widgets#(.+?)\]\]@?/g;
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
                acceptNode: (node) => {
                    if (!node.nodeValue || node.nodeValue.indexOf('[[widgets#') === -1) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Ignorar texto dentro da própria UI do editor (ids html-editor-*).
                    for (let p = node.parentNode; p && p !== document.body; p = p.parentNode) {
                        if (p.id && p.id.indexOf('html-editor-') === 0) return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });
            const textNodes = [];
            let tn;
            while ((tn = walker.nextNode())) textNodes.push(tn);

            textNodes.forEach((node) => {
                const text = node.nodeValue;
                const frag = document.createDocumentFragment();
                let lastIndex = 0, m;
                varRe.lastIndex = 0;
                while ((m = varRe.exec(text)) !== null) {
                    if (m.index > lastIndex) frag.appendChild(document.createTextNode(text.slice(lastIndex, m.index)));
                    const sig = m[1].trim();
                    frag.appendChild(document.createComment(' widgets-var#' + sig + ' < '));
                    frag.appendChild(document.createComment(' widgets-var#' + sig + ' > '));
                    lastIndex = m.index + m[0].length;
                }
                if (lastIndex < text.length) frag.appendChild(document.createTextNode(text.slice(lastIndex)));
                if (node.parentNode) node.parentNode.replaceChild(frag, node);
            });
        }

        /**
         * Converte comentários de widget (<!-- widgets#X->render({...}) < --> ... > -->)
         * em divs .conn2flow-widget-wrapper. Operação cirúrgica via varredura de nós COMMENT.
         */
        convertWidgetCommentsToWrappers() {
            // req-044 §1.2: variáveis de widget inline ([[widgets#...]] ou @[[widgets#...]]@) viram
            // comentários TEMPORÁRIOS rotulados como `widgets-var#` (distintos dos comentários reais
            // `widgets#`). req-045: a conversão é CIRÚRGICA (sem reescrever body.innerHTML) para não
            // destruir os overlays/toolbar do editor.
            this.convertWidgetVariablesToComments();
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT, null);
            const comments = [];
            let n;
            while ((n = walker.nextNode())) comments.push(n);

            // Grupo 1 = prefixo (widgets-var | widgets); grupo 2 = assinatura.
            const openRe = /^\s*(widgets-var|widgets)#(.+?)\s*<\s*$/i;
            const closeRe = /^\s*(widgets-var|widgets)#\s*(.+?)\s*>\s*$/i;

            for (let i = 0; i < comments.length; i++) {
                const c = comments[i];
                if (!c.parentNode) continue;
                const mo = c.data.match(openRe);
                if (!mo) continue;
                const isVariable = mo[1].toLowerCase() === 'widgets-var';
                // req-044 §1.2: unescape das entidades antes de processar a assinatura.
                const signature = this.htmlUnescape(mo[2].trim());
                const rawSig = mo[2].trim();

                // Procurar o fechamento correspondente (compara a assinatura crua, ainda escapada).
                let close = null;
                for (let j = i + 1; j < comments.length; j++) {
                    const mc = comments[j].data.match(closeRe);
                    if (mc && mc[1].toLowerCase() === mo[1].toLowerCase() && mc[2].trim() === rawSig) {
                        close = comments[j];
                        break;
                    }
                }
                if (!close || close.parentNode !== c.parentNode) continue;

                const parsed = this.parseWidgetSignature(signature);
                // req-044 §1.2: id único + metadados no mapa em memória.
                const widgetId = this.nextWidgetId();
                const wrapper = this.createWrapperEl({
                    id: widgetId, isVariable: isVariable,
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
                // Preservar o mockup original (o que será reescrito entre os comentários no save).
                wrapper.setAttribute('data-widget-mockup', inner.innerHTML);
                c.parentNode.insertBefore(wrapper, c);
                c.parentNode.removeChild(c);
                if (close.parentNode) close.parentNode.removeChild(close);

                // req-039: se não houver mockup mas houver slug, renderizar o esqueleto do widget.
                if (!inner.innerHTML.trim() && parsed.slug) {
                    this.requestWidgetRender(wrapper);
                }
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

        // req-039: snapshot inclui a rolagem vertical do iframe para restaurar o viewport.
        captureSnapshot() {
            return {
                html: this.extractUserHtml(false),
                scrollTop: window.pageYOffset || document.documentElement.scrollTop || 0
            };
        }

        restoreScroll(top) {
            try { window.scrollTo(0, top || 0); } catch (e) { /* noop */ }
        }

        pushUndo() {
            const snap = this.captureSnapshot();
            const top = this.undoStack[this.undoStack.length - 1];
            if (top && top.html === snap.html) return;
            this.undoStack.push(snap);
            if (this.undoStack.length > this.config.undoLimit + 1) this.undoStack.shift();
            this.redoStack = [];
            this.notifyHistory();
        }

        undo() {
            if (this.undoStack.length <= 1) return;
            const current = this.undoStack.pop();
            this.redoStack.push(current);
            const prev = this.undoStack[this.undoStack.length - 1];
            this.applyState(prev.html);
            this.restoreScroll(prev.scrollTop);
            this.notifyHistory();
        }

        redo() {
            if (!this.redoStack.length) return;
            const next = this.redoStack.pop();
            this.undoStack.push(next);
            this.applyState(next.html);
            this.restoreScroll(next.scrollTop);
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
            // req-039: re-renderizar o esqueleto dos widgets sem mockup (preview não é salvo no snapshot).
            this.rerenderVisibleWidgets();
            try {
                window.parent.postMessage(JSON.stringify({ action: 'c2f-he:dom-changed' }), '*');
            } catch (e) { /* noop */ }
        }

        rerenderVisibleWidgets() {
            document.querySelectorAll('.conn2flow-widget-wrapper').forEach((w) => {
                const slug = w.getAttribute('data-widget-slug');
                const mockup = w.getAttribute('data-widget-mockup') || '';
                if (slug && !mockup.trim()) this.requestWidgetRender(w);
            });
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
                '#html-editor-selection-overlay,#html-editor-selection-breadcrumb,#html-editor-selection-children,' +
                '#html-editor-breadcrumb-hover-overlay,#html-editor-tailwind-styler,#html-editor-wrap-menu,' +
                '#html-editor-parent-highlight-overlay,#html-editor-insert-ghost,' +
                '#html-editor-modal,.conn2flow-dnd-placeholder,.html-editor-container,.ui.dimmer.modals')
                .forEach((el) => el.remove());

            // req-044 §1.4: variáveis voltam como texto puro [[widgets#signature]] SEM re-escape
            // das entidades. Como container.innerHTML re-escaparia `>`/`&` de um text node, usamos
            // tokens alfanuméricos (não escapáveis) substituídos na string final.
            const varReplacements = [];
            if (widgetsToComments) {
                container.querySelectorAll('.conn2flow-widget-wrapper').forEach((wrapper) => {
                    // req-044 §1: a assinatura é resolvida pelo mapa em memória (data-widget-id),
                    // nunca a partir de um atributo escapado no DOM.
                    const id = wrapper.getAttribute('data-widget-id');
                    const meta = id ? this.widgetsMap[id] : null;
                    const signature = (meta && meta.signature) ? meta.signature :
                        ((wrapper.getAttribute('data-widget-type') || '') +
                            '->render({"grupo_slug": "' + (wrapper.getAttribute('data-widget-slug') || '') + '"})');
                    const isVariable = meta ? !!meta.isVariable
                        : wrapper.getAttribute('data-widget-variable') === 'true';

                    if (isVariable) {
                        const token = '__C2F_WVAR_' + varReplacements.length + '__';
                        varReplacements.push({ token: token, text: '[[widgets#' + signature + ']]' });
                        wrapper.parentNode.replaceChild(document.createTextNode(token), wrapper);
                        return;
                    }

                    // req-039: salvar o MOCKUP original (não o preview renderizado que está no inner).
                    const inner = wrapper.querySelector('.conn2flow-widget-inner');
                    const innerHtml = wrapper.hasAttribute('data-widget-mockup')
                        ? wrapper.getAttribute('data-widget-mockup')
                        : (inner ? inner.innerHTML : '');
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
            let out = container.innerHTML.trim();
            varReplacements.forEach((r) => { out = out.split(r.token).join(r.text); });
            return out;
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
