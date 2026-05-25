# Conn2Flow Spec-Driven Development

Este diretÃ³rio adiciona uma camada repo-wide de Spec-Driven Development ao `conn2flow`.

O objetivo desta camada nÃ£o Ã© reescrever o repositÃ³rio nem substituir a arquitetura atual do sistema. O objetivo Ã© controlar como novas mudanÃ§as entram no repositÃ³rio, como sÃ£o classificadas, como viram batches pequenos e como sÃ£o validadas sem descaracterizar o legado funcional.

## Ordem normativa

1. `sdd/README.md`
2. sdd numerados em `sdd/`, incluindo `sdd/00-baseline-architecture.md`
3. `sdd/process/00-START-HERE.md`
4. `sdd/process/01-WORKFLOW.md`
5. `sdd/implementation/BATCH-INDEX.md` e o batch ativo
6. `sdd/validation/VALIDATION-CHECKLIST.md`
7. `sdd/decisions/DECISION-LOG.md`

## Regras de ouro

- O legado documentado em `sdd/00-baseline-architecture.md` Ã© considerado funcional e aprovado.
- Nenhuma mudanÃ§a deve descartar, reescrever amplamente ou "modernizar" o legado sem mudanÃ§a normativa aprovada.
- `sdd/human-requests/` Ã© intake humano nÃ£o normativo e serve como o log histÃ³rico da conversa entre o UsuÃ¡rio (Engenheiro Chefe) e o Arquiteto. Os executores de desenvolvimento nÃ£o modificam esta pasta.
- sdd numerados sÃ³ devem mudar quando requisito, contrato, critÃ©rio de aceite ou decisÃ£o estrutural realmente mudar.
- Feedback de review, batches pequenos, validaÃ§Ã£o e registro de decisÃµes devem ir para os artefatos prÃ³prios, sem inflar os sdd numerados.
- Cada rodada deve perseguir o menor batch plausÃ­vel e a menor validaÃ§Ã£o capaz de falsificar o slice atual.

## Suporte duplo de IA

Esta estrutura foi instalada para funcionar tanto com Claude Code quanto com GitHub Copilot.

- Claude Code usa `CLAUDE.md` e `.claude/`.
- GitHub Copilot usa `.github/copilot-instructions.md`, `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/`.
- Ambos devem convergir para os mesmos artefatos em `sdd/`.

## Comandos e pontos de entrada

- Claude Code: `/start-sdd-slice`, `/continue-sdd-batch`, `/review-current-batch`, `/raise-spec-change`
- GitHub Copilot: prompts equivalentes em `.github/prompts/`

## Ordem mÃ­nima de leitura para qualquer nova demanda

1. Se a demanda vier de `sdd/human-requests/`, leia primeiro o intake humano.
2. Leia este arquivo.
3. Leia `sdd/00-baseline-architecture.md`.
4. Leia `sdd/process/00-START-HERE.md`.
5. Leia `sdd/process/01-WORKFLOW.md`.
6. Leia `sdd/implementation/BATCH-INDEX.md`.
7. Leia `sdd/validation/VALIDATION-CHECKLIST.md`.
8. Leia `sdd/decisions/DECISION-LOG.md`.

## Estado inicial desta implantaÃ§Ã£o

- `BATCH-000` fecha a implantaÃ§Ã£o do SDD repo-wide no `conn2flow`.
- O prÃ³ximo intake esperado Ã© o `req-001.md`, focado em tarefas e scripts de sincronizaÃ§Ã£o de projetos.
- Enquanto nÃ£o houver intake novo classificado, `sdd/human-requests/CURRENT.md` Ã© o apontador oficial do estado de entrada.
