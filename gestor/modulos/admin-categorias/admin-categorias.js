(function () {
    'use strict';
    function iniciar() {
        if (window.c2fControles && window.c2fControles.ponteAtiva) return;
        var $ = window.jQuery;
        if (!$ || !$.fn.dropdown) return;
        $('.ui.dropdown').dropdown();
        if ($.fn.popup) $('.mini.button').popup({
            delay: { show: 150, hide: 0 }, position: 'top left', variation: 'inverted'
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
