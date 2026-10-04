$(document).ready(function () {
    var select = $('.gestorModule');
    var abrirModulo = function (value) {
        window.open(gestor.raiz + gestor.moduloCaminho + '?id=' + value, '_self');
    };

    if (select.is('[data-c2f-select]')) {
        select.on('change', function () {
            abrirModulo($(this).val());
        });
    } else {
        select.dropdown({
            onChange: function (value, text, $choice) {
                abrirModulo(value);
            }
        });
    }
});
