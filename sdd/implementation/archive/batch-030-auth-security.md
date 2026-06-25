# BATCH-030 - Autenticação, 2FA, Social Login e Segurança

## Escopo do Lote
Este lote implementa melhorias de segurança de autenticação no sistema, incluindo o controle global de métodos de login permitidos e a imposição de autenticação de dois fatores (2FA) via Aplicativo Autenticador (Google Authenticator) e via E-mail no módulo `admin-environment`. Adiciona também login social (OAuth 2.0 Google/Meta), rotação dinâmica de chaves JWT no banco com período de carência (grace period) e endurecimento de segurança contra Session Hijacking e ataques CSRF no painel administrativo e no módulo `perfil-usuario`.

---

## Progresso por Slice

O lote foi quebrado em slices pequenos com alvo de validação explícito:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Migrações Phinx (2FA em `usuarios` + `usuarios_provedores`) | implementado | `php -l` OK |
| 2 | Bibliotecas puras `2fa.php` / `jwt.php` / `oauth.php` | implementado | `php -l` OK + PHPUnit 20/20 (42 asserts) + standalone 28/28 (vetores RFC 4226/6238) |
| 3 | `admin-environment` (toggles login/2FA, credenciais OAuth/JWT no `.env`, rotação JWT) | implementado | `php -l` + `node --check` + JSON OK |
| 4 | `perfil-usuario` (rota de Segurança: 2FA QR/e-mail + vínculos sociais) | implementado | `php -l` + `node --check` + JSON OK |
| 5 | Login admin (render dinâmico + interceptador 2FA + login social) | implementado | `php -l` + `node --check` + JSON OK |
| 6 | Endurecimento (CSRF infra + Session Hijacking) | implementado | `php -l` + PHPUnit `SegurancaTest` |

> **Pendência transversal com o operador**: aplicar as migrações (`Update => Core` / `phinx migrate`), registrar os recursos novos (páginas `signin-2fa`/`social-login`/`oauth-callback` + variáveis i18n) e validar runtime (login tradicional, social, 2FA App/E-mail, rotação JWT, session hijacking).
>
> **CSRF (Slice 6)**: helpers `gestor_csrf_token()`/`gestor_csrf_validar()` prontos em `bibliotecas/seguranca.php`; o rollout estrito em 100% dos controllers admin é incremental e exige validação runtime (aplicação global cega quebraria os AJAX legados que ainda não enviam o token).

---

## Checklist de Implementação

### 1. Métodos de Autenticação e Configuração Global (`admin-environment`)
- [x] Criar migração Phinx para adicionar colunas de 2FA na tabela de usuários (`usuarios`): `two_factor_secret` (varchar), `two_factor_enabled` (boolean), `two_factor_type` (varchar), `two_factor_email_code` (varchar) e `two_factor_email_expire` (datetime). → `20260706100000_add_two_factor_to_usuarios_table.php`
- [x] Implementar classe de suporte a TOTP (RFC 6238) para geração de segredos e validação de códigos compatível com Google Authenticator. → `bibliotecas/2fa.php` (`two_factor_generate_secret`/`two_factor_get_qr_code`/`two_factor_validate_code`).
- [x] Implementar rotina de geração e validação de códigos 2FA via e-mail corporativo. → `bibliotecas/2fa.php` (`two_factor_email_send_code`/`two_factor_email_validate`, usa `comunicacao_email`).
- [x] Adicionar controles globais de autenticação na aba "Configurações de Usuário" do `admin-environment`:
  - [x] Checkbox para habilitar/desabilitar cada método de login separadamente (Senha, Google, Meta).
  - [x] Checkbox para forçar 2FA obrigatório sistêmico (`AUTH_2FA_REQUIRED`).
  - [x] Checkboxes para autorizar métodos de 2FA (App e/ou E-mail).
  - [x] Campos para credenciais OAuth (Client ID, Client Secret e visualização de URIs de callback) e campos para controles de rotação JWT (dias de expiração e horas de carência) + botão "Rotacionar Chaves JWT".
  - [x] Salvar essas chaves dinamicamente no arquivo `.env` (via `admin_environment_env_write`).

### 2. Login Social e Vínculos (OAuth 2.0 Google / Meta)
- [x] Criar tabela de associações de provedores sociais (`usuarios_provedores`): `usuario_id` (FK), `provider_name` (google, meta), `provider_uid` (unique index), `created_at`. → `20260706100010_create_usuarios_provedores_table.php`. **Observação de contrato**: relacionamento por coluna integer + índice (sem FK física — convenção do legado; nenhuma migração do projeto usa `addForeignKey`); índice único composto `(provider_name, provider_uid)`.
- [x] Criar biblioteca de integração OAuth 2.0 (`bibliotecas/oauth.php`) encapsulando fluxos de autorização, redirecionamento e captura de perfil para Google e Meta. → `oauth_redirect_url`/`oauth_authenticate_code`/`oauth_validate_state` (Authorization Code + `state` CSRF; callback `/_api/auth/callback/{provider}`).
- [x] Adicionar seção de "Segurança" no módulo de perfil (`modulos/perfil-usuario/?configurar-seguranca=sim`):
  - [x] Botões para ativar/desativar 2FA de acordo com os métodos permitidos globalmente (App TOTP com QR client-side + E-mail).
  - [x] Botões de "Vincular Conta" (Google/Meta) e status de vínculo social para a conta conectada.
- [x] Integrar botões de Login Social na tela de login administrativa. Se o provedor retornar e-mail coincidente e associado, efetuar login (redirecionando para 2FA se ativo/obrigatório). → `social-login`/`oauth-callback` + interceptador 2FA no callback.

### 3. Rotação Dinâmica de Chaves JWT
- [x] Implementar biblioteca de geração e validação de tokens JSON Web Token (`bibliotecas/jwt.php`) com suporte a histórico de chaves no banco de dados (`variaveis` tabela). → HS256 com `kid`; chaves em `variaveis` (`modulo='sistema'`, `id='jwt_keys'`, coluna `valor` JSON — não há coluna `chave` na tabela).
- [ ] Adicionar suporte a tokens JWT nos endpoints de integração e APIs internas (`_api/`).
- [x] Implementar rotação de chaves JWT (desativa chave ativa, cria nova ativa, grace period de 24h para chaves antigas). → `jwt_rotate_keys()` (lógica na lib; o gatilho de UI/cron vem no Slice 3).
- [ ] Implementar auto-renovação de tokens JWT expirados em carência na resposta se o usuário estiver validado pela chave antiga no grace period. → `jwt_validate_token()` já retorna `['status'=>'Grace']`; a renovação na resposta é responsabilidade do endpoint (pendente).

### 4. Endurecimento de Endpoints Administrativos
- [~] Implementar validação estrita de tokens CSRF em todas as requisições de alteração de estado (POST/PUT/DELETE) no painel do gestor. → **Infra pronta** (`gestor_csrf_token`/`gestor_csrf_validar` em `bibliotecas/seguranca.php`); rollout estrito em 100% dos controllers é **incremental** (validação runtime; aplicação global cega quebraria AJAX legados sem token).
- [x] Implementar proteção contra sequestro de sessão (Session Hijacking): validar `User-Agent` e correspondência de bloco de IP (3 primeiros octetos) do cliente a cada carregamento de página, destruindo a sessão se houver discrepâncias suspeitas. → `seguranca_sessao_validar()` no `gestor_permissao_token()` (fail-safe) + registro no login.
- [ ] Adicionar logs de eventos de segurança (login bem-sucedido, falhas de autenticação, ativação/desativação de 2FA, rotação JWT) via biblioteca nativa `log.php`. → **Pendente** (próximo slice corretivo).

---

## Validação Esperada
O lote será considerado completo após aprovação na checklist de validação de `BATCH-030`.
