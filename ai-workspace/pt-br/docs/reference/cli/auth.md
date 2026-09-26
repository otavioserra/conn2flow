---
title: "CLI: Autenticação"
description: "Comandos c2f da família auth, argumentos e opções declarados no código."
section: reference
order: 40
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/AuthCookieCommand.php
verified_at: e5b61f8e
---

# CLI: Autenticação

A família `auth:*` tem 1 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `auth:cookie`

Código: `cli/src/Commands/AuthCookieCommand.php`.

```text
Usage: c2f auth:cookie [--user=admin] [--project=ID] [--out=temp/agent-cookies.txt]

Generates a Netscape cookie jar file for use with curl -b or Playwright.
Also prints the Cookie header string to stdout.

Options:
  --user       User identifier (name or ID). Default: 'admin' (ID 1)
  --project    Project ID. Uses its test mirror and Docker mount when available
  --out        Output cookie jar path. Default: temp/agent-cookies.txt
```
