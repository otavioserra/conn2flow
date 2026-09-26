---
title: "admin-cron module"
description: "Scheduled-task panel and manual triggers."
section: reference
module: admin-cron
sources:
  - gestor/modulos/admin-cron/admin-cron.php
  - gestor/modulos/admin-cron/admin-cron.js
  - gestor/modulos/admin-cron/admin-cron.json
  - gestor/modulos/admin-cron/includes/admin-cron-dispatch.php
  - gestor/db/migrations/20260901120000_create_cron_tarefas_table.php
verified_at: c4e05805
---

# admin-cron module

This panel manages recurring jobs in cron_tarefas. Some come from the cron key in module manifests; others are created manually. It also supports immediate triggering, pausing, and inspection of the latest result.

## How to use

Open admin-cron/ (the painel option in both languages). Synchronize to collect tasks declared in module JSON files, adjust frequency/state/parameters, create a manual task, or trigger an existing one. The scheduler-detected card infers activity from executions in the last 24 hours; it does not read the server crontab.

## Technical reference

The page handles painel. AJAX handles tarefas, sincronizar, disparar, alternar, salvar, and excluir. Synchronization scans module JSON files; module-task authorship fields (name, description, module, callback) come from the file, while operational state changed in the panel is preserved through user_modified. Only manual tasks can be deleted from the panel. Triggering uses the engine executor; service-restarting tasks can be dispatched in the background through includes/admin-cron-dispatch.php. The panel JSON has no widget, template, hook, or hooks.api.

cron_tarefas has id_cron_tarefas, a unique logical id, nome, descricao, modulo, frequencia, expressao_cron, JSON parametros, ativo, last trigger/duration/status/log, origem, status, versao, checksum, dates, update flags, and project. Indexes cover id, (frequencia, ativo), and modulo.

## Confirmed limitations

> [!CAUTION]
> Synchronization can deactivate module tasks removed from disk; triggering runs real PHP code. Scheduler detection is only an inference, and background dispatch may lose isolation when only setsid is available.

## See also

- [Modules](modulos.md)
