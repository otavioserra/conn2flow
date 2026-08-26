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

- Ao iniciar qualquer requisição ou lote, renderize imediatamente a lista completa de tarefas (Todo List) com caixas de seleção [ ].
- A cada término de etapa/comando relevante, atualize e re-exiba a lista marcando [x] nas etapas concluídas e destacando a etapa atual (⏳ [EM ANDAMENTO]).
- Nunca execute sequências longas de comandos sem atualizar o status visual para o usuário.

## 🛡️ Espectro de 3 Níveis de Autonomia de IA

1. **Nível 1: SUPERVISIONADO (Padrão Mandatório / Human-in-the-Loop)**:
   - O agente implementa código e executa testes, mas **NÃO realiza commit, push ou deploy automático**.
   - O desenvolvedor revisa e aprova as mudanças no chat/IDE antes da consolidação.

2. **Nível 2: AUTÔNOMO MONITORADO (Live Autopilot / Glass-Box no Chat)**:
   - Ativado quando a requisição contiver `modo: autonomo_monitorado` ou o usuário autorizar expressamente o acompanhamento contínuo na tela.
   - O agente executa a esteira completa com **Live Todo List (`[ ]` ➔ `[x]`) visível e atualizado em tempo real**:
     * Criação de branch/worktree isolada (`feat/req-XXX`).
     * Codificação e compilação de recursos (`c2f resources:sync`).
     * Execução de testes automatizados (`c2f db:test`).
     * **DEPLOY EXCLUSIVAMENTE EM AMBIENTE DE TESTE LOCAL** (`c2f manager:update-all` ou Docker local).
     * ⛔ **REGRA INVIOLÁVEL DE SEGURANÇA: NUNCA REALIZAR DEPLOY AUTOMÁTICO EM AMBIENTE DE PRODUÇÃO OU SERVIDORES REMOTOS.**
     * Commit semântico e push na branch de trabalho.
     * Relatório final com logs de execução e evidências de validação.

3. **Nível 3: AUTÔNOMO HEADLESS (Background Silencioso / Black-Box)**:
   - Ativado quando a requisição contiver `modo: autonomo_headless`.
   - O agente executa toda a esteira em segundo plano isolado via MCP Hub / Git Worktrees, emitindo notificação e relatório consolidado apenas ao término.

## 🔒 Regras Mandatórias de Concorrência Multi-Agente

1. **Proibição Absoluta de `git add -A` e `git commit -a`**:
   - O agente DEVE executar `git add <caminho-1> <caminho-2>` listando estritamente os arquivos tocados no seu lote aprovado, prevenindo que commits arrastem código concorrente ou arquivos de outros agentes.
2. **Reserva e Releitura Atômica de Numeração de `req-XXX.md`**:
   - O agente deve reler o diretório `sdd/human-requests/` imediatamente antes de criar arquivos para evitar colisão e sobrescrita de números de requisição.
