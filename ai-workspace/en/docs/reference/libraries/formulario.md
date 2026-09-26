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
  Extrai diretivas de limite (min/max/step) das linhas do campo "Opções" de um campo de formulário. Aceita string multilinha ou array de linhas no formato `min:X`, `max:Y`, `step:Z`.
  Parameters:
  - `$options`: Conteúdo do campo options.
  Returns: ['min'=>string|null, 'max'=>string|null, 'step'=>string|null]
- `formulario_email_cabecalho_normalizar($valor)` — [line 46](../../../../../gestor/bibliotecas/formulario.php#L46)
  Normaliza uma string destinada a cabeçalhos de e-mail, removendo tags e quebras de linha.
- `formulario_form_action_resolver($form_action, $url_raiz)` — [line 58](../../../../../gestor/bibliotecas/formulario.php#L58)
  Resolve a action pública do formulário; vazio sempre aponta para o processador canônico.
- `formulario_email_reply_to_resolver(...$candidatos)` — [line 74](../../../../../gestor/bibliotecas/formulario.php#L74)
  Retorna o primeiro endereço de e-mail válido da lista de candidatos.
- `formulario_email_reply_to_nome_resolver(...$candidatos)` — [line 88](../../../../../gestor/bibliotecas/formulario.php#L88)
  Retorna o primeiro nome de remetente não vazio, já seguro para cabeçalho.
- `formulario_email_assunto_resolver($assunto, $assunto_padrao)` — [line 100](../../../../../gestor/bibliotecas/formulario.php#L100)
  Aplica o assunto padrão quando o valor configurado está vazio ou contém somente espaços.
- `formulario_email_mensagem_resolver($email_data, $carregar_componente = null)` — [line 110](../../../../../gestor/bibliotecas/formulario.php#L110)
  Carrega o componente customizado e recua obrigatoriamente para forms-prepared-email se necessário.
- `formulario_email_template_processar($mensagem, $numero, $form_name, $fields, $dados)` — [line 135](../../../../../gestor/bibliotecas/formulario.php#L135)
  Preenche as variáveis gerais e a célula repetível do template de notificação.
- `formulario_incluir_js(array|false $params = false): void` — [line 199](../../../../../gestor/bibliotecas/formulario.php#L199)
  Inclui JavaScript da biblioteca de formulários na página.
  Parameters:
  - `$params`: Parâmetros da função.
- `formulario_email_processar_imagens(string $html): array` — [line 229](../../../../../gestor/bibliotecas/formulario.php#L229)
  Processa imagens locais no HTML do email para embedding automático.
  Parameters:
  - `$html`: HTML do email a ser processado.
  Returns: Array com HTML processado e array de imagens para embedding.
- `formulario_montar_js_vars(array $formIds, string|null $formAjaxOpcao = null): array` — [line 303](../../../../../gestor/bibliotecas/formulario.php#L303)
  Monta as variáveis JS (gestor.form[id]) de um ou mais formulários.
  Parameters:
  - `$formIds`: Lista de IDs de formulário.
  - `$formAjaxOpcao`: Opção AJAX (opcional).
  Returns: Configurações indexadas por ID do formulário.
- `formulario_controlador(array|false $params = false): void` — [line 510](../../../../../gestor/bibliotecas/formulario.php#L510)
  Controlador de Formulários.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['formId']`: ID do formulário HTML (obrigatório).
  - `$params['formAction']`: URL de ação do formulário (opcional).
  - `$params['formAjaxOpcao']`: Opção AJAX (opcional).
- `formulario_processador(array|false $params = false): void` — [line 563](../../../../../gestor/bibliotecas/formulario.php#L563)
  Processador de Formulários.
  Parameters:
  - `$params`: Parâmetros da função.
- `formulario_acesso_verificar(array|false $params = false): array` — [line 1123](../../../../../gestor/bibliotecas/formulario.php#L1123)
  Verifica estado de acesso para formulários com proteção anti-spam.
  Parameters:
  - `$params`: Parâmetros (tipo obrigatório).
  Returns: Estado do acesso com 'permitido' e 'status'.
- `formulario_acesso_cadastrar(array|false $params = false): void` — [line 1191](../../../../../gestor/bibliotecas/formulario.php#L1191)
  Cadastra tentativa de acesso para formulários com controle anti-spam.
  Parameters:
  - `$params`: Parâmetros (tipo obrigatório, antispam opcional, maximoCadastros opcional, maximoCadastrosSimples opcional).
- `formulario_acesso_falha(array|false $params = false): void` — [line 1346](../../../../../gestor/bibliotecas/formulario.php#L1346)
  Registra falha de acesso para formulários.
  Parameters:
  - `$params`: Parâmetros (tipo obrigatório, maximoCadastros opcional, maximoCadastrosSimples opcional).
- `formulario_acessos_limpeza(array|false $params = false): void` — [line 1479](../../../../../gestor/bibliotecas/formulario.php#L1479)
  Limpa registros antigos das tabelas de formulários.
  Parameters:
  - `$params`: Parâmetros da função.
- `formulario_validacao(array|false $params = false): void` — [line 1531](../../../../../gestor/bibliotecas/formulario.php#L1531)
  Configura validação de formulário com regras personalizadas.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['formId']`: ID do formulário HTML (obrigatório).
  - `$params['validacao']`: []['comparacao'] Dados para regra 'email-comparacao' (opcional).
  - `$params['regrasExtra']`: Regras adicionais além das padrões (opcional).
- `formulario_validacao_campos_obrigatorios(array|false $params = false): void` — [line 1932](../../../../../gestor/bibliotecas/formulario.php#L1932)
  Valida campos obrigatórios no servidor (server-side).
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['redirect']`: URL de redirecionamento em caso de erro (opcional, usa reload se omitido).
  - `$params['campos']`: []['max'] Tamanho máximo para 'texto-obrigatorio' (opcional, padrão 100).
  Returns: Exibe alerta e redireciona se validação falhar.
- `formulario_google_recaptcha(): string|null` — [line 2020](../../../../../gestor/bibliotecas/formulario.php#L2020)
  Obtém chave do site Google reCAPTCHA.
  Returns: Chave pública do site reCAPTCHA ou null se desativado.
- `formulario_google_recaptcha_tipo(): string|null` — [line 2089](../../../../../gestor/bibliotecas/formulario.php#L2089)
  Obtém tipo de Google reCAPTCHA configurado.
  Returns: 'recaptcha-v2' ou 'recaptcha-v3', ou null se desativado.

<!-- c2f:extract:end -->
