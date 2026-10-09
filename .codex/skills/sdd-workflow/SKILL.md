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

## Regra dos 10 Ativos na Raiz e Integridade de Links

- A raiz de `sdd/human-requests/` mantém no máximo **10 requisições** soltas (além de `CURRENT.md` e `README.md`); a raiz de `sdd/implementation/` mantém no máximo **10 relatórios de lote** (além de `BATCH-INDEX.md`). O excedente mais antigo vive em `archive/`.
- Arquivar sem reescrever links é proibido: todo link de markdown em `BATCH-INDEX.md`, `VALIDATION-CHECKLIST.md`, `DECISION-LOG.md` e `CURRENT.md` deve continuar resolvendo após a movimentação.
- Use `php cli/c2f.php ai:archive-sdd [--repo=PATH] [--keep=10] [--repair-links] [--dry-run]` do Core: ele move os excedentes, reescreve os links relativos e `file:///` e falha enquanto restar link órfão sob `sdd/`.
- Não mova esses arquivos com `mv`/`Move-Item` manualmente.


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

1. **Memória do Repositório (`sdd/04-memory-engineering-execution.md` — Git Compartilhado)**:
   - Fatos técnicos objetivos do software: bugs resolvidos no core, hacks temporários de build/banco, particularidades de compilação CSS/Tailwind, comandos CLI descobertos e lições aprendidas. Visível a todos os agentes e desenvolvedores.
2. **Memória Privada da Ferramenta de IA (Local)**:
   - Preferências subjetivas de interação do operador (estilo de resposta, atalhos de prompt, idioma preferido).

## ⚖️ Princípio da Autoridade do Código e da SPEC sobre Memórias

- Toda anotação de restrição técnica em memória deve carregar a data de registro (`YYYY-MM-DD`).
- O código-fonte real, as configurações vigentes (`settings.json`, `.env`), os schemas e os arquivos normativos (`sdd/SPEC.md`, `sdd/0X-*.md`) possuem **autoridade absoluta** sobre anotações de memórias passadas. Se uma restrição mudar no projeto, a anotação antiga em memória deve ser invalidada e atualizada.

---

## 🧯 Árvore compartilhada: o que conferir antes de cada commit

Outro agente pode trocar a branch da árvore e deixar arquivos em stage sem aviso. Antes de **todo** commit numa árvore que não é só sua:

```bash
git branch --show-current          # é a branch em que você pretende commitar?
git diff --cached --name-only      # há stage que não é seu?
```

Se a branch não for a esperada, ou houver stage alheio, **não commite na árvore**. Monte o commit por índice separado, sem tocar nos arquivos nem na branch:

```bash
export GIT_INDEX_FILE="$TEMP/idx-meu-lote"
git read-tree origin/main
git add -- <caminho-1> <caminho-2>
commit=$(git commit-tree "$(git write-tree)" -p origin/main -F mensagem.txt)
unset GIT_INDEX_FILE
git push origin "$commit:refs/heads/main"
```

Arquivo compartilhado (`pages.json`, índices do SDD) entra nesse commit como "versão da `main` mais as minhas entradas", nunca como a cópia da árvore, que carrega o trabalho do outro.

### Proibido em árvore compartilhada

- `git checkout -- <arquivo>` e `git restore <arquivo>`: apagam alteração sem commit de qualquer lote, não só a sua edição. Para desfazer a própria mudança, faça a edição inversa.
- `git reset` que mova a branch, `git stash` e `git clean`.
- `git add -N` e qualquer `git add` sem lista de caminhos.

### Dois agentes no mesmo arquivo-fonte

Quando dois lotes precisam escrever no mesmo arquivo de conteúdo, separe por arquivo (um canal, uma pasta) e deixe o gerador juntar. Quando não der, a ferramenta de edição relê o arquivo no instante de gravar e troca só os blocos que são seus.

### Deixe um aviso onde o outro vai ler

Coordenação entre agentes é escrita na requisição do outro lote (um bloco "Coordenação", com data e autor), não só dita no chat.

---

## 🧾 Antes de afirmar que algo é pré-existente

Falha de teste "que já existia" precisa de prova: rode o mesmo teste sem a sua mudança, ou aponte o lote em que a falha foi registrada. Sem isso, ela é sua até prova em contrário.

## MDD, links externos e reserva via git ls-remote (BL-028)

Na matriz conn2flow-ai-workspace, memory/ substitui sdd/; leia 00-baseline-architecture.md, 01-general-memory.md e 02-policy.md e navegue pelos index.md. Os nomes sdd-* das skills e os exemplos de satélites permanecem compatíveis. Não execute ai:archive-sdd sobre memory/ sem suporte explícito no CLI. A ferramenta histórica só arquiva requisições e lotes: o teto de dez decisões/validações exige verificação própria, não se presume pela saída do comando.

CURRENT.md pode apontar para requisição externa: resolva o link contra a origem, identifique repositório/raiz e leia a aprovação lá. Não crie cópia fictícia local nem execute no repo incorreto. Reparadores só reescrevem caminhos internos; links que saem do repositório são classificados e preservados. Se ferramenta legada rejeitar o link externo, registre a incompatibilidade e não remova o ponteiro só para conseguir PASS.

Antes de reservar número novo, consulte refs com git ls-remote --heads origin para todas as branches envolvidas, fetch das refs relevantes e confira a sequência incluindo arquivos arquivados. Código de saída de grep encadeado não prova reserva nem ausência remota. Crie o intake com caminhos explícitos, faça o push autorizado e confira novamente a ref exata via git ls-remote; em rejeição ou movimento concorrente, releia a sequência e resolva a colisão sem force push. ls-remote é verificação, não lock atômico por si só; o push condicionado à ref anterior e a conferência da reserva são a garantia. Nesta entrega REQ-067 já está reservada: não reserve novo número.
