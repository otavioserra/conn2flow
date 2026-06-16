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
            + '.gallery-item-thumb{width:200px;height:140px;object-fit:cover;border-radius:3px;background:#f4f4f4;flex:0 0 auto;}'
            + '.gallery-item-body{flex:1;min-width:0;}'
            + '.gallery-item-name{font-size:12px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px;}'
            + '.gallery-item-caption{width:100%;}'
            + '.gallery-item-remove{cursor:pointer;opacity:0.6;color:#db2828;}'
            + '.gallery-item-remove:hover{opacity:1;}'
            + '.gallery-empty{padding:14px;color:#999;font-style:italic;text-align:center;'
            + 'border:1px dashed #ccc;border-radius:4px;background:#fafafa;}'
            // req-024: painel retrátil "Configurar Link" de cada imagem.
            + '.gallery-item-link-toggle{cursor:pointer;font-size:12px;color:#2185d0;margin-top:6px;display:inline-block;}'
            + '.gallery-item-link-toggle:hover{text-decoration:underline;}'
            + '.gallery-item-link-toggle i{margin-right:4px;}'
            + '.gallery-item-link-fields{margin-top:8px;padding:10px;background:#f9fafb;border:1px solid #e0e0e0;border-radius:4px;}'
            + '.gallery-link-field{margin-bottom:8px;}'
            + '.gallery-link-field label{display:block;font-size:11px;color:#666;margin-bottom:3px;}'
            + '.gallery-link-field select,.gallery-link-field input{width:100%;padding:6px 8px;border:1px solid #ccc;'
            + 'border-radius:4px;font-size:13px;box-sizing:border-box;background:#fff;}'
            // req-025: autocomplete AJAX de páginas (clonado do Menus), isolado por item curado.
            + '.gallery-page-type-filter{display:flex;gap:10px;margin-bottom:6px;font-size:12px;color:#555;}'
            + '.gallery-page-type-filter label{display:inline-flex;align-items:center;gap:3px;cursor:pointer;width:auto;}'
            + '.gallery-page-type-filter input{width:auto;}'
            + '.gallery-page-search-wrapper{position:relative;}'
            + '.gallery-item-link-suggestions{position:absolute;top:100%;left:0;right:0;z-index:30;background:#fff;'
            + 'border:1px solid #ccc;border-top:none;border-radius:0 0 4px 4px;max-height:220px;overflow-y:auto;'
            + 'box-shadow:0 4px 10px rgba(0,0,0,0.08);}'
            + '.gallery-item-link-suggestion{padding:7px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;}'
            + '.gallery-item-link-suggestion:hover{background:#f3f7fb;}'
            + '.gallery-item-link-suggestion.disabled{opacity:0.5;cursor:not-allowed;background:#f7f7f7;}'
            + '.gallery-suggestion-empty{padding:7px 10px;font-size:12px;color:#999;}';
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

    // Cache do HTML/CSS originais carregados do banco no page load (req-026 / DEC-039).
    // Usado para restaurar o editor ao voltar para a opção "-modificado" do dropdown de modelos.
    var initialHtml = $('textarea.codemirror-html').val() || '';
    var initialCss = $('textarea.codemirror-css').val() || '';

    // Fonte da verdade da curadoria: lista de imagens { id, caminho, imgSrc, nome, legenda, link_* }.
    var items = schema.selected_items.slice().map(normalizeItem);

    var widgetCodeMirror = null;

    // ===== Hidratar inputs com o schema atual

    if (schema.template_id) {
        // Se houver a variante "-modificado" no dropdown (registro com código customizado), seleciona-a.
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

    // ===== Inicialização

    var $template = $('select[name="template_id"]');

    var startTid = $template.val();
    // Inicializa o framework CSS de forma síncrona a partir do data-framework da opção selecionada,
    // garantindo que o previewer funcione no load mesmo quando o loadTemplate não é disparado (-modificado).
    syncFrameworkFromTemplate();
    if (startTid) {
        if (startTid.endsWith('-modificado')) {
            // Mantém o HTML/CSS carregado do banco; não dispara o loadTemplate padrão.
            setTimeout(function () { scheduleWidgetPreview(true); }, 600);
        } else {
            loadTemplate(startTid);
        }
    } else {
        setTimeout(function () { scheduleWidgetPreview(true); }, 600);
    }

    toggleTemplateOptionsWrapper();

    renderItems();
    initSortable();

    // req-019: hidratar os controles de exibição e ativar os checkboxes Fomantic + listeners.
    hydrateGalleryControls();
    $('.ui.checkbox').checkbox();
    $(document).on('change', '#gallery-show-arrows, #gallery-show-dots, #gallery-autoplay, #gallery-loop', function () { serializeAndPreview(); });
    $(document).on('input change', '#gallery-autoplay-speed', function () { serializeAndPreview(); });
    $(document).on('input change', '#gallery-height, #gallery-margin-lateral', function () { serializeAndPreview(); });

    // Hook global usado pelo html-editor-interface.js ao detectar mudança no CodeMirror HTML.
    window.updatedCodeMirrorHtml = function () { scheduleWidgetPreview(false); };

    // ===== Listeners

    $template.on('change', function () {
        var tid = $(this).val();
        // Re-sincroniza o framework CSS da nova opção antes de qualquer preview.
        syncFrameworkFromTemplate();
        toggleTemplateOptionsWrapper();
        if (tid) {
            if (tid.endsWith('-modificado')) {
                // Restaura o HTML/CSS original do registro (cacheado do banco) sem AJAX.
                if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(initialHtml);
                if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(initialCss);
                setTimeout(function () { scheduleWidgetPreview(true, true); }, 150);
            } else {
                loadTemplate(tid);
            }
        } else {
            scheduleWidgetPreview(false);
        }
        if (typeof window.html_editor_refresh_preview === 'function') {
            setTimeout(function () { window.html_editor_refresh_preview(); }, 600);
        }
    });

    // Interceptar submit para serializar a lista de imagens.
    $('.ui.form').on('submit', function () {
        // Limpa o sufixo "-modificado" do input nativo para gravar o template_id limpo no banco.
        var $tempInput = $('#template_id');
        var val = $tempInput.val() || '';
        if (val.endsWith('-modificado')) {
            $tempInput.val(val.substring(0, val.length - 11));
        }
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

    // Lê o data-framework da opção de template selecionada e sincroniza a variável global de estilo.
    // Necessário porque registros "-modificado" não disparam loadTemplate (que setaria o framework via AJAX),
    // deixando gestor.html_editor.framework_css indefinido e quebrando o previewer (req-027 / DEC-040).
    function syncFrameworkFromTemplate() {
        var framework = $('#template_id option:selected').data('framework') || '';
        if (typeof gestor !== 'undefined' && gestor.html_editor) {
            gestor.html_editor.framework_css = framework;
        }
    }

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

        items.push(normalizeItem({
            id: id,
            caminho: caminho,
            imgSrc: dados.imgSrc || '',
            nome: dados.nome || '',
            legenda: ''
        }));

        renderItems();
        serializeAndPreview();
    }

    // req-024: garante o contrato de link em cada item (retrocompat com galerias antigas).
    function normalizeItem(it) {
        it = it || {};
        if (!it.link_type) it.link_type = 'nenhum';
        if (it.link_page_id === undefined || it.link_page_id === null) it.link_page_id = '';
        if (it.link_url === undefined || it.link_url === null) it.link_url = '';
        if (!it.link_target) it.link_target = '_self';
        if (it.link_css_classes === undefined || it.link_css_classes === null) it.link_css_classes = '';
        if (it.link_publisher_id === undefined || it.link_publisher_id === null) it.link_publisher_id = '';
        if (!it.link_order_by) it.link_order_by = 'date_desc';
        return it;
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
        $body.append(buildLinkPanel(it));
        $row.append($body);

        $row.append('<i class="trash alternate icon gallery-item-remove" title="' + (isPtBr() ? 'Remover' : 'Remove') + '"></i>');

        return $row;
    }

    // ===== req-024: painel "Configurar Link" de cada imagem

    function linkTypeOptions() {
        return isPtBr()
            ? [['nenhum', 'Nenhum'], ['pagina', 'Página'], ['link-custom', 'Link Customizado'], ['link-css-classes', 'Link com Classe CSS'], ['publicador', 'Última Publicação']]
            : [['nenhum', 'None'], ['pagina', 'Page'], ['link-custom', 'Custom Link'], ['link-css-classes', 'Link with CSS Class'], ['publicador', 'Latest Publication']];
    }

    function orderByOptions() {
        return isPtBr()
            ? [['date_desc', 'Mais recente primeiro'], ['date_asc', 'Mais antiga primeiro'], ['title_asc', 'Título (A-Z)'], ['title_desc', 'Título (Z-A)']]
            : [['date_desc', 'Newest first'], ['date_asc', 'Oldest first'], ['title_asc', 'Title (A-Z)'], ['title_desc', 'Title (Z-A)']];
    }

    function targetOptions() {
        return isPtBr()
            ? [['_self', 'Mesma aba (_self)'], ['_blank', 'Nova aba (_blank)']]
            : [['_self', 'Same tab (_self)'], ['_blank', 'New tab (_blank)']];
    }

    function buildSelect(cls, pairs, selected) {
        var $sel = $('<select></select>').addClass(cls);
        pairs.forEach(function (p) {
            var $opt = $('<option></option>').attr('value', p[0]).text(p[1]);
            if (String(p[0]) === String(selected)) $opt.prop('selected', true);
            $sel.append($opt);
        });
        return $sel;
    }

    function buildDataSelect(cls, list, selected, placeholderTxt) {
        var $sel = $('<select></select>').addClass(cls);
        $sel.append($('<option value=""></option>').text(placeholderTxt));
        (list || []).forEach(function (o) {
            var val = (o.id !== undefined) ? o.id : (o.value !== undefined ? o.value : '');
            var label = (o.name !== undefined) ? o.name : val;
            var $opt = $('<option></option>').attr('value', val).text(label);
            if (String(val) === String(selected)) $opt.prop('selected', true);
            $sel.append($opt);
        });
        return $sel;
    }

    function fieldWrap(labelTxt, $control, fieldClass) {
        var $f = $('<div class="gallery-link-field"></div>');
        if (fieldClass) $f.addClass(fieldClass);
        $f.append($('<label></label>').text(labelTxt));
        $f.append($control);
        return $f;
    }

    function applyLinkFieldVisibility($fields, type) {
        var show = {
            'gallery-link-row-page': (type === 'pagina'),
            'gallery-link-row-url': (type === 'link-custom' || type === 'link-css-classes'),
            'gallery-link-row-css': (type === 'link-css-classes'),
            'gallery-link-row-target': (type === 'link-custom' || type === 'link-css-classes'),
            'gallery-link-row-publisher': (type === 'publicador'),
            'gallery-link-row-order': (type === 'publicador')
        };
        Object.keys(show).forEach(function (cls) {
            $fields.find('.' + cls).css('display', show[cls] ? 'block' : 'none');
        });
    }

    function buildLinkPanel(it) {
        var publishersList = (typeof galleries_publishers !== 'undefined' && galleries_publishers) ? galleries_publishers : [];
        var selectTxt = isPtBr() ? '— selecione —' : '— select —';

        var $toggle = $('<span class="gallery-item-link-toggle"></span>')
            .html('<i class="linkify icon"></i>' + (isPtBr() ? 'Configurar Link' : 'Configure Link'));

        var hasLink = it.link_type && it.link_type !== 'nenhum';
        var $fields = $('<div class="gallery-item-link-fields"></div>').css('display', hasLink ? 'block' : 'none');

        $fields.append(fieldWrap(isPtBr() ? 'Tipo de link' : 'Link type',
            buildSelect('gallery-link-type', linkTypeOptions(), it.link_type || 'nenhum')));

        // req-025 / DEC-038: o dropdown estático de páginas dá lugar ao autocomplete AJAX do Menus,
        // isolado por item curado (filtro de tipo + busca + sugestões flutuantes).
        $fields.append(buildPageAutocompleteField(it));

        $fields.append(fieldWrap('URL',
            $('<input type="text" class="gallery-link-url">').attr('placeholder', 'https://...').val(it.link_url || ''),
            'gallery-link-row-url'));

        $fields.append(fieldWrap(isPtBr() ? 'Classe CSS' : 'CSS class',
            $('<input type="text" class="gallery-link-css">').attr('placeholder', isPtBr() ? 'ex: minha-classe' : 'e.g. my-class').val(it.link_css_classes || ''),
            'gallery-link-row-css'));

        $fields.append(fieldWrap(isPtBr() ? 'Abrir em' : 'Open in',
            buildSelect('gallery-link-target', targetOptions(), it.link_target || '_self'),
            'gallery-link-row-target'));

        $fields.append(fieldWrap(isPtBr() ? 'Publicador' : 'Publisher',
            buildDataSelect('gallery-link-publisher-id', publishersList, it.link_publisher_id, selectTxt),
            'gallery-link-row-publisher'));

        $fields.append(fieldWrap(isPtBr() ? 'Ordenar por' : 'Order by',
            buildSelect('gallery-link-order-by', orderByOptions(), it.link_order_by || 'date_desc'),
            'gallery-link-row-order'));

        applyLinkFieldVisibility($fields, it.link_type || 'nenhum');

        var $wrap = $('<div class="gallery-item-link-wrap"></div>');
        $wrap.append($toggle);
        $wrap.append($fields);
        return $wrap;
    }

    // ===== req-025 / DEC-038: autocomplete AJAX de páginas (link tipo "Página"), por imagem curada
    //
    // Cada linha da curadoria é dinâmica e há várias abertas ao mesmo tempo. Para evitar colisões
    // de seleção jQuery, os rádios de tipo usam `name` único (`gallery_page_search_type_${id}`) e os
    // demais elementos (input de busca, lista de sugestões, hidden do slug) são isolados por
    // `data-id="${id}"` + classes locais.

    function resolvePageNameLocal(slug) {
        var list = (typeof galleries_pages !== 'undefined' && galleries_pages) ? galleries_pages : [];
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].id) === String(slug)) return list[i].name || slug;
        }
        return null;
    }

    function buildPageAutocompleteField(it) {
        var pt = isPtBr();
        var typeName = 'gallery_page_search_type_' + it.id;

        var $f = $('<div class="gallery-link-field gallery-link-row-page"></div>');
        $f.append($('<label></label>').text(pt ? 'Página' : 'Page'));

        // Filtro de tipo de página (Página / Sistema / Ambos), isolado por `name` único.
        var typeOptions = [
            ['pagina', pt ? 'Página' : 'Page'],
            ['sistema', pt ? 'Sistema' : 'System'],
            ['ambos', pt ? 'Ambos' : 'Both']
        ];
        var $filter = $('<div class="gallery-page-type-filter"></div>');
        typeOptions.forEach(function (opt, i) {
            var $lbl = $('<label></label>');
            var $radio = $('<input type="radio" class="gallery-page-type-radio">')
                .attr('name', typeName).attr('value', opt[0]).attr('data-id', it.id);
            if (i === 0) $radio.prop('checked', true);
            $lbl.append($radio).append(document.createTextNode(' ' + opt[1]));
            $filter.append($lbl);
        });
        $f.append($filter);

        // Input de busca + lista flutuante de sugestões + hidden com o slug selecionado.
        var $wrap = $('<div class="gallery-page-search-wrapper"></div>');
        var $input = $('<input type="text" class="gallery-item-link-search">')
            .attr('data-id', it.id)
            .attr('autocomplete', 'off')
            .attr('placeholder', pt ? 'Buscar página...' : 'Search page...');
        var $sugg = $('<div class="gallery-item-link-suggestions"></div>')
            .attr('data-id', it.id).css('display', 'none');
        var $hidden = $('<input type="hidden" class="gallery-link-page-id">').val(it.link_page_id || '');

        $wrap.append($input).append($sugg).append($hidden);
        $f.append($wrap);

        // Hidratação do nome amigável: varredura local em galleries_pages; senão fetch via AJAX.
        if (it.link_page_id) {
            var localName = resolvePageNameLocal(it.link_page_id);
            if (localName) {
                $input.val(localName);
            } else {
                $input.val(it.link_page_id);
                fetchPageName(it.id, it.link_page_id);
            }
        }

        return $f;
    }

    function fetchPageName(itemId, slug) {
        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'pages-fetch', params: { ids: [slug] } },
            success: function (dados) {
                if (!dados || dados.status !== 'Ok') return;
                var found = null;
                (dados.results || []).forEach(function (r) { if (r && String(r.value) === String(slug)) found = r; });
                if (!found) return;
                var $input = $('.gallery-item-link-search[data-id="' + itemId + '"]');
                if ($input.length && !$input.is(':focus')) $input.val(found.name || slug);
                var idx = indexOfId(itemId);
                if (idx >= 0 && !items[idx].link_url && found.url) items[idx].link_url = found.url;
            }
        });
    }

    function galleryPageTypeFor(itemId) {
        return $('input.gallery-page-type-radio[data-id="' + itemId + '"]:checked').val() || 'pagina';
    }

    function hideGalleryPageSuggestions(itemId) {
        $('.gallery-item-link-suggestions[data-id="' + itemId + '"]').hide().empty();
    }

    function renderGalleryPageSuggestions(itemId, results) {
        var $sugg = $('.gallery-item-link-suggestions[data-id="' + itemId + '"]');
        if ($sugg.length === 0) return;

        $sugg.empty();

        var idx = indexOfId(itemId);
        var currentSel = (idx >= 0) ? String(items[idx].link_page_id || '') : '';

        var rows = (results || []).filter(function (r) { return r && r.value; });
        if (rows.length === 0) {
            $sugg.append($('<div class="gallery-suggestion-empty"></div>')
                .text(isPtBr() ? 'Nenhuma página encontrada' : 'No pages found.'));
        } else {
            rows.forEach(function (r) {
                var $i = $('<div class="gallery-item-link-suggestion"></div>')
                    .attr('data-id', r.value)
                    .attr('data-name', r.name || r.value)
                    .attr('data-url', r.url || '')
                    .text(r.name || r.value);
                if (currentSel && String(r.value) === currentSel) $i.addClass('disabled');
                $sugg.append($i);
            });
        }
        $sugg.show();
    }

    function runGalleryPageSearch(itemId, query) {
        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'pages-search',
                q: query,
                params: { q: query, tipo: galleryPageTypeFor(itemId) }
            },
            success: function (dados) {
                renderGalleryPageSuggestions(itemId, (dados && dados.status === 'Ok') ? (dados.results || []) : []);
            },
            error: function () { renderGalleryPageSuggestions(itemId, []); }
        });
    }

    var galleryPageSearchTimer = null;

    // Campo de busca com debounce de 300ms; limpar o campo zera o vínculo de página do item.
    $(document).on('input', '.gallery-item-link-search', function () {
        var itemId = $(this).attr('data-id');
        var value = ($(this).val() || '').trim();
        if (galleryPageSearchTimer) clearTimeout(galleryPageSearchTimer);

        if (value === '') {
            var idx = indexOfId(itemId);
            if (idx >= 0) {
                items[idx].link_page_id = '';
                items[idx].link_url = '';
                $(this).closest('.gallery-page-search-wrapper').find('.gallery-link-page-id').val('');
                serializeAndPreview();
            }
            hideGalleryPageSuggestions(itemId);
            return;
        }

        galleryPageSearchTimer = setTimeout(function () { runGalleryPageSearch(itemId, value); }, 300);
    });

    // Trocar o tipo de página recarrega a busca atual daquele item.
    $(document).on('change', '.gallery-page-type-radio', function () {
        var itemId = $(this).attr('data-id');
        var v = ($('.gallery-item-link-search[data-id="' + itemId + '"]').val() || '').trim();
        if (v !== '') runGalleryPageSearch(itemId, v);
    });

    // Selecionar uma sugestão: associa slug + URL canônica e exibe o nome amigável.
    $(document).on('click', '.gallery-item-link-suggestion', function () {
        var $opt = $(this);
        if ($opt.hasClass('disabled')) return;

        var $sugg = $opt.closest('.gallery-item-link-suggestions');
        var itemId = $sugg.attr('data-id');
        var idx = indexOfId(itemId);
        if (idx < 0) return;

        var slug = $opt.attr('data-id');
        if (!slug) return;

        items[idx].link_page_id = slug;
        items[idx].link_url = $opt.attr('data-url') || '';

        var $wrap = $sugg.closest('.gallery-page-search-wrapper');
        $wrap.find('.gallery-link-page-id').val(slug);
        $wrap.find('.gallery-item-link-search').val($opt.attr('data-name') || slug);

        hideGalleryPageSuggestions(itemId);
        serializeAndPreview();
    });

    // Fechar as listas de sugestões ao clicar fora ou pressionar Esc.
    $(document).on('click', function (e) {
        if ($(e.target).closest('.gallery-page-search-wrapper').length === 0) {
            $('.gallery-item-link-suggestions').hide();
        }
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) $('.gallery-item-link-suggestions').hide();
    });

    // Editar legenda (debounced).
    var captionTimer = null;
    var linkInputTimer = null;
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

    // req-024: alternar o painel "Configurar Link".
    $(document).on('click', '.gallery-item-link-toggle', function () {
        $(this).siblings('.gallery-item-link-fields').slideToggle(150);
    });

    // req-024: trocar o tipo de link reseta a visibilidade dos sub-campos.
    $(document).on('change', '.gallery-link-type', function () {
        var $item = $(this).closest('.gallery-item');
        var idx = indexOfId($item.attr('data-id'));
        if (idx < 0) return;
        var type = $(this).val();
        items[idx].link_type = type;
        applyLinkFieldVisibility($item.find('.gallery-item-link-fields'), type);
        serializeAndPreview();
    });

    // req-024: salvar os demais campos de link no item correspondente.
    $(document).on('change input', '.gallery-link-page-id, .gallery-link-url, .gallery-link-css, .gallery-link-target, .gallery-link-publisher-id, .gallery-link-order-by', function () {
        var $el = $(this);
        var idx = indexOfId($el.closest('.gallery-item').attr('data-id'));
        if (idx < 0) return;

        if ($el.hasClass('gallery-link-page-id')) items[idx].link_page_id = $el.val();
        else if ($el.hasClass('gallery-link-url')) items[idx].link_url = $el.val();
        else if ($el.hasClass('gallery-link-css')) items[idx].link_css_classes = $el.val();
        else if ($el.hasClass('gallery-link-target')) items[idx].link_target = $el.val();
        else if ($el.hasClass('gallery-link-publisher-id')) items[idx].link_publisher_id = $el.val();
        else if ($el.hasClass('gallery-link-order-by')) items[idx].link_order_by = $el.val();

        if ($el.is('input')) {
            if (linkInputTimer) clearTimeout(linkInputTimer);
            linkInputTimer = setTimeout(function () { serializeAndPreview(); }, 400);
        } else {
            serializeAndPreview();
        }
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
        var tid = $('#template_id').val() || schema.template_id || '';
        if (tid.endsWith('-modificado')) {
            tid = tid.substring(0, tid.length - 11); // remove '-modificado'
        }
        out.template_id = tid;
        // req-019: controles de exibição do carrossel/slider.
        out.show_arrows = $('#gallery-show-arrows').is(':checked');
        out.show_dots = $('#gallery-show-dots').is(':checked');
        out.autoplay = $('#gallery-autoplay').is(':checked');
        var speed = parseInt($('#gallery-autoplay-speed').val(), 10);
        out.autoplay_speed = (speed >= 500) ? speed : 3000;
        out.loop = $('#gallery-loop').is(':checked');
        // req-024: altura do container (default 300) e margem lateral (default 0).
        var h = parseInt($('#gallery-height').val(), 10);
        out.height = (h >= 1) ? h : 300;
        var ml = parseInt($('#gallery-margin-lateral').val(), 10);
        out.margin_lateral = (!isNaN(ml) && ml >= 0) ? ml : 0;
        return out;
    }

    // req-019: hidrata os controles de exibição a partir do schema (com defaults seguros).
    function galleryBoolOr(v, def) {
        if (v === undefined || v === null) return def;
        return (v === true || v === 'true' || v === 1 || v === '1');
    }

    function hydrateGalleryControls() {
        $('#gallery-show-arrows').prop('checked', galleryBoolOr(schema.show_arrows, true));
        $('#gallery-show-dots').prop('checked', galleryBoolOr(schema.show_dots, true));
        $('#gallery-autoplay').prop('checked', galleryBoolOr(schema.autoplay, false));
        var sp = parseInt(schema.autoplay_speed, 10);
        $('#gallery-autoplay-speed').val((sp >= 500) ? sp : 3000);
        $('#gallery-loop').prop('checked', galleryBoolOr(schema.loop, true));
        // req-024: altura e margem lateral.
        var hh = parseInt(schema.height, 10);
        $('#gallery-height').val((hh >= 1) ? hh : 300);
        var mm = parseInt(schema.margin_lateral, 10);
        $('#gallery-margin-lateral').val((!isNaN(mm) && mm >= 0) ? mm : 0);
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
                    fields_schema: JSON.stringify(out),
                    grupo_slug: gestor.moduloRegistroId || ''
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
                        framework: (gestor.html_editor && gestor.html_editor.framework_css) ? gestor.html_editor.framework_css : 'fomantic-ui',
                        extraParams: {
                            customScripts: [
                                { src: gestor.raiz + gestor.moduloId + '/widget.js' + '?v=' + gestor.versao },
                            ]
                        }
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

        // req-043 §3.3: variável inline do widget (copiar e colar em páginas/editor visual).
        $('#hep-widget-val').val('[[widgets#galleries->render({"grupo_slug": "' + slug + '"})]]');
    }

    // req-043 §3.3: copiar a variável do widget para a área de transferência (com fallback).
    $(document).on('click', '#btn-copy-widget-val', function (e) {
        e.preventDefault();
        var input = document.getElementById('hep-widget-val');
        if (!input) return;
        copyWidgetVarToClipboard(input.value || '', $(this));
    });

    function copyWidgetVarToClipboard(text, $btn) {
        function feedback() {
            if (!$btn || !$btn.length) return;
            var original = $btn.data('original-html');
            if (typeof original === 'undefined') { original = $btn.html(); $btn.data('original-html', original); }
            $btn.html('<i class="check icon"></i> ' + (isPtBr() ? 'Copiado!' : 'Copied!'));
            setTimeout(function () { $btn.html(original); }, 1500);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(feedback, function () { fallbackCopyWidgetVar(text); feedback(); });
        } else {
            fallbackCopyWidgetVar(text); feedback();
        }
    }

    function fallbackCopyWidgetVar(text) {
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
