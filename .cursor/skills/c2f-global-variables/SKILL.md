---
name: c2f-global-variables
description: Use ao consultar, acessar ou modificar a variável superglobal $_GESTOR no Conn2Flow: chaves de roteamento, caminhos de ambiente, estado de sessão e respostas AJAX.
user-invocable: false
---

# Estrutura da Variável Superglobal `$_GESTOR` (`CONN2FLOW-GLOBAL-VARIABLES.md`)

Consulte e aplique as seguintes convenções ao acessar ou definir propriedades em `$_GESTOR`:

## 1. Chaves de Roteamento e Módulo

* `$_GESTOR['modulo-id']`: Identificador do módulo em execução (ex: `admin-paginas`, `produtos`).
* `$_GESTOR['opcao']`: Ação ou subpercurso ativo (ex: `adicionar`, `editar`, `listar`, `status`).
* `$_GESTOR['tipo']`: Classificação secundária da rota (ex: `banco`, `pagina`, `parametros`).
* `$_GESTOR['pagina']`: String contendo o HTML processado da página que será entregue no layout.

---

## 2. Caminhos e Diretórios de Ambiente

* `$_GESTOR['raiz']`: URL base / domínio raiz da instalação com barra final (ex: `https://meusite.com/`).
* `$_GESTOR['ROOT_PATH']`: Caminho absoluto do sistema de arquivos para a raiz do servidor.
* `$_GESTOR['logs-path']`: Caminho para gravação de arquivos de log (`gestor/logs/` ou caminho configurado).
* `$_GESTOR['linguagem-codigo']`: Código do idioma ativo (ex: `pt-br`, `en`).

---

## 3. Respostas AJAX e JSON

* `$_GESTOR['ajax-json']`: Array associativo retornado ao cliente em requisições AJAX (`status => 'Ok'`, `data => [...]`, `erro => ...`).
* `$_GESTOR['ajax-opcao']`: Identificador da ação AJAX recebida do frontend.
* `$_GESTOR['json']`: Flag booleana indicando se o resultado da requisição será entregue exclusivamente em formato JSON.

---

## 4. Estado do Usuário e Sessão

* `$_GESTOR['usuario-id']`: ID numérico do usuário autenticado na sessão atual.
* `$_GESTOR['usuario-nome']`: Nome exibido do usuário autenticado.
