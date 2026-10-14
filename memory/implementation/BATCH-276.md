---
id: "BATCH-276"
title: "Capas isométricas de Páginas de Lousa e Interface"
status: "implemented-pending-homologation"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "Duas capas WebP e manifestos entregues; Lab 3.0/3.1 validado em 19/19 verificações por ambiente."
summary_medium: "REQ-267 coordenada com REQ-123 / BATCH-117 do Site.\nGeração built-in image_gen, manifestos com SHA-256 e validação estrita de peso."
---

# BATCH-276 — REQ-267

Projeto: conn2flow em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
Site coordenado: conn2flow-site em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site, REQ-123 / BATCH-117.
Topologia dupla; autonomia autonomo_monitorado, autorizada pelo pedido /goal de 2026-10-14.

- [x] Gerar dashboard-pages.webp e interface.webp pelo built-in image_gen.
- [x] Converter para WebP 1024x1024, método 6, qualidade 90, entre 60.000 e 100.000 bytes.
- [x] Atualizar manifesto com caminhos, dimensões, bytes e SHA-256.
- [x] Validar os 77 assets dos manifestos Core/Site e as 13 verificações de URL/fallback existentes.
- [x] resources:sync --skip-css --no-origin-update: saída 0, 4.341 recursos.
- [x] Publicar pelo pipeline oficial nos ambientes de teste.
- [x] Conferir arquivos HTTP e cards do Dashboard autenticado.
- [x] Preparar entrega de evidências ao Macro-Arquiteto.

Prompts e originais: [req267-generation.json](../validation/req267-generation.json). Gerador de conversão: [req267-integrate.py](../validation/req267-integrate.py). Inventário: [req267-assets.json](../validation/req267-assets.json). Validador reproduzível: [req267-validate.py](../validation/req267-validate.py).

Origens finais isoladas para os dois destinos: conn2flow-covers-267 (017fe4b8) e conn2flow-site-covers-123 (44771f8b), mais os assets do lote. A referência linha-3.1 estava antiga e foi descartada como origem de publicação após a validação detectar ausência do card de Páginas de Lousa. Worktrees auxiliares são preservadas; junctions apontam somente às árvores principais.

Defeitos encontrados: parâmetro --only=dashboard é inválido (alvos são tabelas), corrigido executando compilação geral; PATH padrão escolhe Bash WSL, corrigido por PATH do processo com Git Bash. Primeira tentativa do pipeline parou antes de enviar o Core e desligou a manutenção. Uma minificação de admin-categorias falhou, sem alteração de autoria; nova tentativa em andamento.

Limites: resources:sync não tem DocumentRoot no checkout e informa que dist precisa da etapa oficial de projeto. Homologação humana e consolidação em main não são afirmadas.


## Entrega técnica — 2026-10-14

- [x] Pipeline oficial project:update-all conn2flow-site-local: saída 0; assets em dist; manutenção desligada.
- [x] Pipeline oficial project:update-all conn2flow-v31-local: saída 0; assets em dist; manutenção desligada.
- [x] Oito arquivos HTTP 200 com SHA-256 idêntico aos manifestos em cada ambiente.
- [x] Dashboard autenticado: seis cards com capas carregadas em 1024x1024; sem erro de console; sem overflow em 1440 e 390 px.
- [x] Capturas desktop/mobile e cards examinados.
- [x] Auto-revisão de escopo e integridade dos manifestos; controle negativo: manifesto original do Core falha pela ausência das duas capas.
- [ ] Homologação humana e consolidação em main.

Resultados completos: req267-delivery.json e req267-evidence/{v30,v31}/report.json, em memory/validation do Core. São 19/19 verificações por ambiente. Prompts finais individuais: req267-generation.json. Todos os PNG originais permanecem no diretório generated_images do Codex; a entrega de produção é WebP nos caminhos canônicos solicitados.

Distinção de aceite: arquivos-api e interface fornecem recursos internos e não têm cards neste perfil do Dashboard. Ambos têm capa publicada e validada por HTTP/hash; os seis cards listados têm has-cover, imagem decodificada e naturalWidth/naturalHeight 1024. Não foram criados atalhos administrativos artificiais para esses módulos.

Defeitos de execução resolvidos: PATH apontava para WSL em vez de Git Bash; conversão MSYS não pode ser desativada globalmente para jq nativo lendo caminhos /c. A validação inicial recebeu 503 de outro pipeline com trava própria e foi repetida depois de sua conclusão. A sessão compartilhada de administrador foi renovada antes da conferência final. A referência linha-3.1 estava antiga: a primeira publicação nesse destino removeu o card de Páginas de Lousa; a validação detectou isso, e a publicação foi corrigida usando os checkouts atuais isolados do Core/Site (mesmas origens validadas na 3.0). admin-categorias.js já está vazio no HEAD e gera aviso não fatal de minificação. O compilador também emite avisos de Tailwind em recursos herdados, mantidos fora do escopo das capas.

O que não foi exercitado: geração de IA funcional dos módulos, formulários, pagamentos ou regressão completa de PHP/JS; o lote entrega somente assets e manifestos. Arquivos adicionais de outros lotes encontrados pelo project:verify foram preservados. Nenhuma produção publicada; nenhuma homologação humana atribuída.

Handoff ao Macro-Arquiteto (topologia dupla, autonomo_monitorado): revisar BATCH-276/REQ-267 no conn2flow em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow e BATCH-117/REQ-123 no conn2flow-site em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site. Evidências acima; escopo: oito capas e dois manifestos. MCP Hub indisponível nesta sessão; entrega por arquivos e resumo no chat, sem alegar despacho remoto. Próxima ação: revisão dos assets/diffs e encaminhamento à homologação humana.
