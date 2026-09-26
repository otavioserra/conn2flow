---
title: "interface module"
description: "Shared text catalog for the administrative interface."
section: reference
module: interface
sources:
  - gestor/modulos/interface/interface.json
  - gestor/bibliotecas/interface.php
  - gestor/db/migrations/20250723165549_create_variaveis_table.php
verified_at: c4e05805
---

# interface module

This resource module supplies shared labels, messages, and hints for the administrative interface in pt-br and en. Its JSON defines no PHP controller, own pages, or module table; other modules look up its variables under the interface id.

## How to use

JSON declares no interface/ route. Operators see these texts in other modules' lists, forms, and actions. To customize them, open variables/ and select interface, then save values for the desired language. Gestor looks up variables by module namespace.

## Technical reference

interface.json contains resources.pt-br.variables and resources.en.variables with id/value/type entries; it has no page or AJAX switch, widget, template, hook, or hooks.api. Labels such as field-name and tooltip-button-edit are used by the interface.php library. Persisted values are variaveis rows with modulo=interface and the matching language; the migration defines id_variaveis, language, modulo, id, valor, tipo, grupo, and descricao. interface.php coordinates shared rendering, validation, and CRUD actions, but is not an interface-module controller.

## Confirmed limitations

> [!CAUTION]
> Changing a label in one language does not change the other. Treating interface as a CRUD page or as an independent table contradicts its JSON: it is a variable catalog used by the shared interface.

## See also

- [Variables](variables.md)
- [Modules](modulos.md)
