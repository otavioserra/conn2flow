$(document).ready(function () {
    $('.gestorModule')
        .dropdown({
            onChange: function (value, text, $choice) {
                window.open(gestor.raiz + gestor.moduloCaminho + '?id=' + value, '_self');
            }
        });
});
