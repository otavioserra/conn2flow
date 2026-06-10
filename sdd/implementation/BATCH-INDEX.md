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
| BATCH-000 a BATCH-017 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-000-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-000-017.md) | Os detalhes de implementação e validações dos primeiros 18 lotes foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-018 | complete | Tipo Publicador, Correções no Menus e Módulo de Galerias (req-018) | VALIDATION-CHECKLIST.md#batch-018 | Adição do tipo 'publicador' e correções no menus, mais criação do novo módulo 'galleries' com seleção em lote e reordenação Sortable.js |
| BATCH-019 | complete | Correções no Menus e Lógica do Módulo de Galerias (req-019) | VALIDATION-CHECKLIST.md#batch-019 | Target `[[item#target]]` + bloco `item-separator` nos 12 templates de menu; ajustes visuais (gap-8, hamburguer por botão, footer-colunas clicável, dropdown hover fallback); `menus.widget.js`/`galleries.widget.js` públicos (hambúrguer, hover, carrossel/slider com setas/dots/autoplay); galerias com controles `show_arrows`/`show_dots`/`autoplay`/`autoplay_speed`/`loop`, resolução `caminho`>`imgSrc`, blocos `controls-arrows`/`controls-dots`/`dot-item` e variáveis globais; alvo/modo de IA `galleries` + prompts `galleries.md`/`menus.md`. Ver DEC-027..DEC-031. Validação estática + teste de unidade 27/27 OK; deploy/validação runtime pendente com o operador |
| BATCH-020 | complete | Integração do Tailwind CSS CLI no Core do Sistema e Pipeline de Release (req-020) | VALIDATION-CHECKLIST.md#batch-020 | Compilação Tailwind CSS CLI em synchronize-manager.sh, sync-core-to-project.sh e no pipeline GitHub Actions release-gestor.yml. Implementado e validado em 2026-06-08. |
| BATCH-021 | complete | Preparação de Lançamento da Versão 2.8.0 (req-021) | VALIDATION-CHECKLIST.md#batch-021 | Correção do bug de HTML no publisher-pages, botão 'Adicionar todos os campos' no publisher e assets/documentação do v2.8.0. Ver DEC-034. Validação estática (node --check) OK; recompilação das páginas (Update => Core) e validação runtime pendentes com o operador. Fechado em 2026-06-08. |
| BATCH-022 | complete | Pré-visualizador de HTML Externo Unificado (req-022) | VALIDATION-CHECKLIST.md#batch-022 | Implementado e validado estaticamente em 2026-06-09; validação runtime em Docker pendente com o operador. |
| BATCH-023 | complete | Otimização de CSS Automático com Filtragem de Redundâncias (req-023) | VALIDATION-CHECKLIST.md#batch-023 | Filtragem de regras duplicadas do Tailwind CDN no frontend (`updateCSSCompiled` em `html-editor-interface.js`) para diminuir o CSS compilado inline. Ver DEC-036. Validação estática (`node --check`) OK; validação runtime no navegador pendente com o operador. |
| BATCH-024 | complete | Links Dinâmicos em Galerias, Controles de Exibição e Correções de Layout (req-024) | VALIDATION-CHECKLIST.md#batch-024 | Links individuais por imagem (página/custom/css/publicador) com painel "Configurar Link" e resolução no widget; altura e margem lateral globais; espaçamento das tags no publisher-highlights; padding dos submenus no menus-horizontal-navbar; legenda do masonry. Ver DEC-037. Validação estática (node --check / php -l) OK; deploy/validação runtime pendentes com o operador. Fechado em 2026-06-10. |
| BATCH-025 | complete | Autocomplete de Páginas em Galerias, Ajuste do Menu Horizontal e Preparação Final de Release (req-025) | VALIDATION-CHECKLIST.md#batch-025 | Autocomplete AJAX de páginas na curadoria de galerias (isolado por imagem), correção de links inativos (`pointer-events-none cursor-default`), setinha alinhada nos submenus do navbar (flex/space-between), thumbnails ampliados (200×140), e atualização de changelogs/READMEs/workflow de release (v2.8.0 → 2026-06-10). Ver DEC-038. Validação estática (node --check / php -l) OK; deploy/validação runtime pendentes com o operador. Fechado em 2026-06-10. |
| BATCH-026 | complete | Ajuste do Modo de IA de Destaques e Preservação de Template Modificado (req-026) | VALIDATION-CHECKLIST.md#batch-026 | Prompts de IA de Destaques (bloco no-item + variáveis adicionais) e Galerias (variáveis de link + regra obrigatória de âncora `<a>`); preservação de HTML/CSS customizado em edição/clonagem nos 3 módulos (menus/galleries/publisher-highlights) via opção `[id]-modificado` no dropdown + cache JS e limpeza do sufixo no submit. Ver DEC-039. Fechado em 2026-06-10. |
| BATCH-027 | complete | Resolução de Framework CSS e Variáveis de Destaques de Modelo Modificado (req-027) | VALIDATION-CHECKLIST.md#batch-027 | Slice corretivo do BATCH-026: `data-framework` (do `framework_css` da tabela `templates`) nas options de `menus.php`/`galleries.php`/`publisher-highlights.php` + helper `syncFrameworkFromTemplate()` no `ready`/`change` dos 3 JS para inicializar `gestor.html_editor.framework_css` síncronamente; e `extractVariablesFromHtml()` (regex client-side `[[item#X]]` → `{id}`) no `publisher-highlights.js` para popular o mapeamento em registros `-modificado`. Ver DEC-040. Validação estática (node --check / php -l) OK; deploy/validação runtime pendentes com o operador. Fechado em 2026-06-10. |
| BATCH-028 | blocked | Brainstorm: Estruturação de Framework de Testes Unitários e E2E (planejado) | - | Criação da pasta tests/ na raiz do repositório, configuração do PHPUnit (PHP), Vitest (JS) e Playwright (E2E) para automatizar a validação de novos recursos. |
| BATCH-029 | blocked | Brainstorm: Autenticação, 2FA, Social Login e Segurança (planejado) | - | Análise de melhorias do módulo perfil-usuario, 2FA, OAuth (Google/Meta), tokens JWT e estrutura de autenticação monolítica |
| BATCH-DATA-001 | blocked | Batch-Data-001: Reestruturação e Otimização de Dados e Sincronização | VALIDATION-CHECKLIST.md#batch-data-001 | Projeto de Arquitetura concluído. AGUARDANDO AUTORIZAÇÃO PARA IMPLEMENTAÇÃO. |

## Regra operacional

Não abra um novo batch funcional sem atualizar este índice. Se o escopo mudar de forma normativa, registre primeiro a mudança em `sdd/change-requests/`.
