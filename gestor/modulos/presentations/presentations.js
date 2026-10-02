/**
 * presentations.js — tela de adicionar, editar e clonar do módulo `presentations`.
 *
 * O formulário de opções é declarativo: cada campo com `data-schema-key` grava no `fields_schema`
 * pelo caminho indicado (`loop`, `texts.title`), e cada tabela com `data-schema-list` edita uma lista
 * de objetos com as colunas de `data-columns`. A pré-visualização renderiza no servidor e roda o
 * mesmo controlador público que o site.
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    var cfg = (typeof gestor !== 'undefined' && gestor.presentationsAdmin) ? gestor.presentationsAdmin : {};
    var schema = (cfg.schema && typeof cfg.schema === 'object') ? cfg.schema : {};
    var textos = cfg.textos || {};

    var initialHtml = $('textarea.codemirror-html').val() || '';
    var initialCss = $('textarea.codemirror-css').val() || '';
    var previewTimer = null;
    var previewLastSnapshot = null;
    var widgetCodeMirror = null;

    function texto(id, padrao) {
        return (textos && textos[id]) ? textos[id] : (padrao || '');
    }

    function moduloUrl() {
        var url = String(gestor.raiz || '') + String(gestor.moduloCaminho || '').replace(/^\/+/, '');
        url = url.replace(/([^:])\/{2,}/g, '$1/');
        if (url.charAt(url.length - 1) !== '/') url += '/';
        return url;
    }

    // ===== Caminhos no schema

    function lerCaminho(caminho) {
        var partes = String(caminho).split('.');
        var alvo = schema;
        for (var i = 0; i < partes.length; i++) {
            if (alvo === null || typeof alvo !== 'object') return undefined;
            alvo = alvo[partes[i]];
        }
        return alvo;
    }

    function gravarCaminho(caminho, valor) {
        var partes = String(caminho).split('.');
        var alvo = schema;
        for (var i = 0; i < partes.length - 1; i++) {
            if (alvo[partes[i]] === null || typeof alvo[partes[i]] !== 'object' || Array.isArray(alvo[partes[i]])) alvo[partes[i]] = {};
            alvo = alvo[partes[i]];
        }
        alvo[partes[partes.length - 1]] = valor;
    }

    function valorDoCampo($campo) {
        if ($campo.is(':checkbox')) return $campo.is(':checked');
        if ($campo.attr('type') === 'number') {
            var n = parseInt($campo.val(), 10);
            return isNaN(n) ? 0 : n;
        }
        return $campo.val() || '';
    }

    function preencherCampos() {
        $('[data-schema-key]').each(function () {
            var $campo = $(this);
            var valor = lerCaminho($campo.attr('data-schema-key'));
            if ($campo.is(':checkbox')) {
                $campo.prop('checked', valor === true || valor === 'true' || valor === 1 || valor === '1');
            } else if (typeof valor !== 'undefined' && valor !== null) {
                $campo.val(String(valor));
            }
        });
    }

    function lerCampos() {
        $('[data-schema-key]').each(function () {
            gravarCaminho($(this).attr('data-schema-key'), valorDoCampo($(this)));
        });
    }

    // ===== Listas

    function colunasDaLista($tabela) {
        try { return JSON.parse($tabela.attr('data-columns') || '[]'); } catch (e) { return []; }
    }

    function celula(coluna, valor) {
        var $td = $('<td></td>');
        var $campo;
        if (coluna.type === 'checkbox') {
            $campo = $('<input type="checkbox">').prop('checked', valor === true || valor === 'true' || valor === 1 || valor === '1');
            $td.addClass('center aligned');
        } else if (coluna.type === 'textarea') {
            $campo = $('<textarea rows="2"></textarea>').val(valor || '');
        } else {
            $campo = $('<input type="text">').val(valor || '');
        }
        $campo.attr('data-list-column', coluna.key);
        return $td.append($campo);
    }

    function desenharListas() {
        $('[data-schema-list]').each(function () {
            var $tabela = $(this);
            var colunas = colunasDaLista($tabela);
            var itens = lerCaminho($tabela.attr('data-schema-list'));
            var $corpo = $tabela.find('tbody').empty();
            if (!Array.isArray(itens)) itens = [];

            itens.forEach(function (item) {
                var $linha = $('<tr></tr>');
                colunas.forEach(function (coluna) { $linha.append(celula(coluna, item ? item[coluna.key] : '')); });
                $linha.append(
                    $('<td class="center aligned collapsing"></td>').append(
                        $('<button type="button" class="ui icon basic button" data-list-up></button>').attr('title', texto('js-list-up')).append('<i class="arrow up icon"></i>'),
                        $('<button type="button" class="ui icon basic button" data-list-down></button>').attr('title', texto('js-list-down')).append('<i class="arrow down icon"></i>'),
                        $('<button type="button" class="ui icon red basic button" data-list-remove></button>').attr('title', texto('js-list-remove')).append('<i class="trash icon"></i>')
                    )
                );
                $corpo.append($linha);
            });
        });
    }

    function lerListas() {
        $('[data-schema-list]').each(function () {
            var $tabela = $(this);
            var itens = [];
            $tabela.find('tbody tr').each(function () {
                var item = {};
                $(this).find('[data-list-column]').each(function () {
                    item[$(this).attr('data-list-column')] = $(this).is(':checkbox') ? $(this).is(':checked') : ($(this).val() || '');
                });
                itens.push(item);
            });
            gravarCaminho($tabela.attr('data-schema-list'), itens);
        });
    }

    function lerTudo() {
        lerCampos();
        lerListas();
        var tid = $('#template_id').val() || '';
        schema.template_id = tid.endsWith('-modificado') ? tid.substring(0, tid.length - 11) : tid;
        $('input[name="fields_schema"]').val(JSON.stringify(schema));
    }

    $(document).on('click', '[data-schema-list-add]', function () {
        lerListas();
        var caminho = $(this).attr('data-schema-list-add');
        var itens = lerCaminho(caminho);
        if (!Array.isArray(itens)) itens = [];
        itens.push({});
        gravarCaminho(caminho, itens);
        desenharListas();
        schedulePreview(false);
    });

    $(document).on('click', '[data-list-remove],[data-list-up],[data-list-down]', function () {
        var $linha = $(this).closest('tr');
        if ($(this).is('[data-list-remove]')) $linha.remove();
        else if ($(this).is('[data-list-up]')) $linha.prev('tr').before($linha);
        else $linha.next('tr').after($linha);
        lerTudo();
        schedulePreview(false);
    });

    $(document).on('input change', '[data-schema-key],[data-list-column]', function () {
        lerTudo();
        schedulePreview(false);
    });

    // ===== Abas

    $('.menuOpcoesWidget .item').tab({ context: '.widget-options-tabs' });

    function contentTabHandler(tabID) {
        var chave = gestor.moduloId + 'tabContentActive';
        var tab = tabID || localStorage.getItem(chave) || 'hep-preview';
        if ($('.menuConteudoWidget .item[data-tab="' + tab + '"]').length === 0) tab = 'hep-preview';

        if (!tabID) $('.menuConteudoWidget .item').tab('change tab', tab);

        if (tab === 'hep-preview') schedulePreview(true, true);
        if (tab === 'hep-editor' && typeof window.contentPageTabHandler === 'function') window.contentPageTabHandler();
        if (tab === 'hep-widget') updateWidgetCodeTab();
    }

    $('.menuConteudoWidget .item').tab({
        context: '.widget-content-tabs',
        onLoad: function (tabPath) {
            localStorage.setItem(gestor.moduloId + 'tabContentActive', tabPath);
            contentTabHandler(tabPath);
        }
    });

    // Os campos recebem o valor antes de virar componente: o dropdown lê a opção marcada ao iniciar.
    preencherCampos();
    desenharListas();
    $('.ui.checkbox').checkbox();
    $('.ui.dropdown').dropdown();

    if (schema.template_id) {
        var modId = schema.template_id + '-modificado';
        var targetId = $('#template_id option[value="' + modId + '"]').length ? modId : schema.template_id;
        $('#template_id').val(targetId);
        setTimeout(function () { $('#template_id').dropdown('set selected', targetId); }, 50);
    }

    lerTudo();
    syncFrameworkFromTemplate();
    contentTabHandler();
    setTimeout(function () { schedulePreview(true, true); }, 500);

    // ===== Modelo

    $('#template_id').on('change', function () {
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

    window.updatedCodeMirrorHtml = function () { schedulePreview(false); };

    $('.ui.form').on('submit', function () {
        lerTudo();
        var tid = $('#template_id').val() || '';
        if (tid.endsWith('-modificado')) $('#template_id').val(tid.substring(0, tid.length - 11));
        return true;
    });

    $(document).on('click', '#btn-copy-widget-val', function (e) {
        e.preventDefault();
        copyToClipboard($('#hep-widget-val').val() || '', $(this));
    });

    function loadTemplate(template_id) {
        $.ajax({
            type: 'POST',
            url: moduloUrl(),
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'template-load',
                _csrf_token: (window.gestor && gestor.csrfToken) ? gestor.csrfToken : '',
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
        if (typeof gestor !== 'undefined' && gestor.html_editor && framework) gestor.html_editor.framework_css = framework;
    }

    // ===== Pré-visualização

    function schedulePreview(immediate, force) {
        if (previewTimer) clearTimeout(previewTimer);
        previewTimer = setTimeout(function () { refreshPreview(!!force); }, immediate ? 0 : 500);
    }

    function refreshPreview(force) {
        previewTimer = null;
        lerTudo();

        var $iframe = $('#iframe-widget-preview');
        if ($iframe.length === 0) return;
        if (typeof window.html_editor_get_html !== 'function' || typeof window.html_editor_get_css !== 'function') {
            setTimeout(function () { schedulePreview(true, true); }, 200);
            return;
        }

        var html = window.html_editor_get_html();
        var css = window.html_editor_get_css();
        var snapshot = JSON.stringify({ html: html, css: css, schema: schema });
        if (!force && snapshot === previewLastSnapshot) return;

        $('.hep-preview-dimmer').addClass('active');

        $.ajax({
            type: 'POST',
            url: moduloUrl(),
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'widget-preview',
                _csrf_token: (window.gestor && gestor.csrfToken) ? gestor.csrfToken : '',
                params: { html: html, css: css, fields_schema: JSON.stringify(schema) }
            },
            success: function (dados) {
                $('.hep-preview-dimmer').removeClass('active');
                if (!dados || dados.status !== 'Ok') return;
                previewLastSnapshot = snapshot;

                var doc;
                if (typeof window.previewExternalHtmlConteudo === 'function') {
                    doc = window.previewExternalHtmlConteudo({
                        htmlDoUsuario: dados.html || '',
                        cssDoUsuario: css,
                        framework: (gestor.html_editor && gestor.html_editor.framework_css) ? gestor.html_editor.framework_css : 'tailwindcss'
                    });
                } else {
                    doc = '<!doctype html><html><head><meta charset="utf-8"><style>' + css + '</style></head><body>' + (dados.html || '') + '</body></html>';
                }
                // O controlador público do widget roda dentro da pré-visualização.
                if (cfg.widgetScript) {
                    doc = (doc.indexOf('</body>') !== -1) ? doc.replace('</body>', cfg.widgetScript + '</body>') : doc + cfg.widgetScript;
                }
                $iframe.attr('srcdoc', doc);
            },
            error: function () { $('.hep-preview-dimmer').removeClass('active'); }
        });
    }

    // ===== Código do widget

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
            widgetCodeMirror.setSize('100%', 600);
        }

        var innerHtml = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var signature = 'presentations->render({"grupo_slug": "' + currentSlug() + '"})';
        widgetCodeMirror.getDoc().setValue('<!-- widgets#' + signature + ' < -->\n' + innerHtml + '\n<!-- widgets#' + signature + ' > -->');
        widgetCodeMirror.refresh();
        $('#hep-widget-val').val('[[widgets#' + signature + ']]');
    }

    function currentSlug() {
        if (typeof gestor !== 'undefined' && gestor.moduloRegistroId) return gestor.moduloRegistroId;
        return texto('js-slug-placeholder', '[slug]');
    }

    function copyToClipboard(text, $btn) {
        function feedback() {
            var original = $btn.data('original-html');
            if (typeof original === 'undefined') { original = $btn.html(); $btn.data('original-html', original); }
            $btn.html('<i class="check icon"></i> ' + texto('js-copied'));
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
