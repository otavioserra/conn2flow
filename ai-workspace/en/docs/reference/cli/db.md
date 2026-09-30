---
title: "CLI: Database"
description: "c2f db commands, arguments and options declared in source code."
section: reference
order: 60
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DbCheckMigrationsCommand.php
  - cli/src/Commands/DbTestCommand.php
  - cli/src/Commands/DbUpdateCommand.php
verified_at: e5b61f8e
---

# CLI: Database

The `db:*` family has 3 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `db:check-migrations`

Source: `cli/src/Commands/DbCheckMigrationsCommand.php`.

```text
Usage: c2f db:check-migrations [projectID] [--dir=<migrations folder>]

Without a project, checks the core (and its plugins). With a project from environment.json, also checks its migrations and plugins. --dir adds another folder. Exit code 1 when a duplicate is found.
```

Reports duplicated migration versions or classes across core, plugins and project (req-197). On the server all of them land in the same `db/migrations`, and Phinx refuses every run when two collide. The same file in two sources (the core copy in the project mirror) is not a clash. Runs automatically as the first check of `project:update-all` and works as a pre-commit check.

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
