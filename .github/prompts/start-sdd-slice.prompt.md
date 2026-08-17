---
name: start-sdd-slice
description: Inicia uma demanda em repositÃ³rio SDD identificando sdd relevantes, batch atual, artefato correto e validaÃ§Ã£o mÃ­nima.
agent: sdd-coordinator
argument-hint: 'Descreva a demanda ou passe um .md em sdd/human-requests/. Se passar a pasta, o fluxo usa CURRENT.md, depois README.md, depois o .md mais recente.'
---

Para a demanda abaixo:

1. Se a demanda for um caminho em `sdd/human-requests/`, leia primeiro esse intake como material nÃ£o normativo. Se a demanda apontar sÃ³ para a pasta, escolha `CURRENT.md`, depois `README.md`, depois o `.md` mais recente.
2. Leia os artefatos SDD de entrada do projeto.
3. Identifique os sdd numerados relevantes.
4. Classifique a demanda: change request, implementaÃ§Ã£o de batch, review ou validaÃ§Ã£o.
5. Determine o menor conjunto de arquivos a ler depois dos sdd.
6. Declare uma hipÃ³tese local falsificÃ¡vel e a menor validaÃ§Ã£o disponÃ­vel.
7. Se o contexto jÃ¡ for suficiente, comece a execuÃ§Ã£o em vez de apenas planejar.

Demanda:

${input:task:Descreva a tarefa}