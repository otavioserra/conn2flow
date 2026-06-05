/**
 * menus.widget.js — comportamento público do widget de menus (req-019 / DEC-030).
 *
 * Incluído no site quando uma página renderiza o widget `menus`. Adiciona interatividade
 * que não depende exclusivamente de recursos do Tailwind (peer-checked, named groups), os
 * quais podem falhar no CSS final do site:
 *   - Menu mobile (hambúrguer): alterna a lista ao clicar no botão `.menu-mobile-btn`.
 *   - Dropdown: fallback de hover para abrir/fechar o submenu `.group-hover-sub-menu`.
 *
 * Usa delegação de eventos no document para funcionar com múltiplos menus na página e
 * com conteúdo injetado após o load.
 */
$(document).ready(function () {

    // ===== Menu mobile (hambúrguer): clique no botão alterna a lista de itens.
    $(document).on('click', '.menu-mobile-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);

        var $list = $btn.siblings('.menu-mobile-list').first();
        if ($list.length === 0) $list = $btn.closest('nav').find('.menu-mobile-list').first();
        if ($list.length === 0) return;

        if ($list.hasClass('hidden')) {
            $list.removeClass('hidden').addClass('flex');
            $btn.attr('aria-expanded', 'true');
        } else {
            $list.removeClass('flex').addClass('hidden');
            $btn.attr('aria-expanded', 'false');
        }
    });

    // ===== Dropdown: fallback de hover do submenu (caso os named groups do Tailwind v3 falhem).
    // O `.group-hover-sub-menu` é filho direto do `.group-hover-sub-trigger`.
    $(document).on('mouseenter', '.group-hover-sub-trigger', function () {
        $(this).children('.group-hover-sub-menu').first().removeClass('hidden').addClass('block');
    });
    $(document).on('mouseleave', '.group-hover-sub-trigger', function () {
        $(this).children('.group-hover-sub-menu').first().removeClass('block').addClass('hidden');
    });
});
