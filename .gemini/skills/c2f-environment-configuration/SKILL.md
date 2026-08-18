---
name: c2f-environment-configuration
description: "Use ao adicionar, ler ou modificar credenciais, API keys, tokens, parametros de conexao e configuracoes sensiveis no Conn2Flow."
user-invocable: false
---

# ConfiguraÃ§Ã£o de Ambiente e VariÃ¡veis SensÃ­veis do Conn2Flow

> [!CAUTION]
> **PROTOCOLO OBRIGATÃ“RIO PARA CREDENCIAIS E SEGREDOS**:
> NUNCA insira credenciais, API keys, tokens ou senhas diretamente no cÃ³digo-fonte PHP!
> Todo dado sensÃ­vel DEVE seguir o fluxo: `.env` -> `config.php` -> `$_CONFIG` ou `$_GESTOR`.

## 1. Fluxo ObrigatÃ³rio para Novas VariÃ¡veis SensÃ­veis

### Passo 1: Registrar no Template `.env`
Arquivo: `gestor/autenticacoes.exemplo/dominio/.env`

```env
# === Minha Nova IntegraÃ§Ã£o ===
MINHA_API_KEY=
MINHA_API_SECRET=
MINHA_WEBHOOK_URL=
```

> [!IMPORTANT]
> O arquivo `autenticacoes.exemplo/` Ã© o template de referÃªncia. A instalaÃ§Ã£o real fica em `gestor/autenticacoes/<dominio>/.env` (que Ã© gitignored). Ao registrar no template, vocÃª garante que novos deploys e desenvolvedores saibam quais variÃ¡veis configurar.

### Passo 2: Mapear em `gestor/config.php`
```php
// Em gestor/config.php, dentro do bloco de carregamento de configuraÃ§Ãµes:
$_CONFIG['minha_api_key']    = $_ENV['MINHA_API_KEY'] ?? '';
$_CONFIG['minha_api_secret'] = $_ENV['MINHA_API_SECRET'] ?? '';
$_CONFIG['minha_webhook_url'] = $_ENV['MINHA_WEBHOOK_URL'] ?? '';
```

### Passo 3: Consumir no CÃ³digo
```php
// Em qualquer mÃ³dulo PHP:
$apiKey = $_CONFIG['minha_api_key'];
$secret = $_CONFIG['minha_api_secret'];

// VerificaÃ§Ã£o antes de uso:
if (empty($_CONFIG['minha_api_key'])) {
    // Log de erro ou fallback seguro
}
```

---

## 2. Categorias de ConfiguraÃ§Ã£o em `$_CONFIG`

| Categoria | Exemplos de Chaves | Origem |
|---|---|---|
| **Banco de Dados** | Via `$_BANCO['host']`, `$_BANCO['nome']` | `.env` |
| **SessÃµes/Cookies** | `session_lifetime`, `cookie_secure`, `cookie_httponly` | `.env` / hardcoded |
| **SeguranÃ§a CSP/CORS** | `csp_policy`, `cors_origins` | `config.php` |
| **OAuth** | `oauth_google_client_id`, `oauth_google_secret` | `.env` |
| **Email/SMTP** | `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass` | `.env` |
| **Pagamentos** | `paypal_client_id`, `stripe_key` | `.env` |
| **APIs Externas** | `openai_api_key`, `anthropic_api_key` | `.env` |

---

## 3. Estrutura de DiretÃ³rios

```
gestor/
  autenticacoes.exemplo/    <-- Template (versionado no Git)
    dominio/
      .env                  <-- Exemplo com todas as chaves documentadas
  autenticacoes/            <-- InstalaÃ§Ã£o real (GITIGNORED)
    meusite.com/
      .env                  <-- Valores reais e secretos
  config.php                <-- Bootstrap: carrega .env e popula $_CONFIG, $_BANCO, $_GESTOR
```
