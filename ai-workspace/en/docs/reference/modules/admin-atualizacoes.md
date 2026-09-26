---
title: "admin-atualizacoes module"
description: "System update orchestration and history."
section: reference
module: admin-atualizacoes
sources:
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.php
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.js
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.json
  - gestor/modulos/admin-atualizacoes/resources
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/db/migrations/20250814141000_create_atualizacoes_execucoes_table.php
verified_at: c4e05805
---

# admin-atualizacoes module

This module shows core-update sessions and history and forwards panel actions to the update controller. Session logs live in temp/atualizacoes/sessions, plans in logs/atualizacoes, and persistent summaries in atualizacoes_execucoes.

## How to use

Open admin-atualizacoes/ to review recent executions and start or follow an update. Open admin-atualizacoes/detalhe/?log=<name> for a session log or ?plano=<name> for a plan. Both routes exist in pt-br and en. The former disparar page falls back to the listing and is not a route in JSON.

## Technical reference

The page switch handles listar-atualizacoes, detalhe-atualizacao, and legacy disparar. AJAX update accepts internal actions start, deploy, db, finalize, status, and cancel; call_system() builds parameters, includes controladores/atualizacoes/atualizacoes-sistema.php, and decodes its JSON output. atualizacoes-lista receives log and history rows; atualizacoes-detalhe-comp receives escaped content. JSON declares no widget, template, hook, or hooks.api.

The atualizacoes_execucoes migration defines a numeric id, session_id, modo, release_tag, checksum, env_added/stats_removed/stats_copied counters, started_at, finished_at, status, exit_code, error_message, plan/log paths, and record dates. The list reads the latest 15 records and up to 20 session logs.

## Confirmed limitations

> [!WARNING]
> The ?plano= branch of admin_atualizacoes_detalhe() uses $dir without initializing it; plan viewing can fail even when the file exists. The JSON table metadata uses generic CRUD aliases that do not match the migration's columns; this screen queries history with explicit SQL.

> [!CAUTION]
> call_system() replaces $_GET and $_REQUEST to simulate a call into the included controller. This flow can run a real update; dry_run is an optional flag, not the default behavior.

## See also

- [Modules](modulos.md)
