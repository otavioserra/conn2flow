# Snippets de JavaScript
Este arquivo contém snippets de funções úteis para operações comuns em javascript no Conn2Flow. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Agentes
Você pode usar os seguintes snippets para interagir com agentes no javascript. Bem como caso haja necessidade, criar novos snippets e editar esse arquivo.

## Snippets
```javascript
// [1] AJAX Default

var ajaxDefault = {
    type: 'POST',
    url: gestor.raiz + gestor.moduloCaminho + '/',
    ajaxOpcao: 'ajaxOpcao',
    data: {
        opcao: gestor.moduloOpcao,
        ajax: 'sim'
    },
    dataType: 'json',
    beforeSend: function () {
        loadDimmer(true);
        msg_erro_resetar();
    },
    success: function (dados) {
        switch (dados.status) {
            case 'Ok':
                this.successCallback(dados);
                break;
            default:
                this.successNotOkCallback(dados);
                console.log('ERROR - ' + this.ajaxOpcao + ' - ' + dados.status);

        }

        loadDimmer(false);
    },
    error: function (txt) {
        switch (txt.status) {
            case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
            default:
                console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:');
                console.log(txt);
                loadDimmer(false);
        }
    },
    successCallback: function (response) { },
    successNotOkCallback: function (response) { }
};

function loadDimmer(show = true) {
    if (show) {
        $('.template-options-wrapper .dimmer').addClass('active');
    } else {
        $('.template-options-wrapper .dimmer').removeClass('active');
    }
}

function msg_erro_resetar() {
    
}

// [2] AJAX Call
function ajaxCall() {
    const ajax = ajaxDefault;

    ajax.ajaxOpcao = 'opcao_backend';
    ajax.data.ajaxOpcao = ajax.ajaxOpcao;
    ajax.data.params = {
        param1: 'value1',
        param2: 'value2',
        paramN: 'valueN',
    };

    ajax.successCallback = function (response) {
        if (response.data) {
            // Processar os dados recebidos
        }
    };

    ajax.successNotOkCallback = function (response) {
        // Tratar erros ou respostas não OK
    };

    $.ajax(ajax);
}

// [3] Dropdown

$('.dropdown')
    .dropdown({
        onChange: function (value, text, $choice) {
            setTimeout(function () {
                functionCallBack();
            }, 100);
        }
    });
```