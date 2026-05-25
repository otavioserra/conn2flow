/**
 * publisher-highlights.js (BATCH-009)
 *
 * Painel de curadoria de blocos de destaques:
 *  - Carrega campos do publicador selecionado (AJAX publisher-load)
 *  - Carrega variáveis @[[item#...]]@ do modelo selecionado (AJAX template-load)
 *  - Mapeia variáveis x campos (variable_mapping)
 *  - Regra manual/latest + count + selected_items
 *  - Serializa o schema final para o input hidden `fields_schema` antes do submit
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    // ===== Estado do schema (re-hidratado a partir do PHP)

    var schema = (typeof publisher_highlights_initial_schema !== 'undefined' && publisher_highlights_initial_schema) ? publisher_highlights_initial_schema : {
        rule: 'latest',
        count: 4,
        selected_items: [],
        variable_mapping: {}
    };

    if (!schema.variable_mapping) schema.variable_mapping = {};
    if (!Array.isArray(schema.selected_items)) schema.selected_items = [];

    var availableItemVars = []; // [{id:'titulo'}, ...]   extraídas do template HTML
    var availablePublisherFields = []; // [{id:'titulo', name:'Título', type:'text'}, ...]

    // ===== Hidratar inputs com o schema atual

    $('#rule').val(schema.rule || 'latest');
    $('#count').val(schema.count || 4);
    $('#selected_items').val((schema.selected_items || []).join('\n'));

    toggleManualWrapper();

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

    // ===== Listeners

    $publisher.on('change', function () {
        var pid = $(this).val();
        availablePublisherFields = [];
        renderPublisherFields();
        if (pid) loadPublisher(pid);
    });

    $template.on('change', function () {
        var tid = $(this).val();
        availableItemVars = [];
        renderItemVars();
        if (tid) loadTemplate(tid);
    });

    $('#rule').on('change', function () {
        schema.rule = $(this).val();
        toggleManualWrapper();
    });

    $('#count').on('change input', function () {
        var v = parseInt($(this).val(), 10);
        schema.count = isNaN(v) || v < 1 ? 1 : v;
    });

    $('#selected_items').on('change input', function () {
        schema.selected_items = $(this).val().split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    });

    // Interceptar submit para serializar o schema

    $(document).on('submit', '#_gestor-interface-edit-dados, #_gestor-interface-insert-dados', function () {
        // Garantir consistência com o estado dos inputs
        schema.rule = $('#rule').val() || 'latest';
        schema.count = parseInt($('#count').val(), 10) || 4;
        schema.selected_items = ($('#selected_items').val() || '').split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);

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
            },
            successNotOkCallback: function (dados) {
                msg_erro_mostrar((dados && dados.message) ? dados.message : 'Erro ao carregar modelo');
            }
        });
        $.ajax(req);
    }

    // ===== Rendering

    function renderItemVars() {
        var $list = $('#available-fields-list').empty();
        if (availableItemVars.length === 0) {
            $list.append('<div class="ui basic mini label">' + getMsg('msg-nenhum-campo-template', 'Nenhuma variável do modelo') + '</div>');
        } else {
            availableItemVars.forEach(function (v) {
                var mapped = schema.variable_mapping[v.id];
                var $btn = $('<div class="ui basic small button item-var" data-var="' + v.id + '"></div>')
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
                var $btn = $('<div class="ui basic small button publisher-field" data-field="' + f.id + '"></div>')
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
    // (modelo simples; uma futura iteração pode evoluir para drag-and-drop)

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
