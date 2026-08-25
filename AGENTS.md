# Agentes SDD — Configuração Multi-Agente OpenAI Codex

## Papéis de Agente Duplo

### Arquiteto (Macro-Orquestrador)
- **Responsabilidade**: Traduzir necessidades humanas em requisitos técnicos padronizados no `sdd/`.
- **Ferramentas**: Gemini / Antigravity / GPT no modo planejamento.
- **Regra**: Nunca realiza commits ou push de código diretamente.

### Executor (Micro-Operador)
- **Responsabilidade**: Implementar o código, rodar testes e registrar evidências de validação.
- **Ferramentas**: OpenAI Codex / GPT no VS Code.
- **Regra**: Lê o briefing em `sdd/human-requests/CURRENT.md` antes de iniciar qualquer alteração.

### Humano-no-Loop (Você)
- **Responsabilidade**: Direcionar o Arquiteto e revisar diffs de código antes de consolidar.

## Configuração de Skills

Todas as 33 skills do framework estão disponíveis em `.codex/skills/` e devem ser consultadas conforme o marco de fluxo descrito em `CODEX.md`.

## Convenções de Nomenclatura

- Skills Core do Framework: `c2f-*` (26 skills).
- Skills de Workflow SDD: `sdd-*`, `start-*`, `continue-*`, `raise-*`, `review-*`, `project-*` (7 skills).
