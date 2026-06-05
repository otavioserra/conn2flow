/**
 * menus.js
 *
 * Painel de montagem de menus do site (req-015 + req-016):
 *  - Seleciona um modelo visual de menu (AJAX template-load) e carrega html/css no editor.
 *  - Constrói a ÁRVORE de itens do menu com itens tipados (página / link-custom / cabecalho /
 *    link-action / separador), editor drag-and-drop bidimensional estilo WordPress
 *    (arrastar na vertical = ordenar; arrastar na horizontal = indentar/desindentar como
 *    filho), edição inline de rótulo/URL/classes e exclusão recursiva.
 *  - Serializa a árvore (selected_items hierárquico + template_id) para o input hidden
 *    `fields_schema` antes do submit e no preview ao vivo (AJAX widget-preview).
 *
 * O editor de árvore é um componente PRÓPRIO: Pointer Events em JS vanilla (sem jQuery UI /
 * nestedSortable / Sortable.js) e visual Fomantic-UI. Internamente trabalha com uma lista
 * plana de nós com `depth` (modelo WordPress); a conversão flat<->árvore ocorre nas
 * fronteiras (hidratação, submit e preview). Ver DEC-023.
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    var STEP = 24; // px de recuo horizontal por nível de aninhamento

    // ===== Estilos do editor de árvore (injetados uma única vez)

    (function injectTreeStyles() {
        if (document.getElementById('menus-tree-styles')) return;
        var css = ''
            + '#menu-tree{margin-top:12px;}'
            + '.menu-tree-row{display:flex;align-items:center;gap:8px;padding:8px 10px;margin:4px 0;'
            + 'background:#fff;border:1px solid #e0e0e0;border-radius:4px;cursor:pointer;user-select:none;}'
            + '.menu-tree-row.selected{border-color:#2185d0;box-shadow:0 0 0 1px #2185d0 inset;background:#f4f9ff;}'
            + '.menu-tree-row.dragging-block{opacity:0.45;}'
            + '.menu-tree-handle{cursor:grab;opacity:0.6;touch-action:none;}'
            + '.tree-dragging .menu-tree-handle{cursor:grabbing;}'
            + '.menu-tree-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'
            + '.menu-tree-label.sep{color:#999;font-style:italic;}'
            + '.menu-tree-edit,.menu-tree-delete{cursor:pointer;opacity:0.6;}'
            + '.menu-tree-edit:hover,.menu-tree-delete:hover{opacity:1;}'
            + '.menu-tree-placeholder{display:flex;align-items:center;justify-content:center;gap:12px;min-height:38px;'
            + 'margin:4px 0;border:2px dashed #2185d0;border-radius:4px;background:#e8f3ff;color:#2185d0;font-size:13px;'
            + 'font-weight:bold;user-select:none;box-sizing:border-box;}'
            + '.menu-tree-placeholder .ph-arrow{font-size:18px;line-height:1;opacity:0.85;}'
            + '.menu-tree-placeholder .ph-text{letter-spacing:0.3px;}'
            + '.menu-tree-edit-panel{margin:2px 0 8px;padding:10px;}'
            + '.menu-tree-empty{padding:14px;color:#999;font-style:italic;text-align:center;'
            + 'border:1px dashed #ccc;border-radius:4px;background:#fafafa;}';
        var style = document.createElement('style');
        style.id = 'menus-tree-styles';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    })();

    // ===== Abas externas "Pré-Visualização" / "Editor HTML" / "Código do Widget"

    const tabMenuContent = 'tabMenuContentActive';

    function contentMenusTabHandler(tabID = null) {
        const tabActive = localStorage.getItem(gestor.moduloId + tabMenuContent);
        const tab = tabID || tabActive;

        if (tab !== null) {
            if (!tabID) $('.menuConteudoMenu .item').tab('change tab', tab);

            switch (tab) {
                case 'hep-preview':
                    scheduleWidgetPreview(true, true);
                    break;
                case 'hep-editor':
                    window.contentPageTabHandler();
                    break;
                case 'hep-widget':
                    updateWidgetCodeTab();
                    break;
            }
        }
    }

    $('.menuConteudoMenu .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            contentMenusTabHandler(tabPath);
            localStorage.setItem(gestor.moduloId + tabMenuContent, tabPath);
        }
    });

    contentMenusTabHandler();

    // ===== Estado do schema (re-hidratado a partir do PHP)

    var schema = (typeof menus_initial_schema !== 'undefined' && menus_initial_schema) ? menus_initial_schema : {
        selected_items: [],
        template_id: ''
    };

    if (!Array.isArray(schema.selected_items)) schema.selected_items = [];

    // Fonte da verdade do editor: lista plana de nós com `depth`.
    // Nó: { id, type, label, url, page_id, css_classes, depth }
    var treeItems = [];
    var selectedRowId = null;     // item clicado (ponto de inserção)
    var drag = null;              // estado do arraste em andamento

    // ===== Hidratar inputs com o schema atual

    if (schema.template_id) {
        $('#template_id').val(schema.template_id);
        setTimeout(function () { $('#template_id').dropdown('set selected', schema.template_id); }, 50);
    }

    // ===== AJAX padrão

    var ajaxDefault = {
        type: 'POST',
        url: gestor.raiz + gestor.moduloCaminho + '/',
        ajaxOpcao: 'ajaxOpcao',
        data: { opcao: gestor.moduloOpcao, ajax: 'sim' },
        dataType: 'json',
        beforeSend: function () { loadDimmer(true); msg_erro_resetar(false); },
        success: function (dados) {
            if (dados.status === 'Ok' && typeof this.successCallback === 'function') this.successCallback(dados);
            else if (typeof this.successNotOkCallback === 'function') this.successNotOkCallback(dados);
            loadDimmer(false);
        },
        error: function (txt) {
            if (txt.status === 401 && txt.responseJSON && txt.responseJSON.redirect) {
                window.open(gestor.raiz + txt.responseJSON.redirect, '_self');
                return;
            }
            console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:', txt);
            loadDimmer(false);
        },
        successCallback: function () { },
        successNotOkCallback: function () { }
    };

    var widgetCodeMirror = null;     // instância do CodeMirror da aba "Código do Widget"
    var manualSearchTimer = null;    // debounce do campo de busca

    // ===== Inicialização

    var $template = $('select[name="template_id"]');
    var $itemType = $('select[id="item_type"]');

    // Componentes Fomantic do construtor de itens.
    $('.ui.radio.checkbox').checkbox();
    toggleItemTypeFields();

    if ($template.val()) loadTemplate($template.val());
    else setTimeout(function () { scheduleWidgetPreview(true); }, 600);

    toggleTemplateOptionsWrapper();

    // Hidratar a árvore de itens a partir do schema (resolve nomes/urls de páginas).
    initTree();

    // Ao clicar na aba externa de Pré-Visualização, garantir refresh.
    $(document).on('click', '.menuConteudoMenu .item[data-tab="hep-preview"]', function () {
        scheduleWidgetPreview(true, true);
    });

    // Hook global usado pelo html-editor-interface.js ao detectar mudança no CodeMirror HTML.
    window.updatedCodeMirrorHtml = function () { scheduleWidgetPreview(false); };

    // ===== Listeners

    $template.on('change', function () {
        var tid = $(this).val();
        toggleTemplateOptionsWrapper();
        if (tid) loadTemplate(tid);
        else scheduleWidgetPreview(false);
        if (typeof window.html_editor_refresh_preview === 'function') {
            setTimeout(function () { window.html_editor_refresh_preview(); }, 350);
        }
    });

    $itemType.on('change', function () {
        var value = $(this).val();
        toggleItemTypeFields(value);
    });

    // Interceptar submit para serializar a árvore.
    $('.ui.form').on('submit', function () {
        $('input[name="fields_schema"]').val(JSON.stringify(currentSchemaOut()));
        return true;
    });

    // ===== AJAX: carregar template (extrai item#X do html + carrega html/css no editor)

    function loadTemplate(template_id) {
        var req = $.extend(true, {}, ajaxDefault, {
            data: $.extend({}, ajaxDefault.data, { ajaxOpcao: 'template-load', params: { template_id: template_id } }),
            ajaxOpcao: 'template-load',
            successCallback: function (dados) {
                gestor.html_editor.framework_css = dados.framework_css || null;

                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(dados.html || '');
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(dados.css || '');

                setTimeout(function () { scheduleWidgetPreview(true, true); }, 150);
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Helpers gerais

    function isPtBr() {
        return (typeof gestor !== 'undefined' && gestor.language === 'pt-br');
    }

    function toggleTemplateOptionsWrapper() {
        if ($template.val()) $('.template-options-wrapper').show();
        else $('.template-options-wrapper').hide();
    }

    function loadDimmer(show) {
        if (show) $('.template-options-wrapper .dimmer').addClass('active');
        else $('.template-options-wrapper .dimmer').removeClass('active');
    }

    function msg_erro_resetar(show) {
        if (show) $('#error-message').removeClass('hidden');
        else $('#error-message').addClass('hidden');
    }

    function msg_erro_mostrar(mensagem) {
        $('#error-message-content').text(mensagem);
        msg_erro_resetar(true);
    }

    // ===== Widget Preview (prévia ao vivo do menu com dados reais via AJAX widget-preview)

    var widgetPreviewTimer = null;
    var widgetPreviewLastSnapshot = null;
    var widgetPreviewRetryCount = 0;

    function currentSchemaOut() {
        var out = $.extend(true, {}, schema);
        out.selected_items = buildTree(treeItems);
        out.template_id = $('#template_id').val() || schema.template_id || '';
        return out;
    }

    function scheduleWidgetPreview(immediate, force) {
        if (widgetPreviewTimer) clearTimeout(widgetPreviewTimer);
        widgetPreviewTimer = setTimeout(function () {
            refreshWidgetPreview(!!force);
        }, immediate ? 0 : 600);
    }

    function queueWidgetPreviewRetry(delay) {
        if (widgetPreviewRetryCount >= 8) return;

        widgetPreviewRetryCount += 1;
        if (widgetPreviewTimer) clearTimeout(widgetPreviewTimer);
        widgetPreviewTimer = setTimeout(function () {
            refreshWidgetPreview(true);
        }, delay || 150);
    }

    function refreshWidgetPreview(force) {
        widgetPreviewTimer = null;

        var hasTemplate = !!($template.val() || schema.template_id);
        var editorReady = (typeof window.html_editor_get_html === 'function') && (typeof window.html_editor_get_css === 'function');
        var $iframe = $('#iframe-menus-preview');

        if ($iframe.length === 0) {
            queueWidgetPreviewRetry(150);
            return;
        }

        if (!editorReady) {
            if (hasTemplate) queueWidgetPreviewRetry(150);
            return;
        }

        var html = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var css = (typeof window.html_editor_get_css === 'function') ? window.html_editor_get_css() : '';

        if (hasTemplate && html === '' && css === '') {
            queueWidgetPreviewRetry(150);
            return;
        }

        widgetPreviewRetryCount = 0;

        var out = currentSchemaOut();

        var snapshot = JSON.stringify({ html: html, css: css, schema: out });

        if (!force && snapshot === widgetPreviewLastSnapshot) return;

        var $dimmer = $('.hep-preview-dimmer');
        $dimmer.addClass('active');

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'widget-preview',
                params: {
                    html: html,
                    css: css,
                    fields_schema: JSON.stringify(out)
                }
            },
            success: function (dados) {
                $dimmer.removeClass('active');
                if (!dados || dados.status !== 'Ok') return;
                if ($iframe.length === 0) return;

                widgetPreviewLastSnapshot = snapshot;

                var doc = '<!doctype html><html><head><meta charset="utf-8">';
                doc += `<!-- CDN do TailwindCSS -->
                <script>
                    const originalWarn = console.warn;
                    console.warn = function (...args) {
                        if (args[0] && args[0].includes('cdn.tailwindcss.com')) return;
                        originalWarn.apply(console, args);
                    };
                </script><script src="https://cdn.tailwindcss.com"></script>`;
                doc += '</head><body>' + (dados.html || '') + '</body></html>';

                $iframe.on('load', function () { $dimmer.removeClass('active'); });
                $iframe.attr('srcdoc', doc);
            },
            error: function () { $dimmer.removeClass('active'); }
        });
    }

    function serializeAndPreview() { scheduleWidgetPreview(false); }

    // ===== Conversões flat <-> árvore

    function genId() {
        return 'item_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    // Achata uma árvore de objetos em nós planos com `depth`.
    function flattenTree(nodes, depth, out) {
        (nodes || []).forEach(function (n) {
            if (!n || typeof n !== 'object') return;
            out.push({
                id: n.id || genId(),
                type: n.type || 'pagina',
                label: n.label || '',
                url: n.url || '',
                // req-019: alvo do link (`_self`/`_blank`), relevante para o tipo `link-custom`.
                target: n.target || '',
                page_id: n.page_id || '',
                css_classes: n.css_classes || '',
                // req-018: campos do tipo `publicador` (gerador dinâmico de sub-itens).
                publisher_id: n.publisher_id || '',
                publisher_name: n.publisher_name || '',
                count: n.count || '',
                order_by: n.order_by || '',
                depth: depth
            });
            if (n.children && n.children.length) flattenTree(n.children, depth + 1, out);
        });
    }

    // Reconstrói a árvore aninhada a partir da lista plana, usando `depth`.
    function buildTree(flat) {
        var root = [];
        var stack = []; // [{ node, depth }]
        (flat || []).forEach(function (it) {
            var node = {
                id: it.id,
                type: it.type,
                label: it.label || '',
                url: it.url || '',
                css_classes: it.css_classes || '',
                children: []
            };
            if (it.type === 'pagina') node.page_id = it.page_id || '';
            // req-019: o alvo é persistido para link-custom (os demais usam o padrão do widget).
            if (it.type === 'link-custom') node.target = it.target || '_self';
            if (it.type === 'publicador') {
                node.publisher_id = it.publisher_id || '';
                node.publisher_name = it.publisher_name || '';
                node.count = (parseInt(it.count, 10) > 0) ? parseInt(it.count, 10) : 5;
                node.order_by = it.order_by || 'date_desc';
            }

            while (stack.length && stack[stack.length - 1].depth >= it.depth) stack.pop();
            if (stack.length === 0) root.push(node);
            else stack[stack.length - 1].node.children.push(node);

            stack.push({ node: node, depth: it.depth });
        });
        return root;
    }

    // Garante recuos válidos (sem "saltos": depth[i] <= depth[i-1] + 1).
    function normalizeDepths() {
        for (var i = 0; i < treeItems.length; i++) {
            if (i === 0) { treeItems[i].depth = 0; continue; }
            var maxDepth = treeItems[i - 1].depth + 1;
            if (treeItems[i].depth > maxDepth) treeItems[i].depth = maxDepth;
            if (treeItems[i].depth < 0) treeItems[i].depth = 0;
        }
    }

    function indexOfId(id) {
        for (var i = 0; i < treeItems.length; i++) if (treeItems[i].id === id) return i;
        return -1;
    }

    // Tamanho do bloco = o item no índice + todos os seus descendentes (depth maior).
    function getBlockLength(index) {
        var d = treeItems[index].depth;
        var n = 1;
        for (var i = index + 1; i < treeItems.length; i++) {
            if (treeItems[i].depth > d) n++;
            else break;
        }
        return n;
    }

    // ===== Hidratação inicial da árvore

    function initTree() {
        var items = Array.isArray(schema.selected_items) ? schema.selected_items : [];

        if (items.length === 0) { treeItems = []; renderTree(); return; }

        var isLegacy = items.every(function (x) { return typeof x === 'string'; });

        if (isLegacy) {
            // Formato BATCH-015: lista de slugs -> nós `pagina` de nível raiz.
            treeItems = items.map(function (slug) {
                return { id: genId(), type: 'pagina', label: '', url: '', page_id: slug, css_classes: '', depth: 0 };
            });
        } else {
            var flat = [];
            flattenTree(items, 0, flat);
            treeItems = flat;
        }

        renderTree();
        hydratePageNodes();
    }

    // Resolve nome/url reais dos nós tipo `pagina` que ainda não os tenham.
    function hydratePageNodes() {
        var ids = [];
        treeItems.forEach(function (it) {
            if (it.type === 'pagina' && it.page_id && (!it.label || !it.url)) ids.push(it.page_id);
        });
        if (ids.length === 0) return;

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'pages-fetch', params: { ids: ids } },
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') return;
                var map = {};
                (dados.results || []).forEach(function (r) { if (r && r.value) map[r.value] = r; });
                treeItems.forEach(function (it) {
                    if (it.type === 'pagina' && map[it.page_id]) {
                        if (!it.label) it.label = map[it.page_id].name || it.page_id;
                        if (!it.url) it.url = map[it.page_id].url || '';
                    }
                });
                renderTree();
            }
        });
    }

    // ===== Render da árvore

    function typeIcon(type) {
        switch (type) {
            case 'link-custom': return 'linkify';
            case 'cabecalho': return 'heading';
            case 'link-action': return 'bolt';
            case 'separador': return 'minus';
            case 'publicador': return 'rss';
            default: return 'file outline'; // pagina
        }
    }

    function typeName(type) {
        var pt = isPtBr();
        switch (type) {
            case 'link-custom': return pt ? 'Link' : 'Link';
            case 'cabecalho': return pt ? 'Cabeçalho' : 'Header';
            case 'link-action': return pt ? 'Ação' : 'Action';
            case 'separador': return pt ? 'Separador' : 'Separator';
            case 'publicador': return pt ? 'Publicador' : 'Publisher';
            default: return pt ? 'Página' : 'Page';
        }
    }

    function treeRowEl(id) { return $('#menu-tree .menu-tree-row[data-id="' + id + '"]'); }

    function buildTreeRow(it) {
        var $row = $('<div class="menu-tree-row"></div>')
            .attr('data-id', it.id)
            .css('margin-left', (it.depth * STEP) + 'px');

        if (selectedRowId === it.id) $row.addClass('selected');

        $row.append('<i class="bars icon menu-tree-handle" title="' + (isPtBr() ? 'Arraste para ordenar/aninhar' : 'Drag to order/nest') + '"></i>');
        $row.append('<i class="' + typeIcon(it.type) + ' icon"></i>');

        if (it.type === 'separador') {
            // req-019: exibe o rótulo do separador quando houver; senão, o marcador genérico.
            var sepText = it.label ? it.label : ('— ' + typeName(it.type) + ' —');
            $row.append($('<span class="menu-tree-label sep"></span>').text(sepText));
        } else if (it.type === 'publicador') {
            // req-018: barra do nó publicador indica o publicador e o limite de filhos dinâmicos.
            var pubName = it.publisher_name || it.publisher_id || (isPtBr() ? '(sem publicador)' : '(no publisher)');
            var limite = (parseInt(it.count, 10) > 0) ? parseInt(it.count, 10) : 5;
            var pubText = (isPtBr() ? 'Publicador: ' : 'Publisher: ') + pubName
                + (isPtBr() ? ' (limite: ' : ' (limit: ') + limite + ')';
            $row.append($('<span class="menu-tree-label"></span>').text(pubText));
        } else {
            var labelText = it.label || it.page_id || (isPtBr() ? '(sem rótulo)' : '(no label)');
            $row.append($('<span class="menu-tree-label"></span>').text(labelText));
        }

        $row.append($('<span class="ui mini label menu-tree-type"></span>').text(typeName(it.type)));

        // req-019: o separador também é editável (para definir o rótulo opcional).
        $row.append('<i class="edit icon menu-tree-edit" title="' + (isPtBr() ? 'Editar' : 'Edit') + '"></i>');
        $row.append('<i class="trash alternate icon menu-tree-delete" title="' + (isPtBr() ? 'Remover' : 'Remove') + '"></i>');

        return $row;
    }

    function renderTree() {
        var $tree = $('#menu-tree');
        if ($tree.length === 0) return;

        $tree.empty();

        if (treeItems.length === 0) {
            $tree.append($('<div class="menu-tree-empty"></div>').text(
                isPtBr() ? 'Nenhum item ainda. Adicione itens acima.' : 'No items yet. Add items above.'
            ));
            return;
        }

        treeItems.forEach(function (it) { $tree.append(buildTreeRow(it)); });
    }

    // ===== Adição de itens

    function addItem(data) {
        var node = {
            id: genId(),
            type: data.type || 'pagina',
            label: data.label || '',
            url: data.url || '',
            target: data.target || '',
            page_id: data.page_id || '',
            css_classes: data.css_classes || '',
            publisher_id: data.publisher_id || '',
            publisher_name: data.publisher_name || '',
            count: data.count || '',
            order_by: data.order_by || '',
            depth: 0
        };

        var insertAt = treeItems.length;
        if (selectedRowId) {
            var si = indexOfId(selectedRowId);
            if (si >= 0) {
                insertAt = si + getBlockLength(si); // logo após o bloco do selecionado (como irmão)
                node.depth = treeItems[si].depth;
            }
        }

        treeItems.splice(insertAt, 0, node);
        normalizeDepths();
        renderTree();
        serializeAndPreview();
    }

    // Seletor de tipo + campos condicionais
    // req-018 item 2: o `#item_type` voltou a ser um <select> nativo (sem conversão Fomantic),
    // com listener `change` direto. Lemos o valor via `.val()`, com 'pagina' como rede de segurança.
    function currentItemType() {
        return ($itemType.val() || $('#item_type').val() || 'pagina');
    }

    function toggleItemTypeFields(typeArg) {
        var type = typeArg || currentItemType();
        $('#page-type-filter-wrapper').toggle(type === 'pagina');
        $('#manual-search-wrapper').toggle(type === 'pagina');
        // req-019: o rótulo também fica disponível no separador (título/divisor textual opcional).
        $('#field-custom-label').toggle(type === 'link-custom' || type === 'cabecalho' || type === 'link-action' || type === 'separador');
        $('#field-custom-url').toggle(type === 'link-custom' || type === 'link-action');
        // req-019: alvo do link (`_self`/`_blank`) somente para o tipo link-custom.
        $('#field-custom-target').toggle(type === 'link-custom');
        $('#field-custom-css').toggle(type === 'link-action');
        // req-018: campos do tipo `publicador` (publicador / limite / ordenação).
        $('#field-publisher-wrapper').toggle(type === 'publicador');
        $('#btn-add-item-wrapper').toggle(type !== 'pagina');
    }

    // Botão "Adicionar" para tipos não-página (página é adicionada ao clicar na sugestão).
    $(document).on('click', '#btn-add-item', function () {
        var type = currentItemType();
        if (type === 'pagina') return;

        var label = ($('#custom-label').val() || '').trim();
        var url = ($('#custom-url').val() || '').trim();
        var css = ($('#custom-css').val() || '').trim();

        if (type === 'separador') {
            // req-019: rótulo opcional (separador pode ser um título/divisor textual).
            addItem({ type: 'separador', label: label });
        } else if (type === 'cabecalho') {
            if (!label) { msg_erro_mostrar(isPtBr() ? 'Informe o rótulo do cabeçalho.' : 'Enter the header label.'); return; }
            addItem({ type: 'cabecalho', label: label, url: '#' });
        } else if (type === 'link-custom') {
            if (!label || !url) { msg_erro_mostrar(isPtBr() ? 'Informe rótulo e URL.' : 'Enter label and URL.'); return; }
            // req-019: alvo do link (_self por padrão).
            var target = $('#custom-target').val() || '_self';
            addItem({ type: 'link-custom', label: label, url: url, target: target });
        } else if (type === 'link-action') {
            if (!label) { msg_erro_mostrar(isPtBr() ? 'Informe o rótulo.' : 'Enter the label.'); return; }
            addItem({ type: 'link-action', label: label, url: url, css_classes: css });
        } else if (type === 'publicador') {
            var pubId = $('#item_publisher_id').val();
            if (!pubId) { msg_erro_mostrar(isPtBr() ? 'Selecione um publicador.' : 'Select a publisher.'); return; }
            var pubName = ($('#item_publisher_id option:selected').text() || pubId).trim();
            var count = parseInt($('#item_publisher_count').val(), 10);
            if (!(count > 0)) count = 5;
            var orderBy = $('#item_publisher_order_by').val() || 'date_desc';
            addItem({
                type: 'publicador',
                label: pubName,
                url: '#',
                publisher_id: pubId,
                publisher_name: pubName,
                count: count,
                order_by: orderBy
            });
            // Restaura o limite padrão; mantém o publicador/ordenação para adições em série.
            $('#item_publisher_count').val('5');
        }

        $('#custom-label, #custom-url, #custom-css').val('');
        $('#custom-target').val('_self');
        msg_erro_resetar(false);
    });

    // ===== Autocomplete de páginas (tipo `pagina`)

    function currentPageType() { return $('input[name="page_search_type"]:checked').val() || 'pagina'; }

    function manualNoResultsMsg() { return isPtBr() ? 'Nenhuma página encontrada' : 'No pages found.'; }

    function hideManualSuggestions() { $('#search-suggestions-dropdown').hide().empty(); }

    function renderManualSuggestions(results) {
        var $dropdown = $('#search-suggestions-dropdown');
        if ($dropdown.length === 0) return;

        $dropdown.empty();

        var selected = {};
        treeItems.forEach(function (it) { if (it.type === 'pagina' && it.page_id) selected[it.page_id] = true; });

        var rows = (results || []).filter(function (r) { return r && r.value; });

        if (rows.length === 0) {
            $dropdown.append($('<div class="item" style="padding: 8px 12px; color: #999;"></div>').text(manualNoResultsMsg()));
        } else {
            rows.forEach(function (r) {
                var $item = $('<div class="item" style="padding: 8px 12px; cursor: pointer;"></div>')
                    .attr('data-id', r.value)
                    .attr('data-name', r.name || r.value)
                    .attr('data-url', r.url || '')
                    .text(r.name || r.value);

                if (selected[r.value]) {
                    $item.addClass('disabled').css({ opacity: 0.5, cursor: 'not-allowed' });
                }
                $dropdown.append($item);
            });
        }

        $dropdown.show();
    }

    function runManualSearch(query) {
        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'pages-search',
                q: query,
                params: { q: query, tipo: currentPageType() }
            },
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') { renderManualSuggestions([]); return; }
                renderManualSuggestions(dados.results || []);
            },
            error: function () { renderManualSuggestions([]); }
        });
    }

    // Campo de busca com debounce de 300ms.
    $(document).on('input', '#manual_search_input', function () {
        var value = ($(this).val() || '').trim();
        if (manualSearchTimer) clearTimeout(manualSearchTimer);
        if (value === '') { hideManualSuggestions(); return; }
        manualSearchTimer = setTimeout(function () { runManualSearch(value); }, 300);
    });

    // Trocar o tipo de página recarrega a busca atual.
    $(document).on('change', 'input[name="page_search_type"]', function () {
        var v = ($('#manual_search_input').val() || '').trim();
        if (v !== '') runManualSearch(v);
    });

    // Selecionar uma sugestão de página -> cria um nó `pagina` na árvore.
    $(document).on('click', '#search-suggestions-dropdown .item', function () {
        var $item = $(this);
        if ($item.hasClass('disabled')) return;

        var slug = $item.attr('data-id');
        if (!slug) return;

        addItem({
            type: 'pagina',
            page_id: slug,
            label: $item.attr('data-name') || slug,
            url: $item.attr('data-url') || ''
        });

        $('#manual_search_input').val('');
        hideManualSuggestions();
    });

    // Fechar a lista de sugestões ao clicar fora ou pressionar Esc.
    $(document).on('click', function (e) {
        if ($(e.target).closest('.search-autocomplete-wrapper').length === 0) hideManualSuggestions();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) hideManualSuggestions();
    });

    // ===== Interações da árvore: selecionar, editar, remover

    // Selecionar/desmarcar a linha (define o ponto de inserção do próximo item).
    $(document).on('click', '.menu-tree-row', function (e) {
        if ($(e.target).closest('.menu-tree-edit, .menu-tree-delete, .menu-tree-handle').length) return;
        var id = $(this).attr('data-id');
        selectedRowId = (selectedRowId === id) ? null : id;
        renderTree();
    });

    // Remover o item e todos os seus descendentes.
    $(document).on('click', '.menu-tree-delete', function (e) {
        e.stopPropagation();
        var id = $(this).closest('.menu-tree-row').attr('data-id');
        var idx = indexOfId(id);
        if (idx < 0) return;

        treeItems.splice(idx, getBlockLength(idx));
        if (selectedRowId === id) selectedRowId = null;

        normalizeDepths();
        renderTree();
        serializeAndPreview();
    });

    // Abrir painel de edição inline.
    $(document).on('click', '.menu-tree-edit', function (e) {
        e.stopPropagation();
        var id = $(this).closest('.menu-tree-row').attr('data-id');
        openEditPanel(id);
    });

    // req-018: opções do dropdown de publicadores (clonadas do construtor #item_publisher_id).
    function publisherOptionsHtml(selected) {
        var html = '';
        $('#item_publisher_id option').each(function () {
            var v = $(this).attr('value') || '';
            var sel = (v === selected) ? ' selected' : '';
            html += '<option value="' + v + '"' + sel + '>' + $(this).text() + '</option>';
        });
        return html;
    }

    // req-018: opções fixas de ordenação das publicações do publicador (mesma família do
    // publisher-highlights / DEC-017).
    function orderByOptionsHtml(selected) {
        var pt = isPtBr();
        var opts = [
            ['date_desc', pt ? 'Mais recentes primeiro' : 'Newest first'],
            ['date_asc', pt ? 'Mais antigas primeiro' : 'Oldest first'],
            ['title_asc', pt ? 'Título (A→Z)' : 'Title (A→Z)'],
            ['title_desc', pt ? 'Título (Z→A)' : 'Title (Z→A)']
        ];
        return opts.map(function (o) {
            var sel = (o[0] === selected) ? ' selected' : '';
            return '<option value="' + o[0] + '"' + sel + '>' + o[1] + '</option>';
        }).join('');
    }

    // req-019: opções do alvo do link (mesma janela / nova aba).
    function targetOptionsHtml(selected) {
        var pt = isPtBr();
        var opts = [
            ['_self', pt ? 'Mesma janela' : 'Same window'],
            ['_blank', pt ? 'Nova aba' : 'New tab']
        ];
        return opts.map(function (o) {
            var sel = (o[0] === selected) ? ' selected' : '';
            return '<option value="' + o[0] + '"' + sel + '>' + o[1] + '</option>';
        }).join('');
    }

    function openEditPanel(id) {
        $('.menu-tree-edit-panel').remove();

        var idx = indexOfId(id);
        if (idx < 0) return;
        var it = treeItems[idx];

        var $panel = $('<div class="menu-tree-edit-panel ui segment"></div>')
            .attr('data-id', id)
            .css('margin-left', (it.depth * STEP + 24) + 'px');

        function addField(labelText, cls, value) {
            var $f = $('<div class="field" style="margin-bottom:8px;"></div>');
            $f.append($('<label style="font-size:12px;"></label>').text(labelText));
            $f.append($('<input type="text">').addClass(cls).val(value || ''));
            return $f;
        }

        function addSelectField(labelText, cls, optionsHtml, value) {
            var $f = $('<div class="field" style="margin-bottom:8px;"></div>');
            $f.append($('<label style="font-size:12px;"></label>').text(labelText));
            var $sel = $('<select class="ui fluid dropdown" style="display:block;"></select>').addClass(cls).html(optionsHtml);
            $sel.val(value || '');
            $f.append($sel);
            return $f;
        }

        if (it.type === 'separador') {
            // req-019: rótulo opcional do separador (título/divisor textual).
            $panel.append(addField(isPtBr() ? 'Rótulo (opcional)' : 'Label (optional)', 'edit-label', it.label));
        } else {
            $panel.append(addField(isPtBr() ? 'Rótulo' : 'Label', 'edit-label', it.label));
        }
        if (it.type === 'link-custom' || it.type === 'link-action') $panel.append(addField('URL', 'edit-url', it.url));
        // req-019: alvo do link (apenas link-custom).
        if (it.type === 'link-custom') {
            $panel.append(addSelectField(isPtBr() ? 'Abrir link em' : 'Open link in', 'edit-target', targetOptionsHtml(it.target || '_self'), it.target || '_self'));
        }
        if (it.type === 'publicador') {
            // req-018: edição inline do nó publicador (rótulo já adicionado acima).
            $panel.append(addSelectField(isPtBr() ? 'Publicador' : 'Publisher', 'edit-publisher', publisherOptionsHtml(it.publisher_id), it.publisher_id));
            $panel.append(addField(isPtBr() ? 'Limite' : 'Limit', 'edit-count', (parseInt(it.count, 10) > 0 ? it.count : 5)));
            $panel.append(addSelectField(isPtBr() ? 'Ordenação' : 'Sort', 'edit-order-by', orderByOptionsHtml(it.order_by || 'date_desc'), it.order_by || 'date_desc'));
        }
        if (it.type === 'link-action' || it.type === 'publicador') $panel.append(addField(isPtBr() ? 'Classes CSS' : 'CSS classes', 'edit-css', it.css_classes));

        var $btns = $('<div style="margin-top:6px;"></div>');
        $btns.append($('<button type="button" class="ui mini primary button menu-tree-save"></button>').text(isPtBr() ? 'Salvar' : 'Save'));
        $btns.append($('<button type="button" class="ui mini button menu-tree-cancel"></button>').text(isPtBr() ? 'Cancelar' : 'Cancel'));
        $panel.append($btns);

        treeRowEl(id).after($panel);
    }

    $(document).on('click', '.menu-tree-save', function () {
        var $panel = $(this).closest('.menu-tree-edit-panel');
        var id = $panel.attr('data-id');
        var idx = indexOfId(id);
        if (idx < 0) { $panel.remove(); return; }
        var it = treeItems[idx];

        // req-019: o rótulo é salvo para todos os tipos, inclusive o separador (rótulo opcional).
        it.label = ($panel.find('.edit-label').val() || '').trim();
        if (it.type === 'link-custom' || it.type === 'link-action') it.url = ($panel.find('.edit-url').val() || '').trim();
        // req-019: alvo do link (apenas link-custom).
        if (it.type === 'link-custom') it.target = ($panel.find('.edit-target').val() || '_self').trim();
        if (it.type === 'link-action' || it.type === 'publicador') it.css_classes = ($panel.find('.edit-css').val() || '').trim();
        if (it.type === 'publicador') {
            it.publisher_id = ($panel.find('.edit-publisher').val() || '').trim();
            it.publisher_name = ($panel.find('.edit-publisher option:selected').text() || it.publisher_id).trim();
            var c = parseInt($panel.find('.edit-count').val(), 10);
            it.count = (c > 0) ? c : 5;
            it.order_by = ($panel.find('.edit-order-by').val() || 'date_desc').trim();
        }

        $panel.remove();
        renderTree();
        serializeAndPreview();
    });

    $(document).on('click', '.menu-tree-cancel', function () {
        $(this).closest('.menu-tree-edit-panel').remove();
    });

    // ===== Drag-and-drop bidimensional (Pointer Events, vanilla)

    function blockContains(i) { return drag && i >= drag.startIndex && i < drag.startIndex + drag.blockLen; }

    function prevDepthExcludingBlock(insertBefore) {
        for (var i = insertBefore - 1; i >= 0; i--) {
            if (blockContains(i)) continue;
            return treeItems[i].depth;
        }
        return -1;
    }

    // req-018: o nó imediatamente acima do ponto de inserção (ignorando o bloco arrastado).
    // Usado para impedir que itens virem filhos de um nó `publicador` (que gera os próprios filhos).
    function prevItemExcludingBlock(insertBefore) {
        for (var i = insertBefore - 1; i >= 0; i--) {
            if (blockContains(i)) continue;
            return treeItems[i];
        }
        return null;
    }

    function showPlaceholder(insertBefore, depth) {
        var $ph = $('#menu-tree-placeholder');
        if ($ph.length === 0) {
            // req-017 item 1.4: a caixa de drop tem a altura de um item real (não mais achatada)
            // e exibe "Solte o item aqui" entre setas ← / → indicando que arrastar para os lados
            // recua/avança a hierarquia.
            $ph = $('<div id="menu-tree-placeholder" class="menu-tree-placeholder"></div>');
            var dropText = isPtBr() ? 'Solte o item aqui' : 'Drop item here';
            $ph.append('<span class="ph-arrow">←</span>');
            $ph.append($('<span class="ph-text"></span>').text(dropText));
            $ph.append('<span class="ph-arrow">→</span>');
        }
        $ph.css('margin-left', (depth * STEP) + 'px');

        if (insertBefore >= treeItems.length) $('#menu-tree').append($ph);
        else treeRowEl(treeItems[insertBefore].id).before($ph);
    }

    function onPointerMove(e) {
        if (!drag) return;
        drag.moved = true;

        var y = e.clientY;
        var insertBefore = treeItems.length;
        for (var i = 0; i < treeItems.length; i++) {
            if (blockContains(i)) continue;
            var el = treeRowEl(treeItems[i].id).get(0);
            if (!el) continue;
            var rect = el.getBoundingClientRect();
            if (y < rect.top + rect.height / 2) { insertBefore = i; break; }
        }
        drag.targetIndex = insertBefore;

        var deltaDepth = Math.round((e.clientX - drag.startX) / STEP);
        var desired = drag.startDepth + deltaDepth;
        var prevItem = prevItemExcludingBlock(insertBefore);
        var maxD = (prevItem ? prevItem.depth : -1) + 1;
        // req-018: não permitir aninhar como filho de um nó `publicador` (filhos são dinâmicos).
        if (prevItem && prevItem.type === 'publicador') maxD = prevItem.depth;
        if (desired < 0) desired = 0;
        if (desired > maxD) desired = maxD;
        drag.targetDepth = desired;

        showPlaceholder(insertBefore, desired);
    }

    function applyDrag() {
        var start = drag.startIndex, len = drag.blockLen;
        var block = treeItems.slice(start, start + len);
        var target = drag.targetIndex;

        treeItems.splice(start, len);
        if (target > start) target -= len;

        var delta = drag.targetDepth - drag.startDepth;
        block.forEach(function (it) { it.depth = Math.max(0, it.depth + delta); });

        for (var i = 0; i < block.length; i++) treeItems.splice(target + i, 0, block[i]);

        normalizeDepths();
        renderTree();
        serializeAndPreview();
    }

    function onPointerUp() {
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        $('#menu-tree').removeClass('tree-dragging');
        $('#menu-tree-placeholder').remove();
        $('.menu-tree-row').removeClass('dragging-block');

        if (drag && drag.moved) applyDrag();
        drag = null;
    }

    $(document).on('pointerdown', '.menu-tree-handle', function (e) {
        var id = $(this).closest('.menu-tree-row').attr('data-id');
        var startIndex = indexOfId(id);
        if (startIndex < 0) return;

        e.preventDefault();

        var blockLen = getBlockLength(startIndex);
        drag = {
            id: id,
            startIndex: startIndex,
            blockLen: blockLen,
            startDepth: treeItems[startIndex].depth,
            startX: e.clientX,
            targetIndex: startIndex,
            targetDepth: treeItems[startIndex].depth,
            moved: false
        };

        for (var i = startIndex; i < startIndex + blockLen; i++) treeRowEl(treeItems[i].id).addClass('dragging-block');
        $('#menu-tree').addClass('tree-dragging');

        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);
    });

    // ===== Aba "Código do Widget" — CodeMirror read-only

    function updateWidgetCodeTab() {
        if (typeof CodeMirror === 'undefined') {
            setTimeout(updateWidgetCodeTab, 100);
            return;
        }

        var $textarea = $('#hep-widget-code');
        if ($textarea.length === 0) return;

        if (!widgetCodeMirror) {
            var existingWrapper = $textarea.next('.CodeMirror');
            if (existingWrapper.length > 0) widgetCodeMirror = existingWrapper[0].CodeMirror;
        }

        if (!widgetCodeMirror) {
            widgetCodeMirror = CodeMirror.fromTextArea($textarea.get(0), {
                mode: 'htmlmixed',
                htmlMode: true,
                readOnly: true,
                lineNumbers: true,
                lineWrapping: true,
                theme: 'tomorrow-night-bright',
                indentUnit: 4
            });
            widgetCodeMirror.setSize('100%', 800);
        }

        var slug = (typeof gestor !== 'undefined' && gestor.moduloRegistroId)
            ? gestor.moduloRegistroId
            : (isPtBr() ? '[slug-do-menu]' : '[menu-slug]');

        var innerHtml = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var wrapper = '<!-- widgets#menus->render({"grupo_slug": "' + slug + '"}) < -->\n'
            + innerHtml + '\n'
            + '<!-- widgets#menus->render({"grupo_slug": "' + slug + '"}) > -->';

        widgetCodeMirror.getDoc().setValue(wrapper);
        widgetCodeMirror.refresh();
    }
});
