---
description: Faz review findings-first do batch atual em repositório SDD. Use quando a implementação já existe e o foco agora é localizar bug, regressão, spec drift, batch drift e validação ausente.
disable-model-invocation: true
allowed-tools: Read Grep Glob Bash(git diff *) Bash(git status *)
argument-hint: [foco-opcional]
---

# Review do batch atual

Revise o batch atual em modo findings-first.

## Foco padrão

1. bug funcional
2. regressão
3. spec drift
4. batch drift
5. validação ausente

## Formato esperado

1. findings primeiro, do mais severo para o menos severo
2. perguntas ou premissas em seguida
3. resumo apenas por último

## Foco adicional desta rodada

$ARGUMENTS