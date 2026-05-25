---
name: review-current-batch
description: Revisa o batch atual de um repositório SDD com foco em spec drift, batch drift, bugs e validação.
agent: sdd-reviewer
argument-hint: 'Opcionalmente cite arquivos, risco suspeito ou um .md em sdd/human-requests/.'
---

Revise a mudança mais recente do batch atual.

Se o contexto adicional apontar para `sdd/human-requests/`, use esse arquivo apenas como briefing não normativo sobre a expectativa humana da rodada.

Contexto adicional:

${input:context:Sem contexto adicional}
