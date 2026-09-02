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
