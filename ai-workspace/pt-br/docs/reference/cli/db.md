---
title: "CLI: Banco de dados"
description: "Comandos c2f da família db, argumentos e opções declarados no código."
section: reference
order: 60
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DbCheckMigrationsCommand.php
  - cli/src/Commands/DbTestCommand.php
  - cli/src/Commands/DbUpdateCommand.php
verified_at: b1a69317
---

# CLI: Banco de dados

A família `db:*` tem 3 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `db:check-migrations`

Código: `cli/src/Commands/DbCheckMigrationsCommand.php`.

```text
Usage: c2f db:check-migrations [projectID] [--dir=<migrations folder>]

Without a project, checks the core (and its plugins). With a project from environment.json, also checks its migrations and plugins. --dir adds another folder. Exit code 1 when a duplicate is found.
```

Acusa versão ou classe de migração duplicada entre o core, os plugins e o projeto (req-197). No servidor todas vão para a mesma `db/migrations`, e o Phinx recusa todas as execuções quando duas colidem. Mesmo arquivo em duas origens (a cópia do core no espelho do projeto) não é choque. Roda sozinho como primeira verificação do `project:update-all` e serve para pre-commit.

## `db:test`

Código: `cli/src/Commands/DbTestCommand.php`.

```text
Usage: c2f db:test [--filter=TestName]

Runs PHPUnit tests configured in phpunit.xml.
```

## `db:update`

Código: `cli/src/Commands/DbUpdateCommand.php`.

```text
Usage: c2f db:update

Synchronizes data schemas and seeds into the local database.
```
