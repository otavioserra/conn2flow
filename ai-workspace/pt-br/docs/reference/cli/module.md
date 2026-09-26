---
title: "CLI: Comandos module"
description: "Referência dos comandos module registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/ModuleCreateCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos module

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `module:create`

Código: `cli/src/Commands/ModuleCreateCommand.php`.

```text
Usage: c2f module:create <module-id> [options]

Creates a complete canonical module in gestor/modulos/<module-id>/ with:
  - <module-id>.php (Controller with lifecycle hooks)
  - <module-id>.json (Schema metadata with natural_key strategy)
  - <module-id>.js (Frontend script)
  - resources/pt-br/ & resources/en/ (Pages, templates, variables)

Arguments:
  module-id     Kebab-case identifier of the module (e.g. 'relatorios-gerenciais').

Options:
  --table=NAME  Custom database table name (default: same as module identifier with underscores).
```

## Depois do scaffold

`module:create` normaliza o id para kebab case e recusa diretório existente. O comando gera controller, JSON, JS e recursos pt-br/en; revise permissões, CRUD e histórico antes de sincronizar. Veja [criar módulo](../../guides/create-a-module.md).
