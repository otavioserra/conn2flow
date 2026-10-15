---
id: "HANDOFF-REQ-273-BATCH-282"
title: "Campo domain implementado; falta validação no ambiente publicado"
status: "pending-runtime-validation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Código, suítes, publicação SSH e editor aprovados; persistência/AJAX real ainda não exercitados."
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

Atualização: destino correto indicado pelo humano é conn2flow-site-local,
local=true e deploy_mode=ssh. project:update-all concluiu com saída 0; SQL, CSS,
assets e 822 arquivos conferidos. Editar/adicionar/clonar com opção Domínio,
seleção serializada no schema e widget preview com atributos corretos;
desktop/mobile sem overflow e zero pageerror. Script/JSON em memory/validation/req273-browser*.
Pendência: persistência do schema por UI e submissões AJAX válidas/inválidas
em pt-br/en. Não declarar conclusão integral antes dessas evidências.
Outro agente trabalha em conn2flow-site: coordenar uso do ambiente compartilhado,
sem sobrepor pipelines. Nenhum arquivo do Site foi alterado nesta entrega.
Alteração anterior de schema-metadata.json permanece fora do commit.

Ação esperada: revisar o diff e complementar a validação de submissão,
preservando a homologação exclusivamente humana.
MCP Hub indisponível: este arquivo constitui o recibo; sem despacho remoto alegado.
