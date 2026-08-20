---
name: c2f-javascript-ajax
description: "LEIA ANTES de escrever chamadas AJAX, requisições fetch ou scripts frontend que comunicam com o backend. Se não ler: erros 401 não tratam expiração de sessão, loaders congelam na tela e formulários duplicam envio."
user-invocable: false
---

# Padrão de Chamadas AJAX e JavaScript Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Escrever código JavaScript frontend que realiza requisições assíncronas (AJAX / Fetch) para controladores do Gestor.
- **SKIP APENAS SE**: Scripts utilitários puramente visuais (ex: animações simples sem comunicação assíncrona).
- **CONSEQUÊNCIA DE IGNORAR**: Travamento da UI com spinners infinitos em caso de erro, perda de token CSRF, ausência de tratamento de sessão expirada (401) e cliques duplicados em botões de ação.

---

Consulte e aplique as seguintes convenções ao implementar requisições AJAX e componentes de interface no frontend Conn2Flow:

1. **Estrutura Base do `ajaxDefault`**:
   - O objeto base utiliza variáveis do objeto global `gestor`:
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
     ```

2. **Padrão de Função de Chamada (`ajaxCall`)**:
   - Clone `ajaxDefault`, defina `ajaxOpcao` e preencha os parâmetros e callbacks:
     ```javascript
     function minhaAcaoAjax() {
         var ajax = Object.assign({}, ajaxDefault);
         ajax.ajaxOpcao = 'minha_opcao_backend';
         ajax.data = Object.assign({}, ajaxDefault.data, {
             ajaxOpcao: ajax.ajaxOpcao,
             params: {
                 param1: 'valor1',
                 param2: 'valor2'
             }
         });

         ajax.successCallback = function (response) {
             if (response.data) {
                 // Processar resposta com sucesso
             }
         };

         ajax.successNotOkCallback = function (response) {
             // Tratar resposta Not OK do backend
         };

         $.ajax(ajax);
     }
     ```

3. **Integração com Componentes Semantic UI (`.dropdown`)**:
   - Para disparar callbacks ao alterar seleções em dropdowns Semantic UI:
     ```javascript
     $('.dropdown').dropdown({
         onChange: function (value, text, $choice) {
             setTimeout(function () {
                 minhaFuncaoCallback();
             }, 100);
         }
     });
     ```
