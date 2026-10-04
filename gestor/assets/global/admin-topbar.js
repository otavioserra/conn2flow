/** REQ-228: native disclosures and account shortcuts, independent of module forms. */
(function () {
    'use strict';
    function iniciar() {
        var bar = document.querySelector('[data-admin-topbar]');
        if (!bar || !window.gestor || bar.dataset.topbarReady) return;
        var state = JSON.parse(bar.getAttribute('data-topbar-state'));
        bar.dataset.topbarReady = '1';
        var dialog = document.getElementById('topbar-customize');
        var status = bar.querySelector('[data-topbar-status]');
        var errorNotice = bar.querySelector('[data-topbar-error]');
        var dialogStatus = dialog.querySelector('[data-topbar-dialog-status]');
        var current = bar.querySelector('[data-topbar-current]');
        var busy = false;
        var opener = null;
        var toggles = Array.from(bar.querySelectorAll('[data-topbar-toggle]'));
        function icons() {
            if (window.lucide) window.lucide.createIcons();
        }
        function closeAll(focus) {
            toggles.forEach(function (button) {
                if (button.getAttribute('aria-expanded') === 'true') {
                    document.getElementById(button.getAttribute('aria-controls')).hidden = true;
                    button.setAttribute('aria-expanded', 'false');
                    if (focus) button.focus();
                }
            });
        }
        toggles.forEach(function (button) {
            var panel = document.getElementById(button.getAttribute('aria-controls'));
            function open() {
                closeAll(false);
                panel.hidden = false;
                button.setAttribute('aria-expanded', 'true');
            }
            button.addEventListener('click', function () {
                if (!panel.hidden) closeAll(false); else open();
            });
            button.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault(); open();
                    var items = panel.querySelectorAll('a:not([hidden]), button:not([hidden]), select');
                    if (items.length) items[event.key === 'ArrowUp' ? items.length - 1 : 0].focus();
                }
            });
            panel.addEventListener('keydown', function (event) {
                if (event.target.tagName === 'SELECT') return;
                var items = Array.from(panel.querySelectorAll('a, button, select')).filter(function (item) {
                    return !item.hidden && !item.disabled && item.getClientRects().length;
                });
                if (['ArrowDown', 'ArrowUp', 'Home', 'End'].indexOf(event.key) === -1 || !items.length) return;
                event.preventDefault();
                var index = items.indexOf(document.activeElement);
                if (event.key === 'Home') index = 0;
                else if (event.key === 'End') index = items.length - 1;
                else index = (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items[index].focus();
            });
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-topbar-disclosure]')) closeAll(false);
        });
        document.addEventListener('focusin', function (event) {
            if (!event.target.closest('[data-topbar-disclosure]')) closeAll(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggles.some(function (button) { return button.getAttribute('aria-expanded') === 'true'; })) {
                event.preventDefault(); closeAll(true);
            }
        });
        function isFavorite(id) {
            return state.favoritos.some(function (item) { return item.id === id; });
        }
        function fill(template, item) {
            var node = bar.querySelector(template).content.firstElementChild.cloneNode(true);
            node.querySelector('span').textContent = item.nome;
            node.querySelector('[data-lucide]').setAttribute('data-lucide', item.icone);
            if (node.tagName === 'A') {
                node.href = item.url;
                node.setAttribute('aria-label', item.nome);
                node.setAttribute('data-c2f-dica', item.nome);
            }
            return node;
        }
        function render() {
            var shortcuts = bar.querySelector('[data-topbar-shortcuts]');
            var overflow = bar.querySelector('[data-topbar-overflow]');
            shortcuts.replaceChildren(); overflow.replaceChildren();
            state.favoritos.forEach(function (item, index) {
                var link = fill('[data-topbar-link-template]', item);
                link.dataset.topbarIndex = index;
                shortcuts.appendChild(link);
                var more = fill('[data-topbar-overflow-template]', item);
                more.dataset.topbarOverflowIndex = index;
                overflow.appendChild(more);
            });
            bar.querySelector('[data-topbar-empty]').hidden = state.favoritos.length !== 0;
            current.hidden = !state.atual;
            current.disabled = busy || !state.disponivel;
            current.setAttribute('aria-pressed', String(isFavorite(state.atual)));
            current.querySelector('span').textContent = isFavorite(state.atual) ? current.dataset.removeLabel : current.dataset.addLabel;
            dialog.querySelectorAll('input[type=checkbox]').forEach(function (input) {
                if (!busy) input.checked = isFavorite(input.value);
                input.disabled = busy || !state.disponivel;
            });
            icons();
        }
        async function change(id, selected) {
            if (busy || !state.disponivel) return;
            busy = true; render();
            errorNotice.hidden = true;
            status.textContent = ''; dialogStatus.textContent = '';
            try {
                var form = new URLSearchParams({ ajax: 'sim', opcao: window.gestor.moduloOpcao || '',
                    ajaxOpcao: selected ? 'admin-topbar-adicionar' : 'admin-topbar-remover', paginaId: id });
                var token = document.querySelector('meta[name=csrf-token]');
                var response = await fetch(window.location.pathname, { method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-Token': window.gestor.csrfToken || (token ? token.content : '') }, body: form.toString() });
                if (response.status === 401) { window.location.assign(window.gestor.raiz + 'signin/'); return; }
                if (!response.ok) throw new Error();
                var json = await response.json();
                if (json.status !== 'Ok' || !json.data || !Array.isArray(json.data.favoritos)) throw new Error();
                state.favoritos = json.data.favoritos;
                status.textContent = bar.dataset.msgSaved; dialogStatus.textContent = bar.dataset.msgSaved;
            } catch (error) {
                status.textContent = bar.dataset.msgError; dialogStatus.textContent = bar.dataset.msgError;
                errorNotice.textContent = bar.dataset.msgError; errorNotice.hidden = false;
            } finally { busy = false; render(); }
        }
        current.addEventListener('click', function () { change(state.atual, !isFavorite(state.atual)); });
        var choices = dialog.querySelector('[data-topbar-choices]');
        state.catalogo.forEach(function (item) {
            var node = fill('[data-topbar-choice-template]', item);
            var input = node.querySelector('input'); input.value = item.id;
            input.addEventListener('change', function () { change(item.id, input.checked); });
            choices.appendChild(node);
        });
        var search = dialog.querySelector('#topbar-search');
        function filter() {
            var query = search.value.toLocaleLowerCase(); var count = 0;
            choices.querySelectorAll('label').forEach(function (label) {
                label.hidden = label.textContent.toLocaleLowerCase().indexOf(query) === -1;
                if (!label.hidden) count++;
            });
            dialog.querySelector('[data-topbar-no-results]').hidden = count !== 0;
        }
        search.addEventListener('input', filter);
        bar.querySelector('[data-topbar-customize]').addEventListener('click', function () {
            opener = bar.querySelector('[data-topbar-toggle="topbar-more"]'); closeAll(false);
            search.value = ''; filter();
            dialogStatus.textContent = state.disponivel ? '' : bar.dataset.msgUnavailable;
            dialog.showModal(); search.focus();
        });
        dialog.querySelector('[data-topbar-dialog-close]').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                var rect = dialog.getBoundingClientRect();
                if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
            }
        });
        dialog.addEventListener('close', function () { if (opener) opener.focus(); });
        var language = bar.querySelector('#topbar-language');
        var codes = window.gestor.languages && window.gestor.languages.codigos;
        Array.from(language.options).forEach(function (option) {
            if (Array.isArray(codes)) option.disabled = !codes.some(function (item) { return item.codigo === option.value; });
        });
        language.value = window.gestor.language;
        language.addEventListener('change', function () {
            var root = new URL(window.gestor.raizSemLang, window.location.origin);
            var relative = window.location.pathname.slice(root.pathname.length);
            if (relative.indexOf(window.gestor.language + '/') === 0) relative = relative.slice(window.gestor.language.length + 1);
            var url = new URL(language.value + '/' + relative, root);
            url.search = window.location.search; url.hash = window.location.hash;
            window.location.assign(url.href);
        });
        render();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
