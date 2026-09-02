---
name: c2f-environment-configuration
description: "LEIA ANTES de adicionar ou manipular credenciais, variáveis de ambiente (.env) e configurações centrais em config.php. Se não ler: segredos vazam no repositório git público, variáveis não propagam para produção ou geram erros fatais."
user-invocable: false
---

# Configuração de Ambiente e Variáveis Sensíveis do Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Declarar, ler ou alterar variáveis de ambiente (`$_ENV`), constantes em `gestor/config.php` ou templates em `autenticacoes.exemplo/`.
- **SKIP APENAS SE**: Valores de apresentação/UI que devem residir obrigatoriamente no sistema de variáveis (`variables.json`).
- **CONSEQUÊNCIA DE IGNORAR**: Vazamento de credenciais privadas em repositórios públicos, erros fatais de configuração indefinida em produção ou mistura indevida de dados de apresentação no `.env`.

---

> [!CAUTION]
> **PROTOCOLO OBRIGATÓRIO PARA CREDENCIAIS E SEGREDOS**:
> NUNCA insira credenciais, API keys, tokens ou senhas diretamente no código-fonte PHP!
> Todo dado sensível DEVE seguir o fluxo: `.env` -> `config.php` -> `$_CONFIG` ou `$_GESTOR`.

## 1. Fluxo Obrigatório para Novas Variáveis Sensíveis

### Passo 1: Registrar no Template `.env`
Arquivo: `gestor/autenticacoes.exemplo/dominio/.env`

```env
# === Minha Nova Integração ===
MINHA_API_KEY=
MINHA_API_SECRET=
MINHA_WEBHOOK_URL=
```

> [!IMPORTANT]
> O arquivo `autenticacoes.exemplo/` é o template de referência versionado no Git. A instalação real fica em `gestor/autenticacoes/<dominio>/.env` (que é gitignored). Ao registrar no template, você garante que novos deploys e desenvolvedores saibam quais variáveis configurar.

### Passo 2: Mapear em `gestor/config.php`
```php
// Em gestor/config.php, dentro do bloco de carregamento de configurações:
$_CONFIG['minha_api_key']    = $_ENV['MINHA_API_KEY'] ?? '';
$_CONFIG['minha_api_secret'] = $_ENV['MINHA_API_SECRET'] ?? '';
$_CONFIG['minha_webhook_url'] = $_ENV['MINHA_WEBHOOK_URL'] ?? '';
```

### Passo 3: Consumir no Código
```php
// Em qualquer módulo PHP:
$apiKey = $_CONFIG['minha_api_key'];
$secret = $_CONFIG['minha_api_secret'];

// Verificação antes de uso:
if (empty($_CONFIG['minha_api_key'])) {
    // Log de erro ou fallback seguro
}
```

---

## 2. Governança da Variável `HTML_SANITIZE`

O Conn2Flow utiliza a flag `HTML_SANITIZE` no `.env` para controlar a minificação e limpeza do HTML entregue:

```env
# Habilita a higienização e minificação de HTML para visitantes públicos (padrão: true)
HTML_SANITIZE=true
```

* **`HTML_SANITIZE=true` (Padrão em Produção)**:
  - Remove comentários HTML (`<!-- ... -->`), CSS (`/* ... */`) e JS (`// ...`), compactando espaços em branco para visitantes anônimos.
  - **Bypass Automático**: Usuários autenticados no Gestor ou com Live Editor ativo (`gestor_dashboard_toolbar_ativo() === true`) têm a sanitização 100% desligada para preservar marcadores de widgets (`<!-- widgets#... -->`) e notas de layout.
* **`HTML_SANITIZE=false` (Debug Global)**:
  - Desativa a sanitização incondicionalmente para todos os visitantes durante depurações profundas de layout.

---

## 3. Categorias de Configuração em `$_CONFIG`

| Categoria | Exemplos de Chaves | Origem |
|---|---|---|
| **Banco de Dados** | Via `$_BANCO['host']`, `$_BANCO['nome']` | `.env` |
| **Sessões/Cookies** | `session_lifetime`, `cookie_secure`, `cookie_httponly` | `.env` / hardcoded |
| **Segurança CSP/CORS** | `csp_policy`, `cors_origins` | `config.php` |
| **Performance HTML** | `html_sanitize` | `.env` |
| **OAuth** | `oauth_google_client_id`, `oauth_google_secret` | `.env` |
| **Email/SMTP** | `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass` | `.env` |
| **Pagamentos** | `paypal_client_id`, `stripe_key` | `.env` |
| **APIs Externas** | `openai_api_key`, `anthropic_api_key` | `.env` |

---

## 4. Estrutura de Diretórios

```
gestor/
  autenticacoes.exemplo/    <-- Template (versionado no Git)
    dominio/
      .env                  <-- Exemplo com todas as chaves documentadas
  autenticacoes/            <-- Instalação real (GITIGNORED)
    meusite.com/
      .env                  <-- Valores reais e secretos
  config.php                <-- Bootstrap: carrega .env e popula $_CONFIG, $_BANCO, $_GESTOR
```

---

## Configuração no ambiente VM HestiaCP (req-034)

### O `.env` ativo mudou de lugar

Com o Gestor na VM, o `.env` que o site realmente lê **não está no repositório de
autoria** — está no servidor:

```
/home/<usuario-hestia>/web/<dominio>/conn2flow-gestor/autenticacoes/<dominio>/.env
```

Editar o `.env` do repositório local não tem efeito nenhum sobre
`https://conn2flow.local/`. Leia e escreva pelo SSH, e sempre com backup:

```bash
ssh otavio@192.168.1.108 'sudo cp <env> <env>.before-<req> && sudo tee -a <env>'
sudo chown admin:admin <env>   # o pool PHP-FPM roda como o dono do docroot
```

> [!WARNING]
> `autenticacoes/` guarda uma pasta POR HOST. Se houver uma pasta vazia
> (`localhost/`, por exemplo) e o `SERVER_NAME` da execução não bater com o
> domínio, o Gestor carrega a pasta errada. Em CLI, declare o host explicitamente.

### Chaves do driver SSH do HestiaCP

O pool PHP-FPM do tenant roda como o usuário do tenant: sem `sudo` e com
`open_basedir` fora de `/usr/local/hestia`. O driver `cli` não funciona nesse
contexto. Use `HESTIACP_DRIVER=ssh`:

```dotenv
HESTIACP_DRIVER=ssh                                  # cli | api | ssh
HESTIACP_SSH_HOST=127.0.0.1
HESTIACP_SSH_USER=otavio                             # conta COM sudo no servidor
HESTIACP_SSH_PORT=22
HESTIACP_SSH_IDENTITY=/home/admin/.ssh/hestia_driver # chave dedicada, lida pelo binário ssh
HESTIACP_SSH_SUDO=true
HESTIACP_BIN_PATH=/usr/local/hestia/bin
```

> [!CAUTION]
> Essa chave dá ao processo web acesso a uma conta com `sudo` irrestrito — na
> prática, root na máquina. É aceitável no Lab descartável; em produção,
> restrinja-a por comando forçado no `authorized_keys` (wrapper que só aceita
> `v-*`) ou prefira `HESTIACP_DRIVER=api` com `HESTIACP_ACCESS_KEY`/`SECRET_KEY`.

### Configuração do Host Manager que costuma faltar

`HOST_MANAGER_SERVER_IP` é obrigatória para provisionar tenants e sua ausência
aparece adiante como `Parâmetro obrigatório ausente: server_ip`:

```dotenv
HOST_MANAGER_SERVER_IP=192.168.1.108
CLOUDFLARE_API_TOKEN=...    # dispensável para domínios .local (a etapa é pulada)
CLOUDFLARE_ACCOUNT_ID=...
```

### Leia `$_ENV` antes de `getenv()`

O Dotenv do Gestor é carregado em modo imutável e, conforme os adaptadores
ativos, pode preencher apenas a superglobal. Código que consulta só `getenv()`
enxerga como ausente uma variável que está no `.env`. O padrão correto é o de
`config-project.php`:

```php
$valor = $_ENV['MINHA_CHAVE'] ?? getenv('MINHA_CHAVE');
```
