---
title: "variables module"
description: "Editing module configuration variables by language."
section: reference
module: variables
sources:
  - gestor/modulos/variables/variables.php
  - gestor/modulos/variables/variables.js
  - gestor/modulos/variables/variables.json
  - gestor/modulos/variables/resources
  - gestor/bibliotecas/configuracao.php
  - gestor/db/migrations/20250723165549_create_variaveis_table.php
verified_at: a9a226e0
---

# variables module

This screen edits each module's configuration variables. Its selector chooses a modulos record, while the configuracao library builds editing cards from variaveis records in the current language.

## How to use

Open variables/ and choose a module in the searchable selector; the URL then carries that module's id parameter. Edit, add, or remove variables in the cards and save. The route and flow are the same in pt-br and en. The selector excludes the bibliotecas group.

## Technical reference

The controller's only main case is variables, with the alteracoes interface. AJAX opcao returns only an empty payload and Ok status. configuracao_administracao() builds the fields; configuracao_administracao_salvar() compares submitted values with rows by modulo and language, updates or inserts them, deletes omitted rows, and increments the modulos record's version and modification date when values change. The JSON defines no specific widget, template, hook, or hooks.api.

The module JSON points its main table to modulos because the screen also displays metadata for the selected module. The actual values live in variaveis: id_variaveis, language, modulo, id, MEDIUMTEXT valor, tipo, grupo, and descricao in the initial migration. Library types include string, text, bool, number, quantidade, dinheiro, css, js, html, editor-texto, and date types.

## Confirmed limitations

> [!WARNING]
> This screen is not a modulos CRUD. Earlier status/delete buttons were removed because they acted on modulos and could deactivate or delete the entire module. There are no dedicated variables/adicionar/ or variables/editar/ pages.

> [!CAUTION]
> The selected-module query concatenates id and language into SQL; the configuration library also concatenates modulo and language in queries. This flow writes several rows without an explicit transaction.

## See also

- [Modules](modulos.md)
