---
name: raise-spec-change
description: Abre ou atualiza uma mudança de requisito no fluxo SDD antes de partir para implementação.
agent: sdd-coordinator
argument-hint: 'Descreva a mudança de requisito ou passe um .md em sdd/human-requests/.'
---

Para a mudança abaixo:

1. Se a mudança vier como caminho em `sdd/human-requests/`, leia primeiro esse intake humano. Se vier apenas a pasta, use `CURRENT.md`, depois `README.md`, depois o `.md` mais recente.
2. Identifique quais sdd numerados seriam impactados.
3. Avalie se a mudança deve entrar em `sdd/change-requests/`, `sdd/decisions/` e `sdd/implementation/`.
4. Proponha o menor change request coerente com o fluxo atual.
5. Não implemente código até a mudança normativa ficar explícita.

Mudança proposta:

${input:change:Descreva a mudança}
