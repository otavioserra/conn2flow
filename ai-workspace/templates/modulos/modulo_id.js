$(document).ready(function () {
    function exemploChamadas() {
        // Quando for fazer uma requisição AJAX, usar o modelo à seguir.
        $(document.body).on('mouseup tap', 'seletor', function (e) {
            if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

            var ajaxOpcao = 'opcao'; // Opção que será chamada na interface AJAX no `modulo_id.php`
            var params = {}; // Parâmetros adicionais que desejar enviar para o servidor.

            $.ajax({
                type: 'POST',
                url: gestor.raiz + gestor.moduloCaminho + '/',
                data: {
                    opcao: gestor.moduloOpcao,
                    ajax: 'sim',
                    ajaxOpcao,
                    params
                },
                dataType: 'json',
                beforeSend: function () {
                    $.carregar_abrir();
                },
                success: function (dados) {
                    switch (dados.status) {
                        case 'ok':
                            // Lógica para quando a resposta for OK
                            break;
                        default:
                            console.log('ERROR - ' + ajaxOpcao + ' - Dados:');
                            console.log(dados);

                    }

                    $.carregar_fechar();
                },
                error: function (txt) {
                    switch (txt.status) {
                        case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                        default:
                            console.log('ERROR AJAX - ' + ajaxOpcao + ' - Dados:');
                            console.log(txt);
                            $.carregar_fechar();
                    }
                }
            });
        });
    }

    function start() {
        // Lógica de inicialização

        exemploChamadas();
    }

    start();
});