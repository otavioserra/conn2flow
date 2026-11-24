# Backend PHP: dispatcher AJAX do módulo — receita completa

Subskill de `c2f-javascript-ajax`. Leia ao criar ou alterar o `switch ($_GESTOR['ajax-opcao'])` de um controller. As regras invioláveis também estão na skill-mãe.

## Dispatcher e handler de referência

No controlador PHP do módulo, toda rota AJAX DEVE ser interceptada no início do ciclo com `interface_ajax_iniciar()` e finalizada com `interface_ajax_finalizar()`:

```php
function modulo_start() {
    global $_GESTOR;

    gestor_incluir_bibliotecas();

    // 1. Interceptador de Chamadas AJAX
    if (!empty($_GESTOR['ajax'])) {
        interface_ajax_iniciar();

        switch ($_GESTOR['ajax-opcao']) {
            case 'minha-acao':
                modulo_ajax_minha_acao();
                break;
            default:
                $_GESTOR['ajax-json'] = array(
                    'status' => 'Erro',
                    'message' => 'Ação AJAX desconhecida: ' . ($_GESTOR['ajax-opcao'] ?? '')
                );
                break;
        }

        interface_ajax_finalizar();
        return;
    }

    // 2. Roteamento de Páginas Síncronas Normais
    switch ($_GESTOR['opcao']) {
        case 'dashboard':
        default:
            modulo_dashboard();
            break;
    }
}

function modulo_ajax_minha_acao() {
    global $_GESTOR;

    // Recupera dados enviados
    $param1 = $_REQUEST['param1'] ?? '';

    // Lógica de negócio / banco
    // ...

    // Retorno de Sucesso padronizado
    $_GESTOR['ajax-json'] = array(
        'status' => 'Ok',
        'data' => array(
            'id' => 123,
            'resultado' => 'Processado com sucesso'
        )
    );
}
```
