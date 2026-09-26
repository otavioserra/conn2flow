---
title: "CLI: Comandos plugin"
description: "Referência dos comandos plugin registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/PluginBuildCommand.php
  - cli/src/Commands/PluginCommitCommand.php
  - cli/src/Commands/PluginReleaseCommand.php
  - cli/src/Commands/PluginResourcesCommand.php
  - cli/src/Commands/PluginSyncCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos plugin

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `plugin:build`

Código: `cli/src/Commands/PluginBuildCommand.php`.

```text
Usage: c2f plugin:build [private|public]

Defaults to 'private'.
```

## `plugin:commit`

Código: `cli/src/Commands/PluginCommitCommand.php`.

```text
Usage: c2f plugin:commit <message> [private|public]

Defaults to 'private'.
```

## `plugin:release`

Código: `cli/src/Commands/PluginReleaseCommand.php`.

```text
Usage: c2f plugin:release <type> <tagMsg> <commitMsg> [private|public]

Arguments:
  type       Release type (patch, minor, major)
  tagMsg     Tag annotation message
  commitMsg  Release commit message
  pluginType private (default) or public
```

## `plugin:resources`

Código: `cli/src/Commands/PluginResourcesCommand.php`.

```text
Usage: c2f plugin:resources [private|public]

Defaults to 'private'.
```

## `plugin:sync`

Código: `cli/src/Commands/PluginSyncCommand.php`.

```text
Usage: c2f plugin:sync [private|public]

Defaults to 'private'.
```

## Pacote

`plugin:build` usa `private` como padrão e chama o script Bash de build; `public` seleciona a outra modalidade. `plugin:resources` compila o plugin ativo. O instalador procura `manifest.json`, com `id`, `name` e `version`; veja [criar plugin](../../guides/create-a-plugin.md).
