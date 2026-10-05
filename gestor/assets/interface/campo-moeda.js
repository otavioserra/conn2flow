/** Controle monetário compartilhado: exibição pt-BR, transporte decimal sem símbolo. */
(function (window, document) {
    'use strict';
    function decimal(value) {
        var raw = String(value || '').replace(/^(R\$|US\$|USD|EUR|BRL|\$|€)\s*/, '').replace(/[\s\u00a0]/g, '');
        if (!raw) return '';
        if (raw.indexOf(',') >= 0) raw = raw.replace(/\./g, '').replace(',', '.');
        return /^\d+(\.\d{1,2})?$/.test(raw) ? Number(raw).toFixed(2) : '';
    }
    function currency(input) {
        var selector = input.getAttribute('data-c2f-moeda-campo');
        var field = selector && document.querySelector(selector);
        return (field && field.value) || input.getAttribute('data-c2f-moeda') || 'BRL';
    }
    function format(input, value) {
        var amount = decimal(value);
        input.value = amount === '' ? '' : new Intl.NumberFormat('pt-BR', { style: 'currency', currency: currency(input) }).format(Number(amount));
    }
    function start(root) {
        (root || document).querySelectorAll('[data-c2f-mascara="moeda"], .c2fc-campo-moeda').forEach(function (input) {
            if (input.dataset.c2fMoedaPronto) return;
            input.dataset.c2fMoedaPronto = '1';
            input.inputMode = 'decimal';
            format(input, input.value);
            input.addEventListener('input', function () {
                var digits = input.value.replace(/\D/g, '');
                format(input, digits ? (Number(digits) / 100).toFixed(2) : '');
            });
            if (input.form && !input.form.c2fMoedaLigado) {
                input.form.c2fMoedaLigado = true;
                input.form.addEventListener('formdata', function (event) {
                    input.form.querySelectorAll('[data-c2f-mascara="moeda"], .c2fc-campo-moeda').forEach(function (field) {
                        if (field.name && !field.disabled) event.formData.set(field.name, decimal(field.value));
                    });
                });
            }
        });
    }
    document.addEventListener('change', function (event) {
        document.querySelectorAll('[data-c2f-moeda-campo]').forEach(function (input) {
            if (event.target.matches(input.getAttribute('data-c2f-moeda-campo'))) format(input, input.value);
        });
    });
    window.C2FCampoMoeda = { iniciar: start, decimal: decimal, formatar: format };
    function ready() {
        start(document);
        new MutationObserver(function (records) {
            records.forEach(function (record) { record.addedNodes.forEach(function (node) { if (node.querySelectorAll) start(node.parentNode || node); }); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})(window, document);
