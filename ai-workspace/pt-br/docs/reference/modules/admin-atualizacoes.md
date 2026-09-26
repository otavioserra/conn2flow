---
title: "Módulo admin-atualizacoes"
description: "Orquestração e histórico de atualizações do sistema."
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

# Módulo admin-atualizacoes

Exibe sessões e histórico de atualizações do core e encaminha as ações do painel ao controlador de atualização. Os logs de sessão ficam em temp/atualizacoes/sessions, planos em logs/atualizacoes e o resumo persistente em atualizacoes_execucoes.

## Como usar

Abra admin-atualizacoes/ para ver execuções recentes e iniciar ou acompanhar a atualização. Use admin-atualizacoes/detalhe/?log=<nome> para um log de sessão ou ?plano=<nome> para um plano. As duas rotas constam em pt-br e en. A página antiga disparar redireciona logicamente para a lista e não está declarada como rota no JSON.

## Referência técnica

O switch de página trata listar-atualizacoes, detalhe-atualizacao e o caso legado disparar. AJAX update recebe uma ação interna start, deploy, db, finalize, status ou cancel; call_system() monta parâmetros e inclui controladores/atualizacoes/atualizacoes-sistema.php, decodificando sua saída JSON. O componente atualizacoes-lista recebe linhas de logs e histórico; atualizacoes-detalhe-comp recebe o conteúdo escapado. O JSON não declara widget, template, hook ou hooks.api.

A migration de atualizacoes_execucoes define id numérico, session_id, modo, release_tag, checksum, contadores env_added/stats_removed/stats_copied, started_at, finished_at, status, exit_code, error_message, caminhos de plano/log e datas de registro. A lista lê os últimos 15 registros e até 20 logs de sessão.

## Limitações confirmadas

> [!WARNING]
> No ramo ?plano=, admin_atualizacoes_detalhe() usa a variável $dir sem inicializá-la; o caminho de plano pode falhar mesmo quando o arquivo existe. A tabela descrita no JSON traz aliases genéricos de CRUD que não correspondem às colunas reais da migration; a tela consulta o histórico com SQL explícito.

> [!CAUTION]
> call_system() substitui $_GET e $_REQUEST para simular uma chamada ao controlador incluído. O fluxo pode executar atualização real; dry_run é uma flag opcional, não o comportamento padrão.

## Veja também

- [Módulos](modulos.md)
