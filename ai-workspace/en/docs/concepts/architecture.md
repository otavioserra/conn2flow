---
title: "Conn2Flow architecture"
description: "How the router, modules, resources, and database assemble a page."
section: concepts
order: 10
sources:
  - gestor/gestor.php
  - gestor/config.php
  - gestor/bibliotecas/gestor.php
verified_at: 3b099ff0
---

# Conn2Flow architecture

The Gestor is the PHP entry point. `config.php` prepares paths, languages, and configuration; `gestor.php` interprets the route, checks access, retrieves the page, and assembles the response. The [request lifecycle](request-lifecycle.md) explains the order and the branches for the API and static files.

## Modules and resources

Each module lives in `gestor/modulos/<id>/` and may contain a PHP controller, JSON metadata, JavaScript, widgets, and `resources/<language>/`. Global resources live in `gestor/resources/<language>/`. The compiler turns authored files into `*Data.json`; the updater writes records to SQL. During normal execution, pages, layouts, components, and variables are read from the database. See [resources](resources.md) and [hooks](hooks.md).

Administrative modules commonly use `interface_iniciar()` and `interface_finalizar()` for CRUD, permissions, and screen assembly. Other modules can use their own controllers and routes. Pages are selected by the active language and matching path.

## Responsibilities

- `gestor/gestor.php`: routing and response assembly;
- `gestor/bibliotecas/`: shared functions such as `gestor.php`, `interface.php`, `banco.php`, and `widgets.php`;
- `gestor/modulos/`: module behavior and resources;
- `gestor/controladores/`: API, static files, and updates;
- `cli/`: authoring, compilation, and deployment commands.

> [!IMPORTANT]
> Editing an HTML file under `resources/` alone does not change the published page: the pipeline must update database records and rebuild derived CSS.
