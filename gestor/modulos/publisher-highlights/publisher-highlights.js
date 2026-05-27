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

    initManualItemsDropdown($publisher.val() || '');
    toggleManualWrapper();
    toggleOrderByWrapper();
    toggleTemplateOptionsWrapper();

    // ===== Listeners

    $publisher.on('change', function () {
        var pid = $(this).val();
        availablePublisherFields = [];
        renderPublisherFields();

        // Limpar seleção atual e reconfigurar dropdown manual para o novo publisher
        schema.selected_items = [];
        resetManualItemsDropdown(pid);

        if (pid) loadPublisher(pid);
    });

    $template.on('change', function () {
        var tid = $(this).val();
        availableItemVars = [];
        renderItemVars();
        toggleTemplateOptionsWrapper();
        if (tid) loadTemplate(tid);
    });

    $('#rule').on('change', function () {
        schema.rule = $(this).val();
        toggleManualWrapper();
        toggleOrderByWrapper();
    });

    $('#count').on('change input', function () {
        var v = parseInt($(this).val(), 10);
        schema.count = isNaN(v) || v < 1 ? 1 : v;
    });

    $('#order_by').on('change', function () {
        schema.order_by = $(this).val() || 'date_desc';
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

                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(dados.html || '');
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(dados.css || '');
                if (typeof window.html_editor_refresh_preview === 'function') window.html_editor_refresh_preview();

                // Publicar variáveis do template para o html-editor (alvo publisher-highlights)
                if (typeof window.publisher_highlights_update_target_variables === 'function') {
                    window.publisher_highlights_update_target_variables(availableItemVars);
                }
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Manual items dropdown (Fomantic UI multiple search selection)

    function initManualItemsDropdown(publisher_id) {
        var $sel = $('#selected_items');
        if ($sel.length === 0) return;

        // Configurar Fomantic com apiSettings — query dinâmica filtrada pelo publisher selecionado
        $sel.dropdown({
            apiSettings: {
                url: gestor.raiz + gestor.moduloCaminho + '/',
                method: 'POST',
                data: {
                    opcao: gestor.moduloOpcao,
                    ajax: 'sim',
                    ajaxOpcao: 'publisher-pages-search'
                },
                beforeSend: function (settings) {
                    settings.data.params = {
                        publisher_id: $publisher.val() || '',
                        q: settings.urlData ? (settings.urlData.query || '') : ''
                    };
                    return settings;
                },
                onResponse: function (response) {
                    return response;
                }
            },
            saveRemoteData: false,
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
            }
        });

        // Pré-hidratar com os slugs salvos (resolve nomes via AJAX publisher-pages-fetch)
        if (publisher_id && schema.selected_items && schema.selected_items.length > 0) {
            hydrateManualItemsDropdown(publisher_id, schema.selected_items);
        }
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
        // Manter a configuração; o publisher_id atual será lido em beforeSend a partir do select.
    }

    // ===== Rendering

    function renderItemVars() {
        var $list = $('#available-fields-list').empty();
        if (availableItemVars.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-template', 'Nenhuma variável do modelo') + '</div>');
        } else {
            availableItemVars.forEach(function (v) {
                var mapped = schema.variable_mapping[v.id];
                var $btn = $('<div class="ui basic small button item-var" data-var="' + v.id + '" style="margin-bottom:6px;margin-right:6px;"></div>')
                    .text('@[[item#' + v.id + ']]@')
                    .append(mapped ? ' <i class="exchange icon"></i> <span class="ui teal label">' + mapped + '</span>' : '');
                $list.append($btn);
            });
        }
        renderLinkedVars();
    }

    function renderPublisherFields() {
        var $list = $('#missing-fields-list').empty();
        if (availablePublisherFields.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-publisher', 'Nenhum campo do publicador') + '</div>');
        } else {
            availablePublisherFields.forEach(function (f) {
                var $btn = $('<div class="ui basic small button publisher-field" data-field="' + f.id + '" style="margin-bottom:6px;margin-right:6px;"></div>')
                    .text(f.name + ' (' + f.id + ')');
                $list.append($btn);
            });
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
                .html('@[[item#' + varName + ']]@ <i class="exchange icon"></i> <span class="ui teal label">' + fieldName + '</span> <i class="delete icon" data-unlink="' + varName + '"></i>');
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
    });

    $(document).on('click', '[data-unlink]', function (e) {
        e.stopPropagation();
        var varName = $(this).data('unlink');
        delete schema.variable_mapping[varName];
        renderItemVars();
    });

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
});
