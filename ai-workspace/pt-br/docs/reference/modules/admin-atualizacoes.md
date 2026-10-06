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
  - gestor/bibliotecas/atualizacoes-automatica.php
  - gestor/bibliotecas/atualizacoes-execucao.php
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.cron.php
verified_at: 914c7b10
---

# Módulo admin-atualizacoes

Exibe sessões e histórico de atualizações do core e encaminha as ações do painel ao controlador de atualização. Os logs de sessão ficam em temp/atualizacoes/sessions, planos em logs/atualizacoes e o resumo persistente em atualizacoes_execucoes.

## Como usar

Abra admin-atualizacoes/ para ver execuções recentes e iniciar ou acompanhar a atualização. Use admin-atualizacoes/detalhe/?log=<nome> para um log de sessão ou ?plano=<nome> para um plano. As duas rotas constam em pt-br e en. A página antiga disparar redireciona logicamente para a lista e não está declarada como rota no JSON.

**Choques das entregas (req-198).** A lista mostra os últimos 50 registros de `atualizacoes_choques`: data, camada e origem, arquivo, motivo (sobreposto, editado no servidor, retirado com edição), camada dona, versão e resolução. "Ver diff" abre `admin-atualizacoes/detalhe/?choque=<id>`, com o diff e o caminho da versão nova guardada em `backups/overrides/`. No detalhe fica a decisão (req-199 / BATCH-205): os botões das decisões que valem para o motivo e, em "Mesclar…", um editor com o resultado à esquerda (começa com a versão no ar) e a versão nova à direita. A decisão vai por AJAX (`ajaxOpcao=choque-resolver`) para `atualizacoes_choques_resolver()`, a mesma função da API; resolvido, o detalhe mostra quando e por quem (`painel:<e-mail>`).

## Referência técnica

O switch de página trata listar-atualizacoes, detalhe-atualizacao e o caso legado disparar. AJAX update recebe uma ação interna start, deploy, db, finalize, status ou cancel; call_system() monta parâmetros e inclui controladores/atualizacoes/atualizacoes-sistema.php, decodificando sua saída JSON. O componente atualizacoes-lista recebe linhas de logs e histórico; atualizacoes-detalhe-comp recebe o conteúdo escapado. O JSON declara a tarefa cron automática, sem widget, template ou hooks.api.

A migration de atualizacoes_execucoes define id numérico, session_id, modo, release_tag, checksum, contadores env_added/stats_removed/stats_copied, started_at, finished_at, status, exit_code, error_message, caminhos de plano/log e datas de registro. A lista lê os últimos 15 registros e até 20 logs de sessão.

## Limitações confirmadas

> [!WARNING]
> A tabela descrita no JSON traz aliases genéricos de CRUD que não correspondem às colunas reais da migration; a tela consulta o histórico com SQL explícito.

> [!CAUTION]
> call_system() substitui $_GET e $_REQUEST para simular uma chamada ao controlador incluído. O fluxo pode executar atualização real; dry_run é uma flag opcional, não o comportamento padrão.

## Veja também

- [Módulos](modulos.md)

## Manual e Automático

**Manual** mantém o controle da execução e do histórico. **Automático** permite ligar a rotina, escolher período diário (1 dia), semanal (7 dias) ou mensal (30 dias), hora de 0 a 23 no horário do servidor e backup adicional. O padrão é desligado, semanal, às 03h, com backup. Salve e confira o estado, a próxima checagem e a última tentativa. Verificar agora consulta versões; não dispara instalação nem reinicia o período da rotina.

A tarefa admin-atualizacoes-automatica aparece em [admin-cron](admin-cron.md), com frequência horária. Ativar a opção no painel não instala o cron do servidor: o host precisa chamar a engine gestor/cron.php. A cada execução elegível, a tarefa escolhe a maior tag estável gestor-v… do GitHub, sem drafts ou prereleases, e compara com a versão instalada. O período usa ultima_automatica, com duas horas de tolerância; consulta que falha não marca o período como cumprido.

A configuração por instalação fica em autenticacoes/<domínio>/atualizacao-automatica.json, fora da pasta pública e do pacote. A escrita usa arquivo temporário e rename. Choques pendentes, trava ocupada, versão já instalada ou recusada impedem o disparo. A automação entrega somente tag e backup ao atualizador compartilhado: execução completa, snapshot, verificação e volta automática permanecem obrigatórios.

Uma execução pendente impede nova tentativa. O resultado é lido em um ciclo posterior: sucesso encerra; falha ou rollback recusa a versão até liberação explícita. locked não a recusa, pois não houve instalação; execução cujo registro sumiu também é encerrada sem recusar. Após liberar uma versão, a nova tentativa ainda depende do período e horário.

Não há modo só avisar, espera por idade da release, seleção de dias da semana nem notificação por e-mail. A hora não é convertida para o fuso do navegador. Consulte a [biblioteca de atualização automática](../libraries/atualizacoes-automatica.md) para estado e decisões.
