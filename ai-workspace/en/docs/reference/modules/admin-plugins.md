---
title: "admin-plugins module"
description: "Plugin registration, installation, and updates."
section: reference
module: admin-plugins
sources:
  - gestor/modulos/admin-plugins/admin-plugins.php
  - gestor/modulos/admin-plugins/admin-plugins.js
  - gestor/modulos/admin-plugins/admin-plugins.json
  - gestor/modulos/admin-plugins/resources
  - gestor/db/migrations/20250723165533_create_plugins_table.php
  - gestor/db/migrations/20250903130000_alter_plugins_table_add_phase1_fields.php
verified_at: c4e05805
---

# admin-plugins module

This module registers plugins and controls installation, updates, and reprocessing. The record stores the package source; processing delegates installation to the plugin controller and updates execution state in plugins.

## How to use

Open admin-plugins/ to list, admin-plugins/adicionar/ to register, and admin-plugins/editar/?id=<slug> to configure. admin-plugins/executar/ presents execution, while admin-plugins/teste/ exposes test tools. These routes exist in pt-br and en. Choose a file, public repository, private repository, or local path as the source according to the form, then install, update, or reprocess.

## Technical reference

The main switch handles adicionar, editar, executar, acao, and teste; listing/status/deletion use the shared interface. AJAX update accepts params.acao instalar, atualizar, reprocessar, or status. The controller also exposes CLI --action=install|update|reprocess with --plugin=<id>. It discovers GitHub releases, downloads artifacts, verifies SHA-256 when provided, and calls plugin_process_cli(); the result updates status_execucao. Its JSON defines no module widget, template, hook, or hooks.api; installed plugins may provide their own hooks.

plugins initially has id_plugins, id_usuarios, nome, id, status, versao, and dates. The phase-1 migration adds origem_tipo, origem_referencia, origem_branch_tag, origem_credencial_ref, versao_instalada, checksum_pacote, manifest_json, status_execucao, and installation/update dates. The id index added in that migration is not unique.

## Confirmed limitations

> [!WARNING]
> Checksum verification occurs only when a SHA-256 file is available; a missing checksum does not mean the package was verified. Installation/update executes plugin code and changes files and database state. The initial migration declares no uniqueness for id, and the later index is nonunique.

## See also

- [Modules](modulos.md)
