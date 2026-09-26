---
title: "admin-prompts-ia module"
description: "AI prompt library by target and language."
section: reference
module: admin-prompts-ia
sources:
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.php
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.js
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.json
  - gestor/modulos/admin-prompts-ia/resources
  - gestor/db/migrations/20251007105018_create_prompts_ia_table.php
  - gestor/db/migrations/20260331100000_add_id_usuarios_to_prompts_ia.php
  - gestor/db/migrations/20251013145700_create_alvos_ia_table.php
verified_at: 98ac881d
---

# admin-prompts-ia module

This module maintains reusable prompts for AI targets. Each record has a name, slug, target, language, text, and default flag; the target is chosen from alvos_ia records.

## How to use

Open admin-prompts-ia/, create at admin-prompts-ia/adicionar/, and edit at admin-prompts-ia/editar/?id=<slug>. Choose a target, write the prompt in CodeMirror, and mark it as default if appropriate. All three routes exist in pt-br and en. The shared interface supplies listing, status, and deletion.

## Technical reference

The main switch handles adicionar and editar; AJAX opcao/verificar-padrao counts active prompts with padrao=1 for a target. JavaScript only configures CodeMirror in Markdown mode. Creation stores the operator's id_usuarios. hook_apply_filters('admin-prompts-ia', 'padrao.update.where', ...) can restrict the UPDATE clearing a default; module JSON declares no hooks.api, widget, or own template.

The prompts_ia migration creates id_prompts_ia, nome, id, language, alvo, padrao, MEDIUMTEXT prompt, status, versao, dates, file_version, and checksum, with unique (id, language); a later migration adds id_usuarios. alvos_ia defines target choices by language.

## Confirmed limitations

> [!WARNING]
> Without an additional filter, marking a prompt as default clears padrao for every prompt with the same target, without filtering language or user. The AJAX check also does not filter language/user. Although creation records id_usuarios, the controller's edit/list paths do not themselves enforce an ownership scope.

## See also

- [AI modes](admin-modos-ia.md)
- [AI servers](admin-ia.md)
