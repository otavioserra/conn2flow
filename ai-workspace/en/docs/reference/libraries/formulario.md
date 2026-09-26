---
title: "formulario.php library"
label: "Forms"
description: "The public form processor (validation, anti-bot, CAPTCHA, per-IP limits, storage and e-mail), injecting forms into the page and validating admin panel forms."
section: reference
order: 60
sources:
  - gestor/bibliotecas/formulario.php
  - gestor/bibliotecas/seguranca.php
  - gestor/config.php
verified_at: 1189dc2f
---

# `formulario.php` library

Three roles:
1. **Public forms** (those of the `forms` module): `formulario_controlador()` prepares the page and `formulario_processador()` receives the submission.
2. **Admin panel form validation**: `formulario_validacao()` builds the browser-side rules, used by [interface.php](interface.md) and several modules.
3. Forms' own **CAPTCHA** and per-IP limits.

## On the page: `formulario_controlador()`

```php
formulario_controlador(['formId' => 'contact']);            // or ['contact', 'newsletter']
```

It builds each form's configuration (`formulario_montar_js_vars()`: fields, rules, texts, CAPTCHA) and exposes it in `gestor.form[<id>]`; with a single id, also in the old flat format. It includes the library JS once. In ~1% of calls, it cleans expired accesses (`formulario_acessos_limpeza()`).

## On submission: `formulario_processador()`

It answers over AJAX (`$_GESTOR['ajax-json']`) to the form POST (`_formId` and the fields), in this order:

1. **Honeypot**: a filled `honeypot` field = bot.
2. **Submission age**: the browser-sent `timestamp` must be at most 2 days old.
3. **Definition** of the form in the `forms` table (current language): name, `fields_schema` and status (inactive = rejected).
4. **Per-IP limit** (`formulario_acesso_verificar()`), per form: `bloqueado` rejects.
5. **CAPTCHA** ([seguranca.php](seguranca.md)): with Turnstile, always; with reCAPTCHA, from the `antispam` status on or when the schema has `force_recaptcha: true`; a failed v3 can fall back to v2 (`status: require_v2`) if v2 is active.
6. **Schema fields**: required ones, valid e-mail and URL, minimum length (3 for text, or the one in Options) and maximum (254 for text/email/password/url, 10000 for textarea, 1000 for the rest), number and date ranges from Options (`formulario_parse_limits()`: `min:`, `max:`, `step:`).
7. **Stores** into `forms_submissions` (submission name from the schema's `field_name`, the `name` field or the first text field). `password` fields are stored **empty**.
8. **Counts the submission** in the per-IP limit (`formulario_acesso_cadastrar()`).
9. **Notification e-mail**: recipients from the schema's `email.recipients` (separated by `;`, accepting `Name <email>`), or the `.env` sender; subject, reply-to (the first valid e-mail of the submission) and the form's message component, falling back to `forms-prepared-email` (`formulario_email_*`). Before sending, the `formulario.email.mensagem` filter ([hooks](../../concepts/hooks.md)) can change the HTML, and site images become embedded attachments (`formulario_email_processar_imagens()`).
10. Stores `email_status` in the submission and returns success with the redirect (`/sucesso/` by default).

Limits from the `.env`: `FORMULARIOS_MAXIMO_CADASTROS` and `FORMULARIOS_MAXIMO_CADASTROS_SIMPLES` (overridable by `access_max` and `access_max_simple` in the schema), plus the block and cleanup times (`formularios-*` in [global variables](../../concepts/global-variables.md)). The logic is the same as the [attempt control](autenticacao.md), in `formulario_acesso_verificar()`, `formulario_acesso_cadastrar()` and `formulario_acesso_falha()`.

> [!NOTE]
> The "anti-replay" `timestamp` comes from the browser itself: a bot that fills it with the current time passes. The real bot protection is the honeypot, the CAPTCHA and the per-IP limit (which, behind a CDN, depends on the [real IP](ip.md)).

## Admin panel validation: `formulario_validacao()`

```php
formulario_validacao([
    'formId' => 'form-usuarios',
    'validacao' => [
        ['regra' => 'email', 'campo' => 'email', 'label' => 'E-mail'],
        ['regra' => 'texto-obrigatorio', 'campo' => 'nome', 'label' => 'Name'],
    ],
]);
```

It builds the Fomantic UI rules for the form, with error texts from the variables. Rules: `nao-vazio`, `texto-obrigatorio`, `texto-obrigatorio-verificar-campo`, `selecao-obrigatorio`, `email`, `email-comparacao`, `email-comparacao-verificar-campo`, `senha`, `senha-comparacao` and `manual`. `formulario_validacao_campos_obrigatorios()` does the server-side check and, if something is missing, shows the alert and redirects. `formulario_incluir_js()` includes the validation script.

## Legacy reCAPTCHA

`formulario_google_recaptcha()` and `formulario_google_recaptcha_tipo()` return the public key and the type (v2/v3) when reCAPTCHA is active. They have no callers in the core: the current CAPTCHA goes through `gestor_captcha_validar()`.

**Internal helpers:** `formulario_email_cabecalho_normalizar()`, `formulario_form_action_resolver()` (an empty action points to the canonical processor), `formulario_email_reply_to_resolver()`, `formulario_email_reply_to_nome_resolver()`, `formulario_email_assunto_resolver()`, `formulario_email_mensagem_resolver()`, `formulario_email_template_processar()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/formulario.php` by `c2f docs:extract` — 21 functions. Do not edit inside this block.

- `formulario_parse_limits(mixed $options): array` — [line 28](../../../../../gestor/bibliotecas/formulario.php#L28)
- `formulario_email_cabecalho_normalizar($valor)` — [line 46](../../../../../gestor/bibliotecas/formulario.php#L46)
- `formulario_form_action_resolver($form_action, $url_raiz)` — [line 58](../../../../../gestor/bibliotecas/formulario.php#L58)
- `formulario_email_reply_to_resolver(...$candidatos)` — [line 74](../../../../../gestor/bibliotecas/formulario.php#L74)
- `formulario_email_reply_to_nome_resolver(...$candidatos)` — [line 88](../../../../../gestor/bibliotecas/formulario.php#L88)
- `formulario_email_assunto_resolver($assunto, $assunto_padrao)` — [line 100](../../../../../gestor/bibliotecas/formulario.php#L100)
- `formulario_email_mensagem_resolver($email_data, $carregar_componente = null)` — [line 110](../../../../../gestor/bibliotecas/formulario.php#L110)
- `formulario_email_template_processar($mensagem, $numero, $form_name, $fields, $dados)` — [line 135](../../../../../gestor/bibliotecas/formulario.php#L135)
- `formulario_incluir_js(array|false $params = false): void` — [line 199](../../../../../gestor/bibliotecas/formulario.php#L199)
- `formulario_email_processar_imagens(string $html): array` — [line 229](../../../../../gestor/bibliotecas/formulario.php#L229)
- `formulario_montar_js_vars(array $formIds, string|null $formAjaxOpcao = null): array` — [line 303](../../../../../gestor/bibliotecas/formulario.php#L303)
- `formulario_controlador(array|false $params = false): void` — [line 510](../../../../../gestor/bibliotecas/formulario.php#L510)
- `formulario_processador(array|false $params = false): void` — [line 563](../../../../../gestor/bibliotecas/formulario.php#L563)
- `formulario_acesso_verificar(array|false $params = false): array` — [line 1123](../../../../../gestor/bibliotecas/formulario.php#L1123)
- `formulario_acesso_cadastrar(array|false $params = false): void` — [line 1191](../../../../../gestor/bibliotecas/formulario.php#L1191)
- `formulario_acesso_falha(array|false $params = false): void` — [line 1346](../../../../../gestor/bibliotecas/formulario.php#L1346)
- `formulario_acessos_limpeza(array|false $params = false): void` — [line 1479](../../../../../gestor/bibliotecas/formulario.php#L1479)
- `formulario_validacao(array|false $params = false): void` — [line 1531](../../../../../gestor/bibliotecas/formulario.php#L1531)
- `formulario_validacao_campos_obrigatorios(array|false $params = false): void` — [line 1932](../../../../../gestor/bibliotecas/formulario.php#L1932)
- `formulario_google_recaptcha(): string|null` — [line 2020](../../../../../gestor/bibliotecas/formulario.php#L2020)
- `formulario_google_recaptcha_tipo(): string|null` — [line 2089](../../../../../gestor/bibliotecas/formulario.php#L2089)

<!-- c2f:extract:end -->
