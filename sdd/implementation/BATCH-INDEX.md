# Batch Index

Este arquivo controla o estado dos batches do `conn2flow` no modelo SDD.

## Status usados aqui

- `complete`: batch fechado e validado
- `ready-for-intake`: próximo slice reservado, aguardando intake humano classificado
- `in-progress`: implementação em andamento
- `blocked`: depende de decisão, requisito ou validação adicional

## Batches

| Batch | Status | Escopo | Alvo de validação | Observações |
| --- | --- | --- | --- | --- |
| BATCH-000 | complete | Onboarding do SDD repo-wide no `conn2flow` | Kits Claude/Copilot instalados, controle `sdd/` criado, baseline registrado | Fechado em 2026-05-25 |
| BATCH-001 | complete | Plano 1: tarefas e scripts de sincronização de projetos | VALIDATION-CHECKLIST.md#batch-001 | Implementado e validado em 2026-05-25 (composto em 3 tarefas: Core & Project, Project e Core) |
| BATCH-002 | complete | Motor de Widgets Envelopados e Módulo `publisher-highlights` | VALIDATION-CHECKLIST.md#batch-002 | Implementado e validado no core do Conn2Flow em 2026-05-25 (suporte a comentários HTML e CRUD de curadoria) |
| BATCH-003 | complete | Correções e melhorias do módulo `publisher-highlights` (req-004) | VALIDATION-CHECKLIST.md#batch-003 | Slice corretivo do BATCH-002 (form completo em adicionar/clonar, dropdown template_id, order_by, autocomplete múltiplo, variáveis item# no html-editor) |
| BATCH-004 | complete | Renomeação física de diretórios e arquivos de templates (req-005) | VALIDATION-CHECKLIST.md#batch-004 | Alinhar nomes de diretórios/arquivos com `id` do JSON; checksums já estavam vazios |
# Batch Index

Este arquivo controla o estado dos batches do `conn2flow` no modelo SDD.

## Status usados aqui

- `complete`: batch fechado e validado
- `ready-for-intake`: próximo slice reservado, aguardando intake humano classificado
- `in-progress`: implementação em andamento
- `blocked`: depende de decisão, requisito ou validação adicional

## Batches

| Batch | Status | Escopo | Alvo de validação | Observações |
| --- | --- | --- | --- | --- |
| BATCH-000 | complete | Onboarding do SDD repo-wide no `conn2flow` | Kits Claude/Copilot instalados, controle `sdd/` criado, baseline registrado | Fechado em 2026-05-25 |
| BATCH-001 | complete | Plano 1: tarefas e scripts de sincronização de projetos | VALIDATION-CHECKLIST.md#batch-001 | Implementado e validado em 2026-05-25 (composto em 3 tarefas: Core & Project, Project e Core) |
| BATCH-002 | complete | Motor de Widgets Envelopados e Módulo `publisher-highlights` | VALIDATION-CHECKLIST.md#batch-002 | Implementado e validado no core do Conn2Flow em 2026-05-25 (suporte a comentários HTML e CRUD de curadoria) |
| BATCH-003 | complete | Correções e melhorias do módulo `publisher-highlights` (req-004) | VALIDATION-CHECKLIST.md#batch-003 | Slice corretivo do BATCH-002 (form completo em adicionar/clonar, dropdown template_id, order_by, autocomplete múltiplo, variáveis item# no html-editor) |
| BATCH-004 | complete | Renomeação física de diretórios e arquivos de templates (req-005) | VALIDATION-CHECKLIST.md#batch-004 | Alinhar nomes de diretórios/arquivos com `id` do JSON; checksums já estavam vazios |
| BATCH-005 | complete | Correções visuais, simulação, mapeamento e fallback (req-006) | VALIDATION-CHECKLIST.md#batch-005 | 9 itens: SQL INNER JOIN, iframe auto-refresh, simulação highlights, visual segment/labels, filtro linked_template, bloco no-item |
| BATCH-006 | complete | Diagnóstico, mapeamento melhorado e preview real (req-007) | VALIDATION-CHECKLIST.md#batch-006 | 5 itens: fallback params + debug, ocultar mapeados, diferenciar campos padrões/dinâmicos, tabs + widget-preview AJAX, componente simulação simplificado |
| BATCH-007 | complete | Busca manual, abas externas e fallback de simulação (req-008) | VALIDATION-CHECKLIST.md#batch-008 | 4 itens corretivos sobre o BATCH-006: AJAX manual no dropdown, 2 abas externas preservando html-editor, fallbacks url/date, validação do componente highlights-simulation |
| BATCH-008 | complete | Variáveis sem arrobas, regex de simulação e debounce global (req-009) | VALIDATION-CHECKLIST.md#batch-008 | 4 itens: remover @[[ ]]@ do painel adm (JS + páginas HTML), regex sem arrobas na simulação, sync com publisher_highlights_update_target_variables, debounce global em todos os controles |
| BATCH-009 | complete | Persistência template_id, preview mapeado, simulação completa, limpeza arrobas (req-010) | VALIDATION-CHECKLIST.md#batch-009 | 5 itens: template_id no fields_schema, normalizar fields_values, fallbacks robustos, refresh ao trocar template, remover @@ decorativos |
| BATCH-010 | complete | Ordem manual, hidratação selects, vínculo no inserir, simulação diversificada (req-011) | VALIDATION-CHECKLIST.md#batch-010 | 5 itens: jquery-custom-dropdown onAdd/onRemove, setTimeout em #rule/#order_by, força tem_vinculo para highlights, offsets por varName, enriquecer mocks |
| BATCH-011 | in-progress | Nova Aba de Código do Widget no Editor de Destaques (req-012) | VALIDATION-CHECKLIST.md#batch-011 | 3 itens: html das páginas adicionar/editar/clonar, inicialização do CodeMirror read-only e sincronização do slug no javascript |
| BATCH-012 | ready-for-intake | Atualização e Alinhamento dos READMEs Globais da Raiz (req-013) | Visual/Revisão | Revisão do README.md e README-PT-BR.md para documentar o motor de widgets envelopados, destaques curados, aba do widget e a doc 03-spec do SDD |
| BATCH-013 | in-progress | Correção de Sincronização e Concorrência no Dropdown de Curadoria (req-013) | VALIDATION-CHECKLIST.md#batch-013 | 3 itens: flag ignoreCallbacks no setValues/onAdd/onRemove, simplificar syncSelection e validação do scheduleWidgetPreview |
| BATCH-DATA-001 | blocked | Batch-Data-001: Reestruturação e Otimização de Dados e Sincronização | VALIDATION-CHECKLIST.md#batch-data-001 | Projeto de Arquitetura concluído. AGUARDANDO AUTORIZAÇÃO PARA IMPLEMENTAÇÃO. |

## Regra operacional

Não abra um novo batch funcional sem atualizar este índice. Se o escopo mudar de forma normativa, registre primeiro a mudança em `sdd/change-requests/`.
