---
title: "admin-modos-ia module"
description: "AI instruction modes by target and language."
section: reference
module: admin-modos-ia
sources:
  - gestor/modulos/admin-modos-ia/admin-modos-ia.php
  - gestor/modulos/admin-modos-ia/admin-modos-ia.js
  - gestor/modulos/admin-modos-ia/admin-modos-ia.json
  - gestor/modulos/admin-modos-ia/resources
  - gestor/db/migrations/20251013145832_create_modos_ia_table.php
  - gestor/db/migrations/20251013145700_create_alvos_ia_table.php
verified_at: 98ac881d
---

# admin-modos-ia module

This module maintains instruction modes for AI-assisted resources. A mode has a name, slug, target, prompt, language, and default flag; available targets come from alvos_ia.

## How to use

Open admin-modos-ia/, create at admin-modos-ia/adicionar/, and edit at admin-modos-ia/editar/?id=<slug>. Choose a target in the current language, enter instructions in the CodeMirror editor, and mark a default if needed. Routes exist in both languages. Listing, status, and deletion use the shared interface.

## Technical reference

The main switch handles adicionar and editar; listing/status/deletion use the standard interface. AJAX opcao with verificar-padrao queries whether an active default prompt exists for the target. JavaScript configures CodeMirror in Markdown mode. JSON defines no widget, template, hook, or hooks.api. The modos_ia migration defines id_modos_ia, nome, id, language, alvo, padrao, MEDIUMTEXT prompt, status, versao, dates, file_version, and checksum; (id, language) is unique. alvos_ia stores target ids/names by language.

## Confirmed limitations

> [!WARNING]
> When marking a mode as default, both create and edit clear padrao in prompts_ia, not modos_ia. The AJAX check also queries prompts_ia. Thus mode defaults are not kept unique among modes and can change prompt defaults. The UPDATE does not filter language.

## See also

- [AI prompts](admin-prompts-ia.md)
- [AI servers](admin-ia.md)
