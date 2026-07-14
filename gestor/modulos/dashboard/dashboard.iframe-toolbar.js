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
    var openDropdown = null;   // menuEl (caixa) do dropdown atualmente aberto (mede a altura do iframe).
    var closeTimer = null;     // hover-intent: timer de fechamento adiado, cancelável.
    var FORCE_OPEN = 'c2f-dropdown-force-open'; // força a caixa visível por JS (independe do :hover).

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
            // Altura do iframe: inclui o dropdown aberto (menu de módulos / Página / Usuário), que
            // apenas sobrepõe — não empurra a página. A caixa é medida já VISÍVEL (FORCE_OPEN),
            // então `offsetHeight` é confiável (não depende do timing do `:hover`).
            var h = persistentHeight();
            if (openDropdown) {
                h = Math.max(h, MAIN_H + openDropdown.offsetHeight + 8);
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

        function closeDropdowns() {
            // Fecha imediatamente qualquer dropdown aberto (usado ao entrar/sair da edição).
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            if (openDropdown) { openDropdown.classList.remove(FORCE_OPEN); }
            openDropdown = null;
        }

        function setEdit(on) {
            editOn = on;
            closeDropdowns(); // BATCH-081: entrar/sair da edição fecha os dropdowns abertos.
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

        // BATCH-080: Modelos de sessão e Assistente IA — acionam os painéis no host (dashboard.toolbar.js).
        var templatesBtn = document.getElementById('c2f-templates-btn');
        if (templatesBtn) {
            templatesBtn.addEventListener('click', function () {
                window.parent.postMessage({ type: 'c2f-toolbar:edit-templates' }, origin);
            });
        }
        var aiBtn = document.getElementById('c2f-ai-btn');
        if (aiBtn) {
            aiBtn.addEventListener('click', function () {
                window.parent.postMessage({ type: 'c2f-toolbar:edit-ai' }, origin);
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

        // O hover dos dropdowns (menu de módulos + Página + Usuário) é registrado adiante, num
        // sistema hover-intent unificado (ver registerHoverDropdown), que resolve a corrida do
        // crescimento assíncrono do iframe.

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

        // ===== Hover-intent unificado dos dropdowns (menu de módulos + Página + Usuário).
        //
        // As caixas vivem DENTRO deste iframe (30px). Ao abrir, o iframe precisa crescer para caber a
        // caixa — senão ela fica RECORTADA e, ao mover o mouse do trigger (30px) para a caixa, o
        // ponteiro "cai" fora do iframe e o `:hover` do CSS se perde → a caixa some (agravado após
        // redimensionar a janela, pois o crescimento assíncrono via postMessage perde a corrida).
        //
        // Correção: (1) a visibilidade da caixa é FORÇADA por JS (`FORCE_OPEN`), não só pelo `:hover`,
        // e a altura é medida com a caixa já visível (offsetHeight confiável); (2) o fechamento é
        // ADIADO (hover-intent) e cancelável — o mouse tem tempo de alcançar a caixa enquanto o iframe
        // cresce, e ao entrar nela (mouseenter do próprio group, que a contém) o timer é cancelado.
        function openDropdownMenu(menuEl) {
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            if (openDropdown && openDropdown !== menuEl) { openDropdown.classList.remove(FORCE_OPEN); }
            openDropdown = menuEl;
            menuEl.classList.add(FORCE_OPEN); // visível ANTES de medir → offsetHeight correto.
            pushHeight();
        }

        function scheduleCloseDropdown(menuEl) {
            if (closeTimer) { clearTimeout(closeTimer); }
            closeTimer = setTimeout(function () {
                closeTimer = null;
                menuEl.classList.remove(FORCE_OPEN);
                if (openDropdown === menuEl) { openDropdown = null; pushHeight(); }
            }, 220);
        }

        function registerHoverDropdown(groupEl, menuEl) {
            if (!groupEl || !menuEl) { return; }
            // O group CONTÉM a caixa (descendente): mover o trigger→caixa não dispara mouseleave
            // enquanto a caixa estiver visível dentro do iframe já crescido.
            groupEl.addEventListener('mouseenter', function () { openDropdownMenu(menuEl); });
            groupEl.addEventListener('mouseleave', function () { scheduleCloseDropdown(menuEl); });
        }

        var pageDD = document.getElementById('c2f-page-dropdown');
        var userDD = document.getElementById('c2f-user-dropdown');
        registerHoverDropdown(menu, dropdown); // menu de módulos (dropdown = <ul> interno)
        registerHoverDropdown(pageDD, pageDD ? pageDD.querySelector('.c2f-dropdown-menu') : null);
        registerHoverDropdown(userDD, userDD ? userDD.querySelector('.c2f-dropdown-menu') : null);

        // Redimensionar a janela com um dropdown aberto → reajusta a altura do iframe (a caixa pode
        // ter mudado de altura com a nova largura).
        window.addEventListener('resize', function () { if (openDropdown) { pushHeight(); } });

        // Reentrada automática no modo de edição após restaurar um backup (BATCH-085). O roteador
        // sinaliza `gestor.siteToolbarBackupRestaurado` na página HOSPEDEIRA (same-origin → acessível
        // por window.parent). Entramos em edição já com o conteúdo do backup renderizado pelo backend.
        try {
            if (window.parent && window.parent.gestor && window.parent.gestor.siteToolbarBackupRestaurado && !editOn) {
                setEdit(true);
            }
        } catch (e) { /* acesso ao parent indisponível → ignora */ }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToolbar, { once: true });
    } else {
        initToolbar();
    }
})();
