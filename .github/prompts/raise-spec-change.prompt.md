---
name: raise-spec-change
description: Abre ou atualiza uma mudanÃ§a de requisito no fluxo SDD antes de partir para implementaÃ§Ã£o.
agent: sdd-coordinator
argument-hint: 'Descreva a mudanÃ§a de requisito ou passe um .md em sdd/human-requests/.'
---

Para a mudanÃ§a abaixo:

1. Se a mudanÃ§a vier como caminho em `sdd/human-requests/`, leia primeiro esse intake humano. Se vier apenas a pasta, use `CURRENT.md`, depois `README.md`, depois o `.md` mais recente.
2. Identifique quais sdd numerados seriam impactados.
3. Avalie se a mudanÃ§a deve entrar em `sdd/change-requests/`, `sdd/decisions/` e `sdd/implementation/`.
4. Proponha o menor change request coerente com o fluxo atual.
5. NÃ£o implemente cÃ³digo atÃ© a mudanÃ§a normativa ficar explÃ­cita.

MudanÃ§a proposta:

${input:change:Descreva a mudanÃ§a}