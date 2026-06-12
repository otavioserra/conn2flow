# BATCH-030 - Autenticação, 2FA, Social Login e Segurança

## Escopo do Lote
Este lote implementa melhorias de segurança no módulo de perfil de usuário (`perfil-usuario`), incluindo autenticação de dois fatores (2FA), integração de login social (OAuth 2.0 com Google/Meta), gerenciamento e validação de tokens JWT para sessões de API, e auditoria/endurecimento da segurança de endpoints contra sequestro de sessão e ataques CSRF.

---

## Checklist de Implementação

### 1. Autenticação de Dois Fatores (2FA)
- [ ] Criar migração Phinx para adicionar colunas de 2FA na tabela de usuários (`usuarios`): `two_factor_secret` (varchar, null), `two_factor_enabled` (boolean, default false).
- [ ] Implementar classe de suporte a TOTP (RFC 6238) para geração de segredos e validação de códigos (compatível com Google Authenticator/Authy).
- [ ] Adicionar aba "Segurança" no CRUD de perfil de usuário (`modulos/perfil-usuario`):
  - [ ] Fluxo de ativação: exibir QR Code (via biblioteca local ou CDN segura) + campo de confirmação do código.
  - [ ] Fluxo de desativação: exigir senha do usuário e código 2FA ativo para desativar.
- [ ] Integrar validação 2FA no fluxo de login administrativo (`controladores/admin/login.php`):
  - [ ] Se 2FA estiver ativo, redirecionar para tela intermediária de inserção do código de 6 dígitos antes de iniciar a sessão completa.

### 2. Login Social (OAuth 2.0 Google / Meta)
- [ ] Criar tabela de associações de provedores sociais (`usuarios_provedores`): `usuario_id`, `provider_name` (google, meta), `provider_uid`, `created_at`.
- [ ] Criar biblioteca de integração OAuth 2.0 (`bibliotecas/oauth.php`) encapsulando fluxos de autorização, redirecionamento e captura de perfil para Google e Meta (Facebook).
- [ ] Adicionar botões de "Vincular Conta" (Google/Meta) na aba de Segurança do Perfil de Usuário.
- [ ] Integrar botões de Login Social na tela de login administrativa. Se o e-mail retornado pelo provedor coincidir e estiver vinculado, autenticar diretamente (aplicando 2FA se ativo).

### 3. Sessões baseadas em JWT para Endpoints da API
- [ ] Implementar biblioteca de geração e validação de tokens JSON Web Token (`bibliotecas/jwt.php`) usando chaves privadas geradas localmente.
- [ ] Adicionar suporte a tokens JWT nos endpoints de integração e APIs internas (`_api/`), permitindo autenticação stateless segura.
- [ ] Configurar tempo de expiração curto (ex: 15 minutos) e fluxo de refresh token para sessões ativas.

### 4. Endurecimento de Endpoints Administrativos
- [ ] Implementar validação estrita de tokens CSRF em todas as requisições de alteração de estado (POST/PUT/DELETE) no painel do gestor.
- [ ] Implementar proteção contra sequestro de sessão (Session Hijacking): validar `User-Agent` e variação de bloco de IP do cliente a cada carregamento de página, destruindo a sessão se houver discrepâncias suspeitas.
- [ ] Adicionar logs de eventos de segurança (login bem-sucedido, falhas de autenticação, ativação/desativação de 2FA) via biblioteca nativa `log.php`.

---

## Validação Esperada
O lote será considerado completo após aprovação na checklist de validação de `BATCH-030`.
