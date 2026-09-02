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

> 🚫 **Bloqueio de Memory Gardening**: não invoque nem pode a memória de execução apenas por encerrar uma sessão ou concluir um batch. Abaixo de 50 KB e 200 linhas o arquivo está saudável e não deve ser reescrito; em 50 KB / 200 linhas há somente alerta preventivo, e a poda é obrigatória apenas em 75 KB / 300 linhas.

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
   - **Goal Mode (`/goal`) para Execução Contínua**:
     * Em tarefas complexas ou fatias que exigem múltiplos ciclos de teste e correção, utilize o comando `/goal` no prompt do Claude / IDE.
     * Exemplo de instrução: `"/goal Execute o lote BATCH-XXX até que todos os testes do VALIDATION-CHECKLIST.md passem e o relatório esteja preenchido."`
     * O agente permanece em loop autônomo ininterrupto até satisfazer deterministamente todas as condições de encerramento do checklist técnico, impedindo paradas prematuras.

3. **Nível 3: AUTÔNOMO HEADLESS (Background Silencioso / Black-Box)**:
   - Ativado quando a requisição contiver `modo: autonomo_headless`.
   - O agente executa toda a esteira em segundo plano isolado via MCP Hub / Git Worktrees, emitindo notificação e relatório consolidado apenas ao término.

## 🔒 Regras Mandatórias de Concorrência & Reserva Atômica de Requisições (`req-XXX.md`)

1. **Proibição Absoluta de `git add -A` e `git commit -a`**:
   - O agente DEVE executar `git add <caminho-1> <caminho-2>` listando estritamente os arquivos tocados no seu lote aprovado, prevenindo que commits arrastem código concorrente ou arquivos de outros agentes.
2. **Protocolo de Reserva Atômica para Criação de `req-XXX.md`**:
   - Qualquer agente (Arquiteto ou Executor) está autorizado a criar novos arquivos `req-XXX.md` quando instruído pelo usuário no chat ou ao levantar uma demanda técnica essencial, seguindo estritamente:
     1. Executar `git pull origin <branch>` para obter o estado mais recente.
     2. Reler atomicamente o diretório `sdd/human-requests/` para identificar o próximo número sequencial vago.
     3. Criar o arquivo `req-XXX.md`, atualizar `sdd/human-requests/CURRENT.md` e commitar/pushar imediatamente:
        ```bash
        git add sdd/human-requests/req-XXX.md sdd/human-requests/CURRENT.md
        git commit -m "docs(sdd): reserve REQ-XXX for <titulo>"
        git push origin <branch>
        ```
3. **Identificação Obrigatória de Repositório nos Handoffs**:
   - Todo handoff humano-agente ou inter-agentes deve explicitar no topo da mensagem o identificador e o caminho absoluto da raiz do repositório alvo:
     * **Projeto**: `<nome-do-projeto>`
     * **Caminho Raiz**: `<caminho-absoluto-da-raiz>`
     * **Requisição**: `REQ-XXX` | **Batch**: `BATCH-YYY`
   - Previne que agentes executores ou revisores operem no repositório incorreto em ambientes com múltiplos workspaces abertos.

## 🧠 Camadas Canônicas de Memória

1. **Memória do Repositório (`sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` — Git Compartilhado)**:
   - Fatos técnicos objetivos do software: bugs resolvidos no core, hacks temporários de build/banco, particularidades de compilação CSS/Tailwind, comandos CLI descobertos e lições aprendidas. Visível a todos os agentes e desenvolvedores.
2. **Memória Privada da Ferramenta de IA (Local)**:
   - Preferências subjetivas de interação do operador (estilo de resposta, atalhos de prompt, idioma preferido).

## ⚖️ Princípio da Autoridade do Código e da SPEC sobre Memórias

- Toda anotação de restrição técnica em memória deve carregar a data de registro (`YYYY-MM-DD`).
- O código-fonte real, as configurações vigentes (`settings.json`, `.env`), os schemas e os arquivos normativos (`sdd/SPEC.md`, `sdd/0X-*.md`) possuem **autoridade absoluta** sobre anotações de memórias passadas. Se uma restrição mudar no projeto, a anotação antiga em memória deve ser invalidada e atualizada.
