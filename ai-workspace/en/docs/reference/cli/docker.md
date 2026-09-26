---
title: "CLI: Docker"
description: "c2f docker commands, arguments and options declared in source code."
section: reference
order: 70
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DockerLogsCommand.php
  - cli/src/Commands/DockerPhpVersionCommand.php
  - cli/src/Commands/DockerStatusCommand.php
  - cli/src/Commands/DockerTruncateLogsCommand.php
verified_at: e5b61f8e
---

# CLI: Docker

The `docker:*` family has 4 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `docker:logs`

Source: `cli/src/Commands/DockerLogsCommand.php`.

```text
Usage: c2f docker:logs [--lines=50]

Displays container logs.
```

## `docker:php-version`

Source: `cli/src/Commands/DockerPhpVersionCommand.php`.

```text
Usage: c2f docker:php-version

Runs 'docker exec conn2flow-app php -v'
```

## `docker:status`

Source: `cli/src/Commands/DockerStatusCommand.php`.

```text
Usage: c2f docker:status

Shows running containers and ports.
```

## `docker:truncate-logs`

Source: `cli/src/Commands/DockerTruncateLogsCommand.php`.

```text
Usage: c2f docker:truncate-logs

Cleans the PHP error log inside the Docker container.
```
