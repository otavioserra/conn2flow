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
    // REQ-243: campo de percentual (`data-c2f-mascara="percentual"`): só número de 0 a 100, com até duas casas.
    // Na tela a vírgula é o separador; o formulário transporta o decimal com ponto.
    function percentual(value) {
        var partes = String(value || '').replace(/[.]/g, ',').replace(/[^0-9,]/g, '').split(',');
        var inteiro = partes[0].slice(0, 3);
        if (parseInt(inteiro || '0', 10) > 100) inteiro = '100';
        if (partes.length === 1) return inteiro;
        return (inteiro || '0') + ',' + (inteiro === '100' ? '' : partes.slice(1).join('').slice(0, 2));
    }
    function startPercentual(root) {
        (root || document).querySelectorAll('[data-c2f-mascara="percentual"]').forEach(function (input) {
            if (input.dataset.c2fPercentualPronto) return;
            input.dataset.c2fPercentualPronto = '1';
            input.inputMode = 'decimal';
            // O valor que vem do servidor traz ponto e zeros à direita (`10.00`): mostra `10`.
            input.value = percentual(String(input.value || '').replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1')).replace(/,$/, '');
            input.addEventListener('input', function () { input.value = percentual(input.value); });
            input.addEventListener('blur', function () { input.value = input.value.replace(/,$/, ''); });
            if (input.form && !input.form.c2fPercentualLigado) {
                input.form.c2fPercentualLigado = true;
                input.form.addEventListener('formdata', function (event) {
                    input.form.querySelectorAll('[data-c2f-mascara="percentual"]').forEach(function (field) {
                        if (field.name && !field.disabled) event.formData.set(field.name, percentual(field.value).replace(/,$/, '').replace(',', '.'));
                    });
                });
            }
        });
    }
    window.C2FCampoMoeda = { iniciar: function (root) { start(root); startPercentual(root); }, decimal: decimal, formatar: format, percentual: percentual };
    function ready() {
        start(document);
        startPercentual(document);
        new MutationObserver(function (records) {
            records.forEach(function (record) { record.addedNodes.forEach(function (node) { if (node.querySelectorAll) { start(node.parentNode || node); startPercentual(node.parentNode || node); } }); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})(window, document);
