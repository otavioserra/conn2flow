---
title: "CLI: Database"
description: "c2f db commands, arguments and options declared in source code."
section: reference
order: 60
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DbTestCommand.php
  - cli/src/Commands/DbUpdateCommand.php
verified_at: e5b61f8e
---

# CLI: Database

The `db:*` family has 2 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `db:test`

Source: `cli/src/Commands/DbTestCommand.php`.

```text
Usage: c2f db:test [--filter=TestName]

Runs PHPUnit tests configured in phpunit.xml.
```

## `db:update`

Source: `cli/src/Commands/DbUpdateCommand.php`.

```text
Usage: c2f db:update

Synchronizes data schemas and seeds into the local database.
```
