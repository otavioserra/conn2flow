# Conn2Flow Spec-Driven Development

Este diretório adiciona uma camada repo-wide de Spec-Driven Development ao `conn2flow`.

O objetivo desta camada não é reescrever o repositório nem substituir a arquitetura atual do sistema. O objetivo é controlar como novas mudanças entram no repositório, como são classificadas, como viram batches pequenos e como são validadas sem descaracterizar o legado funcional.

## Ordem normativa

1. `memory/README.md`
2. sdd numerados em `memory/`, incluindo `memory/00-baseline-architecture.md`
3. `memory/process/00-START-HERE.md`
4. `memory/process/01-WORKFLOW.md`
5. `memory/implementation/BATCH-INDEX.md` e o batch ativo
6. `memory/validation/VALIDATION-CHECKLIST.md`
7. `memory/decisions/DECISION-LOG.md`

## Regras de ouro

- O legado documentado em `memory/00-baseline-architecture.md` é considerado funcional e aprovado.
- Nenhuma mudança deve descartar, reescrever amplamente ou "modernizar" o legado sem mudança normativa aprovada.
- `memory/human-requests/` é intake humano não normativo e serve como o log histórico da conversa entre o Usuário (Engenheiro Chefe) e o Arquiteto. Os executores de desenvolvimento não modificam esta pasta.
- sdd numerados só devem mudar quando requisito, contrato, critério de aceite ou decisão estrutural realmente mudar.
- Feedback de review, batches pequenos, validação e registro de decisões devem ir para os artefatos próprios, sem inflar os sdd numerados.
- Cada rodada deve perseguir o menor batch plausível e a menor validação capaz de falsificar o slice atual.

## Suporte duplo de IA

Esta estrutura foi instalada para funcionar tanto com Claude Code quanto com GitHub Copilot.

- Claude Code usa `CLAUDE.md` e `.claude/`.
- GitHub Copilot usa `.github/copilot-instructions.md`, `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/`.
- Ambos devem convergir para os mesmos artefatos em `memory/`.

## Comandos e pontos de entrada

- Claude Code: `/start-sdd-slice`, `/continue-sdd-batch`, `/review-current-batch`, `/raise-spec-change`
- GitHub Copilot: prompts equivalentes em `.github/prompts/`

## Ordem mínima de leitura para qualquer nova demanda

1. Se a demanda vier de `memory/human-requests/`, leia primeiro o intake humano.
2. Leia este arquivo.
3. Leia `memory/00-baseline-architecture.md`.
4. Leia `memory/process/00-START-HERE.md`.
5. Leia `memory/process/01-WORKFLOW.md`.
6. Leia `memory/implementation/BATCH-INDEX.md`.
7. Leia `memory/validation/VALIDATION-CHECKLIST.md`.
8. Leia `memory/decisions/DECISION-LOG.md`.

## Estado inicial desta implantação

- `BATCH-000` fecha a implantação do SDD repo-wide no `conn2flow`.
- O próximo intake esperado é o `req-001.md`, focado em tarefas e scripts de sincronização de projetos.
- Enquanto não houver intake novo classificado, `memory/human-requests/CURRENT.md` é o apontador oficial do estado de entrada.
