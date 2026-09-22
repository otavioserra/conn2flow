# BATCH-177: Correção do Fluxo de reCAPTCHA v3/v2 e Turnstile em Telas de Autenticação Tailwind

Execução da [req-172](../human-requests/req-172.md).

---

## Atividades e Checklist

### 1. [x] Frontend: Execução de Captcha Agnóstica ao Fomantic em `perfil-usuario.js`
- [x] Criar interceptador nativo de submit para os formulários de autenticação (`#_gestor-form-logar`, `#_gestor-form-autenticar`, `#_gestor-form-signup`, `#_gestor-form-forgot-password`).
- [x] Executar `grecaptcha.execute()` e anexar `token` e `action` mesmo quando `temFormFomantic` for falso.
- [x] Garantir que o formulário continue a submissão nativa após anexar os inputs de token.
- [x] Suportar o mesmo fluxo para tokens do Cloudflare Turnstile quando ativo.

### 2. [x] Backend: Suporte a Fallback de reCAPTCHA v2 no Módulo `perfil-usuario`
- [x] Em `perfil_usuario_oauth_authenticate()` e fluxos correlatos em `perfil-usuario.php`:
  - Se a validação do v3 falhar e `usuario-recaptcha-v2-active` for `true`, verificar a presença de `g-recaptcha-response`.
  - Se `g-recaptcha-response` estiver ausente, exigir o v2 sem disparar o erro genérico de robô.
  - Se `g-recaptcha-response` estiver presente, validar via `gestor_captcha_validar(null, ['v2' => true])`.
  - Se aprovado no v2, prosseguir com a autenticação de credenciais.

### 3. [x] Frontend: Renderização de Widget reCAPTCHA v2 sob Demanda
- [x] Permitir a injeção/exibição do widget reCAPTCHA v2 (checkbox) nas telas de login/autenticação quando exigido pelo backend.

### 4. [x] Validação e Testes
- [x] Adicionar testes unitários no PHPUnit cobrindo:
  - Submissão com token v3 válido.
  - Submissão com token v3 falho acionando exigência de v2.
  - Submissão com token v2 aprovado liberando o login.
- [x] Executar `npx vitest run` e `composer test` garantindo 0 falhas e 0 regressões.

---

## Critérios de Aceite e Validação

1. **Login em Tailwind sem Robô Falso**: Formulários de login em Tailwind executam reCAPTCHA v3 e enviam token válido.
2. **Segundo Degrau Ativo**: Se v3 falhar, o usuário tem a chance de resolver o reCAPTCHA v2 interativo.
3. **Aprovação v2**: Resolver o v2 com sucesso permite o login com credenciais corretas.
4. **Suíte 100% Verde**: Testes PHPUnit e Vitest aprovados com exit 0.

---

## Evidências de Execução

- `php -l gestor/modulos/perfil-usuario/perfil-usuario.php` e `php -l tests/Unit/PHP/PerfilUsuarioCaptchaFallbackTest.php`: sem erros.
- PHPUnit focado: 147 testes, 825 asserções, exit 0.
- PHPUnit completo: 1.190 testes, 7.848 asserções, 4 pulados, exit 0. Permaneceram 4 depreciações da aplicação e 2 do PHPUnit.
- Vitest focado: 81 testes, exit 0. Vitest completo: 30 arquivos, 426 testes, exit 0.
- `node --check` em `perfil-usuario.js` e `perfil-usuario.min.js`: sem erros.
- `assets:minify --verificar`: zero derivados desatualizados. `resources:sync`: 2.882 recursos sincronizados; uma segunda passagem confirmou zero recompilações pendentes e 237 recursos em cache.
- `project:update-all conn2flow-site-local`: oito estágios concluídos no Lab HestiaCP e publicados em `conn2flow.local`.
- Teste real com Playwright e chaves oficiais do Google: um token v3 inválido exibiu o checkbox v2; após resolver o checkbox, a resposta deixou o estado v2 e avançou para a validação das credenciais. A configuração temporária do Lab foi restaurada.
- `git diff --check`: exit 0; review findings-first concluído sem findings bloqueantes.

## Estado

complete
