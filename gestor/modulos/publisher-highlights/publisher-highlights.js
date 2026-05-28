/**
 * publisher-highlights.js
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
                    scheduleWidgetPreview(true);
                    break;
                case 'hep-editor':
                    window.contentPageTabHandler();
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

    var schema = (typeof publisher_highlights_initial_schema !== 'undefined' && publisher_highlights_initial_schema) ? publisher_highlights_initial_schema : {
        rule: 'latest',
        count: 4,
        order_by: 'date_desc',
        selected_items: [],
        variable_mapping: {}
    };

    if (!schema.variable_mapping) schema.variable_mapping = {};
    if (!Array.isArray(schema.selected_items)) schema.selected_items = [];
    if (!schema.order_by) schema.order_by = 'date_desc';

    var availableItemVars = []; // [{id:'titulo'}, ...]   extraídas do template HTML
    var availablePublisherFields = []; // [{id:'titulo', name:'Título', type:'text'}, ...]

    // ===== Hidratar inputs com o schema atual

    $('#rule').val(schema.rule || 'latest');
    $('#count').val(schema.count || 4);
    $('#order_by').val(schema.order_by || 'date_desc');

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

    // ===== Inicialização

    var $publisher = $('select[name="publisher_id"]');
    var $template = $('select[name="template_id"]');

    if ($publisher.val()) loadPublisher($publisher.val());
    if ($template.val()) loadTemplate($template.val());
    else setTimeout(function () { scheduleWidgetPreview(true); }, 600);

    initManualItemsDropdown($publisher.val() || '');
    toggleManualWrapper();
    toggleOrderByWrapper();
    toggleTemplateOptionsWrapper();

    // req-008 item 2: ao clicar na aba externa de Pré-Visualização, garantir refresh.
    $(document).on('click', '.menuConteudoDestaque .item[data-tab="hep-preview"]', function () {
        scheduleWidgetPreview(true);
    });

    // req-008 item 2: hook global usado pelo html-editor-interface.js ao detectar
    // mudança no conteúdo do CodeMirror HTML (e por extensão CSS).
    window.updatedCodeMirrorHtml = function () { scheduleWidgetPreview(false); };

    // ===== Listeners

    $publisher.on('change', function () {
        var pid = $(this).val();
        availablePublisherFields = [];
        renderPublisherFields();

        // Limpar seleção atual e reconfigurar dropdown manual para o novo publisher
        schema.selected_items = [];
        resetManualItemsDropdown(pid);

        if (pid) loadPublisher(pid);
        scheduleWidgetPreview(false);
    });

    $template.on('change', function () {
        var tid = $(this).val();
        availableItemVars = [];
        renderItemVars();
        syncEditorVariables();
        toggleTemplateOptionsWrapper();
        if (tid) loadTemplate(tid);
        else scheduleWidgetPreview(false);
    });

    $('#rule').on('change', function () {
        schema.rule = $(this).val();
        toggleManualWrapper();
        toggleOrderByWrapper();
        scheduleWidgetPreview(false);
    });

    $('#count').on('change input', function () {
        var v = parseInt($(this).val(), 10);
        schema.count = isNaN(v) || v < 1 ? 1 : v;
        scheduleWidgetPreview(false);
    });

    $('#order_by').on('change', function () {
        schema.order_by = $(this).val() || 'date_desc';
        scheduleWidgetPreview(false);
    });

    // Interceptar submit para serializar o schema

    $(document).on('submit', '#_gestor-interface-edit-dados, #_gestor-interface-insert-dados', function () {
        // Garantir consistência com o estado dos inputs
        schema.rule = $('#rule').val() || 'latest';
        schema.count = parseInt($('#count').val(), 10) || 4;
        schema.order_by = $('#order_by').val() || 'date_desc';

        var sel = $('#selected_items').val();
        if (Array.isArray(sel)) {
            schema.selected_items = sel.filter(function (s) { return s !== ''; });
        } else if (typeof sel === 'string' && sel.length > 0) {
            schema.selected_items = sel.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        }

        $('input[name="fields_schema"]').val(JSON.stringify(schema));
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

                // Publicar variáveis do template para o html-editor (alvo publisher-highlights)
                if (typeof window.publisher_highlights_update_target_variables === 'function') {
                    window.publisher_highlights_update_target_variables(availableItemVars);
                }

                // req-007 item 4: prévia ao vivo com dados reais (substitui o refresh com simulação)
                scheduleWidgetPreview(true);
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Manual items dropdown (Fomantic UI multiple search selection)

    // req-008 item 1: inicialização local do dropdown (sem apiSettings). A busca é
    // disparada manualmente via $.ajax debounced ouvindo o input.search interno do Fomantic.

    var manualSearchTimer = null;

    function initManualItemsDropdown(publisher_id) {
        var $sel = $('#selected_items');
        if ($sel.length === 0) return;

        $sel.dropdown({
            forceSelection: false,
            allowAdditions: false,
            fullTextSearch: true,
            preserveHTML: false,
            onChange: function (value) {
                if (typeof value === 'string') {
                    schema.selected_items = value.length ? value.split(',') : [];
                } else if (Array.isArray(value)) {
                    schema.selected_items = value;
                }
                scheduleWidgetPreview(false);
            }
        });

        // Conectar input.search interno (gerado pelo Fomantic) ao AJAX manual debounced.
        var $search = $sel.parent().find('input.search');
        $search.off('input.hepSearch keyup.hepSearch').on('input.hepSearch keyup.hepSearch', function () {
            var q = $(this).val();
            if (manualSearchTimer) clearTimeout(manualSearchTimer);
            manualSearchTimer = setTimeout(function () {
                manualItemsSearch(q);
            }, 250);
        });

        // Disparo inicial (lista vazia ou pré-carregada) e pré-hidratação dos selecionados.
        if (publisher_id) {
            if (schema.selected_items && schema.selected_items.length > 0) {
                hydrateManualItemsDropdown(publisher_id, schema.selected_items);
            }
            manualItemsSearch('');
        }
    }

    function manualItemsSearch(q) {
        var $sel = $('#selected_items');
        if ($sel.length === 0) return;

        var publisher_id = $publisher.val() || '';
        if (!publisher_id) return;

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'publisher-pages-search',
                publisher_id: publisher_id,
                q: q,
                params: { publisher_id: publisher_id, q: q }
            },
            dataType: 'json',
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') return;

                var results = dados.results || [];
                var keepIds = (schema.selected_items || []).slice();

                // Manter apenas options selecionadas (preservando ordem/seleção atual);
                // remover demais options para depois injetar os resultados retornados.
                $sel.find('option').each(function () {
                    var val = $(this).attr('value');
                    if (keepIds.indexOf(val) === -1) $(this).remove();
                });

                // Mapear os IDs já presentes para evitar duplicatas
                var present = {};
                $sel.find('option').each(function () { present[$(this).attr('value')] = true; });

                results.forEach(function (r) {
                    if (!r || !r.value) return;
                    if (present[r.value]) return;
                    $sel.append('<option value="' + r.value + '">' + $('<div>').text(r.name || r.value).html() + '</option>');
                });

                $sel.dropdown('refresh');
                if (keepIds.length > 0) $sel.dropdown('set selected', keepIds);
            }
        });
    }

    function hydrateManualItemsDropdown(publisher_id, ids) {
        var req = $.extend(true, {}, ajaxDefault, {
            data: $.extend({}, ajaxDefault.data, { ajaxOpcao: 'publisher-pages-fetch', params: { publisher_id: publisher_id, ids: ids } }),
            ajaxOpcao: 'publisher-pages-fetch',
            successCallback: function (dados) {
                var $sel = $('#selected_items');
                $sel.empty();

                // Recriar options preservando a ordem armazenada em schema.selected_items
                var byValue = {};
                (dados.results || []).forEach(function (r) { byValue[r.value] = r.name || r.value; });

                ids.forEach(function (slug) {
                    var name = byValue[slug] || slug;
                    $sel.append('<option value="' + slug + '" selected>' + $('<div>').text(name).html() + '</option>');
                });

                $sel.dropdown('set selected', ids);
            }
        });
        $.ajax(req);
    }

    function resetManualItemsDropdown(publisher_id) {
        var $sel = $('#selected_items');
        if ($sel.length === 0) return;
        $sel.dropdown('clear');
        $sel.empty();
        $sel.dropdown('refresh');
        // Recarregar a lista para o novo publisher selecionado.
        if (publisher_id) manualItemsSearch('');
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
            var $row = $('<div class="ui label" style="margin:2px 0;display:inline-block;"></div>')
                .html('[[item#' + varName + ']] <i class="exchange icon"></i> <span class="ui teal label">' + fieldName + '</span> <i class="delete icon" data-unlink="' + varName + '"></i>');
            $list.append($row);
        });
    }

    // ===== Linking workflow: click variável -> click campo

    var pendingVar = null;

    $(document).on('click', '.item-var', function () {
        pendingVar = $(this).data('var');
        $('.item-var').removeClass('blue').addClass('basic');
        $(this).removeClass('basic').addClass('blue');
    });

    $(document).on('click', '.publisher-field', function () {
        if (!pendingVar) return;
        var fieldId = $(this).data('field');
        schema.variable_mapping[pendingVar] = fieldId;
        pendingVar = null;
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
        if (typeof window.publisher_highlights_update_target_variables !== 'function') return;
        window.publisher_highlights_update_target_variables(availableItemVars);
    }

    // ===== Helpers

    function toggleManualWrapper() {
        if (($('#rule').val() || 'latest') === 'manual') $('#manual-items-wrapper').show();
        else $('#manual-items-wrapper').hide();
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
    // Debounce de 400ms para evitar spam durante edição contínua nos editores.

    var widgetPreviewTimer = null;
    var widgetPreviewLastSnapshot = null;

    function scheduleWidgetPreview(immediate) {
        if (widgetPreviewTimer) clearTimeout(widgetPreviewTimer);
        widgetPreviewTimer = setTimeout(refreshWidgetPreview, immediate ? 0 : 400);
    }

    function refreshWidgetPreview() {
        widgetPreviewTimer = null;

        var html = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var css = (typeof window.html_editor_get_css === 'function') ? window.html_editor_get_css() : '';

        // Garantir o schema mais recente
        schema.rule = $('#rule').val() || 'latest';
        schema.count = parseInt($('#count').val(), 10) || 4;
        schema.order_by = $('#order_by').val() || 'date_desc';

        var sel = $('#selected_items').val();
        if (Array.isArray(sel)) schema.selected_items = sel.filter(function (s) { return s !== ''; });
        else if (typeof sel === 'string' && sel.length > 0) schema.selected_items = sel.split(',').map(function (s) { return s.trim(); }).filter(Boolean);

        var snapshot = JSON.stringify({
            html: html, css: css,
            publisher_id: $publisher.val() || '',
            schema: schema
        });
        if (snapshot === widgetPreviewLastSnapshot) return;
        widgetPreviewLastSnapshot = snapshot;

        var $iframe = $('#iframe-publisher-highlights-preview');
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
                    fields_schema: JSON.stringify(schema)
                }
            },
            success: function (dados) {
                $dimmer.removeClass('active');
                if (!dados || dados.status !== 'Ok') return;
                if ($iframe.length === 0) return;

                var doc = '<!doctype html><html><head><meta charset="utf-8">';
                // Reaproveitar o CSS framework do iframe interno do html-editor — picsum funciona via URL absoluta
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
});
