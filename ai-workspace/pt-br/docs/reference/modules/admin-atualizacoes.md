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
  - gestor/db/migrations/20260930210000_create_atualizacoes_choques_table.php
  - gestor/db/migrations/20260930220000_add_resolucao_fields_to_atualizacoes_choques.php
  - gestor/bibliotecas/atualizacoes-choques.php
verified_at: ca4337fc
---

# Módulo admin-atualizacoes

Exibe sessões e histórico de atualizações do core e encaminha as ações do painel ao controlador de atualização. Os logs de sessão ficam em temp/atualizacoes/sessions, planos em logs/atualizacoes e o resumo persistente em atualizacoes_execucoes.

## Como usar

Abra admin-atualizacoes/ para ver execuções recentes e iniciar ou acompanhar a atualização. Use admin-atualizacoes/detalhe/?log=<nome> para um log de sessão ou ?plano=<nome> para um plano. As duas rotas constam em pt-br e en. A página antiga disparar redireciona logicamente para a lista e não está declarada como rota no JSON.

**Choques das entregas (req-198).** A lista mostra os últimos 50 registros de `atualizacoes_choques`: data, camada e origem, arquivo, motivo (sobreposto, editado no servidor, retirado com edição), camada dona, versão e resolução. "Ver diff" abre `admin-atualizacoes/detalhe/?choque=<id>`, com o diff e o caminho da versão nova guardada em `backups/overrides/`. No detalhe fica a decisão (req-199 / BATCH-205): os botões das decisões que valem para o motivo e, em "Mesclar…", um editor com o resultado à esquerda (começa com a versão no ar) e a versão nova à direita. A decisão vai por AJAX (`ajaxOpcao=choque-resolver`) para `atualizacoes_choques_resolver()`, a mesma função da API; resolvido, o detalhe mostra quando e por quem (`painel:<e-mail>`).

## Referência técnica

O switch de página trata listar-atualizacoes, detalhe-atualizacao e o caso legado disparar. AJAX update recebe uma ação interna start, deploy, db, finalize, status ou cancel; call_system() monta parâmetros e inclui controladores/atualizacoes/atualizacoes-sistema.php, decodificando sua saída JSON. O componente atualizacoes-lista recebe linhas de logs e histórico; atualizacoes-detalhe-comp recebe o conteúdo escapado. O JSON não declara widget, template, hook ou hooks.api.

A migration de atualizacoes_execucoes define id numérico, session_id, modo, release_tag, checksum, contadores env_added/stats_removed/stats_copied, started_at, finished_at, status, exit_code, error_message, caminhos de plano/log e datas de registro. A lista lê os últimos 15 registros e até 20 logs de sessão.

## Limitações confirmadas

> [!WARNING]
> A tabela descrita no JSON traz aliases genéricos de CRUD que não correspondem às colunas reais da migration; a tela consulta o histórico com SQL explícito.

> [!CAUTION]
> call_system() substitui $_GET e $_REQUEST para simular uma chamada ao controlador incluído. O fluxo pode executar atualização real; dry_run é uma flag opcional, não o comportamento padrão.

## Veja também

- [Módulos](modulos.md)
