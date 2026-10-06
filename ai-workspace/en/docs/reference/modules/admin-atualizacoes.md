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
  - gestor/db/migrations/20260930210000_create_atualizacoes_choques_table.php
  - gestor/db/migrations/20260930220000_add_resolucao_fields_to_atualizacoes_choques.php
  - gestor/bibliotecas/atualizacoes-choques.php
  - gestor/bibliotecas/atualizacoes-automatica.php
  - gestor/bibliotecas/atualizacoes-execucao.php
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.cron.php
verified_at: 914c7b10
---

# admin-atualizacoes module

This module shows core-update sessions and history and forwards panel actions to the update controller. Session logs live in temp/atualizacoes/sessions, plans in logs/atualizacoes, and persistent summaries in atualizacoes_execucoes.

## How to use

Open admin-atualizacoes/ to review recent executions and start or follow an update. Open admin-atualizacoes/detalhe/?log=<name> for a session log or ?plano=<name> for a plan. Both routes exist in pt-br and en. The former disparar page falls back to the listing and is not a route in JSON.

**Delivery clashes (req-198).** The list shows the last 50 rows of `atualizacoes_choques`: date, layer and origin, file, reason (overridden, edited on the server, removal blocked by an edit), owner layer, version and resolution. "View diff" opens `admin-atualizacoes/detalhe/?choque=<id>`, with the diff and the path of the new version kept in `backups/overrides/`. The detail page holds the decision (req-199 / BATCH-205): buttons for the decisions valid for the reason and, under "Merge…", an editor with the result on the left (it starts with the live version) and the new version on the right. The decision goes through AJAX (`ajaxOpcao=choque-resolver`) to `atualizacoes_choques_resolver()`, the same function as the API; once resolved, the detail shows when and by whom (`painel:<e-mail>`).

## Technical reference

The page switch handles listar-atualizacoes, detalhe-atualizacao, and legacy disparar. AJAX update accepts internal actions start, deploy, db, finalize, status, and cancel; call_system() builds parameters, includes controladores/atualizacoes/atualizacoes-sistema.php, and decodes its JSON output. atualizacoes-lista receives log and history rows; atualizacoes-detalhe-comp receives escaped content. JSON declares the automatic cron task, with no widget, template or hooks.api.

The atualizacoes_execucoes migration defines a numeric id, session_id, modo, release_tag, checksum, env_added/stats_removed/stats_copied counters, started_at, finished_at, status, exit_code, error_message, plan/log paths, and record dates. The list reads the latest 15 records and up to 20 session logs.

## Confirmed limitations

> [!WARNING]
> The JSON table metadata uses generic CRUD aliases that do not match the migration's columns; this screen queries history with explicit SQL.

> [!CAUTION]
> call_system() replaces $_GET and $_REQUEST to simulate a call into the included controller. This flow can run a real update; dry_run is an optional flag, not the default behavior.

## See also

- [Modules](modulos.md)

## Manual and Automatic

**Manual** retains execution and history controls. **Automatic** enables the routine and selects daily (1 day), weekly (7 days) or monthly (30 days) checks, an hour from 0 to 23 in server time, and an extra backup. Defaults are disabled, weekly, 03:00, with backup. Save and review status, next check and last attempt. Check now queries releases; it does not install or restart the routine's interval.

The admin-atualizacoes-automatica task appears in [admin-cron](admin-cron.md) with hourly frequency. Enabling it in the panel does not install the server's cron entry: the host must invoke gestor/cron.php. An eligible run selects the highest stable gestor-v… GitHub tag, excluding drafts/prereleases, and compares it with the installed version. The interval uses ultima_automatica with a two-hour tolerance; failed queries do not consume the interval.

Per-installation configuration lives in autenticacoes/<domain>/atualizacao-automatica.json outside the public folder and update package. Writing uses a temporary file and rename. Pending clashes, an occupied lock, an installed or refused version block execution. Automation passes only tag and backup to the shared updater: full execution, snapshot, verification and automatic rollback remain mandatory.

A pending run blocks another attempt. A later cycle reads its result: success completes; failure or rollback refuses that version until explicitly released. locked does not refuse it because installation never started; a missing execution record also clears pending state without refusal. Releasing a version still leaves interval and hour checks in effect.

There is no notify-only mode, release-age delay, weekday selection or email notification. The browser's timezone does not change the hour. See the [automatic update library](../libraries/atualizacoes-automatica.md) for state and decisions.
