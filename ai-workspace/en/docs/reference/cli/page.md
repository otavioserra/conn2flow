---
title: "CLI: Commands page"
description: "Reference for page commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/PageInspectCommand.php
verified_at: e5b61f8e
---

# CLI: Commands page

Syntax from executable help; check the source before running commands with external effects.

## `page:inspect`

Source: `cli/src/Commands/PageInspectCommand.php`.

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
