---
name: start-sdd-slice
description: Inicia uma demanda em repositório SDD identificando sdd relevantes, batch atual, artefato correto e validação mínima.
agent: sdd-coordinator
argument-hint: 'Descreva a demanda ou passe um .md em sdd/human-requests/. Se passar a pasta, o fluxo usa CURRENT.md, depois README.md, depois o .md mais recente.'
---

Para a demanda abaixo:

1. Se a demanda for um caminho em `sdd/human-requests/`, leia primeiro esse intake como material não normativo. Se a demanda apontar só para a pasta, escolha `CURRENT.md`, depois `README.md`, depois o `.md` mais recente.
2. Leia os artefatos SDD de entrada do projeto.
3. Identifique os sdd numerados relevantes.
4. Classifique a demanda: change request, implementação de batch, review ou validação.
5. Determine o menor conjunto de arquivos a ler depois dos sdd.
6. Declare uma hipótese local falsificável e a menor validação disponível.
7. Se o contexto já for suficiente, comece a execução em vez de apenas planejar.

Demanda:

${input:task:Descreva a tarefa}
