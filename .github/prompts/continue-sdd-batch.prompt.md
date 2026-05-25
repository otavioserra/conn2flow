---
name: continue-sdd-batch
description: Retoma trabalho no batch atual de um repositório SDD sem perder o contexto dos sdd e artefatos incrementais.
agent: sdd-coordinator
argument-hint: 'Opcionalmente descreva o que mudou ou passe um .md em sdd/human-requests/.'
---

Retome o trabalho considerando sdd, batch atual, decisions, validation e arquivos alterados manualmente desde a última rodada.

Se a atualização vier como caminho em `sdd/human-requests/`, releia primeiro esse intake humano. Se vier apenas a pasta, use `CURRENT.md`, depois `README.md`, depois o `.md` mais recente.

Atualização:

${input:update:Sem atualização adicional}
