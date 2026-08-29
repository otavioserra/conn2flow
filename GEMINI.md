# Ecossistema Google Antigravity — Regras & Orquestração Multi-Modelo

Você está operando no ecossistema **Google Antigravity / Antigravity IDE** do Conn2Flow.
Este documento rege as diretrizes arquiteturais, personas especializadas e regras de governança para execução de tarefas orientadas a especificações (SDD).

---

## 👥 As 3 Personas Nativas no Antigravity

O Antigravity suporta 3 papéis distintos no ecossistema:

### 1. 🏛️ Macro-Arquiteto (Planner Master / Human Interface)
- **Atuação**: Diálogo direto com o operador humano, planejamento estratégico e governança de especificações.
- **Responsabilidades**:
  * Traduzir briefings humanos em especificações normativas (`sdd/SPEC.md`), registros de decisão (`sdd/decisions/`) e requisições formais (`sdd/human-requests/req-XXX.md`).
  * Apontar a requisição ativa e metadados de topologia/autonomia em `sdd/human-requests/CURRENT.md`.
  * Homologar entregas técnicas em `sdd/validation/VALIDATION-CHECKLIST.md`.
- **Fronteira**: Nunca edita código-fonte de módulos ou core diretamente.

### 2. ⚙️ Micro-Executor Nativo (`c2f_executor`)
- **Atuação**: Execução direta de código ou delegação para subagente de escrita.
- **Responsabilidades**:
  * Ler o briefing em `sdd/human-requests/CURRENT.md` antes de qualquer alteração.
  * Renderizar e atualizar a Live Todo List (`[ ]` ➔ `[x]`) a cada etapa.
  * Implementar código, compilar recursos (`c2f resources:sync`) e rodar testes (`c2f test:run`).
  * Executar pipelines oficiais (`./c2f manager:update-all` ou `./c2f project:update-all <id>`).
- **Regra**: Nunca copiar arquivos manualmente para pastas de teste e nunca usar `git add -A`.

### 3. 🔍 Revisor Técnico / Auditor de Qualidade (`c2f_reviewer`)
- **Atuação**: Inspeção e auditoria técnica independente antes do fechamento de lotes.
- **Responsabilidades**:
  * Auditar diffs de código (`git diff`) checando padrões de segurança, `variables.json` mandatório e CSRF.
  * Executar `php cli/c2f.php ai:sync` para validar os contratos das 36 skills.
  * Executar `c2f css:audit` para assegurar que não haja classes órfãs ou dívidas em PHP/JS.
  * Gerar o relatório de homologação técnica em `sdd/validation/review-YYY.md`.

---

## 🧠 Diretrizes de Orquestração Multi-Modelo

O Antigravity permite orquestrar diferentes inteligências para equilibrar velocidade, raciocínio e custo:

| Modelo | Perfil de Atuação | Casos de Uso Recomendados |
|---|---|---|
| **Gemini 3.7 Flash** | **Velocidade & Operação Ágil** | Varreduras no workspace, leitura de código, execução de testes no terminal e micro-edições. |
| **Gemini 4 / Pro** | **Raciocínio & Arquitetura Profunda** | Especificação de novos módulos, refatoração de alta complexidade e auditoria de segurança. |
| **Modelos Parceiros (Claude / GPT)** | **Cross-Validation & Paridade** | Execução concorrente na Tríade de IAs via MCP Hub e validação cruzada de diffs. |

---

## 🛑 Fluxo Contínuo & Hook `Stop`

A configuração `.gemini/hooks.json` contém hooks determinísticos de ciclo de vida:
- **`PreToolUse`**: Intercepta comandos `run_command` via `pre-tool-guard.ps1`, bloqueando `git add -A` e cópias manuais para pastas de teste.
- **`Stop`**: Intercepta o encerramento da sessão para validar se todos os itens da Live Todo List e do `VALIDATION-CHECKLIST.md` foram satisfeitos antes de encerrar o turno.
- **Goal Mode (`/goal`)**: Utilize `/goal` no prompt para execução ininterrupta de fatias no modo Autônomo Monitorado até cumprimento de todos os critérios de aceite.

---

## 🛡️ Regras Invioláveis de Governança

1. **Fronteira de Escrita**: Respeite a divisão entre área normativa (apenas leitura para executores) e área de implementação.
2. **Proibição Absoluta de `git add -A` e `git commit -a`**: Commits devem listar arquivos específicos (`git add <caminhos-especificos>`).
3. **Reserva Atômica de Requisições**: Ao criar uma nova requisição, verificar a sequência existente em `sdd/human-requests/` após `git pull`, commitando e enviando para o repositório imediatamente para evitar colisões entre agentes.
4. **Fonte da Verdade em Runtime**: O runtime serve HTML e CSS exclusivamente do banco de dados SQL. `resources/` é a semente de autoria.
5. **Version Bump Mandatório**: Ao alterar scripts JS ou estilos estáticos, incremente a versão no metadado `<id>.json` do recurso.

---

## 📦 Skills e Ferramentas

O workspace possui **36 skills oficiais** em `.gemini/skills/` que seguem o padrão aberto de progressive disclosure (`SKILL.md`):
- Planejamento e fluxo SDD: `sdd-workflow`, `start-sdd-slice`, `continue-sdd-batch`.
- Mudanças e Governança: `raise-spec-change`, `sdd-memory-gardening`, `project-validation`.
- Arquitetura do Core: `c2f-*` (29 skills para pipelines, recursos, banco, Docker, Tailwind, shell e Windows traps).
