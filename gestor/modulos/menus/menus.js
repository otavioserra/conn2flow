/**
 * menus.js
 *
 * Painel de montagem de menus do site (req-015):
 *  - Seleciona um modelo visual de menu (AJAX template-load) e carrega html/css no editor.
 *  - Curadoria de itens do menu via autocomplete de busca de páginas (AJAX pages-search),
 *    com tags reordenáveis (Sortable.js) — menus são livres de publicadores.
 *  - Serializa o schema final (selected_items + template_id) para o input hidden
 *    `fields_schema` antes do submit.
 *
 * Nota: este é o estágio inicial do módulo (req-015). Em lotes futuros o autocomplete será
 * expandido para montar a árvore de menus multi-nível.
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    // Cursores grab/grabbing para a curadoria de itens (mesma UX das tags de destaques).
    (function injectDragCursorStyles() {
        if (document.getElementById('menus-drag-cursor-styles')) return;
        var css = '.drag-label{cursor:grab !important;}'
            + '.drag-label .grip.vertical.icon{cursor:grab !important;}'
            + '.drag-label:active,.drag-label:active *{cursor:grabbing !important;}'
            + '.sortable-drag{cursor:grabbing !important;}'
            + '.remove-tag-btn{cursor:pointer !important;}';
        var style = document.createElement('style');
        style.id = 'menus-drag-cursor-styles';
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

    // Estado compartilhado da curadoria de itens (autocomplete + tags reordenáveis).
    var widgetCodeMirror = null;     // instância do CodeMirror da aba "Código do Widget"
    var manualSearchTimer = null;    // debounce do campo de busca
    var manualSortable = null;       // instância do Sortable do contêiner de tags

    // ===== Inicialização

    var $template = $('select[name="template_id"]');

    if ($template.val()) loadTemplate($template.val());
    else setTimeout(function () { scheduleWidgetPreview(true); }, 600);

    toggleTemplateOptionsWrapper();

    // Hidratar as tags de itens já curados (resolve os nomes reais dos slugs).
    hydrateSelectedLabels();

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

    // Interceptar submit para serializar o schema

    $('.ui.form').on('submit', function () {
        // req-010 padrão: persistir template_id dentro do fields_schema (não há coluna dedicada).
        schema.template_id = $('#template_id').val() || '';

        // selected_items é mantido em memória pelas tags e pelo Sortable.
        var out = $.extend(true, {}, schema);

        $('input[name="fields_schema"]').val(JSON.stringify(out));

        return true;
    });

    // ===== AJAX: carregar template (extrai item#X do html + carrega html/css no editor)

    function loadTemplate(template_id) {
        var req = $.extend(true, {}, ajaxDefault, {
            data: $.extend({}, ajaxDefault.data, { ajaxOpcao: 'template-load', params: { template_id: template_id } }),
            ajaxOpcao: 'template-load',
            successCallback: function (dados) {
                // Framework CSS do template (ex: Tailwind) pode ser necessário para renderizar a prévia.
                gestor.html_editor.framework_css = dados.framework_css || null;

                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(dados.html || '');
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(dados.css || '');

                // Prévia ao vivo com dados reais após o editor absorver html/css do template.
                setTimeout(function () { scheduleWidgetPreview(true, true); }, 150);
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Helpers

    function toggleTemplateOptionsWrapper() {
        // Bloco de opções do template só aparece quando há um template selecionado.
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

    // ===== Widget Preview
    // Renderiza a prévia ao vivo do menu com dados reais via AJAX widget-preview.

    var widgetPreviewTimer = null;
    var widgetPreviewLastSnapshot = null;
    var widgetPreviewRetryCount = 0;

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

        var out = $.extend(true, {}, schema);

        var snapshot = JSON.stringify({
            html: html, css: css,
            schema: out
        });

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
                    // Remove Tailwind CDN warnings
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

    // ===== Curadoria de itens: autocomplete de busca Ajax + tags reordenáveis
    // A fonte da verdade é schema.selected_items (lista ordenada de slugs de páginas).
    // Cada adição/remoção/reordenação atualiza o array e dispara o preview.

    function isPtBr() {
        return (typeof gestor !== 'undefined' && gestor.language === 'pt-br');
    }

    function manualNoResultsMsg() {
        return isPtBr() ? 'Nenhum item encontrado' : 'No results found.';
    }

    function hideManualSuggestions() {
        $('#search-suggestions-dropdown').hide().empty();
    }

    // req-015 item 1.3: o contêiner de tags só aparece quando houver ao menos um item.
    function toggleSelectedLabelsVisibility() {
        var $container = $('#selected-labels-container');
        if (schema.selected_items && schema.selected_items.length > 0) {
            $container.show();
        } else {
            $container.hide();
        }
    }

    // Monta uma tag (Fomantic UI Label) com handle de arraste e botão de remover.
    function buildSelectedLabel(slug, name) {
        var $label = $('<div class="ui label teal drag-label" style="cursor: grab; display: inline-flex; align-items: center; user-select: none;"></div>')
            .attr('data-id', slug);
        $label.append('<i class="grip vertical icon" style="margin-right: 6px; opacity: 0.6;"></i>');
        $label.append(document.createTextNode(name || slug));
        $label.append('<i class="delete icon remove-tag-btn" style="margin-left: 8px; cursor: pointer;"></i>');
        return $label;
    }

    // Renderiza todas as tags respeitando estritamente a ordem de schema.selected_items.
    function renderSelectedLabels(namesMap) {
        var $container = $('#selected-labels-container');
        if ($container.length === 0) return;

        $container.empty();
        (schema.selected_items || []).forEach(function (slug) {
            var name = (namesMap && namesMap[slug]) ? namesMap[slug] : slug;
            $container.append(buildSelectedLabel(slug, name));
        });
        ensureManualSortable();
        toggleSelectedLabelsVisibility();
    }

    // Inicializa o Sortable uma única vez; relê a ordem física do DOM ao soltar uma tag.
    // req-015 item 1.4: toda a área da tag é arrastável; o filtro evita arraste pelo "x".
    function ensureManualSortable() {
        var el = document.getElementById('selected-labels-container');
        if (!el || typeof Sortable === 'undefined' || manualSortable) return;

        manualSortable = new Sortable(el, {
            animation: 150,
            filter: '.remove-tag-btn',
            onEnd: function () {
                var newOrder = [];
                $('#selected-labels-container .drag-label').each(function () {
                    var id = $(this).attr('data-id');
                    if (id) newOrder.push(id);
                });
                schema.selected_items = newOrder;
                scheduleWidgetPreview(false);
            }
        });
    }

    // Hidratação inicial: resolve os nomes reais dos slugs curados via pages-fetch.
    function hydrateSelectedLabels() {
        var ids = (schema.selected_items || []).slice();

        if (ids.length === 0) {
            renderSelectedLabels({});
            return;
        }

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'pages-fetch',
                params: { ids: ids }
            },
            success: function (dados) {
                var namesMap = {};
                if (dados && dados.status === 'Ok') {
                    (dados.results || []).forEach(function (r) {
                        if (r && r.value) namesMap[r.value] = r.name || r.value;
                    });
                }
                renderSelectedLabels(namesMap);
            },
            error: function () { renderSelectedLabels({}); }
        });
    }

    // Desenha a lista flutuante de sugestões; itens já selecionados ficam desabilitados.
    function renderManualSuggestions(results) {
        var $dropdown = $('#search-suggestions-dropdown');
        if ($dropdown.length === 0) return;

        $dropdown.empty();

        var selected = {};
        (schema.selected_items || []).forEach(function (slug) { selected[slug] = true; });

        var rows = (results || []).filter(function (r) { return r && r.value; });

        if (rows.length === 0) {
            $dropdown.append($('<div class="item" style="padding: 8px 12px; color: #999;"></div>').text(manualNoResultsMsg()));
        } else {
            rows.forEach(function (r) {
                var $item = $('<div class="item" style="padding: 8px 12px; cursor: pointer;"></div>')
                    .attr('data-id', r.value)
                    .attr('data-name', r.name || r.value)
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
                params: { q: query }
            },
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') { renderManualSuggestions([]); return; }
                renderManualSuggestions(dados.results || []);
            },
            error: function () { renderManualSuggestions([]); }
        });
    }

    // Campo de busca com debounce de 300ms (evita requisição a cada caractere).
    $(document).on('input', '#manual_search_input', function () {
        var value = ($(this).val() || '').trim();
        if (manualSearchTimer) clearTimeout(manualSearchTimer);

        if (value === '') { hideManualSuggestions(); return; }

        manualSearchTimer = setTimeout(function () { runManualSearch(value); }, 300);
    });

    // Selecionar uma sugestão: adiciona o slug ao final de selected_items.
    $(document).on('click', '#search-suggestions-dropdown .item', function () {
        var $item = $(this);
        if ($item.hasClass('disabled')) return;

        var slug = $item.attr('data-id');
        var name = $item.attr('data-name') || slug;
        if (!slug || (schema.selected_items || []).indexOf(slug) !== -1) return;

        schema.selected_items.push(slug);
        $('#selected-labels-container').append(buildSelectedLabel(slug, name));
        ensureManualSortable();
        // req-015 item 1.3: ao adicionar a primeira tag, garantir que o contêiner apareça.
        toggleSelectedLabelsVisibility();

        $('#manual_search_input').val('');
        hideManualSuggestions();
        scheduleWidgetPreview(false);
    });

    // Remover uma tag pelo ícone "x".
    $(document).on('click', '#selected-labels-container .remove-tag-btn', function (e) {
        e.stopPropagation();
        var $label = $(this).closest('.drag-label');
        var slug = $label.attr('data-id');

        $label.remove();
        var idx = (schema.selected_items || []).indexOf(slug);
        if (idx !== -1) schema.selected_items.splice(idx, 1);
        // req-015 item 1.3: ao remover a última tag, esconder novamente o contêiner.
        toggleSelectedLabelsVisibility();

        scheduleWidgetPreview(false);
    });

    // Fechar a lista de sugestões ao clicar fora do componente ou pressionar Esc.
    $(document).on('click', function (e) {
        if ($(e.target).closest('.search-autocomplete-wrapper').length === 0) hideManualSuggestions();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) hideManualSuggestions();
    });

    // ===== Aba "Código do Widget" — CodeMirror read-only

    function updateWidgetCodeTab() {
        // Lib pode ainda não estar no escopo global ao abrir a aba; reagendar.
        if (typeof CodeMirror === 'undefined') {
            setTimeout(updateWidgetCodeTab, 100);
            return;
        }

        var $textarea = $('#hep-widget-code');
        if ($textarea.length === 0) return;

        // Dedup: reaproveitar a instância já anexada à textarea ao alternar entre abas.
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

        // Slug do menu: na edição vem de gestor.moduloRegistroId; na inserção, placeholder localizado.
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
