---
name: c2f-global-variables
description: "LEIA ANTES de ler ou escrever nas superglobais $_GESTOR, $_CONFIG, $_BANCO ou $_ENV. Se não ler: estado de roteamento é sobrescrito, dados de sessão são violados e respostas AJAX corrompem o envelope JSON."
user-invocable: false
---

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Acessar, modificar ou registrar dados nas superglobais centrais do Conn2Flow (`$_GESTOR`, `$_CONFIG`, `$_BANCO`, `$_ENV`).
- **SKIP APENAS SE**: Funções puras que recebem todos os parâmetros por argumento sem usar estado global.
- **CONSEQUÊNCIA DE IGNORAR**: Colisão de chaves no array global, corrupção da resposta AJAX (`$_GESTOR['ajax-resposta']`), ou desvio no fluxo de roteamento de páginas e módulos.

---

﻿---
name: c2f-global-variables
description: "Use ao consultar, acessar ou modificar as superglobais do Conn2Flow: $_GESTOR (runtime), $_CONFIG (configuracao central), $_BANCO (conexao DB) e $_ENV (ambiente)."
user-invocable: false
---

# Superglobais do Conn2Flow (`$_GESTOR`, `$_CONFIG`, `$_BANCO`, `$_ENV`)

Consulte e aplique as seguintes convenÃ§Ãµes ao acessar ou definir propriedades nas superglobais do Conn2Flow.

## 1. `$_GESTOR` â€” Estado DinÃ¢mico do Runtime

Array associativo populado durante o bootstrap (`gestor/config.php`). ContÃ©m o estado volÃ¡til da requisiÃ§Ã£o atual.

### 1.1. Roteamento e MÃ³dulo
* `$_GESTOR['modulo-id']`: Identificador do mÃ³dulo em execuÃ§Ã£o (ex: `admin-paginas`, `produtos`).
* `$_GESTOR['opcao']`: AÃ§Ã£o ou subpercurso ativo (ex: `adicionar`, `editar`, `listar`, `status`).
* `$_GESTOR['tipo']`: ClassificaÃ§Ã£o secundÃ¡ria da rota (ex: `banco`, `pagina`, `parametros`).
* `$_GESTOR['pagina']`: String contendo o HTML processado da pÃ¡gina que serÃ¡ entregue no layout.

### 1.2. Caminhos e DiretÃ³rios
* `$_GESTOR['raiz']`: URL base / domÃ­nio raiz da instalaÃ§Ã£o com barra final (ex: `https://meusite.com/`).
* `$_GESTOR['ROOT_PATH']`: Caminho absoluto do sistema de arquivos para a raiz do servidor.
* `$_GESTOR['modulos-path']`: Caminho absoluto para a pasta de mÃ³dulos.
* `$_GESTOR['logs-path']`: Caminho para gravaÃ§Ã£o de arquivos de log.
* `$_GESTOR['linguagem-codigo']`: CÃ³digo do idioma ativo (ex: `pt-br`, `en`).

### 1.3. Respostas AJAX e JSON
* `$_GESTOR['ajax-json']`: Array associativo retornado ao cliente em requisiÃ§Ãµes AJAX (`status => 'Ok'`, `data => [...]`, `erro => ...`).
* `$_GESTOR['ajax-opcao']`: Identificador da aÃ§Ã£o AJAX recebida do frontend.
* `$_GESTOR['json']`: Flag booleana indicando se o resultado da requisiÃ§Ã£o serÃ¡ entregue exclusivamente em formato JSON.

### 1.4. Estado do UsuÃ¡rio e SessÃ£o
* `$_GESTOR['usuario-id']`: ID numÃ©rico do usuÃ¡rio autenticado na sessÃ£o atual.
* `$_GESTOR['usuario-nome']`: Nome exibido do usuÃ¡rio autenticado.

---

## 2. `$_CONFIG` â€” ConfiguraÃ§Ãµes Centrais do Sistema

Array populado por `gestor/config.php` a partir de `$_ENV` e valores padrÃ£o. ContÃ©m configuraÃ§Ãµes persistentes que NÃƒO mudam entre requisiÃ§Ãµes.

* **SessÃµes e Cookies**: `$_CONFIG['session_lifetime']`, `$_CONFIG['cookie_secure']`, `$_CONFIG['cookie_httponly']`
* **SeguranÃ§a CSP/CORS**: `$_CONFIG['csp_policy']`, `$_CONFIG['cors_origins']`
* **OAuth e AutenticaÃ§Ã£o**: `$_CONFIG['oauth_google_client_id']`, `$_CONFIG['oauth_google_secret']`
* **Email/SMTP**: `$_CONFIG['smtp_host']`, `$_CONFIG['smtp_port']`, `$_CONFIG['smtp_user']`, `$_CONFIG['smtp_pass']`
* **Pagamentos**: `$_CONFIG['paypal_client_id']`, `$_CONFIG['stripe_key']`

---

## 3. `$_BANCO` â€” ConfiguraÃ§Ãµes de ConexÃ£o com Banco de Dados

* `$_BANCO['host']`: Host do servidor MySQL.
* `$_BANCO['nome']`: Nome do banco de dados.
* `$_BANCO['usuario']`: UsuÃ¡rio de conexÃ£o.
* `$_BANCO['senha']`: Senha de conexÃ£o.
* `$_BANCO['tipo']`: Tipo de driver (`mysql`, `pgsql`).

---

## 4. `$_ENV` â€” VariÃ¡veis de Ambiente (Dotenv)

Carregadas automaticamente pelo `Dotenv` a partir de `gestor/autenticacoes/<dominio>/.env`.

> [!IMPORTANT]
> **Protocolo de Novas VariÃ¡veis de Ambiente**: Toda nova chave sensÃ­vel DEVE ser registrada no template `gestor/autenticacoes.exemplo/dominio/.env` para garantir mesclagem automÃ¡tica no deploy/update. Consulte a skill `c2f-environment-configuration` para o fluxo completo.
