$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    var schema = (typeof forms_search_initial_schema !== 'undefined' && forms_search_initial_schema) ? forms_search_initial_schema : {};
    schema.fields = Array.isArray(schema.fields) ? schema.fields : [];

    var initialHtml = $('textarea.codemirror-html').val() || '';
    var initialCss = $('textarea.codemirror-css').val() || '';
    var previewTimer = null;
    var previewLastSnapshot = null;
    var widgetCodeMirror = null;
    var fieldSortable = null;
    var widgetsToAjax = (typeof gestor !== 'undefined' && gestor.widgetsToAjax) ? gestor.widgetsToAjax : '';

    $('.menuForms .item').tab({
        context: '.forms-search-main-tabs',
        onLoad: function (tabPath) {
            localStorage.setItem(gestor.moduloId + 'formsActiveTab', tabPath);
        }
    });

    function contentFormsTabHandler(tabID) {
        var tab = tabID || localStorage.getItem(gestor.moduloId + 'tabFormContentActive') || 'forms-search-preview';
        if ($('.menuConteudoForm .item[data-tab="' + tab + '"]').length === 0) tab = 'forms-search-preview';

        if (!tabID) $('.menuConteudoForm .item').tab('change tab', tab);

        if (tab === 'forms-search-preview') schedulePreview(true, true);
        if (tab === 'forms-search-editor' && typeof window.contentPageTabHandler === 'function') window.contentPageTabHandler();
        if (tab === 'forms-search-widget') updateWidgetCodeTab();
    }

    $('.menuConteudoForm .item').tab({
        context: '.forms-search-content-tabs',
        onLoad: function (tabPath) {
            localStorage.setItem(gestor.moduloId + 'tabFormContentActive', tabPath);
            contentFormsTabHandler(tabPath);
        }
    });

    var savedTab = localStorage.getItem(gestor.moduloId + 'formsActiveTab');
    if (savedTab && $('.menuForms .item[data-tab="' + savedTab + '"]').length > 0) $('.menuForms .item').tab('change tab', savedTab);

    contentFormsTabHandler();

    $('.ui.checkbox').checkbox();
    $('.ui.dropdown').dropdown();

    hydrateSchema();
    renderFields();
    syncFrameworkFromTemplate();
    setTimeout(function () { schedulePreview(true, true); }, 500);

    var $template = $('#template_id');

    $template.on('change', function () {
        var tid = $(this).val() || '';
        syncFrameworkFromTemplate();

        if (tid.endsWith('-modificado')) {
            if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(initialHtml);
            if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(initialCss);
            setTimeout(function () { schedulePreview(true, true); }, 150);
        } else if (tid) {
            loadTemplate(tid);
        } else {
            schedulePreview(false);
        }
    });

    $('#btn-add-form-field').on('click', function () {
        schema.fields.push({
            type: 'text',
            name: nextFieldName(),
            label: '',
            placeholder: '',
            required: false,
            options: []
        });
        renderFields();
        schedulePreview(false);
    });

    $(document).on('input change', '#form_action', function () {
        updateSchemaFromInputs();
        schedulePreview(false);
    });

    $(document).on('input change', '#forms-search-fields-table input,#forms-search-fields-table select,#forms-search-fields-table textarea', function () {
        updateFieldsFromTable();
        schedulePreview(false);
    });

    $(document).on('change', '.forms-search-field-type', function () {
        var $row = $(this).closest('tr');
        var $options = $row.find('.forms-search-field-options');
        var val = fieldTypeValue($(this));
        if (typeUsesOptions(val)) {
            $options.attr('placeholder', optionsPlaceholder(val)).show();
        } else {
            $options.hide().val('');
        }
        updateFieldsFromTable();
        schedulePreview(false);
    });

    $(document).on('click', '.forms-search-field-remove', function () {
        var idx = parseInt($(this).closest('tr').attr('data-index'), 10);
        if (!isNaN(idx)) schema.fields.splice(idx, 1);
        renderFields();
        schedulePreview(false);
    });

    $(document).on('input', '.forms-search-field-label', function () {
        var $row = $(this).closest('tr');
        var $name = $row.find('.forms-search-field-name');
        if ($name.data('manual')) return;
        $name.val(slugify($(this).val()));
    });

    $(document).on('input', '.forms-search-field-name', function () {
        $(this).data('manual', true);
    });

    window.updatedCodeMirrorHtml = function () { schedulePreview(false); };

    $('.ui.form').on('submit', function () {
        updateSchemaFromInputs();
        updateFieldsFromTable();
        var tid = $('#template_id').val() || '';
        if (tid.endsWith('-modificado')) {
            tid = tid.substring(0, tid.length - 11);
            $('#template_id').val(tid);
        }
        schema.template_id = tid;
        $('input[name="fields_schema"]').val(JSON.stringify(schema));
        return true;
    });

    $(document).on('click', '#btn-copy-forms-search-widget-val', function (e) {
        e.preventDefault();
        copyToClipboard($('#forms-search-widget-val').val() || '', $(this));
    });

    function hydrateSchema() {
        // Mantem a tela correta mesmo antes de uma sincronizacao dos recursos HTML no banco.
        $('#form_action').attr('placeholder', 'pages-index-search/').val(schema.form_action || '');

        if (schema.template_id) {
            var modId = schema.template_id + '-modificado';
            var targetId = $('#template_id option[value="' + modId + '"]').length ? modId : schema.template_id;
            $('#template_id').val(targetId);
            setTimeout(function () { $('#template_id').dropdown('set selected', targetId); }, 50);
        }
        syncHiddenSchema();
    }

    function updateSchemaFromInputs() {
        schema.form_action = $('#form_action').val() || '';
        var tid = $('#template_id').val() || '';
        schema.template_id = tid.endsWith('-modificado') ? tid.substring(0, tid.length - 11) : tid;
        syncHiddenSchema();
    }

    function renderFields() {
        var $tbody = $('#forms-search-fields-table tbody');
        $tbody.empty();

        schema.fields.forEach(function (field, idx) {
            $tbody.append(buildFieldRow(field, idx));
        });

        $tbody.find('.ui.checkbox').checkbox();
        $tbody.find('.ui.dropdown').dropdown();
        ensureSortable();
        syncHiddenSchema();
    }

    function buildFieldRow(field, idx) {
        var type = field.type || 'text';
        var required = field.required ? ' checked' : '';
        var options = Array.isArray(field.options) ? field.options.join('\n') : (field.options || '');
        var optionsStyle = typeUsesOptions(type) ? '' : ' style="display:none;"';
        var placeholder = optionsPlaceholder(type);
        var row = ''
            + '<tr data-index="' + idx + '">'
            + '<td class="center aligned"><i class="grip vertical icon forms-search-field-handle"></i></td>'
            + '<td><input type="text" class="forms-search-field-label" value="' + escapeAttr(field.label || '') + '"></td>'
            + '<td><input type="text" class="forms-search-field-name" value="' + escapeAttr(field.name || '') + '"></td>'
            + '<td><select class="forms-search-field-type ui dropdown">'
            + option('text', 'Text', type) + option('email', 'E-mail', type) + option('tel', 'Phone', type) + option('number', 'Number', type) + option('password', 'Password', type) + option('date', 'Date', type) + option('url', 'URL', type) + option('hidden', 'Hidden', type) + option('textarea', 'Textarea', type) + option('select', 'Select', type) + option('radio', 'Radio', type) + option('checkbox', 'Checkbox', type)
            + '</select></td>'
            + '<td><input type="text" class="forms-search-field-placeholder" value="' + escapeAttr(field.placeholder || '') + '"></td>'
            + '<td><textarea class="forms-search-field-options" rows="2" placeholder="' + escapeAttr(placeholder) + '"' + optionsStyle + '>' + escapeHtml(options) + '</textarea></td>'
            + '<td class="center aligned"><div class="ui checkbox"><input type="checkbox" class="forms-search-field-required"' + required + '><label></label></div></td>'
            + '<td class="center aligned"><button type="button" class="ui icon red basic button forms-search-field-remove"><i class="trash icon"></i></button></td>'
            + '</tr>';
        return row;
    }

    function fieldTypeValue($select) {
        var dropdownValue = '';
        if ($select.hasClass('ui') && typeof $select.dropdown === 'function') {
            dropdownValue = $select.dropdown('get value');
        }
        return dropdownValue || $select.val() || 'text';
    }

    function updateFieldsFromTable() {
        var fields = [];
        $('#forms-search-fields-table tbody tr').each(function () {
            var $row = $(this);
            var options = ($row.find('.forms-search-field-options').val() || '')
                .split(/\r\n|\r|\n/)
                .map(function (v) { return $.trim(v); })
                .filter(function (v) { return v !== ''; });
            fields.push({
                label: $row.find('.forms-search-field-label').val() || '',
                name: $row.find('.forms-search-field-name').val() || '',
                type: fieldTypeValue($row.find('.forms-search-field-type')),
                placeholder: $row.find('.forms-search-field-placeholder').val() || '',
                options: options,
                required: $row.find('.forms-search-field-required').is(':checked')
            });
        });
        schema.fields = fields;
        syncHiddenSchema();
    }

    function ensureSortable() {
        var el = document.querySelector('#forms-search-fields-table tbody');
        if (!el || typeof Sortable === 'undefined' || fieldSortable) return;
        fieldSortable = new Sortable(el, {
            animation: 150,
            handle: '.forms-search-field-handle',
            onEnd: function () {
                updateFieldsFromTable();
                renderFields();
                schedulePreview(false);
            }
        });
    }

    function loadTemplate(template_id) {
        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'template-load',
                params: { template_id: template_id }
            },
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') return;
                gestor.html_editor.framework_css = dados.framework_css || null;
                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(dados.html || '');
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(dados.css || '');
                schedulePreview(true, true);
            }
        });
    }

    function syncFrameworkFromTemplate() {
        var framework = $('#template_id option:selected').data('framework') || '';
        if (typeof gestor !== 'undefined' && gestor.html_editor) gestor.html_editor.framework_css = framework;
    }

    function schedulePreview(immediate, force) {
        if (previewTimer) clearTimeout(previewTimer);
        previewTimer = setTimeout(function () { refreshPreview(!!force); }, immediate ? 0 : 500);
    }

    function refreshPreview(force) {
        previewTimer = null;
        updateSchemaFromInputs();
        updateFieldsFromTable();

        var $iframe = $('#iframe-forms-search-preview');
        if ($iframe.length === 0) return;
        if (typeof window.html_editor_get_html !== 'function' || typeof window.html_editor_get_css !== 'function') {
            setTimeout(function () { schedulePreview(true, true); }, 200);
            return;
        }

        var html = window.html_editor_get_html();
        var css = window.html_editor_get_css();
        var snapshot = JSON.stringify({ html: html, css: css, schema: schema });
        if (!force && snapshot === previewLastSnapshot) return;

        $('.forms-search-preview-dimmer').addClass('active');

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
                    form_id: currentSlug(),
                    fields_schema: JSON.stringify(schema)
                }
            },
            success: function (dados) {
                $('.forms-search-preview-dimmer').removeClass('active');
                if (!dados || dados.status !== 'Ok') return;
                previewLastSnapshot = snapshot;

                var doc;
                var signature = formsSearchWidgetSignature();
                var previewWidgetsToAjax = appendWidgetSignature(widgetsToAjax, signature);
                if (typeof window.previewExternalHtmlConteudo === 'function') {
                    doc = window.previewExternalHtmlConteudo({
                        htmlDoUsuario: dados.html || '',
                        cssDoUsuario: css,
                        framework: (gestor.html_editor && gestor.html_editor.framework_css) ? gestor.html_editor.framework_css : 'fomantic-ui',
                        extraParams: {
                            customScripts: [
                                { src: gestor.raiz + 'forms-search/widget.js?v=' + gestor.versao }
                            ],
                            widgetsToAjax: previewWidgetsToAjax
                        }
                    });
                } else {
                    doc = '<!doctype html><html><head><meta charset="utf-8"><style>' + css + '</style>'
                        + '<script>window.gestor=Object.assign({},window.parent.gestor||{});window.gestor.widgetsToAjax='
                        + JSON.stringify(previewWidgetsToAjax) + ';<\/script>'
                        + '<script src="' + gestor.raiz + 'forms-search/widget.js?v=' + gestor.versao + '"><\/script>'
                        + '</head><body>' + (dados.html || '') + '</body></html>';
                }
                $iframe.attr('srcdoc', doc);
            },
            error: function () { $('.forms-search-preview-dimmer').removeClass('active'); }
        });
    }

    function syncHiddenSchema() {
        $('input[name="fields_schema"]').val(JSON.stringify(schema));
    }

    function updateWidgetCodeTab() {
        if (typeof CodeMirror === 'undefined') {
            setTimeout(updateWidgetCodeTab, 100);
            return;
        }
        var $textarea = $('#forms-search-widget-code');
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

        var slug = currentSlug();
        var innerHtml = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var signature = 'forms-search->render({"form_id": "' + slug + '"})';
        var wrapper = '<!-- widgets#' + signature + ' < -->\n' + innerHtml + '\n<!-- widgets#' + signature + ' > -->';
        widgetCodeMirror.getDoc().setValue(wrapper);
        widgetCodeMirror.refresh();
        $('#forms-search-widget-val').val('[[widgets#' + signature + ']]');
    }

    function currentSlug() {
        if (typeof gestor !== 'undefined' && gestor.moduloRegistroId) return gestor.moduloRegistroId;
        // Na inclusao ainda nao existe um id persistido. O preview precisa de um id funcional,
        // e nao do placeholder documental, porque ele compoe data-form-id, ids ARIA e ajaxWidgets.
        return 'forms-search-preview';
    }

    function formsSearchWidgetSignature() {
        return 'forms-search->render(' + JSON.stringify({ form_id: currentSlug() }) + ')';
    }

    function appendWidgetSignature(current, signature) {
        var list = String(current || '').split('<#;>').filter(function (item) { return item !== ''; });
        if (list.indexOf(signature) === -1) list.push(signature);
        return list.join('<#;>');
    }

    function nextFieldName() {
        return 'field_' + (schema.fields.length + 1);
    }

    function slugify(value) {
        return (value || '')
            .toString()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9_]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function option(value, label, selected) {
        return '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + label + '</option>';
    }

    // Tipos que reaproveitam a caixa "Opções": listas (select/radio/checkbox), valor padrão (hidden)
    // e diretivas de limite (text/textarea: min/max de caracteres; number: min/max/step; date: min/max).
    function typeUsesOptions(type) {
        return ['select', 'radio', 'checkbox', 'hidden', 'text', 'textarea', 'number', 'date'].indexOf(type) !== -1;
    }

    function optionsPlaceholder(type) {
        if (type === 'hidden') return isPtBr() ? 'Valor padrão do campo' : 'Default field value';
        if (type === 'text' || type === 'textarea') return 'min:3\nmax:100';
        if (type === 'number') return 'min:18\nmax:100\nstep:1';
        if (type === 'date') return 'min:2026-01-01\nmax:2026-12-31';
        return isPtBr() ? 'valor:Rótulo\nEx: sp:São Paulo' : 'value:Label\nEx: ny:New York';
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function isPtBr() {
        return (typeof gestor !== 'undefined' && gestor.language === 'pt-br');
    }

    function copyToClipboard(text, $btn) {
        function feedback() {
            var original = $btn.data('original-html');
            if (typeof original === 'undefined') { original = $btn.html(); $btn.data('original-html', original); }
            $btn.html('<i class="check icon"></i> ' + (isPtBr() ? 'Copiado!' : 'Copied!'));
            setTimeout(function () { $btn.html(original); }, 1500);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(feedback, function () { fallbackCopy(text); feedback(); });
        } else {
            fallbackCopy(text);
            feedback();
        }
    }

    function fallbackCopy(text) {
        var temp = document.createElement('textarea');
        temp.value = text;
        temp.setAttribute('readonly', '');
        temp.style.position = 'absolute';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        try { document.execCommand('copy'); } catch (err) { }
        document.body.removeChild(temp);
    }
});
