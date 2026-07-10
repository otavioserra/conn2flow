/**
 * dashboard.iframe-toolbar.js
 *
 * Executa DENTRO do iframe `#c2f-site-toolbar` (página `dashboard-site-toolbar`).
 * Controla a própria barra: toggle do modo de edição, redimensionamento do iframe
 * (dropdown de módulos / barra de edição) e a ponte de mensagens com a página
 * hospedeira (`window.parent`), que roda o `dashboard.toolbar.js`.
 *
 * BATCH-077: esta lógica vivia embutida numa tag `<script>` no template
 * `dashboard-site-toolbar.html` (pt-br/en). Foi extraída para este arquivo estático
 * e passou a ser injetada dinamicamente por `gestor_pagina_javascript_incluir`
 * (tipo `iframe-toolbar`) em `dashboard_site_toolbar()`. O hífen em `iframe-toolbar`
 * é obrigatório para casar a regex `/^[A-Za-z0-9-]+$/` do roteador `arquivo-estatico.php`.
 */
(function () {
    'use strict';

    var origin = window.location.origin;
    var MAIN_H = 30;
    var EDIT_H = 44;
    var editOn = false;
    var menuOpen = false;

    function initToolbar() {
        var editBtn = document.getElementById('c2f-toolbar-edit');
        var editBar = document.getElementById('c2f-toolbar-editbar');
        var cancelBtn = document.getElementById('c2f-editbar-cancel');
        var saveBtn = document.getElementById('c2f-editbar-save');
        var menu = document.getElementById('c2f-toolbar-menu');
        var dropdown = menu ? menu.querySelector('ul') : null;
        var root = document.getElementById('c2f-toolbar-root');
        var pageId = root ? (root.getAttribute('data-page-id') || '') : '';

        function persistentHeight() {
            // Altura que EMPURRA a página (barra 30px + editbar quando aberta).
            return MAIN_H + (editOn ? EDIT_H : 0);
        }

        function iframeHeight() {
            // Altura do iframe: inclui o dropdown de módulos (que apenas sobrepõe, não empurra).
            var h = persistentHeight();
            if (menuOpen && dropdown) {
                h = Math.max(h, MAIN_H + dropdown.offsetHeight + 8);
            }
            return h;
        }

        function pushHeight() {
            window.parent.postMessage({
                type: 'c2f-toolbar:resize',
                height: iframeHeight(),
                offset: persistentHeight()
            }, origin);
        }

        function setEdit(on) {
            editOn = on;
            if (editBar) editBar.style.display = on ? 'flex' : 'none';
            pushHeight();
            window.parent.postMessage({ type: on ? 'c2f-toolbar:edit-start' : 'c2f-toolbar:edit-cancel', page_id: pageId }, origin);
        }

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                setEdit(!editOn);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () { setEdit(false); });
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                window.parent.postMessage({ type: 'c2f-toolbar:edit-save', page_id: pageId }, origin);
            });
        }

        // Controles do editor (html-editor-visual-topbar) — postam ao host, que aciona o c2fEditor.
        var undoBtn = document.getElementById('html-editor-undo-btn');
        if (undoBtn) { undoBtn.addEventListener('click', function () { window.parent.postMessage({ type: 'c2f-toolbar:edit-undo' }, origin); }); }
        var redoBtn = document.getElementById('html-editor-redo-btn');
        if (redoBtn) { redoBtn.addEventListener('click', function () { window.parent.postMessage({ type: 'c2f-toolbar:edit-redo' }, origin); }); }
        var addBtn = document.getElementById('html-editor-add-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var r = addBtn.getBoundingClientRect();
                window.parent.postMessage({ type: 'c2f-toolbar:edit-add', x: r.left, y: r.bottom }, origin);
            });
        }
        var backupsBtn = document.getElementById('c2f-backups-btn');
        if (backupsBtn) {
            backupsBtn.addEventListener('click', function () {
                var r = backupsBtn.getBoundingClientRect();
                window.parent.postMessage({ type: 'c2f-toolbar:edit-backups', x: r.left, y: r.bottom, page_id: pageId }, origin);
            });
        }

        // Preview responsivo (screenPagina): redimensiona a área editável na página hospedeira.
        var screenBtns = document.querySelectorAll('.screenPagina');
        Array.prototype.forEach.call(screenBtns, function (btn) {
            btn.addEventListener('click', function () {
                var w = this.getAttribute('data-width') || '100%';
                Array.prototype.forEach.call(screenBtns, function (b) { b.classList.remove('active', 'blue'); });
                this.classList.add('active', 'blue');
                window.parent.postMessage({ type: 'c2f-toolbar:edit-screen', width: w }, origin);
            });
        });

        // Redimensiona o iframe para caber o dropdown de módulos (que excede os 30px).
        if (menu) {
            menu.addEventListener('mouseenter', function () { menuOpen = true; pushHeight(); });
            menu.addEventListener('mouseleave', function () { menuOpen = false; pushHeight(); });
        }

        // Filtro de módulos (item 5) + ocultação de cabeçalhos de grupos vazios (item 6).
        var filterInput = document.getElementById('c2f-modules-filter');
        if (filterInput) {
            // Impede que clicar/digitar no campo dispare navegação/fechamento indevido.
            filterInput.addEventListener('click', function (e) { e.stopPropagation(); });
            filterInput.addEventListener('input', function () {
                var term = (filterInput.value || '').trim().toLowerCase();
                var items = menu ? menu.querySelectorAll('.c2f-menu-item') : [];
                Array.prototype.forEach.call(items, function (it) {
                    var txt = (it.textContent || '').toLowerCase();
                    it.style.display = (!term || txt.indexOf(term) !== -1) ? '' : 'none';
                });
                // Oculta o cabeçalho de cada grupo sem itens visíveis.
                var headers = menu ? menu.querySelectorAll('.c2f-group-header') : [];
                Array.prototype.forEach.call(headers, function (hd) {
                    var visible = false;
                    var n = hd.nextElementSibling;
                    while (n && !(n.classList && n.classList.contains('c2f-group-header'))) {
                        if (n.classList && n.classList.contains('c2f-menu-item') && n.style.display !== 'none') { visible = true; break; }
                        n = n.nextElementSibling;
                    }
                    hd.style.display = visible ? '' : 'none';
                });
                pushHeight(); // a altura do dropdown mudou → reajusta o iframe.
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToolbar, { once: true });
    } else {
        initToolbar();
    }
})();
