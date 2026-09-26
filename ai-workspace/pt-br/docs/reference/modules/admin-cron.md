---
title: "Módulo admin-cron"
description: "Painel das tarefas agendadas e dos disparos manuais."
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

# Módulo admin-cron

Administra tarefas recorrentes da tabela cron_tarefas. Algumas vêm da chave cron dos manifestos de módulos; outras são criadas manualmente. O painel também permite disparo imediato, pausa e consulta do último resultado.

## Como usar

Abra admin-cron/ (opção painel nos dois idiomas). Sincronize para recolher tarefas declaradas nos JSONs, ajuste frequência/estado/parâmetros, crie tarefa manual ou dispare uma tarefa existente. O card de agendador detectado infere atividade por execução nas últimas 24 horas; não lê o crontab do servidor.

## Referência técnica

A página trata painel. AJAX trata tarefas, sincronizar, disparar, alternar, salvar e excluir. A sincronização percorre os JSONs de módulos; campos de autoria de tarefa de módulo (nome, descrição, módulo e callback) vêm do arquivo, enquanto estado operacional alterado no painel é preservado via user_modified. Só tarefas manuais podem ser excluídas pelo painel. O disparo usa o executor da engine; tarefas que reiniciam serviços podem ser despachadas em background por includes/admin-cron-dispatch.php. Não há widget, template, hook ou hooks.api no JSON do painel.

cron_tarefas tem id_cron_tarefas, id lógico único, nome, descricao, modulo, frequencia, expressao_cron, funcao_callback, parametros JSON, ativo, último disparo/duração/status/log, origem, status, versao, checksum, datas, flags de atualização e project. Os índices cobrem id, (frequencia, ativo) e modulo.

## Limitações confirmadas

> [!CAUTION]
> Sincronizar pode desativar tarefas de módulo que sumiram do disco; disparar executa código PHP real. O estado de agendador detectado é apenas inferência, e o disparo em background pode perder isolamento quando somente setsid estiver disponível.

## Veja também

- [Módulos](modulos.md)
