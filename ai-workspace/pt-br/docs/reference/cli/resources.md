---
title: "CLI: Comandos resources"
description: "Referência dos comandos resources registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/ResourcesSyncCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos resources

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `resources:sync`

Código: `cli/src/Commands/ResourcesSyncCommand.php`.

```text
Usage: c2f resources:sync [options]

Executes the full pipeline of resource compilation, scanning layouts, pages, components,
variables, AI modes, and forms, verifying checksums and updating gestor/db/data/*Data.json.

Options:
  --force       Force rebuild of precompiled CSS and assets cache.
```

## Efeito real

`resources:sync` executa o compilador de `resources/` para `gestor/db/data/*Data.json` e publica assets quando há destino configurado. A opção `--force` aparece na ajuda, mas `ResourcesSyncCommand` não a repassa ao compilador. Consulte [recursos](../../concepts/resources.md).
