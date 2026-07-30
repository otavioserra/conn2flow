/**
 * Conn2Flow — Renderizador de PDF por canvas (PDF.js) — req-096 (BATCH-096).
 *
 * Motor B das 3 estratégias de exibição de PDF do Editor HTML. Existe porque Chrome/Android e
 * Safari/iOS NÃO possuem leitor embutido para `<object>`/`<iframe>` apontando para PDF: a página fica
 * em branco ou o navegador força o download. Renderizando as páginas em `<canvas>` via PDF.js, a
 * exibição inline fica idêntica em desktop, Android e iOS.
 *
 * Contrato de marcação (gerado por `html-editor.js` → `buildPdfJsMarkup`):
 *   <div class="conn2flow-pdfjs"
 *        data-pdf-src="/contents/arquivo.pdf"
 *        data-pdf-zoom="page-width|page-fit|<escala numérica>"
 *        data-pdf-toolbar="1|0"
 *        data-pdf-page="1"
 *        data-pdf-scroll="vertical|page"
 *        style="width:100%;height:600px"></div>
 *
 * Injeção: `gestor_pagina_pdf_viewer()` (core) inclui este script + a lib da CDN apenas quando a
 * página realmente contém o contêiner. No `srcdoc` do editor clássico o script é injetado sozinho e
 * carrega a lib sob demanda (ver `ensureLib`).
 *
 * API pública: `window.conn2flowPdfViewerInit()` — idempotente, pode ser chamada após mutações do DOM
 * (o motor visual a chama depois de aplicar/desfazer alterações).
 */
(function () {
    'use strict';

    var PDFJS_VERSION = '3.11.174';
    var PDFJS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/' + PDFJS_VERSION + '/';
    var STYLE_ID = 'conn2flow-pdfjs-styles';
    var READY_ATTR = 'data-c2f-pdfjs-ready';

    function isEnglish() {
        var language = '';
        try {
            if (window.gestor && window.gestor.language) language = String(window.gestor.language);
            else if (window.parent && window.parent !== window && window.parent.gestor && window.parent.gestor.language) {
                language = String(window.parent.gestor.language);
            }
        } catch (e) { /* cross-origin: mantém o idioma padrão */ }
        return language.toLowerCase().indexOf('en') === 0;
    }

    function t(portuguese, english) { return isEnglish() ? english : portuguese; }

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = [
            '.conn2flow-pdfjs{position:relative;display:block;box-sizing:border-box;overflow:hidden;',
            'background:#f1f5f9;border:1px solid #cbd5e1;border-radius:4px;}',
            // O layout do leitor vem do CSS (e não de `style` inline no contêiner), para o editor
            // visual poder salvar o contêiner sem sujeira de runtime — req-097 Fix 1.
            '.conn2flow-pdfjs[data-c2f-pdfjs-ready]{display:flex;flex-direction:column;}',
            '.conn2flow-pdfjs-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;',
            'padding:6px 8px;background:#1f2937;color:#f8fafc;font:13px system-ui,sans-serif;}',
            '.conn2flow-pdfjs-toolbar button{border:0;background:rgba(255,255,255,.14);color:#f8fafc;',
            'border-radius:6px;padding:4px 10px;cursor:pointer;font:13px system-ui,sans-serif;}',
            '.conn2flow-pdfjs-toolbar button:hover{background:rgba(255,255,255,.28);}',
            '.conn2flow-pdfjs-toolbar button[disabled]{opacity:.45;cursor:default;}',
            '.conn2flow-pdfjs-status{flex:1 1 auto;text-align:center;}',
            '.conn2flow-pdfjs-pages{overflow:auto;height:100%;padding:8px;box-sizing:border-box;',
            'display:flex;flex-direction:column;align-items:center;gap:10px;background:#e2e8f0;}',
            '.conn2flow-pdfjs-pages canvas{max-width:100%;height:auto;background:#fff;',
            'box-shadow:0 1px 6px rgba(15,23,42,.25);}',
            '.conn2flow-pdfjs-error{padding:18px;text-align:center;font:14px/1.5 system-ui,sans-serif;',
            'color:#7f1d1d;background:#fef2f2;}',
            '.conn2flow-pdfjs-error a{color:#1d4ed8;}'
        ].join('');
        document.head.appendChild(style);
    }

    /**
     * Garante `window.pdfjsLib`. O core já injeta a lib quando a página tem o contêiner; este
     * carregamento sob demanda cobre o `srcdoc` do editor clássico (preview/editor visual).
     */
    function ensureLib(cb) {
        if (window.pdfjsLib) { configureWorker(); cb(true); return; }
        if (window.__conn2flowPdfjsLoading) {
            window.__conn2flowPdfjsQueue.push(cb);
            return;
        }
        window.__conn2flowPdfjsLoading = true;
        window.__conn2flowPdfjsQueue = [cb];
        var script = document.createElement('script');
        script.src = PDFJS_CDN + 'pdf.min.js';
        script.onload = function () {
            window.__conn2flowPdfjsLoading = false;
            configureWorker();
            var queue = window.__conn2flowPdfjsQueue || [];
            window.__conn2flowPdfjsQueue = [];
            queue.forEach(function (fn) { fn(!!window.pdfjsLib); });
        };
        script.onerror = function () {
            window.__conn2flowPdfjsLoading = false;
            var queue = window.__conn2flowPdfjsQueue || [];
            window.__conn2flowPdfjsQueue = [];
            queue.forEach(function (fn) { fn(false); });
        };
        document.head.appendChild(script);
    }

    function configureWorker() {
        if (!window.pdfjsLib || !window.pdfjsLib.GlobalWorkerOptions) return;
        if (!window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_CDN + 'pdf.worker.min.js';
        }
    }

    function showError(host, src) {
        host.innerHTML = '';
        var box = document.createElement('div');
        box.className = 'conn2flow-pdfjs-error';
        box.innerHTML = '<p>' + t('Não foi possível exibir o PDF neste navegador.',
            'This browser could not display the PDF.') + '</p>' +
            (src ? '<p><a href="' + String(src).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">' +
                t('Abrir o arquivo', 'Open the file') + '</a></p>' : '');
        host.appendChild(box);
    }

    /** Escala de renderização a partir de `data-pdf-zoom` e da largura disponível. */
    function resolveScale(zoom, page, availableWidth, availableHeight) {
        var viewport = page.getViewport({ scale: 1 });
        var numeric = parseFloat(zoom);
        if (!isNaN(numeric) && numeric > 0) return numeric;
        if (zoom === 'page-fit' && availableHeight > 0) {
            return Math.min(availableWidth / viewport.width, availableHeight / viewport.height);
        }
        // page-width (padrão): ajusta pela largura útil.
        return availableWidth > 0 ? (availableWidth / viewport.width) : 1;
    }

    function initViewer(host) {
        var src = host.getAttribute('data-pdf-src') || '';
        if (!src) { showError(host, ''); return; }

        var showToolbar = host.getAttribute('data-pdf-toolbar') !== '0';
        var scrollMode = host.getAttribute('data-pdf-scroll') === 'page' ? 'page' : 'vertical';
        var zoom = host.getAttribute('data-pdf-zoom') || 'page-width';
        var startPage = parseInt(host.getAttribute('data-pdf-page'), 10);
        if (!(startPage > 0)) startPage = 1;

        host.setAttribute(READY_ATTR, '1');
        host.innerHTML = '';

        var toolbar = null;
        var status = null;
        var prevBtn = null;
        var nextBtn = null;
        if (showToolbar) {
            toolbar = document.createElement('div');
            toolbar.className = 'conn2flow-pdfjs-toolbar';
            toolbar.innerHTML =
                '<button type="button" class="c2f-pdfjs-prev">&#8592;</button>' +
                '<span class="conn2flow-pdfjs-status"></span>' +
                '<button type="button" class="c2f-pdfjs-next">&#8594;</button>' +
                '<button type="button" class="c2f-pdfjs-zoom-out">&minus;</button>' +
                '<button type="button" class="c2f-pdfjs-zoom-in">+</button>';
            host.appendChild(toolbar);
            status = toolbar.querySelector('.conn2flow-pdfjs-status');
            prevBtn = toolbar.querySelector('.c2f-pdfjs-prev');
            nextBtn = toolbar.querySelector('.c2f-pdfjs-next');
        }

        var pagesBox = document.createElement('div');
        pagesBox.className = 'conn2flow-pdfjs-pages';
        host.appendChild(pagesBox);

        var state = { doc: null, page: startPage, scaleFactor: 1, total: 0 };

        function availableWidth() {
            var width = pagesBox.clientWidth || host.clientWidth || 640;
            return Math.max(160, width - 20);
        }

        function availableHeight() {
            var height = pagesBox.clientHeight || host.clientHeight || 0;
            return height > 40 ? height - 20 : 0;
        }

        function renderPage(pageNumber, target) {
            return state.doc.getPage(pageNumber).then(function (page) {
                var scale = resolveScale(zoom, page, availableWidth(), availableHeight()) * state.scaleFactor;
                var viewport = page.getViewport({ scale: scale > 0 ? scale : 1 });
                var canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                canvas.setAttribute('aria-label', t('Página', 'Page') + ' ' + pageNumber);
                target.appendChild(canvas);
                return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
            });
        }

        function updateStatus() {
            if (!status) return;
            status.textContent = (scrollMode === 'page')
                ? (t('Página', 'Page') + ' ' + state.page + ' / ' + state.total)
                : (state.total + ' ' + t('páginas', 'pages'));
            if (prevBtn) prevBtn.disabled = (scrollMode !== 'page' || state.page <= 1);
            if (nextBtn) nextBtn.disabled = (scrollMode !== 'page' || state.page >= state.total);
        }

        function render() {
            pagesBox.innerHTML = '';
            updateStatus();
            if (scrollMode === 'page') return renderPage(state.page, pagesBox);
            var chain = Promise.resolve();
            for (var i = 1; i <= state.total; i++) {
                (function (num) { chain = chain.then(function () { return renderPage(num, pagesBox); }); })(i);
            }
            return chain;
        }

        if (toolbar) {
            prevBtn.addEventListener('click', function () {
                if (state.page > 1) { state.page--; render(); }
            });
            nextBtn.addEventListener('click', function () {
                if (state.page < state.total) { state.page++; render(); }
            });
            toolbar.querySelector('.c2f-pdfjs-zoom-in').addEventListener('click', function () {
                state.scaleFactor = Math.min(4, state.scaleFactor * 1.25); render();
            });
            toolbar.querySelector('.c2f-pdfjs-zoom-out').addEventListener('click', function () {
                state.scaleFactor = Math.max(0.25, state.scaleFactor / 1.25); render();
            });
        }

        ensureLib(function (ok) {
            if (!ok || !window.pdfjsLib) { showError(host, src); return; }
            window.pdfjsLib.getDocument(src).promise.then(function (doc) {
                state.doc = doc;
                state.total = doc.numPages;
                if (state.page > state.total) state.page = 1;
                return render();
            }).catch(function () { showError(host, src); });
        });
    }

    function boot() {
        var hosts = document.querySelectorAll('.conn2flow-pdfjs:not([' + READY_ATTR + '])');
        if (!hosts.length) return;
        injectStyles();
        Array.prototype.forEach.call(hosts, function (host) { initViewer(host); });
    }

    window.conn2flowPdfViewerInit = boot;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
