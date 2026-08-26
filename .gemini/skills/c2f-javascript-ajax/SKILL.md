---
name: c2f-javascript-ajax
description: "LEIA OBRIGATORIAMENTE antes de escrever chamadas AJAX, requisições fetch, uploads multipart ou manipuladores backend de AJAX no Gestor. Previne erros 403 Forbidden por CSRF, envelopes JSON quebrados e travamento de UI."
user-invocable: false
---

# Governança e Arquitetura de Integração AJAX no Gestor Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Escrever, editar ou migrar código JavaScript frontend (Vanilla Fetch, jQuery) ou endpoints PHP de backend que realizam comunicação assíncrona (AJAX) em módulos do Gestor.
- **SKIP APENAS SE**: Scripts utilitários puramente visuais locais (ex: animações CSS simples sem chamadas de rede).
- **CONSEQUÊNCIA DE IGNORAR**: Erros 403 Forbidden (Token CSRF inválido ou ausente) por não ativar o modo AJAX do núcleo, envelopes JSON quebrados, travamento de dimmers/spinners e falhas silenciosas de sessão (401).

---

## 🔍 1. Diagnóstico: Como o Gestor Detecta Chamadas AJAX & Prevenção de 403

No núcleo do framework (`gestor.php`), o modo AJAX só é ativado quando a requisição POST/GET envia explicitamente `ajax: 'sim'`:
* **Com `ajax: 'sim'`**: O Gestor popula `$_GESTOR['ajax'] = 'sim'`, lê `$_GESTOR['ajax-opcao']` (a partir do campo `ajaxOpcao`) e **desativa a exigência de token CSRF síncrono de formulário HTML**.
* **Sem `ajax: 'sim'`**: O Gestor assume que se trata de uma submissão de formulário HTML tradicional, exige `$_GESTOR['token']` e **rejeita a requisição imediatamente com 403 Forbidden (`{"status":"error","message":"Token CSRF inválido ou ausente."}`)**.

---

## 🏆 2. Padrão Canônico Frontend em JavaScript Vanilla (Gold Standard)

Em módulos modernos com JavaScript puro (Vanilla JS), utilize sempre o padrão canônico com `URLSearchParams` ou `FormData`:

### A) Requisições de Dados Estruturados (`application/x-www-form-urlencoded`):
```javascript
/**
 * Padrão Canônico de Requisição AJAX no Gestor (Vanilla JS)
 * @param {Object} state - Estado do módulo (deve conter opcao e moduleBaseUrl)
 * @param {string} ajaxOpcao - Ação a ser executada no backend (ex: 'salvar-dados', 'listar')
 * @param {Object} [extraData] - Parâmetros adicionais (objetos/arrays são serializados em JSON)
 * @returns {Promise<Object>} Resposta JSON do backend
 */
function gestorAjax(state, ajaxOpcao, extraData) {
    var params = new URLSearchParams({
        opcao: state.opcao || 'dashboard',
        ajax: 'sim',
        ajaxOpcao: ajaxOpcao
    });

    if (extraData) {
        Object.keys(extraData).forEach(function (key) {
            var val = extraData[key];
            params.set(key, typeof val === 'object' && val !== null ? JSON.stringify(val) : val);
        });
    }

    return fetch(state.moduleBaseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(function (response) {
        if (response.status === 401) {
            window.location.href = (window.gestor && window.gestor.raiz ? window.gestor.raiz : '/') + 'signin/';
            throw new Error('Sessão expirada. Redirecionando...');
        }
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.json();
    })
    .then(function (json) {
        if (!json || json.status !== 'Ok') {
            throw new Error((json && json.message) || 'Erro na resposta do servidor.');
        }
        return json;
    });
}
```

### B) Uploads de Arquivo Multipart (`FormData`):
```javascript
/**
 * Padrão Canônico para Uploads de Arquivo Multipart (Vanilla JS)
 */
function gestorUpload(state, ajaxOpcao, extraData, file) {
    var form = new FormData();
    form.append('opcao', state.opcao || 'dashboard');
    form.append('ajax', 'sim');
    form.append('ajaxOpcao', ajaxOpcao);

    if (extraData) {
        Object.keys(extraData).forEach(function (key) {
            var val = extraData[key];
            form.append(key, typeof val === 'object' && val !== null ? JSON.stringify(val) : val);
        });
    }
    if (file) {
        form.append('file', file);
    }

    return fetch(state.moduleBaseUrl, {
        method: 'POST',
        body: form
    })
    .then(function (response) {
        if (response.status === 401) {
            window.location.href = (window.gestor && window.gestor.raiz ? window.gestor.raiz : '/') + 'signin/';
            throw new Error('Sessão expirada. Redirecionando...');
        }
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.json();
    })
    .then(function (json) {
        if (!json || json.status !== 'Ok') {
            throw new Error((json && json.message) || 'Erro no upload.');
        }
        return json;
    });
}
```

---

## 🏛️ 3. Padrão Canônico Backend em PHP (`modulo.php`)

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

---

## 📦 4. Padrão Legado com jQuery (`ajaxDefault`)

Para módulos e interfaces legadas que utilizam jQuery e Semantic UI:

```javascript
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
        if (dados.status === 'Ok') {
            this.successCallback(dados);
        } else {
            this.successNotOkCallback(dados);
            console.log('ERROR - ' + this.ajaxOpcao + ' - ' + dados.status);
        }
        loadDimmer(false);
    },
    error: function (txt) {
        if (txt.status === 401) {
            window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self");
        } else {
            console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:', txt);
            loadDimmer(false);
        }
    },
    successCallback: function (response) { },
    successNotOkCallback: function (response) { }
};

// Uso:
var ajax = Object.assign({}, ajaxDefault);
ajax.ajaxOpcao = 'minha-acao';
ajax.data = Object.assign({}, ajaxDefault.data, {
    ajaxOpcao: ajax.ajaxOpcao,
    param1: 'valor1'
});
ajax.successCallback = function (response) {
    // response.data ...
};
$.ajax(ajax);
```

---

## ⛔ Regras Invioláveis de AJAX:
1. **SEMPRE envie `ajax: 'sim'`**: Sem este campo, o backend dispara erro `403 Forbidden` por CSRF.
2. **SEMPRE envie `opcao` e `ajaxOpcao`**: Identificam o contexto da página e a ação a ser roteada no `switch ($_GESTOR['ajax-opcao'])`.
3. **Backend DEVE usar `interface_ajax_iniciar()` e `interface_ajax_finalizar()`**: Garante headers JSON corretos, limpeza de buffers e envelope padronizado.
4. **Trate o erro 401 (Unauthorized)**: Redirecione imediatamente para `signin/` em caso de sessão expirada.
5. **Version Bump Obrigatório**: Ao alterar qualquer script JavaScript em `resources/`, incremente a versão (`versao: "X.Y.Z"`) no `<id>.json` ou no manifest do módulo antes de executar `c2f resources:sync` para invalidar o cache do navegador.
