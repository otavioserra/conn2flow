# Agentes SDD — Configuração Multi-Agente OpenAI Codex & Antigravity

## 👥 Papéis de Agente Duplo

### 🏛️ Arquiteto (Macro-Orquestrador)
- **Responsabilidade**: Traduzir necessidades humanas e briefings em especificações normativas (`sdd/SPEC.md`), registros de decisão (`sdd/decisions/`) e requisições formais (`sdd/human-requests/req-XXX.md`).
- **Ferramentas**: Antigravity / Gemini / GPT no modo planejamento.
- **Regra**: Nunca realiza commits ou push de código diretamente no core ou módulos.

### ⚙️ Executor (Micro-Operador)
- **Responsabilidade**: Implementar código, compilar recursos, rodar testes e registrar evidências no lote em `sdd/implementation/batch-YYY.md` e `sdd/validation/VALIDATION-CHECKLIST.md`.
- **Ferramentas**: OpenAI Codex / GPT no VS Code / Claude Code.
- **Regra**: Lê o briefing em `sdd/human-requests/CURRENT.md` antes de iniciar qualquer alteração e atualiza a Live Todo List (`[ ]` ➔ `[x]`).

### 👨‍💻 Humano-no-Loop (Você)
- **Responsabilidade**: Direcionar o Arquiteto e revisar diffs de código antes da consolidação final.

---

## 📦 Configuração de Skills (36 Skills Oficiais)

Todas as **36 skills** do framework estão disponíveis em `.codex/skills/` (e `.claude/skills/`, `.gemini/skills/`, `.github/skills/`, `.cursor/skills/`) e seguem o padrão aberto de progressive disclosure (`SKILL.md`):

### 1. Skills Core do Framework (29 Skills):
- `c2f-agent-visual-inspection`
- `c2f-database-operations`
- `c2f-dev-scripts`
- `c2f-docker-environment`
- `c2f-documentation-governance`
- `c2f-environment-configuration`
- `c2f-file-system-operations`
- `c2f-gestor-functions`
- `c2f-global-variables`
- `c2f-hooks-system`
- `c2f-html-css-pages-and-components`
- `c2f-interface-v2-architecture`
- `c2f-javascript-ajax`
- `c2f-layout-engine-architecture`
- `c2f-modelo-templates`
- `c2f-module-crud-scaffolding`
- `c2f-multilingual-system`
- `c2f-plugin-architecture`
- `c2f-preview-modals-system`
- `c2f-project-pipeline-and-tasks`
- `c2f-projects-system`
- `c2f-resources-system`
- `c2f-shell-and-windows-traps`
- `c2f-system-tasks`
- `c2f-tailwind-css-architecture`
- `c2f-variables-system`
- `c2f-widgets-system`
- `c2f-quill-editor`
- `c2f-assets-management`

### 2. Skills de Governança e Workflow SDD (7 Skills):
- `sdd-workflow`
- `start-sdd-slice`
- `continue-sdd-batch`
- `raise-spec-change`
- `review-current-batch`
- `project-validation`
- `sdd-memory-gardening`

---

## 🛡️ Regras Invioláveis de Governança

1. **Proibição Absoluta de `git add -A` e `git add .`**: Commits devem SEMPRE listar arquivos específicos (`git add <caminhos-especificos>`).
2. **Proibição de Sincronização por Cópia Manual**: NUNCA copie arquivos manualmente (`cp`, `copy`, `Copy-Item`) para pastas de teste/espelho (`dev-environment/data/sites/`). Use sempre `./c2f manager:update-all` (sistema) ou `./c2f project:update-all <id>` (projeto).
3. **Execução Sequencial Exclusiva**: Comandos de compilação em lote (`manager:update-all`, `project:update-all`, `css:rebuild`, `resources:sync`) devem executar um por vez em foreground com logs desbufferizados.
4. **Fonte da Verdade em Runtime**: O runtime serve HTML e CSS exclusivamente do banco de dados SQL. `resources/` é a semente de autoria.
5. **Version Bump Mandatório**: Ao alterar scripts JS ou estilos estáticos, incremente a versão no metadado `<id>.json` do recurso.
6. **Goal Mode (`/goal`)**: Utilize `/goal` no prompt para execução ininterrupta de fatias complexas no modo Autônomo Monitorado até cumprimento de todos os critérios de aceite do `VALIDATION-CHECKLIST.md`.
