---
name: c2f-mdd-indexing-and-handoffs
description: "Use ao criar metadados de memória MDD, regenerar index.md, alterar status ou preparar handoffs entre Executor, Revisor, Arquiteto e humano. Não substitui aprovação normativa nem homologação humana."
---

# Indexação e handoffs MDD — ARCH-015 / REQ-072

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar artefatos MDD, alterar frontmatter/status, regenerar índices ou transferir um lote entre papéis.
- **SKIP APENAS SE**: Trabalho sem artefatos de memória nem transição de papel.
- **CONSEQUÊNCIA DE IGNORAR**: Índices desatualizados, perda do escopo do handoff e falsa homologação.

Leia CURRENT.md e a requisição aprovada no repositório alvo. Na matriz e satélites migrados use memory/; boilerplates legados usam sdd/ até migração autorizada. Código/configuração vigentes prevalecem sobre memória histórica.

## Frontmatter canônico

Documentos novos de governança começam com este mapeamento YAML de strings. Use aspas duplas com escapes JSON (também válidos em YAML); não use listas, objetos, âncoras nem tags. Blocos `|`, `|-`, `|+`, `>`, `>-`, `>+` são aceitos para leitura; prefira strings com `\n` para escrita determinística.

```yaml
---
id: "BATCH-074"
title: "Implementação da Trava Tripla MDD"
status: "ready-for-review"
date: "2026-10-09"
author: "executor"
target_repo: "conn2flow-ai-workspace"
summary_short: "Entrega dos comandos de metadados, índices e handoffs da Trava Tripla MDD."
summary_medium: "Paridade PHP/Python, skill canônica e inbox de revisão.\nEvidências no lote e no recibo."
---
```

| Campo | Regra |
| --- | --- |
| id | ID canônico preservado; não renumerar história |
| title | Título descritivo do artefato |
| status | Estado verificável; nunca inventar aprovação |
| date | Data YYYY-MM-DD |
| author | architect, executor, reviewer ou human |
| target_repo | Identificador do repositório alvo |
| summary_short | Uma linha com 30–140 caracteres; usada diretamente no índice |
| summary_medium | Resumo de duas ou três linhas, escapadas ou em bloco YAML |

Os CLIs preservam campos adicionais de string. Um documento legado recebe defaults inferidos, com date/author vazios quando desconhecidos; preencha-os com fatos verificáveis. As ferramentas não validam o vocabulário de status nem os limites editoriais dos resumos.

## Auto-cura e mutação

No core, os comandos resolvem a raiz do core; use `--repo=RAIZ_ABSOLUTA` para a matriz ou outro projeto. No Python, `--path RAIZ_ABSOLUTA` seleciona o projeto; default é o diretório atual.

```sh
php cli/c2f.php memory:index [pasta] --repo=RAIZ_ABSOLUTA
php cli/c2f.php memory:set req-072 --status=IN-PROGRESS --repo=RAIZ_ABSOLUTA
php cli/c2f.php memory:get req-072 status --json --repo=RAIZ_ABSOLUTA
mdd index [pasta] --path RAIZ_ABSOLUTA
mdd meta set req-072 status IN-PROGRESS --path RAIZ_ABSOLUTA
mdd meta get req-072 status --json --path RAIZ_ABSOLUTA
```

Sem pasta, index regenera todos os index.md existentes sob memory/, incluindo arquivos históricos. Com pasta explícita, cria/reconstrói somente o índice local. Alvos aceitam caminho em memory/ ou nome/ID de arquivo único; ambiguidade falha e exige caminho explícito. Não altere o índice como se fosse fonte de autoria: a regeneração substitui seu conteúdo com tabela e links aos índices filhos. README, CURRENT, BATCH-INDEX, DECISION-LOG e VALIDATION-CHECKLIST são infraestrutura e não entram como registros.

Fallback: primeiro `# Título`; ID do padrão `# ID — Título` ou nome do arquivo; primeiro parágrafo corrido, ignorando cabeçalhos, tabelas, listas e cercas de código, até 120 caracteres incluindo `...`; status da linha Status ou `indexed`/`archived`. YAML incompleto ou não suportado não bloqueia index/get: usa campos válidos e defaults. Mutação recusa cabeçalho inválido/duplicado sem descartar informação. Fallback não modifica o documento.

Set preserva corpo, BOM, finais de linha, comentários e campos não alterados. Strings novas são serializadas com aspas. PHP aceita múltiplos `--campo=valor`; a API Python aceita um dicionário; o comando Python altera um campo por chamada. Cada substituição é atômica; os escritores cooperam pelo lock exclusivo memory/.mdd.lock. Falha ao salvar índice restaura o documento. Crash entre as duas substituições requer memory:index/mdd index; não há transação de filesystem entre dois arquivos. Inspecione lock órfão antes de removê-lo; nunca remova lock de outro processo.

API reutilizável: `mdd_client.core.indexer.index(repo, target=None)`, `get(repo, target, field=None)` e `set_metadata(repo, target, updates)`. Não dispare indexação concorrente com compact/init sobre a mesma raiz. Caminhos externos, symlinks e junctions são recusados para mutação.

## Handoffs por evento

| Topologia | Evento e próximo papel |
| --- | --- |
| solo | Executor conclui testes e auto-revisão contra checklist; prepara revisão humana |
| dupla | Executor entrega lote, resumo executivo e comando de continuidade ao Macro-Arquiteto |
| triade | Executor emite completions/<batch-id>-receipt.json e aciona Revisor Independente; Revisor cria memory/human-reviews/rev-XXX.md |

| Autonomia | Transição de papel |
| --- | --- |
| supervisionado | Preparar prompt completo e aguardar input humano explícito antes de acionar próximo papel |
| autonomo_monitorado | Acionar próximo papel autorizado com Live Todo visível e recibo verificável |
| autonomo_headless | Despachar via mecanismo disponível e persistir recibo/evidências; parar diante de falta de autorização ou contrato |

Combine uma linha de cada tabela. Topologia não amplia autonomia; nenhuma combinação autoriza homologação humana fictícia ou produção. Se MCP Hub não estiver disponível, registre a limitação e use recibo em arquivo; não alegue despacho remoto.

Todo prompt contém: projeto, raiz absoluta, REQ, BATCH, topologia, autonomia, escopo, caminhos de evidência, estado técnico, pendências e ação esperada. Para BATCH-074 declare ambos: conn2flow-ai-workspace em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-ai-workspace e conn2flow em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.

## Inbox de homologação

memory/human-reviews/ mantém até dez rev-XXX.md ativos, além de README/index. O Revisor emite resumo executivo, auditoria de segurança/variáveis/regressão, evidências/testes, parecer RECOMMEND-APPROVAL ou RECOMMEND-REVISION e `[ ] Homologado por: _____`. Somente humano identificado assina homologação. Arquive fichas homologadas antigas com original íntegro em archive/original/ e síntese rastreável em archive/compacted/; atualize índices e repare links. Não arquive pendências para cumprir artificialmente o teto.

Stage/commits listam caminhos específicos. Skills são propagadas exclusivamente por scripts/skills/sync-skills.cjs; não sobrescreva skills locais ou traduções declaradas. Não pode memória saudável para encerrar lote.
