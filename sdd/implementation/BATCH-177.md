# BATCH-177: Correção do Fluxo de reCAPTCHA v3/v2 e Turnstile em Telas de Autenticação Tailwind

Execução da [req-172](../human-requests/req-172.md).

---

## Atividades e Checklist

### 1. [ ] Frontend: Execução de Captcha Agnóstica ao Fomantic em `perfil-usuario.js`
- [ ] Criar interceptador nativo de submit para os formulários de autenticação (`#_gestor-form-logar`, `#_gestor-form-autenticar`, `#_gestor-form-signup`, `#_gestor-form-forgot-password`).
- [ ] Executar `grecaptcha.execute()` e anexar `token` e `action` mesmo quando `temFormFomantic` for falso.
- [ ] Garantir que o formulário continue a submissão nativa após anexar os inputs de token.
- [ ] Suportar o mesmo fluxo para tokens do Cloudflare Turnstile quando ativo.

### 2. [ ] Backend: Suporte a Fallback de reCAPTCHA v2 no Módulo `perfil-usuario`
- [ ] Em `perfil_usuario_oauth_authenticate()` e fluxos correlatos em `perfil-usuario.php`:
  - Se a validação do v3 falhar e `usuario-recaptcha-v2-active` for `true`, verificar a presença de `g-recaptcha-response`.
  - Se `g-recaptcha-response` estiver ausente, exigir o v2 sem disparar o erro genérico de robô.
  - Se `g-recaptcha-response` estiver presente, validar via `gestor_captcha_validar(null, ['v2' => true])`.
  - Se aprovado no v2, prosseguir com a autenticação de credenciais.

### 3. [ ] Frontend: Renderização de Widget reCAPTCHA v2 sob Demanda
- [ ] Permitir a injeção/exibição do widget reCAPTCHA v2 (checkbox) nas telas de login/autenticação quando exigido pelo backend.

### 4. [ ] Validação e Testes
- [ ] Adicionar testes unitários no PHPUnit cobrindo:
  - Submissão com token v3 válido.
  - Submissão com token v3 falho acionando exigência de v2.
  - Submissão com token v2 aprovado liberando o login.
- [ ] Executar `npx vitest run` e `composer test` garantindo 0 falhas e 0 regressões.

---

## Critérios de Aceite e Validação

1. **Login em Tailwind sem Robô Falso**: Formulários de login em Tailwind executam reCAPTCHA v3 e enviam token válido.
2. **Segundo Degrau Ativo**: Se v3 falhar, o usuário tem a chance de resolver o reCAPTCHA v2 interativo.
3. **Aprovação v2**: Resolver o v2 com sucesso permite o login com credenciais corretas.
4. **Suíte 100% Verde**: Testes PHPUnit e Vitest aprovados com exit 0.
