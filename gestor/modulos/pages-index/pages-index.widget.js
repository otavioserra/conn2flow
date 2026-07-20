/**
 * Controlador publico do widget pages-index.
 *
 * Mantem busca, URL, paginacao e ordenacao sincronizadas; evita respostas fora de ordem
 * com AbortController e reutiliza respostas ja obtidas durante a vida da pagina.
 */
$(document).ready(function () {
    var DEBOUNCE_MS = 300;
    var responseCache = Object.create(null);
    var targetUrl = window.location.href;
    var isInIframe = false;

    if (window.self !== window.parent && targetUrl === 'about:srcdoc') {
        isInIframe = true;
        try {
            targetUrl = window.parent.location.href;
        } catch (e) {
            targetUrl = '/';
        }
    }

    ensureKeyboardStyle();

    if (isInIframe) {
        setTimeout(preInitPagesIndex, 500);
    } else {
        preInitPagesIndex();
    }

    function ensureKeyboardStyle() {
        if (document.getElementById('pages-index-widget-style')) return;
        var style = document.createElement('style');
        style.id = 'pages-index-widget-style';
        style.textContent = '.pages-index-item-active{outline:2px solid #2563eb!important;outline-offset:2px;}';
        document.head.appendChild(style);
    }

    function preInitPagesIndex() {
        $('.conn2flow-pages-index').each(function () {
            initPagesIndex(this);
        });
    }

    function initPagesIndex(el) {
        var $root = $(el);
        if ($root.data('c2fIndexReady')) return;
        $root.data('c2fIndexReady', true);

        var grupoSlug = $root.attr('data-grupo-slug') || '';
        var ordenacaoInicial = $root.attr('data-ordenacao') || 'date_desc';
        var $items = $root.find('.pages-index-items').first();
        var $search = $root.find('.pages-index-search').first();
        var $sort = $root.find('.pages-index-sort').first();
        var $loadMore = $root.find('.pages-index-load-more').first();
        var debounceTimer = null;
        var requestController = null;
        var requestToken = 0;
        var carregando = false;
        var activeIndex = -1;

        var searchFromUrl = readSearchFromUrl();
        var initialSearch = searchFromUrl !== null
            ? searchFromUrl
            : ($search.length ? String($search.val() || '').trim() : '');

        var estado = {
            pagina: 1,
            busca: initialSearch,
            ordenacao: ordenacaoInicial
        };

        if ($search.length) {
            $search.val(initialSearch);
            $search.on('input', function () {
                var valor = String($(this).val() || '').trim();
                syncSearchToUrl(valor);
                clearTimeout(debounceTimer);
                cancelPendingRequest();
                debounceTimer = setTimeout(function () {
                    estado.busca = valor;
                    estado.pagina = 1;
                    requisitar(true);
                }, DEBOUNCE_MS);
            });

            $search.on('keydown', function (event) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActiveItem(activeIndex + 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActiveItem(activeIndex - 1);
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    var items = resultItems();
                    var item = items[activeIndex];
                    var link = item && (item.matches('a[href]') ? item : item.querySelector('a[href]'));
                    if (link) {
                        event.preventDefault();
                        link.click();
                    }
                }
            });
        }

        if ($sort.length) {
            $sort.val(ordenacaoInicial);
            $sort.on('change', function () {
                estado.ordenacao = $(this).val() || 'date_desc';
                estado.pagina = 1;
                requisitar(true);
            });
        }

        if ($loadMore.length) {
            $loadMore.on('click', function (event) {
                event.preventDefault();
                if (carregando) return;
                estado.pagina += 1;
                requisitar(false);
            });
        }

        // O servidor ja entrega a primeira pagina filtrada. O destaque e aplicado imediatamente;
        // quando o parametro esta na URL, uma requisicao confirma o estado assincrono do widget.
        highlightResults(initialSearch);
        if (searchFromUrl !== null) requisitar(true);

        function readSearchFromUrl() {
            if (isInIframe) return null;
            try {
                var url = new URL(window.location.href);
                return url.searchParams.has('search') ? (url.searchParams.get('search') || '').trim() : null;
            } catch (e) {
                return null;
            }
        }

        function syncSearchToUrl(value) {
            if (isInIframe || !window.history || typeof window.history.replaceState !== 'function') return;
            try {
                var url = new URL(window.location.href);
                if (value === '') url.searchParams.delete('search');
                else url.searchParams.set('search', value);
                window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
                targetUrl = url.href;
            } catch (e) { }
        }

        function resultItems() {
            var container = $items[0];
            if (!container) return [];
            return Array.prototype.filter.call(container.children, function (item) {
                return !item.classList.contains('pages-index-empty');
            });
        }

        function clearActiveItem() {
            resultItems().forEach(function (item) {
                item.classList.remove('pages-index-item-active');
                item.removeAttribute('aria-selected');
            });
            activeIndex = -1;
        }

        function setActiveItem(index) {
            var items = resultItems();
            if (!items.length) {
                clearActiveItem();
                return;
            }
            if (index < 0) index = items.length - 1;
            if (index >= items.length) index = 0;
            items.forEach(function (item, itemIndex) {
                var active = itemIndex === index;
                item.classList.toggle('pages-index-item-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            activeIndex = index;
            if (typeof items[index].scrollIntoView === 'function') {
                items[index].scrollIntoView({ block: 'nearest' });
            }
        }

        function removeHighlights() {
            if (!$items[0]) return;
            Array.prototype.slice.call($items[0].querySelectorAll('mark[data-pages-index-highlight]')).forEach(function (mark) {
                var parent = mark.parentNode;
                if (!parent) return;
                parent.replaceChild(document.createTextNode(mark.textContent || ''), mark);
                parent.normalize();
            });
        }

        function highlightText(element, term) {
            if (!element || term === '') return;
            var needle = term.toLocaleLowerCase();
            var textNodes = [];

            function collect(node) {
                Array.prototype.forEach.call(node.childNodes || [], function (child) {
                    if (child.nodeType === 3) {
                        textNodes.push(child);
                    } else if (child.nodeType === 1 && child.tagName !== 'MARK' && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
                        collect(child);
                    }
                });
            }

            collect(element);
            textNodes.forEach(function (node) {
                var text = node.nodeValue || '';
                var lower = text.toLocaleLowerCase();
                var cursor = 0;
                var index = lower.indexOf(needle);
                if (index < 0) return;

                var fragment = document.createDocumentFragment();
                while (index >= 0) {
                    if (index > cursor) fragment.appendChild(document.createTextNode(text.slice(cursor, index)));
                    var mark = document.createElement('mark');
                    mark.setAttribute('data-pages-index-highlight', 'true');
                    mark.textContent = text.slice(index, index + term.length);
                    fragment.appendChild(mark);
                    cursor = index + term.length;
                    index = lower.indexOf(needle, cursor);
                }
                if (cursor < text.length) fragment.appendChild(document.createTextNode(text.slice(cursor)));
                node.parentNode.replaceChild(fragment, node);
            });
        }

        function highlightResults(term) {
            term = String(term || '').trim();
            removeHighlights();
            clearActiveItem();
            if (term === '') return;

            resultItems().forEach(function (item) {
                var title = item.querySelector('.pages-index-title,[data-pages-index-title],h1,h2,h3,h4,h5,h6');
                var summary = item.querySelector('.pages-index-summary,[data-pages-index-summary],p');
                highlightText(title, term);
                if (summary && summary !== title) highlightText(summary, term);
            });
        }

        function atualizarMetricas(total) {
            var count = resultItems().length;
            $root.find('[data-page-count]').text(count);
            if (typeof total !== 'undefined' && total !== null && !isNaN(parseInt(total, 10))) {
                $root.find('[data-page-total]').text(parseInt(total, 10));
            }
        }

        function cacheKey() {
            return [grupoSlug, estado.busca.toLocaleLowerCase(), estado.pagina, estado.ordenacao].join('\n');
        }

        function cancelPendingRequest() {
            requestToken += 1;
            if (requestController) requestController.abort();
            requestController = null;
            carregando = false;
            if ($loadMore.length) $loadMore.removeClass('loading');
        }

        function buildRequestBody() {
            var widgetsToAjax = (typeof gestor !== 'undefined' && gestor.widgetsToAjax) ? gestor.widgetsToAjax : '';
            var data = new URLSearchParams();
            data.set('ajax', 'sim');
            data.set('ajaxOpcao', 'pages-index-load');
            data.set('ajaxRegistroId', grupoSlug);
            if (widgetsToAjax) data.set('ajaxWidgets', widgetsToAjax);
            data.set('params[busca]', estado.busca);
            data.set('params[pagina]', String(estado.pagina));
            data.set('params[ordenacao]', estado.ordenacao);
            return data;
        }

        function applyResponse(response, substituir) {
            if (!response || response.status !== 'Ok') return;
            if (substituir) $items.html(response.html || '');
            else $items.append(response.html || '');

            if (response.tem_mais) $loadMore.removeClass('hidden').show();
            else $loadMore.addClass('hidden').hide();

            atualizarMetricas(response.total);
            highlightResults(estado.busca);
        }

        function requisitar(substituir) {
            var key = cacheKey();
            cancelPendingRequest();

            if (responseCache[key]) {
                applyResponse(responseCache[key], substituir);
                return;
            }

            carregando = true;
            var token = ++requestToken;
            requestController = typeof AbortController !== 'undefined' ? new AbortController() : null;
            if ($loadMore.length) $loadMore.addClass('loading');

            fetch(targetUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: buildRequestBody().toString(),
                signal: requestController ? requestController.signal : undefined
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            }).then(function (response) {
                if (token !== requestToken) return;
                if (!response || response.status !== 'Ok') throw new Error('Invalid pages-index response');
                responseCache[key] = response;
                applyResponse(response, substituir);
            }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
            }).finally(function () {
                if (token !== requestToken) return;
                carregando = false;
                requestController = null;
                if ($loadMore.length) $loadMore.removeClass('loading');
            });
        }
    }
});
