---
title: "Biblioteca comunicacao.php"
description: "Envio de e-mail por SMTP (PHPMailer) com o layout de e-mails do site, destinatários, anexos e imagens embutidas; e a página de impressão."
section: reference
order: 280
sources:
  - gestor/bibliotecas/comunicacao.php
  - gestor/config.php
  - gestor/autenticacoes.exemplo/dominio/.env
verified_at: 123b95df
---

# Biblioteca `comunicacao.php`

Todo e-mail que o Gestor envia (cadastro, recuperação de senha, 2FA, formulários) passa por `comunicacao_email()`, que usa o **PHPMailer** incluído em `gestor/bibliotecas/PHPMailer/`. A biblioteca também tem `comunicacao_impressao()`.

## Configuração

Do `.env` ([variáveis globais](../../concepts/global-variables.md)): `EMAIL_ACTIVE`, `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_PORT`, `EMAIL_SECURE`, `EMAIL_FROM(_NAME)` e `EMAIL_REPLY_TO(_NAME)`. Com `EMAIL_ACTIVE=false`, `comunicacao_email()` não envia e devolve `false`.

> [!WARNING]
> **Só TLS implícito funciona** (SMTPS, normalmente porta **465**). O código testa se a chave `secure` existe, e ela sempre existe: `EMAIL_SECURE=false` não desliga a criptografia, e a porta 587 com STARTTLS falha na conexão. Use `EMAIL_PORT=465` e `EMAIL_SECURE=true`, como no `.env` de exemplo. A autenticação SMTP também é sempre ligada.

## Enviar

```php
gestor_incluir_biblioteca('comunicacao');

$ok = comunicacao_email([
    'destinatarios' => [
        ['email' => 'ana@exemplo.com', 'nome' => 'Ana'],
        ['email' => 'arquivo@exemplo.com', 'tipo' => 'bcc'],
    ],
    'mensagem' => [
        'assunto' => 'Pedido recebido',
        'html' => '<p>Olá, #nome#!</p>',
        'htmlVariaveis' => [['variavel' => '#nome#', 'valor' => htmlspecialchars($nome)]],
        'htmlAssinaturaAutomatica' => true,
        'anexos' => [['caminho' => '/tmp/pedido.pdf', 'nome' => 'pedido.pdf', 'tmpCaminho' => '/tmp/pedido.pdf']],
    ],
]);
```

| Parâmetro | |
|---|---|
| `destinatarios[]` | `email`, `nome` e `tipo` (`cc`, `bcc` ou vazio para o destinatário normal) |
| `mensagem.assunto` / `html` | Assunto e corpo HTML |
| `mensagem.htmlTitulo` | O `<title>` do e-mail (padrão: o assunto) |
| `mensagem.htmlVariaveis` | Lista de `['variavel', 'valor']` trocadas no HTML final (**sem escape**) |
| `mensagem.htmlLayoutID` | Id de um **componente** que substitui o `html` como corpo |
| `mensagem.htmlAssinaturaAutomatica` | Acrescenta o componente `layout-emails-assinatura` |
| `mensagem.htmlCompleto` | HTML pronto: pula o layout |
| `mensagem.anexos[]` | `caminho`, `nome`, `tmpCaminho` (apagado depois do envio) |
| `mensagem.imagens[]` | `caminho`, `cid`, `nome`, `imagemTmpCaminho`: imagens embutidas, referenciadas no HTML por `cid:` |
| `remetente` | `de`, `deNome`, `responderPara`, `responderParaNome`: sobrescrevem o `.env` |
| `servidor` | `hospedeiro`, `usuario`, `senha`, `porta`, `seguro`, `debug`: sobrescrevem o `.env` |
| `EMAIL_TESTS` + `EMAIL_*` | Envio de teste com credenciais passadas na hora, mesmo com o e-mail desligado (é o que o `admin-environment` usa) |

### O layout

Sem `htmlCompleto`, o corpo entra no **layout `layout-emails`**: o CSS dele vai num `<style>`, e os marcadores `<!-- mail#titulo -->`, `<!-- mail#css -->` e `<!-- mail#corpo -->` são trocados. Depois vêm as `htmlVariaveis` e as [variáveis globais](gestor.md) (`@[[…]]@`). Para mudar a cara de todos os e-mails do site, edite esse layout. O texto alternativo (sem HTML) é fixo em português.

## Retorno e erros

Devolve `true` se o servidor aceitou a mensagem e `false` em qualquer outro caso: e-mail desligado, erro de conexão, destinatário inválido. O motivo vai para `gestor/logs/email-<data>.log` (ou, com `debug`, para a tabela `historico`).

> [!CAUTION]
> Esse log grava a configuração do servidor **inclusive a senha SMTP** (req-181, item A9). Em falha, os anexos temporários não são apagados.

## Impressão

`comunicacao_impressao(['pagina' => $html, 'titulo' => …])` guarda o HTML na sessão (`impressao`) para a página de impressão exibi-lo pronto para imprimir (opção `impressao`, tratada em `gestor/modulos/global.php`; sem dados, mostra o componente `impressao-sem-dados`).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/comunicacao.php` por `c2f docs:extract` — 2 funções. Não edite dentro deste bloco.

- `comunicacao_impressao(array|false $params = false): void` — [linha 50](../../../../../gestor/bibliotecas/comunicacao.php#L50)
- `comunicacao_email(array|false $params = false): bool|string` — [linha 137](../../../../../gestor/bibliotecas/comunicacao.php#L137)

<!-- c2f:extract:end -->
