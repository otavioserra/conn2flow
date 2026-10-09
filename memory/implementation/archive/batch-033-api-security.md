# BATCH-033 - Segurança no Acesso e Geração de Chaves de API

## Escopo do Lote
Este lote adiciona controles estritos de segurança ao endpoint de autenticação e geração de chaves de API (`oauth-authenticate/`). Adiciona uma nova aba **API** no painel administrativo `admin-environment` permitindo definir quais perfis de usuários (`usuarios_perfis`) estão autorizados a emitir chaves, quais os métodos de login aceitos (senha e/ou código por e-mail) e a imposição de verificação de segundo fator (2FA via app ou e-mail) antes de liberar e entregar os tokens de acesso gerados.

---

## Progresso por Slice

O lote foi quebrado em slices pequenos com alvo de validação explícito:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | `admin-environment` (aba API, lista de perfis, toggles de login/2FA para API e .env) | concluído | `php -l` + `node --check` |
| 2 | `perfil-usuario` UI (ajustes em `oauth-authenticate` e nova página `oauth-authenticate-2fa`) | concluído | `node --check` + validação de JSON |
| 3 | `perfil-usuario` Backend (validação de perfil, interceptador 2FA, holding de tokens e verificação) | concluído | `php -l` + `composer test` |

---

## Checklist de Implementação

### 1. Painel de Configurações (`admin-environment`)
- [x] Criar a aba **API** (`data-tab="api"`) no arquivo `admin-environment.html` (pt-br e en) com a respectiva estilização.
- [x] No PHP de `admin-environment.php`, carregar a lista de perfis ativos (`usuarios_perfis`) e renderizar na aba em formato de checkboxes.
- [x] Mapear e salvar no `.env` (via `admin_environment_env_write`):
  - `AUTH_API_ALLOWED_PROFILES` (lista de IDs de perfis marcados separados por vírgula).
  - `AUTH_API_METHOD_PASSWORD_ACTIVE` (boolean).
  - `AUTH_API_METHOD_EMAIL_ACTIVE` (boolean).
  - `AUTH_API_2FA_REQUIRED` (boolean).
  - `AUTH_API_2FA_METHOD_APP` (boolean).
  - `AUTH_API_2FA_METHOD_EMAIL` (boolean).

### 2. Interface de Autenticação da API (`perfil-usuario`)
- [x] Ajustar a tela `oauth-authenticate` para suportar alternância de campos se login por senha e e-mail estiverem ativos (comportamento análogo ao `signin`).
- [x] Criar o novo template de página `oauth-authenticate-2fa` (HTML + arquivos JSON em pt-br e en) com campo para inserir o código de 6 dígitos e botão para reenviar código por e-mail.

### 3. Backend e Roteamento de Segurança (`perfil-usuario.php`)
- [x] No início de `perfil_usuario_oauth_authenticate()`, obter o perfil do usuário logado/tentando logar e bloquear o acesso se seu ID não estiver na lista `AUTH_API_ALLOWED_PROFILES`.
- [x] Adaptar a validação inicial para suportar login por código OTP sem senha se `AUTH_API_METHOD_EMAIL_ACTIVE` estiver ativo.
- [x] Integrar o interceptador 2FA após a validação inicial das credenciais: se `AUTH_API_2FA_REQUIRED` for true ou o usuário tiver 2FA ativo, armazenar a resposta de tokens em `pending_oauth_tokens` na sessão, definir `pending_oauth_user/mode/type` e redirecionar para `oauth-authenticate-2fa/`.
- [x] Implementar a rota/função `perfil_usuario_oauth_authenticate_2fa()` para renderizar a página 2FA de API, validar o código enviado (TOTP ou e-mail code) e, em caso de sucesso, recuperar `pending_oauth_tokens`, limpar a sessão e entregar a resposta JSON/redirecionamento com chaves.
- [x] Adicionar as variáveis criadas para o BATCH-033 com seus valores default no arquivo de template `gestor/autenticacoes.exemplo/dominio/.env`.

---

## Validação Esperada
- Testes manuais cobrindo o painel administrativo (aba API) e a rota de geração de token da API.
- Linting estático limpo nos arquivos alterados.
