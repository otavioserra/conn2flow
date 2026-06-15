/**
 * publisher-index.js
 *
 * Painel de curadoria de blocos de destaques:
 *  - Carrega campos do publicador selecionado (AJAX publisher-load)
 *  - Carrega variáveis @[[item#...]]@ do modelo selecionado (AJAX template-load)
 *  - Mapeia variáveis x campos (variable_mapping)
 *  - Regra manual (dropdown múltiplo de páginas) ou latest + order_by + count
 *  - Serializa o schema final para o input hidden `fields_schema` antes do submit
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    // req-015 item 1.4: injetar cursores grab/grabbing para a curadoria manual. Toda a tag é
    // arrastável (grab); ao pressionar, vira grabbing; o "x" de remover mantém o cursor de clique.
    (function injectDragCursorStyles() {
        if (document.getElementById('hep-drag-cursor-styles')) return;
        var css = '.drag-label{cursor:grab !important;}'
            + '.drag-label .grip.vertical.icon{cursor:grab !important;}'
            + '.drag-label:active,.drag-label:active *{cursor:grabbing !important;}'
            + '.sortable-drag{cursor:grabbing !important;}'
            + '.remove-tag-btn{cursor:pointer !important;}';
        var style = document.createElement('style');
        style.id = 'hep-drag-cursor-styles';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    })();

    // ===== Semantic UI

    // req-008 item 2: inicializar abas externas "Pré-Visualização" / "Editor HTML"
    const tabPHContent = 'tabPHContentActive';

    function contentHighlightsTabHandler(tabID = null) {
        const tabActive = localStorage.getItem(gestor.moduloId + tabPHContent);
        const tab = tabID || tabActive;

        if (tab !== null) {
            if (!tabID) $('.menuConteudoDestaque .item').tab('change tab', tab);

            switch (tab) {
                case 'hep-preview':
                    scheduleWidgetPreview(true, true);
                    break;
                case 'hep-editor':
                    window.contentPageTabHandler();
                    break;
                // req-014 item 2.5: reabilitar o CodeMirror da aba "Código do Widget".
                case 'hep-widget':
                    updateWidgetCodeTab();
                    break;
            }
        }
    }

    $('.menuConteudoDestaque .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            contentHighlightsTabHandler(tabPath);
            localStorage.setItem(gestor.moduloId + tabPHContent, tabPath);
        }
    });

    contentHighlightsTabHandler();

    // ===== Estado do schema (re-hidratado a partir do PHP)

    var schema = (typeof publisher_index_initial_schema !== 'undefined' && publisher_index_initial_schema) ? publisher_index_initial_schema : {
        rule: 'latest',
        count: 4,
        order_by: 'date_desc',
        selected_items: [],
        variable_mapping: {},
        items_per_page: 10,
        show_search_input: true,
        show_sorting_select: true,
        show_load_more_btn: true,
        show_metrics: true
    };

    if (!Array.isArray(schema.selected_items)) schema.selected_items = [];
    schema.variable_mapping = (schema.variable_mapping && !Array.isArray(schema.variable_mapping) && typeof schema.variable_mapping === 'object')
        ? schema.variable_mapping
        : {};
    if (!schema.order_by) schema.order_by = 'date_desc';

    // req-028: controles de exibição do índice (paginação, busca e ordenação em runtime).
    schema.items_per_page = parseInt(schema.items_per_page, 10) > 0 ? parseInt(schema.items_per_page, 10) : 10;
    schema.show_search_input = (typeof schema.show_search_input === 'undefined') ? true : !!schema.show_search_input;
    schema.show_sorting_select = (typeof schema.show_sorting_select === 'undefined') ? true : !!schema.show_sorting_select;
    schema.show_load_more_btn = (typeof schema.show_load_more_btn === 'undefined') ? true : !!schema.show_load_more_btn;
    schema.show_metrics = (typeof schema.show_metrics === 'undefined') ? true : !!schema.show_metrics;

    // Cache do HTML/CSS originais carregados do banco no page load (req-026 / DEC-039).
    // Usado para restaurar o editor ao voltar para a opção "-modificado" do dropdown de modelos.
    var initialHtml = $('textarea.codemirror-html').val() || '';
    var initialCss = $('textarea.codemirror-css').val() || '';

    var availableItemVars = []; // [{id:'titulo'}, ...]   extraídas do template HTML
    var availablePublisherFields = []; // [{id:'titulo', name:'Título', type:'text'}, ...]

    // ===== Hidratar inputs com o schema atual

    $('#rule').val(schema.rule || 'latest');
    // req-041 §2.1: o campo #count foi removido do CRUD (redundante com items_per_page).
    $('#order_by').val(schema.order_by || 'date_desc');

    // req-028: hidratar os controles de exibição do índice.
    $('#items_per_page').val(schema.items_per_page || 10);
    $('#show_search_input').prop('checked', !!schema.show_search_input);
    $('#show_sorting_select').prop('checked', !!schema.show_sorting_select);
    $('#show_load_more_btn').prop('checked', !!schema.show_load_more_btn);
    $('#show_metrics').prop('checked', !!schema.show_metrics);

    // req-011 item 2: dropdowns Fomantic UI já renderizaram suas cascas visuais antes desse
    // ponto. `.val()` muda o <select> mas a UI segue mostrando o padrão. Disparar
    // `dropdown('set selected', ...)` num setTimeout reforça o estado visual.
    setTimeout(function () {
        $('#rule').dropdown('set selected', schema.rule || 'latest');
        $('#order_by').dropdown('set selected', schema.order_by || 'date_desc');
    }, 50);

    // req-010 item 1: restaurar template_id a partir do schema (não há coluna dedicada
    // na tabela publisher_index — o valor vive dentro de fields_schema).
    if (schema.template_id) {
        // req-026: se houver a variante "-modificado" no dropdown (registro com código
        // customizado), seleciona-a para preservar o HTML/CSS do banco.
        var modId = schema.template_id + '-modificado';
        var targetId = $('#template_id option[value="' + modId + '"]').length ? modId : schema.template_id;
        $('#template_id').val(targetId);
        setTimeout(function () { $('#template_id').dropdown('set selected', targetId); }, 50);
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

    // req-014 / DEC-021: o dropdown múltiplo do Fomantic UI e o componente
    // PublisherIndexCustomDropdown foram removidos. A curadoria manual agora usa um
    // autocomplete de busca Ajax com tags reordenáveis (Sortable.js) — ver a seção
    // "Curadoria manual" mais abaixo. Estado compartilhado declarado aqui para garantir
    // que esteja inicializado antes do bloco de inicialização da página.
    var widgetCodeMirror = null;     // instância do CodeMirror da aba "Código do Widget"
    var manualSearchTimer = null;    // debounce do campo de busca
    var manualSortable = null;       // instância do Sortable do contêiner de tags
    var manualLabelsHydrated = false; // hidratação inicial das tags (uma vez por carga)

    // ===== Inicialização

    var $publisher = $('select[name="publisher_id"]');
    var $template = $('select[name="template_id"]');

    if ($publisher.val()) loadPublisher($publisher.val());
    var startTid = $template.val();
    // req-027: inicializa o framework CSS de forma síncrona a partir do data-framework da opção selecionada.
    syncFrameworkFromTemplate();
    if (startTid) {
        if (startTid.endsWith('-modificado')) {
            // req-026: mantém o HTML/CSS carregado do banco; não dispara o loadTemplate padrão.
            // req-027: extrai as variáveis [[item#X]] localmente do HTML do banco para popular o mapeamento.
            extractVariablesFromHtml(initialHtml);
            setTimeout(function () { scheduleWidgetPreview(true); }, 600);
        } else {
            loadTemplate(startTid);
        }
    } else {
        setTimeout(function () { scheduleWidgetPreview(true); }, 600);
    }

    toggleManualWrapper();
    toggleOrderByWrapper();
    toggleTemplateOptionsWrapper();

    // req-008 item 2: ao clicar na aba externa de Pré-Visualização, garantir refresh.
    $(document).on('click', '.menuConteudoDestaque .item[data-tab="hep-preview"]', function () {
        scheduleWidgetPreview(true, true);
    });

    // req-008 item 2: hook global usado pelo html-editor-interface.js ao detectar
    // mudança no conteúdo do CodeMirror HTML (e por extensão CSS).
    window.updatedCodeMirrorHtml = function () { scheduleWidgetPreview(false); };

    // ===== Listeners

    $publisher.on('change', function () {
        var pid = $(this).val();
        availablePublisherFields = [];
        renderPublisherFields();

        // req-014: limpar a curadoria manual e as tags ao trocar de publicador.
        schema.selected_items = [];
        $('#manual_search_input').val('');
        hideManualSuggestions();
        renderSelectedLabels({});

        if (pid) loadPublisher(pid);
        scheduleWidgetPreview(false);
    });

    $template.on('change', function () {
        var tid = $(this).val();
        // req-027: re-sincroniza o framework CSS da nova opção antes de qualquer preview.
        syncFrameworkFromTemplate();
        availableItemVars = [];
        renderItemVars();
        syncEditorVariables();
        toggleTemplateOptionsWrapper();
        if (tid) {
            if (tid.endsWith('-modificado')) {
                // req-026: restaura o HTML/CSS original do registro (cacheado do banco) sem AJAX.
                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(initialHtml);
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(initialCss);
                // req-027: re-extrai as variáveis [[item#X]] do HTML restaurado para popular o mapeamento.
                extractVariablesFromHtml(initialHtml);
                setTimeout(function () { scheduleWidgetPreview(true, true); }, 150);
            } else {
                loadTemplate(tid);
            }
        } else {
            scheduleWidgetPreview(false);
        }
        // req-010 item 4: forçar refresh do preview interno do editor (substituição
        // por simulação) — complementa o widget-preview AJAX da aba externa.
        if (typeof window.html_editor_refresh_preview === 'function') {
            setTimeout(function () { window.html_editor_refresh_preview(); }, 600);
        }
    });

    $('#rule').on('change', function () {
        schema.rule = $(this).val();
        toggleManualWrapper();
        toggleOrderByWrapper();
        scheduleWidgetPreview(false);
    });

    // req-041 §2.1: listener de #count removido — o elemento não existe mais no CRUD.

    $('#order_by').on('change', function () {
        schema.order_by = $(this).val() || 'date_desc';
        scheduleWidgetPreview(false);
    });

    // req-028: controles de exibição do índice (itens por página + visibilidade dos controles).
    $('#items_per_page').on('input change', function () {
        var v = parseInt($(this).val(), 10);
        schema.items_per_page = (v > 0) ? v : 10;
        scheduleWidgetPreview(false);
    });
    $('#show_search_input, #show_sorting_select, #show_load_more_btn, #show_metrics').on('change', function () {
        schema.show_search_input = $('#show_search_input').prop('checked');
        schema.show_sorting_select = $('#show_sorting_select').prop('checked');
        schema.show_load_more_btn = $('#show_load_more_btn').prop('checked');
        schema.show_metrics = $('#show_metrics').prop('checked');
        scheduleWidgetPreview(false);
    });

    // Interceptar submit para serializar o schema

    $('.ui.form').on('submit', function () {
        // Garantir consistência com o estado dos inputs
        schema.rule = $('#rule').val() || 'latest';
        // req-041 §2.1: count é vestigial (sem input no CRUD); mantém um valor seguro no schema.
        schema.count = parseInt(schema.count, 10) || 4;
        schema.order_by = $('#order_by').val() || 'date_desc';
        // req-028: sincronizar controles de exibição do índice antes de serializar.
        var ipp = parseInt($('#items_per_page').val(), 10);
        schema.items_per_page = (ipp > 0) ? ipp : 10;
        schema.show_search_input = $('#show_search_input').prop('checked');
        schema.show_sorting_select = $('#show_sorting_select').prop('checked');
        schema.show_load_more_btn = $('#show_load_more_btn').prop('checked');
        schema.show_metrics = $('#show_metrics').prop('checked');
        // req-026: limpar o sufixo "-modificado" do input nativo do template antes de salvar.
        var $tempInput = $('#template_id');
        var tmplVal = $tempInput.val() || '';
        if (tmplVal.endsWith('-modificado')) {
            $tempInput.val(tmplVal.substring(0, tmplVal.length - 11));
        }
        // req-010 item 1: persistir template_id dentro do fields_schema (não há coluna dedicada)
        var tid = $('#template_id').val() || '';
        if (tid.endsWith('-modificado')) {
            tid = tid.substring(0, tid.length - 11);
        }
        schema.template_id = tid;

        // req-014: a curadoria manual vive em schema.selected_items (mantida pelas tags e pelo
        // Sortable). Para regras não-manuais, enviar selected_items vazio via cópia, sem destruir
        // o estado em memória — alternar regras na UI não deve perder a curadoria já montada.
        var out = $.extend(true, {}, schema);
        if (out.rule !== 'manual') out.selected_items = [];

        $('input[name="fields_schema"]').val(JSON.stringify(out));

        return true;
    });

    // ===== AJAX: carregar publisher

    function loadPublisher(publisher_id) {
        var req = $.extend(true, {}, ajaxDefault, {
            data: $.extend({}, ajaxDefault.data, { ajaxOpcao: 'publisher-load', params: { publisher_id: publisher_id } }),
            ajaxOpcao: 'publisher-load',
            successCallback: function (dados) {
                availablePublisherFields = dados.campos || [];
                renderPublisherFields();
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar publicador');
            }
        });
        $.ajax(req);
    }

    // Lê o data-framework da opção de template selecionada e sincroniza a variável global de estilo.
    // Necessário porque registros "-modificado" não disparam loadTemplate (que setaria o framework via AJAX),
    // deixando gestor.html_editor.framework_css indefinido e quebrando o previewer (req-027 / DEC-040).
    function syncFrameworkFromTemplate() {
        var framework = $('#template_id option:selected').data('framework') || '';
        if (typeof gestor !== 'undefined' && gestor.html_editor) {
            gestor.html_editor.framework_css = framework;
        }
    }

    // Extrai as variáveis [[item#X]] diretamente do HTML (client-side) e repopula o painel de
    // mapeamento. Usado em registros "-modificado", cujo HTML vem do banco e não passa pelo
    // template-load AJAX que normalmente preencheria availableItemVars (req-027 / DEC-040).
    // availableItemVars é uma lista de objetos { id }, consumida por renderItemVars/syncEditorVariables.
    function extractVariablesFromHtml(htmlContent) {
        var vars = [];
        var seen = {};
        var regex = /\[\[item#([a-zA-Z0-9_\-]+)\]\]/g;
        var match;
        while ((match = regex.exec(htmlContent || '')) !== null) {
            var varName = match[1];
            if (!seen[varName]) {
                seen[varName] = true;
                vars.push({ id: varName });
            }
        }
        availableItemVars = vars;
        renderItemVars();
        syncEditorVariables();
    }

    // ===== AJAX: carregar template (extrai item#X do html + carrega html/css no editor)

    function loadTemplate(template_id) {
        var req = $.extend(true, {}, ajaxDefault, {
            data: $.extend({}, ajaxDefault.data, { ajaxOpcao: 'template-load', params: { template_id: template_id } }),
            ajaxOpcao: 'template-load',
            successCallback: function (dados) {
                availableItemVars = dados.campos || [];
                renderItemVars();

                // Framework CSS do template (ex: Tailwind) pode ser necessário para renderizar a prévia corretamente, então repassar para o editor aplicar no iframe interno.
                gestor.html_editor.framework_css = dados.framework_css || null;

                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(dados.html || '');
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(dados.css || '');

                // Publicar variáveis do template para o html-editor (alvo publisher-index)
                if (typeof window.publisher_index_update_target_variables === 'function') {
                    window.publisher_index_update_target_variables(availableItemVars);
                }

                // req-007 item 4: prévia ao vivo com dados reais (substitui o refresh com simulação)
                // Força uma nova tentativa após o editor absorver html/css carregados do template.
                setTimeout(function () { scheduleWidgetPreview(true, true); }, 150);
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Rendering

    var DEFAULT_PUBLISHER_FIELD_IDS = ['titulo', 'url', 'data'];

    function renderItemVars() {
        var $list = $('#available-fields-list').empty();
        // req-007 item 2: ocultar variáveis já mapeadas
        var visible = availableItemVars.filter(function (v) {
            return !(v.id in (schema.variable_mapping || {}));
        });
        if (visible.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-template', 'Nenhuma variável do modelo') + '</div>');
        } else {
            visible.forEach(function (v) {
                var $btn = $('<div class="ui basic small button item-var" data-var="' + v.id + '" style="margin-bottom:6px;margin-right:6px;"></div>')
                    .text('[[item#' + v.id + ']]');
                $list.append($btn);
            });
        }
        renderPublisherFields();
        renderLinkedVars();
    }

    function renderPublisherFields() {
        var $list = $('#missing-fields-list').empty();

        if (availablePublisherFields.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-publisher', 'Nenhum campo do publicador') + '</div>');
            return;
        }

        // req-007 item 2+3: ocultar campos já vinculados a alguma variável e separar em padrões (grey) vs dinâmicos (teal)
        var usedFields = {};
        Object.keys(schema.variable_mapping || {}).forEach(function (k) {
            usedFields[schema.variable_mapping[k]] = true;
        });

        var defaults = [];
        var dynamics = [];
        availablePublisherFields.forEach(function (f) {
            if (usedFields[f.id]) return;
            if (DEFAULT_PUBLISHER_FIELD_IDS.indexOf(f.id) !== -1) defaults.push(f);
            else dynamics.push(f);
        });

        function appendButtons(fields, color) {
            fields.forEach(function (f) {
                var $btn = $('<div class="ui basic small button publisher-field ' + color + '" data-field="' + f.id + '" style="margin-bottom:6px;margin-right:6px;"></div>')
                    .text(f.name + ' (' + f.id + ')');
                $list.append($btn);
            });
        }

        if (defaults.length > 0) {
            $list.append('<h6 class="ui sub header" style="margin-top:6px;">' + getMsg('msg-campos-padroes', 'Campos Padrões') + '</h6>');
            appendButtons(defaults, 'grey');
        }
        if (dynamics.length > 0) {
            $list.append('<h6 class="ui sub header" style="margin-top:10px;">' + getMsg('msg-campos-dinamicos', 'Campos Dinâmicos') + '</h6>');
            appendButtons(dynamics, 'teal');
        }
        if (defaults.length === 0 && dynamics.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-todos-campos-vinculados', 'Todos os campos do publicador já estão vinculados') + '</div>');
        }
    }

    function renderLinkedVars() {
        var $list = $('#linked-fields-list').empty();
        var keys = Object.keys(schema.variable_mapping || {});
        if (keys.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-vinculado', 'Nenhuma variável vinculada') + '</div>');
            return;
        }
        keys.forEach(function (varName) {
            var fieldName = schema.variable_mapping[varName];
            var $row = $('<div class="ui label" style="margin:2px 4px;display:inline-block;"></div>')
                .html('[[item#' + varName + ']] <i class="exchange icon"></i> <span class="ui teal label">' + fieldName + '</span> <i class="delete icon" data-unlink="' + varName + '"></i>');
            $list.append($row);
        });
    }

    // ===== Linking workflow: click variável -> click campo

    var itemVar = null;
    var pubVar = null;

    $(document).on('click', '.item-var', function () {
        itemVar = $(this).data('var');
        $('.item-var').removeClass('blue').addClass('basic');
        $(this).removeClass('basic').addClass('blue');
    });

    $(document).on('click', '.publisher-field', function () {
        pubVar = $(this).data('field');
        $('.publisher-field').removeClass('blue').addClass('basic');
        $(this).removeClass('basic').addClass('blue');
    });

    $(document).on('click', '.publisher-field,.item-var', function () {
        if (!itemVar || !pubVar) return;
        schema.variable_mapping[itemVar] = pubVar;
        itemVar = null;
        pubVar = null;
        renderItemVars();
        syncEditorVariables();
        scheduleWidgetPreview(false);
    });

    $(document).on('click', '[data-unlink]', function (e) {
        e.stopPropagation();
        var varName = $(this).data('unlink');
        delete schema.variable_mapping[varName];
        renderItemVars();
        syncEditorVariables();
        scheduleWidgetPreview(false);
    });

    // req-009 item 3: sincronizar aba lateral de variáveis do html-editor com o estado atual
    // do mapeamento. Sempre que houver vínculo/desvinculo, repassar a lista de variáveis ativas
    // (do template) para o editor publicar imediatamente.
    function syncEditorVariables() {
        if (typeof window.publisher_index_update_target_variables !== 'function') return;
        window.publisher_index_update_target_variables(availableItemVars);
    }

    // ===== Helpers

    function toggleManualWrapper() {
        var isManual = ($('#rule').val() || 'latest') === 'manual';

        $('#manual-items-wrapper').toggle(isManual);

        if (!isManual) return;

        // req-014: hidratar as tags na primeira exibição do modo manual (resolve os nomes
        // reais dos slugs já curados). Nas exibições seguintes, só garantir o Sortable ativo.
        if (!manualLabelsHydrated) {
            manualLabelsHydrated = true;
            hydrateSelectedLabels();
        } else {
            ensureManualSortable();
        }
    }

    function toggleOrderByWrapper() {
        if (($('#rule').val() || 'latest') === 'latest') $('#order-by-wrapper').show();
        else $('#order-by-wrapper').hide();
    }

    function toggleTemplateOptionsWrapper() {
        // Item 4: bloco de mapeamento só aparece quando há um template selecionado
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

    function getMsg(cssClass, fallback) {
        var $el = $('#template-skeletons .' + cssClass);
        return ($el.length && $el.text()) ? $el.text() : fallback;
    }

    // ===== Widget Preview (req-007 item 4)
    // Renderiza a prévia ao vivo do widget com dados reais via AJAX widget-preview.
    // Debounce de 600ms para evitar spam durante edição contínua nos editores.

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
        var $iframe = $('#iframe-publisher-index-preview');

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

        // Garantir o schema mais recente
        schema.rule = $('#rule').val() || 'latest';
        // req-041 §2.1: count é vestigial (sem input no CRUD); mantém um valor seguro no schema.
        schema.count = parseInt(schema.count, 10) || 4;
        schema.order_by = $('#order_by').val() || 'date_desc';
        // req-028: sincronizar controles de exibição do índice antes de gerar o preview.
        var ippPrev = parseInt($('#items_per_page').val(), 10);
        schema.items_per_page = (ippPrev > 0) ? ippPrev : 10;
        schema.show_search_input = $('#show_search_input').prop('checked');
        schema.show_sorting_select = $('#show_sorting_select').prop('checked');
        schema.show_load_more_btn = $('#show_load_more_btn').prop('checked');
        schema.show_metrics = $('#show_metrics').prop('checked');

        // req-014: para regra não-manual, enviar selected_items vazio via cópia, preservando
        // schema.selected_items (curadoria mantida pelas tags) intacto em memória.
        var out = $.extend(true, {}, schema);
        if (out.rule !== 'manual') out.selected_items = [];

        var snapshot = JSON.stringify({
            html: html, css: css,
            publisher_id: $publisher.val() || '',
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
                    publisher_id: $publisher.val() || '',
                    fields_schema: JSON.stringify(out)
                }
            },
            success: function (dados) {
                $dimmer.removeClass('active');
                if (!dados || dados.status !== 'Ok') return;
                if ($iframe.length === 0) return;

                widgetPreviewLastSnapshot = snapshot;

                var doc = '';
                if (typeof window.previewExternalHtmlConteudo === 'function') {
                    doc = window.previewExternalHtmlConteudo({
                        htmlDoUsuario: dados.html || '',
                        cssDoUsuario: css,
                        framework: (gestor.html_editor && gestor.html_editor.framework_css) ? gestor.html_editor.framework_css : 'fomantic-ui'
                    });
                } else {
                    doc = '<!doctype html><html><head><meta charset="utf-8">';
                    doc += `<!-- CDN do TailwindCSS v4 -->
                    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>`;
                    doc += '</head><body>' + (dados.html || '') + '</body></html>';
                }

                $iframe.on('load', function () { $dimmer.removeClass('active'); });
                $iframe.attr('srcdoc', doc);
            },
            error: function () { $dimmer.removeClass('active'); }
        });
    }

    // ===== Curadoria manual: autocomplete de busca Ajax + tags reordenáveis (req-014 / DEC-021)
    // Substitui o dropdown múltiplo do Fomantic UI. A fonte da verdade é schema.selected_items
    // (lista ordenada de slugs). Cada adição/remoção/reordenação atualiza o array e dispara o preview.

    function isPtBr() {
        return (typeof gestor !== 'undefined' && gestor.language === 'pt-br');
    }

    function manualNoResultsMsg() {
        return isPtBr() ? 'Nenhum item encontrado' : 'No results found.';
    }

    function hideManualSuggestions() {
        $('#search-suggestions-dropdown').hide().empty();
    }

    // Monta uma tag (Fomantic UI Label) com handle de arraste e botão de remover.
    function buildSelectedLabel(slug, name) {
        var $label = $('<div class="ui label teal drag-label" style="cursor: grab; display: inline-flex; align-items: center; user-select: none;"></div>')
            .attr('data-id', slug);
        $label.append('<i class="grip vertical icon" style="margin-right: 6px; opacity: 0.6;"></i>');
        // Usar text node para o nome — evita injeção de HTML vindo do nome da publicação.
        $label.append(document.createTextNode(name || slug));
        $label.append('<i class="delete icon remove-tag-btn" style="margin-left: 8px; cursor: pointer;"></i>');
        return $label;
    }

    // req-015 item 1.3: o contêiner cinza de tags só deve aparecer quando houver ao menos um
    // item selecionado. Vazio, ele fica oculto para não exibir a área pontilhada em branco.
    function toggleSelectedLabelsVisibility() {
        var $container = $('#selected-labels-container');
        if (schema.selected_items && schema.selected_items.length > 0) {
            $container.show();
        } else {
            $container.hide();
        }
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
    function ensureManualSortable() {
        var el = document.getElementById('selected-labels-container');
        if (!el || typeof Sortable === 'undefined' || manualSortable) return;

        manualSortable = new Sortable(el, {
            animation: 150,
            // req-015 item 1.4: toda a área da tag é arrastável (sem restrição de handle); o
            // filtro impede que o clique no "x" de remover inicie um arraste indevido.
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

    // Hidratação inicial: resolve os nomes reais dos slugs curados via publisher-pages-fetch.
    function hydrateSelectedLabels() {
        var publisherId = $publisher.val() || '';
        var ids = (schema.selected_items || []).slice();

        if (!publisherId || ids.length === 0) {
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
                ajaxOpcao: 'publisher-pages-fetch',
                publisher_id: publisherId,
                params: { publisher_id: publisherId, ids: ids }
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
        var publisherId = $publisher.val() || '';
        if (!publisherId) { hideManualSuggestions(); return; }

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'publisher-pages-search',
                publisher_id: publisherId,
                q: query,
                params: { publisher_id: publisherId, q: query }
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

    // ===== Aba "Código do Widget" — CodeMirror read-only (req-014 item 2.5)

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

        // Slug do destaque: na edição vem de gestor.moduloRegistroId; na inserção, placeholder localizado.
        var slug = (typeof gestor !== 'undefined' && gestor.moduloRegistroId)
            ? gestor.moduloRegistroId
            : (isPtBr() ? '[slug-do-destaque]' : '[highlight-slug]');

        var innerHtml = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var wrapper = '<!-- widgets#publisher-index->render({"grupo_slug": "' + slug + '"}) < -->\n'
            + innerHtml + '\n'
            + '<!-- widgets#publisher-index->render({"grupo_slug": "' + slug + '"}) > -->';

        widgetCodeMirror.getDoc().setValue(wrapper);
        widgetCodeMirror.refresh();
    }
});
