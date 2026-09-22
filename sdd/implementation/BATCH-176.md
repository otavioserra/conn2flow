# BATCH-176: Suporte Nativo ao Cloudflare Turnstile no Core do Conn2Flow

Execução da [req-171](../human-requests/req-171.md).

---

## Atividades e Checklist

### 1. [ ] Configuração de Ambiente e Retrocompatibilidade
- [ ] Adicionar suporte a `CAPTCHA_PROVIDER` (`none` | `google-recaptcha` | `cloudflare-turnstile`) em `gestor/config.php`.
- [ ] Adicionar variáveis `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` e `TURNSTILE_MODE` em `gestor/config.php`.
- [ ] Garantir inferência retrocompatível: se `CAPTCHA_PROVIDER` não existir no `.env`, mas `USUARIO_RECAPTCHA_ACTIVE` for `true`, assumir `google-recaptcha`.
- [ ] Atualizar template `.env` em `gestor/autenticacoes.exemplo/dominio/.env` com documentação e chaves de teste da Cloudflare.

### 2. [ ] Abstração Centralizada de Validação Backend
- [ ] Criar helper/função centralizada `gestor_captcha_validar(?string $token = null, array $opcoes = []): array|bool` (ex.: em `gestor/bibliotecas/seguranca.php` ou biblioteca apropriada).
- [ ] Implementar chamada cURL para `https://challenges.cloudflare.com/turnstile/v0/siteverify` com tratamento de timeout (5s) e parse de retorno.
- [ ] Refatorar validações de captcha em `gestor/bibliotecas/formulario.php` e `gestor/modulos/perfil-usuario/perfil-usuario.php` para utilizar a nova função centralizada.
- [ ] Garantir que falha no Turnstile seja contabilizada como abuso no rate-limiting de acessos.

### 3. [ ] Frontend, Componentes e Injeção de Assets
- [ ] Injetar script oficial do Turnstile (`https://challenges.cloudflare.com/turnstile/v0/api.js`) dinamicamente quando o provedor for Turnstile.
- [ ] Integrar renderização do widget nos formulários de autenticação (`perfil-usuario`: `acessar-sistema`, `cadastrar-se`, `esqueceu-a-senha`) nos temas Fomantic UI e Tailwind CSS.
- [ ] Integrar widget no motor de formulários dinâmicos (`gestor/assets/interface/formulario.js` e `formulario.php`).
- [ ] Garantir que o token `cf-turnstile-response` seja enviado em submissões AJAX e manipulado adequadamente em caso de erro/reset.

### 4. [ ] Módulo Administrativo (admin-environment)
- [ ] Atualizar UI de `admin-environment` (HTML e PHP) para permitir seleção de `CAPTCHA_PROVIDER` e inputs de chaves do Turnstile.
- [ ] Implementar endpoint AJAX de teste das chaves do Turnstile no painel administrativo.

### 5. [ ] Testes e Validação
- [ ] Criar testes unitários PHPUnit cobrindo os diferentes cenários do validador (chaves de teste Cloudflare, mock de rede, timeout, erros).
- [ ] Rodar `php -l` nos arquivos modificados.
- [ ] Executar suíte de testes PHPUnit e Vitest sem regressões.

---

## Critérios de Aceite e Validação

1. **Seleção de Provedor**: Suporte transparente a `none`, `google-recaptcha` e `cloudflare-turnstile`.
2. **Retrocompatibilidade**: Instalações prévias continuam funcionando com Google reCAPTCHA sem qualquer quebra.
3. **Abstração Limpa**: Chamadas de validação de captcha no core unificadas na função centralizada.
4. **Proteção Efetiva**: Autenticação e formulários públicos validados com sucesso via Turnstile.
5. **Painel de Gestão**: `admin-environment` configura e testa as credenciais do Turnstile.
6. **Suíte Verde**: 100% dos testes da suíte passam com sucesso.
