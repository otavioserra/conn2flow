(function () {
    'use strict';

    var MIN_LENGTH = 3;
    var DEBOUNCE_MS = 300;
    var cache = Object.create(null);
    var gestorConfig = window.gestor || null;
    try {
        if (!gestorConfig && window.parent && window.parent !== window) gestorConfig = window.parent.gestor || null;
    } catch (e) { }

    function isPreview() {
        return window.location.href === 'about:srcdoc' && window.parent && window.parent !== window;
    }

    function languageIsPtBr() {
        var lang = (gestorConfig && gestorConfig.language) || document.documentElement.lang || '';
        return String(lang).toLowerCase().indexOf('pt') === 0;
    }

    function endpoint() {
        if (isPreview()) {
            try { return window.parent.location.href; } catch (e) { return window.location.href; }
        }
        return window.location.href;
    }

    function requestBody(formId, term, page) {
        var data = new URLSearchParams();
        data.set('ajax', 'sim');
        data.set('ajaxOpcao', 'forms-search-autocomplete');
        data.set('ajaxRegistroId', formId);
        data.set('params[search]', term);
        data.set('params[page]', String(page));
        // Aciona forms_search_render_ajax() tanto no site publicado quanto no srcdoc do CRUD.
        data.set('ajaxWidgets', 'forms-search->render(' + JSON.stringify({ form_id: formId }) + ')');

        if (isPreview()) {
            if (gestorConfig && gestorConfig.moduloOpcao) data.set('opcao', gestorConfig.moduloOpcao);
        }
        return data;
    }

    function cacheKey(formId, term, page) {
        return formId + '\n' + term.toLocaleLowerCase() + '\n' + page;
    }

    function init(form) {
        if (!form || form.getAttribute('data-forms-search-ready') === 'true') return;
        var input = form.querySelector('input[name="search"]');
        var box = form.querySelector('.forms-search-results');
        if (!input || !box) return;

        form.setAttribute('data-forms-search-ready', 'true');
        var formId = form.getAttribute('data-form-id') || form.id || 'busca';
        var state = {
            term: '',
            page: 0,
            timer: null,
            requestToken: 0,
            controller: null,
            activeIndex: -1
        };

        function options() {
            return Array.prototype.slice.call(box.querySelectorAll('.forms-search-result'));
        }

        function openBox() {
            box.hidden = false;
            box.style.display = 'block';
            input.setAttribute('aria-expanded', 'true');
        }

        function closeBox(clear) {
            input.setAttribute('aria-expanded', 'false');
            box.hidden = true;
            box.style.display = 'none';
            state.activeIndex = -1;
            if (clear) box.textContent = '';
        }

        function statusMessage(text) {
            box.textContent = '';
            var message = document.createElement('div');
            message.className = 'forms-search-status';
            message.style.cssText = 'padding:0.9rem 1rem;color:#64748b;font-size:0.875rem;';
            message.textContent = text;
            box.appendChild(message);
            openBox();
        }

        function appendHighlighted(target, text, term) {
            text = String(text || '');
            var source = text.toLocaleLowerCase();
            var needle = String(term || '').toLocaleLowerCase();
            var cursor = 0;
            var index;
            if (!needle) { target.textContent = text; return; }
            while ((index = source.indexOf(needle, cursor)) !== -1) {
                if (index > cursor) target.appendChild(document.createTextNode(text.slice(cursor, index)));
                var mark = document.createElement('mark');
                mark.style.cssText = 'background:#fef08a;color:inherit;padding:0;';
                mark.textContent = text.slice(index, index + needle.length);
                target.appendChild(mark);
                cursor = index + needle.length;
            }
            if (cursor < text.length) target.appendChild(document.createTextNode(text.slice(cursor)));
        }

        function resultElement(item, term) {
            var link = document.createElement('a');
            link.className = 'forms-search-result';
            link.href = item.url || '#';
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');
            link.style.cssText = 'display:block;padding:0.85rem 1rem;border-bottom:1px solid rgba(148,163,184,.24);text-decoration:none;color:inherit;cursor:pointer;';

            var title = document.createElement('span');
            title.className = 'forms-search-result-title';
            title.style.cssText = 'display:block;font-weight:700;line-height:1.35;';
            appendHighlighted(title, item.title, term);
            link.appendChild(title);

            if (item.summary) {
                var summary = document.createElement('span');
                summary.className = 'forms-search-result-summary';
                summary.style.cssText = 'display:block;margin-top:.25rem;color:#64748b;font-size:.875rem;line-height:1.4;';
                appendHighlighted(summary, item.summary, term);
                link.appendChild(summary);
            }

            link.addEventListener('mouseenter', function () {
                setActive(options().indexOf(link));
            });
            return link;
        }

        function setActive(index) {
            var items = options();
            if (!items.length) { state.activeIndex = -1; return; }
            if (index < 0) index = items.length - 1;
            if (index >= items.length) index = 0;
            items.forEach(function (item, itemIndex) {
                var selected = itemIndex === index;
                item.setAttribute('aria-selected', selected ? 'true' : 'false');
                item.style.background = selected ? 'rgba(59,130,246,.10)' : '';
            });
            state.activeIndex = index;
            items[index].scrollIntoView({ block: 'nearest' });
        }

        function render(response, append) {
            if (!append) box.textContent = '';
            else {
                var previousButton = box.querySelector('.forms-search-load-more');
                if (previousButton) previousButton.remove();
            }

            var results = response && Array.isArray(response.results) ? response.results : [];
            results.forEach(function (item) { box.appendChild(resultElement(item, state.term)); });

            if (!append && !results.length) {
                statusMessage(languageIsPtBr() ? 'Nenhum resultado encontrado.' : 'No results found.');
                return;
            }

            if (response && response.tem_mais) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'forms-search-load-more';
                button.style.cssText = 'display:block;width:100%;padding:.8rem 1rem;border:0;background:#f8fafc;color:#2563eb;font-weight:700;cursor:pointer;';
                button.textContent = languageIsPtBr() ? 'Carregar mais' : 'Load more';
                button.addEventListener('click', function () { request(state.page + 1, true); });
                box.appendChild(button);
            }
            state.activeIndex = -1;
            openBox();
        }

        function request(page, append) {
            var term = input.value.trim();
            if (term.length < MIN_LENGTH) { closeBox(true); return; }
            state.term = term;
            var key = cacheKey(formId, term, page);
            if (cache[key]) {
                state.page = page;
                render(cache[key], append);
                return;
            }

            state.requestToken += 1;
            var token = state.requestToken;
            if (state.controller) state.controller.abort();
            state.controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            if (!append) statusMessage(languageIsPtBr() ? 'Buscando…' : 'Searching…');

            fetch(endpoint(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: requestBody(formId, term, page).toString(),
                signal: state.controller ? state.controller.signal : undefined
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            }).then(function (response) {
                if (token !== state.requestToken || input.value.trim() !== term) return;
                if (!response || response.status !== 'Ok') throw new Error('Invalid autocomplete response');
                cache[key] = response;
                state.page = page;
                render(response, append);
            }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
                if (token === state.requestToken) statusMessage(languageIsPtBr() ? 'Não foi possível carregar os resultados.' : 'Unable to load results.');
            });
        }

        input.addEventListener('input', function () {
            clearTimeout(state.timer);
            state.requestToken += 1;
            if (state.controller) state.controller.abort();
            if (input.value.trim().length < MIN_LENGTH) { closeBox(true); return; }
            state.timer = setTimeout(function () { request(1, false); }, DEBOUNCE_MS);
        });

        input.addEventListener('keydown', function (event) {
            var items = options();
            if (event.key === 'ArrowDown' && items.length) {
                event.preventDefault();
                setActive(state.activeIndex + 1);
            } else if (event.key === 'ArrowUp' && items.length) {
                event.preventDefault();
                setActive(state.activeIndex - 1);
            } else if (event.key === 'Enter' && state.activeIndex >= 0 && items[state.activeIndex]) {
                event.preventDefault();
                window.location.assign(items[state.activeIndex].href);
            } else if (event.key === 'Escape') {
                closeBox(false);
            }
        });

        input.addEventListener('focus', function () {
            var term = input.value.trim();
            if (term.length < MIN_LENGTH) return;
            var firstPage = cache[cacheKey(formId, term, 1)];
            if (firstPage) { state.term = term; state.page = 1; render(firstPage, false); }
        });

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) closeBox(false);
        });

        closeBox(true);
    }

    function scan(root) {
        if (root && root.matches && root.matches('form.conn2flow-search-form')) init(root);
        var forms = (root || document).querySelectorAll ? (root || document).querySelectorAll('form.conn2flow-search-form') : [];
        Array.prototype.forEach.call(forms, init);
    }

    function start() {
        scan(document);
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                        if (node.nodeType === 1) scan(node);
                    });
                });
            }).observe(document.documentElement, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
