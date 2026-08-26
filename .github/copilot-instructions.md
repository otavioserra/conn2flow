# Instruções do Copilot — Spec-Driven Development

- Trate `sdd/README.md` e especificações numeradas como fonte normativa.
- Leia `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch ativo e `sdd/validation/VALIDATION-CHECKLIST.md` antes de editar código.
- Memórias de Engenharia: leia `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` e `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` no início da sessão.

## Skills OBRIGATÓRIAS por Marco de Fluxo

Invoque explicitamente a skill correspondente ANTES de editar código ou fechar lotes:
- **Início de Tarefa**: `start-sdd-slice`, `continue-sdd-batch`, `sdd-workflow`.
- **Durante a Edição**: invoque as Core Skills (`c2f-*`) relevantes para a stack tocada.
- **Fechamento e Validação**: `project-validation`, `review-current-batch`, `sdd-memory-gardening`.
- **Mudança Normativa**: `raise-spec-change`.

## Intake Gate do backlog

- `sdd/backlog/` é incubadora de rascunhos. É proibido implementar itens diretamente dali.
- Um item só se torna executável após promoção humana para `sdd/human-requests/req-XXX.md` e associação a um batch.


## 📋 Protocolo de Transparência & Checklist Vivo (Live Todo List)

- Ao iniciar qualquer requisição ou lote, renderize imediatamente a lista completa de tarefas (`Todo List`) com caixas de seleção `[ ]`.
- A cada término de etapa/comando relevante, atualize e re-exiba a lista marcando `[x]` nas etapas concluídas e destacando a etapa atual (`⏳ [EM ANDAMENTO]`).
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
