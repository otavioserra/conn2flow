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
| BATCH-007 | complete | Busca manual, abas externas e fallback de simulação (req-008) | VALIDATION-CHECKLIST.md#batch-007 | 4 itens corretivos sobre o BATCH-006: AJAX manual no dropdown, 2 abas externas preservando html-editor, fallbacks url/date, validação do componente highlights-simulation |
| BATCH-008 | complete | Variáveis sem arrobas, regex de simulação e debounce global (req-009) | VALIDATION-CHECKLIST.md#batch-008 | 4 itens: remover @[[ ]]@ do painel adm (JS + páginas HTML), regex sem arrobas na simulação, sync com publisher_highlights_update_target_variables, debounce global em todos os controles |
| BATCH-009 | complete | Persistência template_id, preview mapeado, simulação completa, limpeza arrobas (req-010) | VALIDATION-CHECKLIST.md#batch-009 | 5 itens: template_id no fields_schema, normalizar fields_values, fallbacks robustos, refresh ao trocar template, remover @@ decorativos |
| BATCH-010 | complete | Ordem manual, hidratação selects, vínculo no inserir, simulação diversificada (req-011) | VALIDATION-CHECKLIST.md#batch-010 | 5 itens: jquery-custom-dropdown onAdd/onRemove, setTimeout em #rule/#order_by, força tem_vinculo para highlights, offsets por varName, enriquecer mocks |
| BATCH-011 | complete | Nova Aba de Código do Widget no Editor de Destaques (req-012) | VALIDATION-CHECKLIST.md#batch-011 | 3 itens: html das páginas adicionar/editar/clonar, inicialização do CodeMirror read-only e sincronização do slug no javascript |
| BATCH-012 | ready-for-intake | Atualização e Alinhamento dos READMEs Globais da Raiz | Visual/Revisão | Revisão do README.md e README-PT-BR.md para documentar o motor de widgets envelopados, destaques curados, aba do widget e a doc 03-spec do SDD |
| BATCH-013 | complete | Correção de Sincronização, CodeMirror e Renderização do Widget (req-013) | VALIDATION-CHECKLIST.md#batch-013 | Flag ignoreCallbacks no setValues/onAdd/onRemove, simplificar syncSelection e validação do scheduleWidgetPreview |
| BATCH-014 | complete | Refatoração de Curadoria Manual, Autocomplete Ajax e Reordenação Drag and Drop (req-014) | VALIDATION-CHECKLIST.md#batch-014 | Substituição do custom dropdown por input autocomplete de busca Ajax, tags em labels do Fomantic UI com suporte a reordenação Sortable.js e correção do hep-widget-code CodeMirror |
| BATCH-015 | complete | Correções Residuais de Destaques e Inicialização do Módulo de Menus (req-015) | VALIDATION-CHECKLIST.md#batch-015 | 4 correções no publisher-highlights (simulação count, margem dropdown, visibilidade dinâmica do container, grab no label todo + filtro) e criação do módulo `menus` desacoplado do publisher (tabela sem publisher_id, 6 templates próprios, autocomplete de páginas). Ver DEC-022. Deploy/validação runtime pendente com o operador (Update => Core) |
| BATCH-016 | complete | Hierarquia Multi-nível de Menus e Drag-and-Drop Estilo WordPress (req-016) | VALIDATION-CHECKLIST.md#batch-016 | Itens tipados (página/link-custom/cabecalho/link-action/separador), filtro de tipo de página no autocomplete, editor de árvore drag-and-drop bidimensional próprio (vanilla + Fomantic, sem Sortable.js) e renderização recursiva `item-parent`/`[[item#children]]`. Ver DEC-023. Contrato de `selected_items` passa a ser árvore tipada. Validação estática + teste do renderizador OK; deploy/validação runtime pendente com o operador |
| BATCH-017 | complete | Ajustes e Correções no Módulo de Menus (req-017) | VALIDATION-CHECKLIST.md#batch-017 | 5 itens: aba Variáveis/Simulação reintegrada ao html-editor para o alvo `menus` (com componente `html-editor-menus-simulation` pt-br/en e simulação recursiva de árvore), fix no alternador de tipos JS (`dropdown('get value')`), placeholder do DnD com altura de item + "Solte o item aqui" ← →, e hover dos submenus corrigido (gaps `mt-1`/`ml-1` removidos em menus-dropdown e menus-horizontal-navbar). Ver DEC-024. Validação estática + teste do simulador OK; deploy/validação runtime pendente com o operador |
| BATCH-018 | complete | Tipo Publicador, Correções no Menus e Módulo de Galerias (req-018) | VALIDATION-CHECKLIST.md#batch-018 | Adição do tipo 'publicador' e correções no menus, mais criação do novo módulo 'galleries' com seleção em lote e reordenação Sortable.js |
| BATCH-019 | complete | Correções no Menus e Lógica do Módulo de Galerias (req-019) | VALIDATION-CHECKLIST.md#batch-019 | Target `[[item#target]]` + bloco `item-separator` nos 12 templates de menu; ajustes visuais (gap-8, hamburguer por botão, footer-colunas clicável, dropdown hover fallback); `menus.widget.js`/`galleries.widget.js` públicos (hambúrguer, hover, carrossel/slider com setas/dots/autoplay); galerias com controles `show_arrows`/`show_dots`/`autoplay`/`autoplay_speed`/`loop`, resolução `caminho`>`imgSrc`, blocos `controls-arrows`/`controls-dots`/`dot-item` e variáveis globais; alvo/modo de IA `galleries` + prompts `galleries.md`/`menus.md`. Ver DEC-027..DEC-031. Validação estática + teste de unidade 27/27 OK; deploy/validação runtime pendente com o operador |
| BATCH-020 | complete | Integração do Tailwind CSS CLI no Core do Sistema e Pipeline de Release (req-020) | VALIDATION-CHECKLIST.md#batch-020 | Compilação Tailwind CSS CLI em synchronize-manager.sh, sync-core-to-project.sh e no pipeline GitHub Actions release-gestor.yml. Implementado e validado em 2026-06-08. |
| BATCH-021 | blocked | Preparação de Lançamento da Versão 2.8.0 (req-021) | - | Atualização de CHANGELOG.md, CHANGELOG-PT-BR.md, README.md, README-PT-BR.md e release-gestor.yml (descrição da release). AGUARDANDO TÉRMINO DOS TESTES. |
| BATCH-022 | blocked | Brainstorm: Estruturação de Framework de Testes Unitários e E2E (planejado) | - | Criação da pasta tests/ na raiz do repositório, configuração do PHPUnit (PHP), Vitest (JS) e Playwright (E2E) para automatizar a validação de novos recursos. |
| BATCH-023 | blocked | Brainstorm: Autenticação, 2FA, Social Login e Segurança (planejado) | - | Análise de melhorias do módulo perfil-usuario, 2FA, OAuth (Google/Meta), tokens JWT e estrutura de autenticação monolítica |
| BATCH-DATA-001 | blocked | Batch-Data-001: Reestruturação e Otimização de Dados e Sincronização | VALIDATION-CHECKLIST.md#batch-data-001 | Projeto de Arquitetura concluído. AGUARDANDO AUTORIZAÇÃO PARA IMPLEMENTAÇÃO. |

## Regra operacional

Não abra um novo batch funcional sem atualizar este índice. Se o escopo mudar de forma normativa, registre primeiro a mudança em `sdd/change-requests/`.
