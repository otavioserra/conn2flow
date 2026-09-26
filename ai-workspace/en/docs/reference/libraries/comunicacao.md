---
title: "comunicacao.php library"
description: "Sending e-mail over SMTP (PHPMailer) with the site's e-mail layout, recipients, attachments and embedded images; and the print page."
section: reference
order: 280
sources:
  - gestor/bibliotecas/comunicacao.php
  - gestor/config.php
  - gestor/autenticacoes.exemplo/dominio/.env
verified_at: 123b95df
---

# `comunicacao.php` library

Every e-mail the Gestor sends (sign-up, password recovery, 2FA, forms) goes through `comunicacao_email()`, which uses the **PHPMailer** bundled in `gestor/bibliotecas/PHPMailer/`. The library also has `comunicacao_impressao()`.

## Configuration

From the `.env` ([global variables](../../concepts/global-variables.md)): `EMAIL_ACTIVE`, `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_PORT`, `EMAIL_SECURE`, `EMAIL_FROM(_NAME)` and `EMAIL_REPLY_TO(_NAME)`. With `EMAIL_ACTIVE=false`, `comunicacao_email()` does not send and returns `false`.

> [!WARNING]
> **Only implicit TLS works** (SMTPS, usually port **465**). The code checks whether the `secure` key exists, and it always does: `EMAIL_SECURE=false` does not turn encryption off, and port 587 with STARTTLS fails to connect. Use `EMAIL_PORT=465` and `EMAIL_SECURE=true`, as in the sample `.env`. SMTP authentication is always on too.

## Sending

```php
gestor_incluir_biblioteca('comunicacao');

$ok = comunicacao_email([
    'destinatarios' => [
        ['email' => 'ana@example.com', 'nome' => 'Ana'],
        ['email' => 'archive@example.com', 'tipo' => 'bcc'],
    ],
    'mensagem' => [
        'assunto' => 'Order received',
        'html' => '<p>Hello, #name#!</p>',
        'htmlVariaveis' => [['variavel' => '#name#', 'valor' => htmlspecialchars($name)]],
        'htmlAssinaturaAutomatica' => true,
        'anexos' => [['caminho' => '/tmp/order.pdf', 'nome' => 'order.pdf', 'tmpCaminho' => '/tmp/order.pdf']],
    ],
]);
```

| Parameter | |
|---|---|
| `destinatarios[]` | `email`, `nome` and `tipo` (`cc`, `bcc` or empty for a regular recipient) |
| `mensagem.assunto` / `html` | Subject and HTML body |
| `mensagem.htmlTitulo` | The e-mail `<title>` (default: the subject) |
| `mensagem.htmlVariaveis` | List of `['variavel', 'valor']` replaced in the final HTML (**unescaped**) |
| `mensagem.htmlLayoutID` | Id of a **component** that replaces `html` as the body |
| `mensagem.htmlAssinaturaAutomatica` | Appends the `layout-emails-assinatura` component |
| `mensagem.htmlCompleto` | Ready HTML: skips the layout |
| `mensagem.anexos[]` | `caminho`, `nome`, `tmpCaminho` (deleted after sending) |
| `mensagem.imagens[]` | `caminho`, `cid`, `nome`, `imagemTmpCaminho`: embedded images, referenced in the HTML with `cid:` |
| `remetente` | `de`, `deNome`, `responderPara`, `responderParaNome`: override the `.env` |
| `servidor` | `hospedeiro`, `usuario`, `senha`, `porta`, `seguro`, `debug`: override the `.env` |
| `EMAIL_TESTS` + `EMAIL_*` | Test send with credentials passed on the spot, even with e-mail turned off (what `admin-environment` uses) |

### The layout

Without `htmlCompleto`, the body goes into the **`layout-emails` layout**: its CSS goes into a `<style>`, and the `<!-- mail#titulo -->`, `<!-- mail#css -->` and `<!-- mail#corpo -->` markers are replaced. Then come the `htmlVariaveis` and the [global variables](gestor.md) (`@[[…]]@`). To change the look of every e-mail of the site, edit that layout. The alternative text (no HTML) is hard-coded in Portuguese.

## Return value and errors

It returns `true` if the server accepted the message and `false` in any other case: e-mail off, connection error, invalid recipient. The reason goes to `gestor/logs/email-<date>.log` (or, with `debug`, to the `historico` table).

> [!CAUTION]
> That log records the server configuration **including the SMTP password** (req-181, item A9). On failure, temporary attachments are not deleted.

## Printing

`comunicacao_impressao(['pagina' => $html, 'titulo' => …])` stores the HTML in the session (`impressao`) for the print page to display it ready to print (the `impressao` option, handled in `gestor/modulos/global.php`; without data, it shows the `impressao-sem-dados` component).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/comunicacao.php` by `c2f docs:extract` — 2 functions. Do not edit inside this block.

- `comunicacao_impressao(array|false $params = false): void` — [line 50](../../../../../gestor/bibliotecas/comunicacao.php#L50)
- `comunicacao_email(array|false $params = false): bool|string` — [line 137](../../../../../gestor/bibliotecas/comunicacao.php#L137)

<!-- c2f:extract:end -->
