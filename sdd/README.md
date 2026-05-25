# Conn2Flow Spec-Driven Development

Este diretório adiciona uma camada repo-wide de Spec-Driven Development ao `conn2flow`.

O objetivo desta camada não é reescrever o repositório nem substituir a arquitetura atual do sistema. O objetivo é controlar como novas mudanças entram no repositório, como são classificadas, como viram batches pequenos e como são validadas sem descaracterizar o legado funcional.

## Ordem normativa

1. `sdd/README.md`
2. sdd numerados em `sdd/`, incluindo `sdd/00-baseline-architecture.md`
3. `sdd/process/00-START-HERE.md`
4. `sdd/process/01-WORKFLOW.md`
5. `sdd/implementation/BATCH-INDEX.md` e o batch ativo
6. `sdd/validation/VALIDATION-CHECKLIST.md`
7. `sdd/decisions/DECISION-LOG.md`

## Regras de ouro

- O legado documentado em `sdd/00-baseline-architecture.md` é considerado funcional e aprovado.
- Nenhuma mudança deve descartar, reescrever amplamente ou "modernizar" o legado sem mudança normativa aprovada.
- `sdd/human-requests/` é intake humano não normativo e serve como o log histórico da conversa entre o Usuário (Engenheiro Chefe) e o Arquiteto. Os executores de desenvolvimento não modificam esta pasta.
- sdd numerados só devem mudar quando requisito, contrato, critério de aceite ou decisão estrutural realmente mudar.
- Feedback de review, batches pequenos, validação e registro de decisões devem ir para os artefatos próprios, sem inflar os sdd numerados.
- Cada rodada deve perseguir o menor batch plausível e a menor validação capaz de falsificar o slice atual.

## Suporte duplo de IA

Esta estrutura foi instalada para funcionar tanto com Claude Code quanto com GitHub Copilot.

- Claude Code usa `CLAUDE.md` e `.claude/`.
- GitHub Copilot usa `.github/copilot-instructions.md`, `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/`.
- Ambos devem convergir para os mesmos artefatos em `sdd/`.

## Comandos e pontos de entrada

- Claude Code: `/start-sdd-slice`, `/continue-sdd-batch`, `/review-current-batch`, `/raise-spec-change`
- GitHub Copilot: prompts equivalentes em `.github/prompts/`

## Ordem mínima de leitura para qualquer nova demanda

1. Se a demanda vier de `sdd/human-requests/`, leia primeiro o intake humano.
2. Leia este arquivo.
3. Leia `sdd/00-baseline-architecture.md`.
4. Leia `sdd/process/00-START-HERE.md`.
5. Leia `sdd/process/01-WORKFLOW.md`.
6. Leia `sdd/implementation/BATCH-INDEX.md`.
7. Leia `sdd/validation/VALIDATION-CHECKLIST.md`.
8. Leia `sdd/decisions/DECISION-LOG.md`.

## Estado inicial desta implantação

- `BATCH-000` fecha a implantação do SDD repo-wide no `conn2flow`.
- O próximo intake esperado é o `req-001.md`, focado em tarefas e scripts de sincronização de projetos.
- Enquanto não houver intake novo classificado, `sdd/human-requests/CURRENT.md` é o apontador oficial do estado de entrada.
