---
name: sdd-workflow
description: "LEIA ANTES de criar ou alterar qualquer arquivo na pasta sdd/ (process, implementation, validation, decisions). Se não ler: o fluxo de Agente Duplo é quebrado e os artefatos de controle perdem a governança."
user-invocable: false
---

# SDD workflow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Iniciar qualquer tarefa do framework SDD, interpretar requisições humanas ou classificar artefatos nas pastas de controle.
- **SKIP APENAS SE**: Tarefas completamente alheias ao ciclo de governança SDD (ex: git commits diretos de infraestrutura).
- **CONSEQUÊNCIA DE IGNORAR**: Desalinhamento entre Arquiteto e Executor, criação de arquivos em locais errados e colapso da metodologia de Agente Duplo.

---

Use esta skill quando o projeto for guiado por sdd versionados.

## Leitura mínima inicial

Comece por `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.

Se a tarefa apontar para `sdd/human-requests/*.md` ou para a pasta `sdd/human-requests/`, leia primeiro esse intake humano. Quando vier apenas a pasta, use a seguinte ordem determinística:

1. `CURRENT.md`
2. `README.md`
3. o arquivo `.md` mais recente

## Classificação da demanda

1. Mudança de requisito ou contrato:
   - registre em `sdd/change-requests/`
   - avalie impacto nos sdd numerados, decisions, batches e validation
2. Feedback de review sem mudança normativa:
   - registre em `sdd/reviews/`
   - mantenha os sdd numerados estáveis
3. Implementação incremental:
   - confira o batch atual em `sdd/implementation/`
   - implemente o menor slice aprovado
   - valide e atualize `sdd/validation/` quando necessário
4. Validação ou spec drift check:
   - comece pela menor checagem automatizada
   - registre evidência e pendências nos artefatos certos

## Regras de ouro

- Os sdd numerados são a fonte normativa.
- `sdd/human-requests/` nunca é fonte normativa; ele só alimenta change requests, reviews, batches, decisions ou validação.
- Não reescreva os sdd numerados para comentários pequenos de review.
- Não abra o próximo batch antes de o atual estar estável e revisável.


## 📋 Protocolo de Transparência & Checklist Vivo (Live Todo List)

- Ao iniciar qualquer requisição ou lote, renderize imediatamente a lista completa de tarefas (`Todo List`) com caixas de seleção `[ ]`.
- A cada término de etapa/comando relevante, atualize e re-exiba a lista marcando `[x]` nas etapas concluídas e destacando a etapa atual (`⏳ [EM ANDAMENTO]`).
- Nunca execute sequências longas de comandos sem atualizar o status visual para o usuário.

## 🛡️ Modos de Autonomia de IA & Trava de Deploy

- **Modo SUPERVISIONADO (Padrão Mandatório)**:
  * O agente implementa código e roda testes, mas **NÃO realiza commit ou deploy automático**.
  * O desenvolvedor revisa e aprova as mudanças no chat/IDE.

- **Modo AUTÔNOMO (Apenas quando explicitado na requisição / usuário)**:
  * Permitido quando a requisição contiver `modo: autonomo` ou o usuário autorizar expressamente.
  * O agente pode: criar branch/worktree (`feat/req-XXX`), codificar, compilar (`c2f resources:sync`), rodar testes (`c2f db:test`), commitar e executar **DEPLOY EXCLUSIVAMENTE EM AMBIENTE DE TESTE LOCAL** (`c2f manager:update-all` ou Docker local).
  * ⛔ **REGRA INVIOLÁVEL DE SEGURANÇA: NUNCA REALIZAR DEPLOY AUTOMÁTICO NO AMBIENTE DE PRODUÇÃO OU SERVIDORES REMOTOS.**
