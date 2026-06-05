/**
 * galleries.js
 *
 * Painel de montagem de galerias de imagens do site (req-018 / DEC-026):
 *  - Seleciona um modelo visual de galeria (AJAX template-load) e carrega html/css no editor.
 *  - Curadoria de imagens EM LOTE: o botão "Selecionar Imagens do Servidor" abre o modal do
 *    gerenciador de arquivos (admin-arquivos) em iframe. Cada imagem clicada chega via
 *    postMessage e é adicionada à lista — o modal permanece ABERTO para seleção sequencial
 *    (o usuário fecha manualmente). Cada item exibe thumbnail, nome, legenda editável e remover.
 *  - Reordenação por drag-and-drop com Sortable.js (CDN).
 *  - Serializa a lista (selected_items + template_id) no input hidden `fields_schema` antes do
 *    submit e no preview ao vivo (AJAX widget-preview).
 *
 * Contrato de cada item: { id, caminho, imgSrc, nome, legenda }.
 */
$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length === 0 && $('#_gestor-interface-insert-dados').length === 0) return;

    // ===== Estilos da lista de imagens (injetados uma única vez)

    (function injectGalleryStyles() {
        if (document.getElementById('galleries-styles')) return;
        var css = ''
            + '#gallery-items{margin-top:12px;display:flex;flex-direction:column;gap:8px;}'
            + '.gallery-item{display:flex;align-items:center;gap:12px;padding:8px 10px;background:#fff;'
            + 'border:1px solid #e0e0e0;border-radius:4px;}'
            + '.gallery-item.sortable-drag,.gallery-item.sortable-ghost{opacity:0.6;}'
            + '.gallery-item-handle{cursor:grab;opacity:0.6;}'
            + '.gallery-item-handle:active{cursor:grabbing;}'
            + '.gallery-item-thumb{width:64px;height:48px;object-fit:cover;border-radius:3px;background:#f4f4f4;flex:0 0 auto;}'
            + '.gallery-item-body{flex:1;min-width:0;}'
            + '.gallery-item-name{font-size:12px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px;}'
            + '.gallery-item-caption{width:100%;}'
            + '.gallery-item-remove{cursor:pointer;opacity:0.6;color:#db2828;}'
            + '.gallery-item-remove:hover{opacity:1;}'
            + '.gallery-empty{padding:14px;color:#999;font-style:italic;text-align:center;'
            + 'border:1px dashed #ccc;border-radius:4px;background:#fafafa;}';
        var style = document.createElement('style');
        style.id = 'galleries-styles';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    })();

    // ===== Abas externas "Pré-Visualização" / "Editor HTML" / "Código do Widget"

    const tabGalleryContent = 'tabGalleryContentActive';

    function contentGalleriesTabHandler(tabID = null) {
        const tabActive = localStorage.getItem(gestor.moduloId + tabGalleryContent);
        const tab = tabID || tabActive;

        if (tab !== null) {
            if (!tabID) $('.menuConteudoGaleria .item').tab('change tab', tab);

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

    $('.menuConteudoGaleria .item').tab({
        onLoad: function (tabPath) {
            contentGalleriesTabHandler(tabPath);
            localStorage.setItem(gestor.moduloId + tabGalleryContent, tabPath);
        }
    });

    contentGalleriesTabHandler();

    // ===== Estado do schema (re-hidratado a partir do PHP)

    var schema = (typeof galleries_initial_schema !== 'undefined' && galleries_initial_schema) ? galleries_initial_schema : {
        selected_items: [],
        template_id: ''
    };

    if (!Array.isArray(schema.selected_items)) schema.selected_items = [];

    // Fonte da verdade da curadoria: lista de imagens { id, caminho, imgSrc, nome, legenda }.
    var items = schema.selected_items.slice();

    var widgetCodeMirror = null;

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

    // ===== Inicialização

    var $template = $('select[name="template_id"]');

    if ($template.val()) loadTemplate($template.val());
    else setTimeout(function () { scheduleWidgetPreview(true); }, 600);

    toggleTemplateOptionsWrapper();

    renderItems();
    initSortable();

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

    // Interceptar submit para serializar a lista de imagens.
    $('.ui.form').on('submit', function () {
        $('input[name="fields_schema"]').val(JSON.stringify(currentSchemaOut()));
        return true;
    });

    // ===== Seleção de imagens em lote (modal do gerenciador de arquivos)

    var imagepick = (typeof galleries_imagepick !== 'undefined' && galleries_imagepick) ? galleries_imagepick : { url: '', head: '', cancel: '' };
    var imagepickStarted = false;

    $(document).on('mouseup tap', '#btn-select-images', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        var $modal = $('.ui.modal.iframePagina');
        if ($modal.length === 0) { msg_erro_mostrar(isPtBr() ? 'Modal do gerenciador indisponível.' : 'File manager modal unavailable.'); return; }

        if (!imagepickStarted) {
            imagepickStarted = true;
            if (imagepick.head) $modal.find('.header').html(imagepick.head);
            if (imagepick.cancel) $modal.find('.cancel.button').html(imagepick.cancel);
        }

        var iframe = $modal.find('iframe').get(0);
        if (iframe) { try { iframe.contentWindow.document.write('<body></body>'); } catch (err) { } }
        $modal.find('iframe').attr('src', imagepick.url);
        $modal.find('iframe').on('load', function () { $modal.dimmer('hide'); });

        $modal.dimmer('show');
        $modal.modal('show');
    });

    // Recepção das imagens selecionadas no iframe do gerenciador (seleção em LOTE: não fecha o modal).
    window.addEventListener('message', function (e) {
        var data;
        try { data = JSON.parse(e.data); } catch (err) { return; }
        if (!data || (data.moduloId !== 'admin-arquivos' && data.moduloId !== 'arquivos')) return;

        var dados;
        try { dados = JSON.parse(decodeURI(data.data)); } catch (err) { return; }

        if (!dados || !dados.tipo || !/image\//.test(dados.tipo)) {
            msg_erro_mostrar(isPtBr() ? 'O arquivo selecionado não é uma imagem.' : 'The selected file is not an image.');
            return;
        }

        addImage(dados);
        // Importante: NÃO fechar o modal — permitir seleção sequencial de várias imagens.
    });

    // ===== AJAX: carregar template

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

    // ===== Curadoria de imagens

    function addImage(dados) {
        var caminho = dados.caminho || '';
        var id = (dados.id !== undefined && dados.id !== null && dados.id !== '') ? String(dados.id) : ('file_' + Date.now().toString(36));

        // Evitar duplicatas: mesma imagem (por caminho) já presente.
        var exists = items.some(function (it) { return it.caminho && caminho && it.caminho === caminho; });
        if (exists) return;

        items.push({
            id: id,
            caminho: caminho,
            imgSrc: dados.imgSrc || '',
            nome: dados.nome || '',
            legenda: ''
        });

        renderItems();
        serializeAndPreview();
    }

    function indexOfId(id) {
        for (var i = 0; i < items.length; i++) if (String(items[i].id) === String(id)) return i;
        return -1;
    }

    function renderItems() {
        var $list = $('#gallery-items');
        if ($list.length === 0) return;

        $list.empty();

        if (items.length === 0) {
            $list.append($('<div class="gallery-empty"></div>').text(
                isPtBr() ? 'Nenhuma imagem ainda. Clique em "Selecionar Imagens do Servidor".' : 'No images yet. Click "Select Images from Server".'
            ));
            return;
        }

        items.forEach(function (it) { $list.append(buildItemRow(it)); });
    }

    function buildItemRow(it) {
        var $row = $('<div class="gallery-item"></div>').attr('data-id', it.id);

        $row.append('<i class="bars icon gallery-item-handle" title="' + (isPtBr() ? 'Arraste para reordenar' : 'Drag to reorder') + '"></i>');

        var $thumb = $('<img class="gallery-item-thumb">').attr('alt', it.nome || '');
        if (it.imgSrc) $thumb.attr('src', it.imgSrc);
        $row.append($thumb);

        var $body = $('<div class="gallery-item-body"></div>');
        $body.append($('<div class="gallery-item-name"></div>').text(it.nome || it.caminho || ''));

        var $caption = $('<input type="text" class="gallery-item-caption">')
            .attr('placeholder', isPtBr() ? 'Legenda (opcional)' : 'Caption (optional)')
            .val(it.legenda || '');
        $body.append($caption);
        $row.append($body);

        $row.append('<i class="trash alternate icon gallery-item-remove" title="' + (isPtBr() ? 'Remover' : 'Remove') + '"></i>');

        return $row;
    }

    // Editar legenda (debounced).
    var captionTimer = null;
    $(document).on('input', '.gallery-item-caption', function () {
        var id = $(this).closest('.gallery-item').attr('data-id');
        var idx = indexOfId(id);
        if (idx < 0) return;
        items[idx].legenda = $(this).val() || '';
        if (captionTimer) clearTimeout(captionTimer);
        captionTimer = setTimeout(function () { serializeAndPreview(); }, 400);
    });

    // Remover imagem.
    $(document).on('click', '.gallery-item-remove', function () {
        var id = $(this).closest('.gallery-item').attr('data-id');
        var idx = indexOfId(id);
        if (idx < 0) return;
        items.splice(idx, 1);
        renderItems();
        serializeAndPreview();
    });

    // ===== Reordenação drag-and-drop (Sortable.js)

    function initSortable() {
        var el = document.getElementById('gallery-items');
        if (!el || typeof Sortable === 'undefined') {
            if (typeof Sortable === 'undefined') setTimeout(initSortable, 200);
            return;
        }
        Sortable.create(el, {
            handle: '.gallery-item-handle',
            animation: 150,
            onEnd: function () {
                // Relê a ordem física do DOM e reordena o array `items`.
                var novaOrdem = [];
                $('#gallery-items .gallery-item').each(function () {
                    var idx = indexOfId($(this).attr('data-id'));
                    if (idx >= 0) novaOrdem.push(items[idx]);
                });
                if (novaOrdem.length === items.length) items = novaOrdem;
                serializeAndPreview();
            }
        });
    }

    // ===== Widget Preview (prévia ao vivo da galeria com dados reais via AJAX widget-preview)

    var widgetPreviewTimer = null;
    var widgetPreviewLastSnapshot = null;
    var widgetPreviewRetryCount = 0;

    function currentSchemaOut() {
        var out = $.extend(true, {}, schema);
        out.selected_items = items.slice();
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
        var $iframe = $('#iframe-galleries-preview');

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
            : (isPtBr() ? '[slug-da-galeria]' : '[gallery-slug]');

        var innerHtml = (typeof window.html_editor_get_html === 'function') ? window.html_editor_get_html() : '';
        var wrapper = '<!-- widgets#galleries->render({"grupo_slug": "' + slug + '"}) < -->\n'
            + innerHtml + '\n'
            + '<!-- widgets#galleries->render({"grupo_slug": "' + slug + '"}) > -->';

        widgetCodeMirror.getDoc().setValue(wrapper);
        widgetCodeMirror.refresh();
    }
});
