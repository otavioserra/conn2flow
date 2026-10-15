---
id: "HANDOFF-REQ-273-BATCH-282"
title: "Campo domain implementado; falta validação no ambiente publicado"
status: "pending-runtime-validation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Código e suítes aprovados; publicação SQL pendente por ausência de Docker no host."
summary_medium: "REQ-273 / BATCH-282; topologia dupla e autonomia monitorada.\nRetorno ao Macro-Arquiteto com pendência explícita do ambiente."
---

Projeto: conn2flow (Core).
Raiz absoluta: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
REQ-273; BATCH-282; topologia dupla; autonomo_monitorado; branch feat/req-273.

Destinatário: Macro-Arquiteto. Escopo: domain nos editores, widget e biblioteca
de formulários; mensagens pt-br/en; alias test:run para PHPUnit existente.

Evidências: [lote](../implementation/BATCH-282.md) e
[checklist](../validation/VALIDATION-CHECKLIST.md). PHPUnit: 1.795 sem falhas,
5 skips; Vitest: 686 aprovados. Recursos compilados e minificados atualizados.

Pendência: manager:update-all saída 1, destino Docker indisponível. Reexecutar
o pipeline em ambiente com conn2flow-app, conferir UI adicionar/editar/clonar,
persistência do schema, sanitização ao colar e submissões AJAX válidas/inválidas
em pt-br/en. Não declarar conclusão integral antes dessas evidências.
Outro agente trabalha em conn2flow-site: coordenar uso do ambiente compartilhado,
sem sobrepor pipelines. Nenhum arquivo do Site foi alterado nesta entrega.
Alteração anterior de schema-metadata.json permanece fora do commit.

Ação esperada: revisar o diff e resolver o destino de testes para fechar a
validação de runtime, preservando a homologação exclusivamente humana.
MCP Hub indisponível: este arquivo constitui o recibo; sem despacho remoto alegado.
