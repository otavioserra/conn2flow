---
title: "CLI: Comandos page"
description: "Referência dos comandos page registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/PageInspectCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos page

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `page:inspect`

Código: `cli/src/Commands/PageInspectCommand.php`.

```text
Usage: c2f page:inspect <url_or_route> [options]

Options:
  --project=ID              Project ID (resolves base URL from environment.json)
  --selector="sel1,sel2"    CSS selectors to inspect in the mounted DOM
  --computed="prop1,prop2"  CSS properties to extract (e.g. display,opacity)
  --screenshot[=PATH]       Capture screenshot (default: temp/inspect-screenshot.png)
  --cookies=PATH            Cookie jar path (default: temp/agent-cookies.txt if exists)

Output: JSON with status, url, consoleErrors, elements, screenshotPath
```
