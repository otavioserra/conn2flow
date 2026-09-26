---
title: "CLI: Banco de dados"
description: "Comandos c2f da família db, argumentos e opções declarados no código."
section: reference
order: 60
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DbTestCommand.php
  - cli/src/Commands/DbUpdateCommand.php
verified_at: e5b61f8e
---

# CLI: Banco de dados

A família `db:*` tem 2 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

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
