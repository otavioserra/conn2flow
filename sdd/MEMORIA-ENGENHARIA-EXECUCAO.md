# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente. Regras consolidadas vivem nas skills e são carregadas sob demanda.
> **Política**: preservar 3 a 5 tarefas recentes, mirar ~3 KB a 4.5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-database-testing`: SQLite em memória ou MySQL isolado `conn2flow_test`.
- `c2f-variables-system`: proibição estrita de strings/textos literais hardcoded em PHP/HTML/JS.
- `c2f-module-crud-scaffolding`: scaffolding canônico baseado em `gestor/modulos/modulos-grupos/`.

## Tarefas recentes

### 2026-08-20 — REQ-013 / BATCH-016: CLI Universal c2f & Live Todo List
- **Catálogo de 34 Comandos**: mapeamento 100% de `tasks.json` em classes OOP sob `cli/src/Commands/`.
- **Wrappers Multiplataforma**: `./c2f` (Bash), `c2f.bat`/`c2f.cmd` (CMD) e `c2f.ps1` (PowerShell).
- **Protocolo de Live Todo List**: acompanhamento contínuo via checklist visual nos kits de IA.

### 2026-08-18 — BATCH-123: HTML é recurso, classe é variável
- **Todo HTML pertence a `resources/`**: `$html .= '<div class="…">'` no PHP não passa pelo pipeline.
- **Classe utilitária é VARIÁVEL do sistema, não constante PHP**: Variável é dado de banco versionado.
- **Extractor do Tailwind**: declarar `<modulo>.json` em `tailwind_sources`.

### 2026-08-18 — BATCH-122: degradação graciosa de schema
- **Gate de schema que falha FECHADO**: `gestor_schema_tabela_existe()` / `gestor_schema_campo_existe()`.
- **Desacoplamento puro**: gerador puro de tokens continua testável isoladamente.

## Pendências e Histórico

- Detalhes anteriores ao BATCH-122 permanecem recuperáveis em `sdd/validation/archive/` e `sdd/implementation/archive/`.
