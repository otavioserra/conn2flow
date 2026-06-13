$(document).ready(function () {
    /**
     * HTML Editor - Controles do Editor Visual (janela pai) — req-034 (BATCH-034)
     *
     * Este arquivo concentra a lógica da JANELA PAI do Editor HTML Visual, separada do
     * já extenso `html-editor-interface.js`:
     *   - Botão "+" com painel de inclusão de elementos HTML e widgets do sistema.
     *   - Carga AJAX da lista de widgets ativos (rota `html-editor-widgets-list`).
     *   - Botões e atalhos de teclado de Desfazer/Refazer (Ctrl+Z / Ctrl+Y / Ctrl+Shift+Z).
     *   - Alças de redimensionamento do preview (resize handles) + indicador de largura.
     *   - Sincronização das larguras pré-definidas (desktop/tablet/mobile).
     *
     * A comunicação com o editor que roda DENTRO do iframe (`html-editor.js`) é feita por
     * `postMessage` usando o namespace de ações `c2f-he:*` definido abaixo.
     */

    // Só ativa quando o componente do HTML Editor existe nesta página.
    if (typeof gestor === 'undefined' || !gestor.html_editor) return;

    // ===== Protocolo de mensagens pai <-> iframe (compartilhado com html-editor.js)
    var ACT = {
        UNDO: 'c2f-he:undo',
        REDO: 'c2f-he:redo',
        COPY: 'c2f-he:copy',
        PASTE: 'c2f-he:paste',
        INSERT_ELEMENT: 'c2f-he:insert-element',
        INSERT_WIDGET: 'c2f-he:insert-widget',
        CANCEL_INSERT: 'c2f-he:cancel-insert',
        HISTORY: 'c2f-he:history'
    };

    // ===== Elementos HTML disponíveis no painel de inclusão (req-034 §4)
    var ELEMENTOS_HTML = [
        { type: 'p', label: 'Parágrafo', icon: 'paragraph' },
        { type: 'h1', label: 'Título H1', icon: 'heading' },
        { type: 'h2', label: 'Título H2', icon: 'heading' },
        { type: 'h3', label: 'Título H3', icon: 'heading' },
        { type: 'img', label: 'Imagem', icon: 'image' },
        { type: 'a', label: 'Link', icon: 'linkify' },
        { type: 'button', label: 'Botão', icon: 'hand pointer' },
        { type: 'div', label: 'Bloco', icon: 'square outline' },
        { type: 'section', label: 'Seção', icon: 'object group outline' }
    ];

    // Módulos de widget exibidos no painel (chave da rota AJAX -> rótulo + ícone).
    var WIDGETS_MODULOS = [
        { key: 'publisher-highlights', label: 'Destaques', icon: 'star' },
        { key: 'menus', label: 'Menus', icon: 'bars' },
        { key: 'galleries', label: 'Galerias', icon: 'images' },
        { key: 'publisher-index', label: 'Índice', icon: 'list' }
    ];

    var widgetsCache = null; // resultado da rota AJAX (carregado uma vez por sessão)

    // ===== Comunicação com o iframe

    function iframeWindow() {
        var el = document.getElementById('iframe-preview');
        return el ? el.contentWindow : null;
    }

    function enviarParaIframe(action, payload) {
        var w = iframeWindow();
        if (!w) return;
        var msg = $.extend({ action: action }, payload || {});
        w.postMessage(JSON.stringify(msg), '*');
    }

    function modalVisualAberto() {
        var $modal = $('.previsualizar.modal');
        return $modal.length > 0 && $modal.hasClass('visible') && $modal.is(':visible');
    }

    // ===== Estilos (alças de resize, indicador e painel de inclusão)

    (function injetarEstilos() {
        if (document.getElementById('html-editor-visual-controls-styles')) return;
        var css = ''
            + '.iframe-resize-handle{position:absolute;top:0;width:10px;height:100%;cursor:ew-resize;'
            + 'background:rgba(59,130,246,0.12);z-index:5;transition:background .15s;}'
            + '.iframe-resize-handle:hover,.iframe-resize-handle.dragging{background:rgba(59,130,246,0.55);}'
            + '.iframe-resize-handle-left{left:-10px;border-radius:4px 0 0 4px;}'
            + '.iframe-resize-handle-right{right:-10px;border-radius:0 4px 4px 0;}'
            + '.iframe-resize-indicator{position:absolute;top:6px;left:50%;transform:translateX(-50%);'
            + 'background:rgba(17,24,39,0.85);color:#fff;font-size:12px;padding:2px 8px;border-radius:10px;'
            + 'z-index:6;pointer-events:none;font-family:monospace;}'
            + '.html-editor-add-panel{position:fixed;z-index:10000;min-width:260px;max-width:320px;'
            + 'max-height:70vh;overflow-y:auto;background:#fff;border:1px solid #d4d4d5;border-radius:6px;'
            + 'box-shadow:0 2px 12px rgba(0,0,0,0.2);padding:0.75rem;display:none;}'
            + '.html-editor-add-panel .he-add-title{font-weight:bold;color:#767676;text-transform:uppercase;'
            + 'font-size:11px;letter-spacing:.5px;margin:0.25rem 0 0.4rem;}'
            + '.html-editor-add-panel .he-add-item{display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.5rem;'
            + 'border-radius:4px;cursor:pointer;color:#333;}'
            + '.html-editor-add-panel .he-add-item:hover{background:#f3f4f6;}'
            + '.html-editor-add-panel .he-add-widget-group{margin-bottom:0.25rem;}'
            + '.html-editor-add-panel .he-add-widget-head{display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.5rem;'
            + 'cursor:pointer;font-weight:600;border-radius:4px;}'
            + '.html-editor-add-panel .he-add-widget-head:hover{background:#f3f4f6;}'
            + '.html-editor-add-panel .he-add-widget-list{padding-left:1.5rem;display:none;}'
            + '.html-editor-add-panel .he-add-widget-group.open .he-add-widget-list{display:block;}'
            + '.html-editor-add-panel .he-add-empty{color:#999;font-size:12px;padding:0.25rem 0.5rem;}';
        var style = document.createElement('style');
        style.id = 'html-editor-visual-controls-styles';
        style.textContent = css;
        document.head.appendChild(style);
    })();

    // ===== Painel de inclusão ("+")

    var $painel = null;

    function construirPainel() {
        if ($painel) return $painel;

        var html = '<div class="html-editor-add-panel">';
        html += '<div class="he-add-title">Elementos HTML</div>';
        ELEMENTOS_HTML.forEach(function (el) {
            html += '<div class="he-add-item he-add-element" data-element="' + el.type + '">'
                + '<i class="' + el.icon + ' icon"></i><span>' + el.label + '</span></div>';
        });
        html += '<div class="ui divider"></div>';
        html += '<div class="he-add-title">Widgets do Sistema</div>';
        WIDGETS_MODULOS.forEach(function (mod) {
            html += '<div class="he-add-widget-group" data-module="' + mod.key + '">'
                + '<div class="he-add-widget-head"><i class="dropdown icon"></i>'
                + '<i class="' + mod.icon + ' icon"></i><span>' + mod.label + '</span></div>'
                + '<div class="he-add-widget-list"><div class="he-add-empty">Carregando...</div></div>'
                + '</div>';
        });
        html += '</div>';

        $painel = $(html);
        $('body').append($painel);
        return $painel;
    }

    function posicionarPainel($botao) {
        var rect = $botao[0].getBoundingClientRect();
        var $p = construirPainel();
        $p.css({ top: (rect.bottom + 6) + 'px', left: rect.left + 'px' });
        // Ajuste para não sair da viewport à direita.
        var pw = $p.outerWidth();
        if (rect.left + pw > window.innerWidth - 10) {
            $p.css({ left: Math.max(10, window.innerWidth - pw - 10) + 'px' });
        }
    }

    function abrirPainel($botao) {
        construirPainel();
        posicionarPainel($botao);
        $painel.show();
        carregarWidgets();
    }

    function fecharPainel() {
        if ($painel) $painel.hide();
    }

    // Toggle do painel no clique do botão "+".
    $(document.body).on('click', '.html-editor-add-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ($painel && $painel.is(':visible')) {
            fecharPainel();
        } else {
            abrirPainel($(this));
        }
    });

    // Fechar painel ao clicar fora.
    $(document).on('mousedown', function (e) {
        if (!$painel || !$painel.is(':visible')) return;
        if ($(e.target).closest('.html-editor-add-panel, .html-editor-add-btn').length === 0) {
            fecharPainel();
        }
    });

    // Expandir/recolher grupos de widget.
    $(document.body).on('click', '.he-add-widget-head', function () {
        $(this).closest('.he-add-widget-group').toggleClass('open');
    });

    // Selecionar elemento HTML do painel -> iniciar inserção no iframe.
    $(document.body).on('click', '.he-add-element', function () {
        var tipo = $(this).data('element');
        fecharPainel();
        enviarParaIframe(ACT.INSERT_ELEMENT, { elementType: String(tipo) });
    });

    // Selecionar widget do painel -> iniciar inserção no iframe.
    $(document.body).on('click', '.he-add-widget-item', function () {
        var $i = $(this);
        fecharPainel();
        enviarParaIframe(ACT.INSERT_WIDGET, {
            widgetModule: String($i.data('module')),
            widgetSlug: String($i.data('slug')),
            widgetName: String($i.data('name') || $i.data('slug'))
        });
    });

    // ===== AJAX da lista de widgets

    function popularWidgets(data) {
        if (!$painel) return;
        WIDGETS_MODULOS.forEach(function (mod) {
            var lista = (data && data[mod.key]) ? data[mod.key] : [];
            var $list = $painel.find('.he-add-widget-group[data-module="' + mod.key + '"] .he-add-widget-list');
            $list.empty();
            if (!lista.length) {
                $list.append('<div class="he-add-empty">Nenhum registro ativo</div>');
                return;
            }
            lista.forEach(function (w) {
                var $item = $('<div class="he-add-item he-add-widget-item">'
                    + '<i class="cube icon"></i><span></span></div>');
                $item.attr('data-module', mod.key);
                $item.attr('data-slug', w.id);
                $item.attr('data-name', w.nome || w.id);
                $item.find('span').text(w.nome || w.id);
                $list.append($item);
            });
        });
    }

    function carregarWidgets() {
        if (widgetsCache) {
            popularWidgets(widgetsCache);
            return;
        }
        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'html-editor-widgets-list'
            },
            success: function (resp) {
                if (resp && resp.status === 'Ok' && resp.data) {
                    widgetsCache = resp.data;
                    popularWidgets(widgetsCache);
                } else {
                    popularWidgets({});
                }
            },
            error: function () {
                popularWidgets({});
            }
        });
    }

    // ===== Desfazer / Refazer

    $(document.body).on('click', '.html-editor-undo-btn', function () {
        if ($(this).is('[disabled]')) return;
        enviarParaIframe(ACT.UNDO);
    });

    $(document.body).on('click', '.html-editor-redo-btn', function () {
        if ($(this).is('[disabled]')) return;
        enviarParaIframe(ACT.REDO);
    });

    function atualizarBotoesHistorico(canUndo, canRedo) {
        var $u = $('.html-editor-undo-btn');
        var $r = $('.html-editor-redo-btn');
        if (canUndo) { $u.removeAttr('disabled'); } else { $u.attr('disabled', 'disabled'); }
        if (canRedo) { $r.removeAttr('disabled'); } else { $r.attr('disabled', 'disabled'); }
    }

    // Atalhos de teclado (apenas com o modal visual aberto e foco na janela pai).
    $(document).on('keydown', function (e) {
        if (!modalVisualAberto()) return;
        var key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && key === 'z' && !e.shiftKey) {
            e.preventDefault();
            enviarParaIframe(ACT.UNDO);
        } else if ((e.ctrlKey || e.metaKey) && (key === 'y' || (key === 'z' && e.shiftKey))) {
            e.preventDefault();
            enviarParaIframe(ACT.REDO);
        } else if ((e.ctrlKey || e.metaKey) && key === 'c' && !ehDigitando(e.target) && selecaoTextoColapsada()) {
            // Copiar o elemento selecionado no iframe (preserva a cópia nativa de texto).
            e.preventDefault();
            enviarParaIframe(ACT.COPY);
        } else if ((e.ctrlKey || e.metaKey) && key === 'v' && !ehDigitando(e.target)) {
            e.preventDefault();
            enviarParaIframe(ACT.PASTE);
        } else if (key === 'escape') {
            // ESC cancela um modo de inserção em andamento.
            enviarParaIframe(ACT.CANCEL_INSERT);
            fecharPainel();
        }
    });

    function ehDigitando(t) {
        if (!t) return false;
        var tag = (t.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || t.isContentEditable;
    }

    function selecaoTextoColapsada() {
        var sel = window.getSelection ? window.getSelection() : null;
        return !sel || sel.isCollapsed || String(sel).length === 0;
    }

    // ===== Redimensionamento do preview (alças + larguras pré-definidas)

    function $frame() { return $('.previsualizar .iframe-preview-frame'); }
    function $indicador() { return $('.previsualizar .iframe-resize-indicator'); }

    function mostrarIndicador(px) {
        var $ind = $indicador();
        if (!$ind.length) return;
        $ind.text(Math.round(px) + 'px').show();
    }

    function ocultarIndicadorDepois() {
        var $ind = $indicador();
        clearTimeout($ind.data('hideTimer'));
        var t = setTimeout(function () { $ind.fadeOut(150); }, 1200);
        $ind.data('hideTimer', t);
    }

    function aplicarLargura(valor) {
        var $f = $frame();
        if (!$f.length) return;
        $f.css('max-width', '100%');
        if (valor === '100%' || valor === '100') {
            $f.css('width', '100%');
        } else {
            $f.css('width', valor);
        }
        mostrarIndicador($f.width());
        ocultarIndicadorDepois();
    }

    // Larguras pré-definidas (desktop=100% / tablet=768px / mobile=375px) via data-width.
    $(document.body).on('click', '.previsualizar .screenPagina', function () {
        var w = $(this).data('width') || '100%';
        $(this).addClass('active blue').siblings('.screenPagina').removeClass('active blue');
        aplicarLargura(String(w));
    });

    // Arraste das alças laterais.
    (function bindResizeHandles() {
        var dragState = null;

        $(document.body).on('mousedown', '.previsualizar .iframe-resize-handle', function (e) {
            e.preventDefault();
            var $f = $frame();
            if (!$f.length) return;
            dragState = {
                side: $(this).hasClass('iframe-resize-handle-left') ? 'left' : 'right',
                startX: e.clientX,
                startWidth: $f.width()
            };
            $(this).addClass('dragging');
            // Bloquear interação com o iframe durante o arraste.
            $('#iframe-preview').css('pointer-events', 'none');
            $('body').css('user-select', 'none');
        });

        $(document).on('mousemove', function (e) {
            if (!dragState) return;
            var delta = e.clientX - dragState.startX;
            // Lado direito cresce com delta positivo; o esquerdo cresce com delta negativo.
            // O frame é centralizado, então cada pixel de arraste move as duas bordas: x2.
            var novo = dragState.side === 'right'
                ? dragState.startWidth + delta * 2
                : dragState.startWidth - delta * 2;
            novo = Math.max(280, Math.min(novo, $('.previsualizar .html-editor-preview-stage').width()));
            $frame().css({ width: novo + 'px', 'max-width': '100%' });
            mostrarIndicador(novo);
        });

        $(document).on('mouseup', function () {
            if (!dragState) return;
            dragState = null;
            $('.previsualizar .iframe-resize-handle').removeClass('dragging');
            $('#iframe-preview').css('pointer-events', '');
            $('body').css('user-select', '');
            ocultarIndicadorDepois();
        });
    })();

    // ===== Mensagens recebidas do iframe

    window.addEventListener('message', function (e) {
        var data;
        try { data = JSON.parse(e.data); } catch (err) { return; }
        if (!data || !data.action) return;

        switch (data.action) {
            case ACT.HISTORY:
                atualizarBotoesHistorico(!!data.canUndo, !!data.canRedo);
                break;
        }
    });
});
