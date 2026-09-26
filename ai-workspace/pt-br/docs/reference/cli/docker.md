---
title: "CLI: Docker"
description: "Comandos c2f da família docker, argumentos e opções declarados no código."
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

A família `docker:*` tem 4 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `docker:logs`

Código: `cli/src/Commands/DockerLogsCommand.php`.

```text
Usage: c2f docker:logs [--lines=50]

Displays container logs.
```

## `docker:php-version`

Código: `cli/src/Commands/DockerPhpVersionCommand.php`.

```text
Usage: c2f docker:php-version

Runs 'docker exec conn2flow-app php -v'
```

## `docker:status`

Código: `cli/src/Commands/DockerStatusCommand.php`.

```text
Usage: c2f docker:status

Shows running containers and ports.
```

## `docker:truncate-logs`

Código: `cli/src/Commands/DockerTruncateLogsCommand.php`.

```text
Usage: c2f docker:truncate-logs

Cleans the PHP error log inside the Docker container.
```
