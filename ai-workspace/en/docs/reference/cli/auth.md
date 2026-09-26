---
title: "CLI: Authentication"
description: "c2f auth commands, arguments and options declared in source code."
section: reference
order: 40
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/AuthCookieCommand.php
verified_at: e5b61f8e
---

# CLI: Authentication

The `auth:*` family has 1 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `auth:cookie`

Source: `cli/src/Commands/AuthCookieCommand.php`.

```text
Usage: c2f auth:cookie [--user=admin] [--project=ID] [--out=temp/agent-cookies.txt]

Generates a Netscape cookie jar file for use with curl -b or Playwright.
Also prints the Cookie header string to stdout.

Options:
  --user       User identifier (name or ID). Default: 'admin' (ID 1)
  --project    Project ID. Uses its test mirror and Docker mount when available
  --out        Output cookie jar path. Default: temp/agent-cookies.txt
```
