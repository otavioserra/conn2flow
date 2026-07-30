/**
 * Conn2Flow — JavaScript do painel administrativo (BATCH-103).
 *
 * Filtro do menu principal do gestor (`@[[pagina#menu]]@` → componente `menu-principal-sistema`),
 * espelhando o comportamento do `c2f-modules-filter` da Editbar: ao digitar, só os itens que casam
 * permanecem visíveis e os blocos de grupo que ficam sem itens são ocultados junto com o cabeçalho.
 *
 * Estrutura sobre a qual o filtro opera (montada por `gestor_pagina_menu`):
 *   .menuConteiner
 *     input#gestor-menu-filtro
 *     .ui.list
 *       .item              → bloco (Dashboard, cada grupo de módulos, Sair)
 *         .ui.small.header → nome do grupo (só nos blocos de categoria)
 *         a.item           → item de menu
 *
 * A comparação ignora acentuação e caixa: digitar 'usuarios' encontra 'Usuários' e 'pag' encontra
 * 'Páginas'. Itens fixos (Dashboard e Sair) também são filtrados — decisão do Engenheiro Chefe, para
 * o comportamento ficar uniforme com a Editbar; limpar o campo devolve o menu completo.
 *
 * API pública: `window.gestorMenuFiltro.aplicar(termo)` — idempotente e usada nos testes.
 */
(function () {
    'use strict';

    var INPUT_ID = 'gestor-menu-filtro';
    var CONTAINER = '.menuConteiner';
    var VAZIO = '.menuFiltroVazio';
    // Marcas combinantes (U+0300-U+036F) montadas por codigo: mantem o fonte 100% ASCII, imune a
    // normalizacao Unicode do arquivo por editores.
    var RE_ACENTOS = new RegExp('[' + String.fromCharCode(0x300) + '-' + String.fromCharCode(0x36f) + ']', 'g');

    /** Minúsculas e sem acentos, para a busca casar 'usuarios' com 'Usuários'. */
    function normalizar(texto) {
        var valor = String(texto == null ? '' : texto).toLowerCase().trim();
        if (!valor.normalize) return valor;
        // NFD separa a letra do acento e RE_ACENTOS remove as marcas combinantes resultantes.
        return valor.normalize('NFD').replace(RE_ACENTOS, '');
    }

    /**
     * Aplica o filtro ao menu.
     *
     * @param {string} termo Texto digitado (vazio restaura o menu inteiro).
     * @param {Element} [escopo] Contêiner do menu (padrão: o primeiro `.menuConteiner` do documento).
     * @return {number} Quantidade de itens visíveis após o filtro.
     */
    function aplicar(termo, escopo) {
        var menu = escopo || document.querySelector(CONTAINER);
        if (!menu) return 0;

        var busca = normalizar(termo);
        var itens = menu.querySelectorAll('a.item');
        var visiveis = 0;

        Array.prototype.forEach.call(itens, function (item) {
            var casa = !busca || normalizar(item.textContent).indexOf(busca) !== -1;
            item.style.display = casa ? '' : 'none';
            if (casa) visiveis++;
        });

        // Bloco sem nenhum item visível sai junto com o cabeçalho do grupo (que vive dentro dele).
        var blocos = menu.querySelectorAll('.ui.list > .item');
        Array.prototype.forEach.call(blocos, function (bloco) {
            var algum = false;
            var links = bloco.querySelectorAll('a.item');
            for (var i = 0; i < links.length; i++) {
                if (links[i].style.display !== 'none') { algum = true; break; }
            }
            bloco.style.display = algum ? '' : 'none';
        });

        // Aviso de "nenhum resultado" (o texto vem do componente, já traduzido por idioma).
        var vazio = menu.querySelector(VAZIO);
        if (vazio) vazio.style.display = (busca && visiveis === 0) ? 'block' : 'none';

        return visiveis;
    }

    function iniciar() {
        var campo = document.getElementById(INPUT_ID);
        if (!campo || campo.getAttribute('data-gestor-menu-filtro') === '1') return;
        campo.setAttribute('data-gestor-menu-filtro', '1');

        var menu = (campo.closest ? campo.closest(CONTAINER) : null) || document.querySelector(CONTAINER);

        campo.addEventListener('input', function () { aplicar(campo.value, menu); });
        // Esc limpa o filtro sem tirar o foco do campo.
        campo.addEventListener('keydown', function (e) {
            if ((e.key || '') !== 'Escape') return;
            campo.value = '';
            aplicar('', menu);
        });
    }

    window.gestorMenuFiltro = { aplicar: aplicar, iniciar: iniciar };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
