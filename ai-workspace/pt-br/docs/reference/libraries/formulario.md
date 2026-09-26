---
title: "Biblioteca formulario.php"
label: "Formulários"
description: "O processador dos formulários públicos (validação, anti-robô, CAPTCHA, limites por IP, gravação e e-mail), a injeção dos formulários na página e a validação de formulários do painel."
section: reference
order: 60
sources:
  - gestor/bibliotecas/formulario.php
  - gestor/bibliotecas/seguranca.php
  - gestor/config.php
verified_at: 1189dc2f
---

# Biblioteca `formulario.php`

Três papéis:
1. **Formulários públicos** (os do módulo `forms`): `formulario_controlador()` prepara a página e `formulario_processador()` recebe o envio.
2. **Validação dos formulários do painel**: `formulario_validacao()` gera as regras do lado do navegador, usada pela [interface.php](interface.md) e por vários módulos.
3. **CAPTCHA** e limites por IP próprios dos formulários.

## Na página: `formulario_controlador()`

```php
formulario_controlador(['formId' => 'contato']);            // ou ['contato', 'newsletter']
```

Monta a configuração de cada formulário (`formulario_montar_js_vars()`: campos, regras, textos, CAPTCHA) e a expõe em `gestor.form[<id>]`; com um id só, também no formato plano antigo. Inclui o JS da biblioteca uma vez. Em ~1% das chamadas, limpa os acessos vencidos (`formulario_acessos_limpeza()`).

## No envio: `formulario_processador()`

Responde por AJAX (`$_GESTOR['ajax-json']`) ao POST do formulário (`_formId` e os campos), nesta ordem:

1. **Honeypot**: campo `honeypot` preenchido = robô.
2. **Validade do envio**: `timestamp` enviado pelo navegador com até 2 dias.
3. **Definição** do formulário na tabela `forms` (idioma atual): nome, `fields_schema` e status (inativo = recusado).
4. **Limite por IP** (`formulario_acesso_verificar()`), por formulário: `bloqueado` recusa.
5. **CAPTCHA** ([seguranca.php](seguranca.md)): com Turnstile, sempre; com reCAPTCHA, a partir do status `antispam` ou quando o schema tem `force_recaptcha: true`; o v3 que falha pode cair no v2 (`status: require_v2`) se o v2 estiver ativo.
6. **Campos** do schema: obrigatórios, e-mail e URL válidos, tamanho mínimo (3 em texto, ou o das Opções) e máximo (254 em text/email/password/url, 10000 em textarea, 1000 nos demais), faixas de número e data das Opções (`formulario_parse_limits()`: `min:`, `max:`, `step:`).
7. **Grava** em `forms_submissions` (nome da submissão pelo `field_name` do schema, pelo campo `name` ou pelo primeiro campo de texto). Campos `password` são gravados **vazios**.
8. **Conta o envio** no limite por IP (`formulario_acesso_cadastrar()`).
9. **E-mail** de notificação: destinatários em `email.recipients` do schema (separados por `;`, aceitando `Nome <email>`), ou o remetente do `.env`; assunto, *reply-to* (o primeiro e-mail válido do envio) e o componente de mensagem do formulário, com recuo para `forms-prepared-email` (`formulario_email_*`). Antes de enviar, o filtro `formulario.email.mensagem` ([hooks](../../concepts/hooks.md)) pode alterar o HTML, e as imagens do site viram anexos embutidos (`formulario_email_processar_imagens()`).
10. Grava `email_status` na submissão e devolve sucesso com o redirecionamento (`/sucesso/` por padrão).

Limites do `.env`: `FORMULARIOS_MAXIMO_CADASTROS` e `FORMULARIOS_MAXIMO_CADASTROS_SIMPLES` (sobrescrevíveis por `access_max` e `access_max_simple` no schema), além dos tempos de bloqueio e limpeza (`formularios-*` em [variáveis globais](../../concepts/global-variables.md)). A lógica é a mesma do [controle de tentativas](autenticacao.md), em `formulario_acesso_verificar()`, `formulario_acesso_cadastrar()` e `formulario_acesso_falha()`.

> [!NOTE]
> O `timestamp` "anti-reenvio" vem do próprio navegador: um robô que o preenche com a hora atual passa. A proteção real contra robôs é o honeypot, o CAPTCHA e o limite por IP (que, atrás de CDN, depende do [IP real](ip.md)).

## Validação no painel: `formulario_validacao()`

```php
formulario_validacao([
    'formId' => 'form-usuarios',
    'validacao' => [
        ['regra' => 'email', 'campo' => 'email', 'label' => 'E-mail'],
        ['regra' => 'texto-obrigatorio', 'campo' => 'nome', 'label' => 'Nome'],
    ],
]);
```

Gera as regras do Fomantic UI para o formulário, com os textos de erro das variáveis. Regras: `nao-vazio`, `texto-obrigatorio`, `texto-obrigatorio-verificar-campo`, `selecao-obrigatorio`, `email`, `email-comparacao`, `email-comparacao-verificar-campo`, `senha`, `senha-comparacao` e `manual`. `formulario_validacao_campos_obrigatorios()` faz a conferência do lado do servidor e, se faltar algo, mostra o alerta e redireciona. `formulario_incluir_js()` inclui o script de validação.

## reCAPTCHA legado

`formulario_google_recaptcha()` e `formulario_google_recaptcha_tipo()` devolvem a chave pública e o tipo (v2/v3) quando o reCAPTCHA está ativo. Não têm chamadores no core: o CAPTCHA atual passa por `gestor_captcha_validar()`.

**Auxiliares internos:** `formulario_email_cabecalho_normalizar()`, `formulario_form_action_resolver()` (ação vazia aponta para o processador canônico), `formulario_email_reply_to_resolver()`, `formulario_email_reply_to_nome_resolver()`, `formulario_email_assunto_resolver()`, `formulario_email_mensagem_resolver()`, `formulario_email_template_processar()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/formulario.php` por `c2f docs:extract` — 21 funções. Não edite dentro deste bloco.

- `formulario_parse_limits(mixed $options): array` — [linha 28](../../../../../gestor/bibliotecas/formulario.php#L28)
- `formulario_email_cabecalho_normalizar($valor)` — [linha 46](../../../../../gestor/bibliotecas/formulario.php#L46)
- `formulario_form_action_resolver($form_action, $url_raiz)` — [linha 58](../../../../../gestor/bibliotecas/formulario.php#L58)
- `formulario_email_reply_to_resolver(...$candidatos)` — [linha 74](../../../../../gestor/bibliotecas/formulario.php#L74)
- `formulario_email_reply_to_nome_resolver(...$candidatos)` — [linha 88](../../../../../gestor/bibliotecas/formulario.php#L88)
- `formulario_email_assunto_resolver($assunto, $assunto_padrao)` — [linha 100](../../../../../gestor/bibliotecas/formulario.php#L100)
- `formulario_email_mensagem_resolver($email_data, $carregar_componente = null)` — [linha 110](../../../../../gestor/bibliotecas/formulario.php#L110)
- `formulario_email_template_processar($mensagem, $numero, $form_name, $fields, $dados)` — [linha 135](../../../../../gestor/bibliotecas/formulario.php#L135)
- `formulario_incluir_js(array|false $params = false): void` — [linha 199](../../../../../gestor/bibliotecas/formulario.php#L199)
- `formulario_email_processar_imagens(string $html): array` — [linha 229](../../../../../gestor/bibliotecas/formulario.php#L229)
- `formulario_montar_js_vars(array $formIds, string|null $formAjaxOpcao = null): array` — [linha 303](../../../../../gestor/bibliotecas/formulario.php#L303)
- `formulario_controlador(array|false $params = false): void` — [linha 510](../../../../../gestor/bibliotecas/formulario.php#L510)
- `formulario_processador(array|false $params = false): void` — [linha 563](../../../../../gestor/bibliotecas/formulario.php#L563)
- `formulario_acesso_verificar(array|false $params = false): array` — [linha 1123](../../../../../gestor/bibliotecas/formulario.php#L1123)
- `formulario_acesso_cadastrar(array|false $params = false): void` — [linha 1191](../../../../../gestor/bibliotecas/formulario.php#L1191)
- `formulario_acesso_falha(array|false $params = false): void` — [linha 1346](../../../../../gestor/bibliotecas/formulario.php#L1346)
- `formulario_acessos_limpeza(array|false $params = false): void` — [linha 1479](../../../../../gestor/bibliotecas/formulario.php#L1479)
- `formulario_validacao(array|false $params = false): void` — [linha 1531](../../../../../gestor/bibliotecas/formulario.php#L1531)
- `formulario_validacao_campos_obrigatorios(array|false $params = false): void` — [linha 1932](../../../../../gestor/bibliotecas/formulario.php#L1932)
- `formulario_google_recaptcha(): string|null` — [linha 2020](../../../../../gestor/bibliotecas/formulario.php#L2020)
- `formulario_google_recaptcha_tipo(): string|null` — [linha 2089](../../../../../gestor/bibliotecas/formulario.php#L2089)

<!-- c2f:extract:end -->
