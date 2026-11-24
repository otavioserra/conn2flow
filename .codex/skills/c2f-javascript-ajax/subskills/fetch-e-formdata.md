# Frontend Vanilla: `fetch` e `FormData` — receita completa

Subskill de `c2f-javascript-ajax`. Leia ao escrever a função de requisição de um módulo moderno (dados ou upload). As regras invioláveis também estão na skill-mãe.

## Funções canônicas

Em módulos modernos com JavaScript puro (Vanilla JS), utilize sempre o padrão canônico com `URLSearchParams` ou `FormData`:

### A) Requisições de Dados Estruturados (`application/x-www-form-urlencoded`):
```javascript
/**
 * Padrão Canônico de Requisição AJAX no Gestor (Vanilla JS)
 * @param {Object} state - Estado do módulo (deve conter opcao e moduleBaseUrl)
 * @param {string} ajaxOpcao - Ação a ser executada no backend (ex: 'salvar-dados', 'listar-itens')
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
