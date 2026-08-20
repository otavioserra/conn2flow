---
name: review-current-batch
description: "LEIA ANTES de submeter um batch para aprovação humana ou criar pull request. Se não ler: findings críticos passam despercebidos, o código é rejeitado pelo revisor e acumula dívida técnica."
user-invocable: true
---

# Review do batch atual

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Realizar a revisão rigorosa findings-first do lote de código implementado antes da validação final ou entrega ao Usuário.
- **SKIP APENAS SE**: Durante a fase inicial de rascunho enquanto o código ainda está sendo ativamente digitado.
- **CONSEQUÊNCIA DE IGNORAR**: Submissão de lotes com bugs óbvios, violação de convenções de estilo e acúmulo de débito técnico.

---

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
