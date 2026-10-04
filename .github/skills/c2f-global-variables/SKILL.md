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

# Superglobais do Conn2Flow (`$_GESTOR`, `$_CONFIG`, `$_BANCO`, `$_ENV`)

Consulte e aplique as seguintes convenções ao acessar ou definir propriedades nas superglobais do Conn2Flow.

## 1. `$_GESTOR` — Estado Dinâmico do Runtime

Array associativo populado durante o bootstrap (`gestor/config.php`). Contém o estado volátil da requisição atual.

### 1.1. Roteamento e Módulo
* `$_GESTOR['modulo-id']`: Identificador do módulo em execução (ex: `admin-paginas`, `produtos`).
* `$_GESTOR['opcao']`: Ação ou subpercurso ativo (ex: `adicionar`, `editar`, `listar`, `status`).
* `$_GESTOR['tipo']`: Classificação secundária da rota (ex: `banco`, `pagina`, `parametros`).
* `$_GESTOR['pagina']`: String contendo o HTML processado da página que será entregue no layout.

### 1.2. Caminhos e Diretórios
* `$_GESTOR['raiz']`: URL base / domínio raiz da instalação com barra final (ex: `https://meusite.com/`).
* `$_GESTOR['ROOT_PATH']`: Caminho absoluto do sistema de arquivos para a raiz do servidor.
* `$_GESTOR['modulos-path']`: Caminho absoluto para a pasta de módulos.
* `$_GESTOR['logs-path']`: Caminho para gravação de arquivos de log.
* `$_GESTOR['linguagem-codigo']`: Código do idioma ativo (ex: `pt-br`, `en`).

### 1.3. Respostas AJAX e JSON
* `$_GESTOR['ajax-json']`: Array associativo retornado ao cliente em requisições AJAX (`status => 'Ok'`, `data => [...]`, `erro => ...`).
* `$_GESTOR['ajax-opcao']`: Identificador da ação AJAX recebida do frontend.
* `$_GESTOR['json']`: Flag booleana indicando se o resultado da requisição será entregue exclusivamente em formato JSON.

### 1.4. Estado do Usuário e Sessão
* `$_GESTOR['usuario-id']`: ID numérico do usuário autenticado na sessão atual.
* `$_GESTOR['usuario-nome']`: Nome exibido do usuário autenticado.

---

## 2. `$_CONFIG` — Configurações Centrais do Sistema

Array populado por `gestor/config.php` a partir de `$_ENV` e valores padrão. Contém configurações persistentes que NÃO mudam entre requisições.

* **Sessões e Cookies**: `$_CONFIG['session_lifetime']`, `$_CONFIG['cookie_secure']`, `$_CONFIG['cookie_httponly']`
* **Segurança CSP/CORS**: `$_CONFIG['csp_policy']`, `$_CONFIG['cors_origins']`
* **OAuth e Autenticação**: `$_CONFIG['oauth_google_client_id']`, `$_CONFIG['oauth_google_secret']`
* **Email/SMTP**: `$_CONFIG['smtp_host']`, `$_CONFIG['smtp_port']`, `$_CONFIG['smtp_user']`, `$_CONFIG['smtp_pass']`
* **Pagamentos**: `$_CONFIG['paypal_client_id']`, `$_CONFIG['stripe_key']`

---

## 3. `$_BANCO` — Configurações de Conexão com Banco de Dados

* `$_BANCO['host']`: Host do servidor MySQL.
* `$_BANCO['nome']`: Nome do banco de dados.
* `$_BANCO['usuario']`: Usuário de conexão.
* `$_BANCO['senha']`: Senha de conexão.
* `$_BANCO['tipo']`: Tipo de driver (`mysql`, `pgsql`).

---

## 4. `$_ENV` — Variáveis de Ambiente (Dotenv)

Carregadas automaticamente pelo `Dotenv` a partir de `gestor/autenticacoes/<dominio>/.env`.

> [!IMPORTANT]
> **Protocolo de Novas Variáveis de Ambiente**: Toda nova chave sensível DEVE ser registrada no template `gestor/autenticacoes.exemplo/dominio/.env` para garantir mesclagem automática no deploy/update. Consulte a skill `c2f-environment-configuration` para o fluxo completo.

---

## 5. Camada de Estado Controlado: `gestor_get()`, `gestor_set()`, `gestor_has()` e `gestor_contexto()`

> [!IMPORTANT]
> **Padrão Obrigatório para Novos Desenvolvimentos (req-229 / Linha 3.1)**:
> Novos módulos e refatorações devem utilizar as funções de acesso controlado da camada `GestorState` em vez de manipular `$_GESTOR` livremente:
> - `gestor_get(string $chave, mixed $padrao = null): mixed`: Leitura segura com suporte nativo a notação pontuada (ex: `gestor_get('banco.conexao.host', 'localhost')`).
> - `gestor_set(string $chave, mixed $valor): bool`: Escrita controlada com proteção contra sobrescrita acidental de chaves críticas.
> - `gestor_has(string $chave): bool`: Verificação determinística de existência (suporta notação pontuada).
> - `gestor_contexto(string $escopo): array`: Extração de fatias delimitadas de contexto (`modulo`, `usuario`, `sistema`, etc.).

### 5.1. Chaves Críticas Protegidas (Somente-Leitura após Boot)
As seguintes chaves do Core são imutáveis via `gestor_set()`:
* `raiz-absoluta`
* `url-raiz`
* `linguagem-codigo`
* `versao-num`

Qualquer tentativa de mutação acidental nessas chaves é bloqueada (retorna `false`) e gera alerta de auditoria nos logs.

### 5.2. 100% de Retrocompatibilidade
O array superglobal `$_GESTOR` continua existindo por debaixo dos panos. Códigos legados continuam operando normalmente sem quebras.
