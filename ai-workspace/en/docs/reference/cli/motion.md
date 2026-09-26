---
title: "CLI: Commands motion"
description: "Reference for motion commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/MotionCommand.php
verified_at: e5b61f8e
---

# CLI: Commands motion

Syntax from executable help; check the source before running commands with external effects.

## `motion:status`

Source: `cli/src/Commands/MotionCommand.php`.

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
