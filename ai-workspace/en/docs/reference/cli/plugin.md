---
title: "CLI: Commands plugin"
description: "Reference for plugin commands registered in the c2f console."
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

# CLI: Commands plugin

Syntax from executable help; check the source before running commands with external effects.

## `plugin:build`

Source: `cli/src/Commands/PluginBuildCommand.php`.

```text
Usage: c2f plugin:build [private|public]

Defaults to 'private'.
```

## `plugin:commit`

Source: `cli/src/Commands/PluginCommitCommand.php`.

```text
Usage: c2f plugin:commit <message> [private|public]

Defaults to 'private'.
```

## `plugin:release`

Source: `cli/src/Commands/PluginReleaseCommand.php`.

```text
Usage: c2f plugin:release <type> <tagMsg> <commitMsg> [private|public]

Arguments:
  type       Release type (patch, minor, major)
  tagMsg     Tag annotation message
  commitMsg  Release commit message
  pluginType private (default) or public
```

## `plugin:resources`

Source: `cli/src/Commands/PluginResourcesCommand.php`.

```text
Usage: c2f plugin:resources [private|public]

Defaults to 'private'.
```

## `plugin:sync`

Source: `cli/src/Commands/PluginSyncCommand.php`.

```text
Usage: c2f plugin:sync [private|public]

Defaults to 'private'.
```

## Package

`plugin:build` defaults to `private` and calls the Bash build script; `public` selects the other mode. `plugin:resources` compiles the active plugin. The installer looks for `manifest.json` with `id`, `name` and `version`; see [create a plugin](../../guides/create-a-plugin.md).
