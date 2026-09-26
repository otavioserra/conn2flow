---
title: "CLI: Comandos motion"
description: "Referência dos comandos motion registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/MotionCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos motion

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `motion:status`

Código: `cli/src/Commands/MotionCommand.php`.

```text
Usage:
  c2f motion:status
  c2f motion:on
  c2f motion:off
  c2f motion:toggle

Controls the operating-system animation preference read by browsers as
prefers-reduced-motion. Reload the browser tab (F5) after changing the state.

Aliases:
  motion:get, anim:status, anim:on, anim:off, anim:toggle
```
