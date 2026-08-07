# BL-017 — Substituição dos componentes interativos Semantic/Fomantic

- **Tipo:** Epic/UX/Accessibility
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** JavaScript e componentes dinâmicos administrativos
- **Relacionados:** BL-016, BL-018, BL-019

## Inventário inicial

O core utiliza amplamente plugins Semantic/Fomantic via jQuery: modal, dropdown, checkbox, popup, tabs, dimmer/loading, form/validation, toast, calendar, search, accordion e transition. O maior volume está em modal, dropdown, checkbox, toast, calendar, busca e tabs. Há também inicializações feitas por cada módulo, o que torna conteúdo AJAX especialmente sujeito a handlers duplicados.

## Componentes a substituir

| Família atual | Contrato Conn2Flow proposto | Requisitos mínimos |
| --- | --- | --- |
| modal/confirm | dialog service | foco preso/restaurado, Escape, backdrop, confirmação async |
| toast/alert | notification service | `aria-live`, níveis, timeout pausável, histórico opcional |
| dropdown/search | select/combobox | teclado, busca remota, loading, valor tipado |
| checkbox | checkbox/switch | label, estado indeterminado, validação |
| tabs/accordion | disclosure/navigation | setas, foco, deep link, estado persistente |
| dimmer/loader | busy overlay | `aria-busy`, cancelamento, sem bloquear leitor de tela |
| form | validation summary | erros por campo e resumo, foco no primeiro erro |
| calendar | date/time picker | locale, timezone, teclado, fallback nativo |
| popup | tooltip/popover | sem conteúdo crítico apenas em hover |
| search | autocomplete | debounce, abort, limites e resultado acessível |

## Arquitetura JavaScript

- módulos ES e JavaScript vanilla como padrão da v3;
- um registry baseado em `data-c2-component`, idempotente;
- `mount(root)` e `unmount(root)` para páginas completas e fragmentos AJAX;
- eventos de domínio (`c2:dialog:closed`, por exemplo), sem chamadas diretas ao fornecedor;
- AbortController para buscas e ações canceláveis;
- proteção contra double-submit, handlers duplicados e respostas fora de ordem;
- CSP sem `eval` e, gradualmente, sem handlers inline.

Remover jQuery é uma decisão separada da remoção do Fomantic. A migração pode manter jQuery em módulos legados, mas os componentes v3 não devem criar uma nova dependência nele.

## Sequenciamento

1. dialog/confirm, alert/toast e busy state;
2. campos, checkbox/switch e validação;
3. dropdown/combobox/search;
4. tabs, accordion, popup e menus;
5. calendário/data e componentes especializados;
6. adaptadores temporários e remoção das inicializações Semantic.

## Critérios de aceite para futura implementação

- componentes funcionam após inserção e remoção de fragmentos AJAX;
- nenhum handler é multiplicado ao reabrir/recarregar a tela;
- suíte cobre teclado, foco e leitores de tela nos fluxos críticos;
- módulos usam somente API/atributos Conn2Flow;
- confirmações destrutivas são inequívocas e canceláveis;
- erros de sessão AJAX acionam o fluxo de login definido pelo core;
- ao final, a busca estática e os testes de runtime não encontram inicializações Semantic/Fomantic.

## Próxima decisão

Priorizar os componentes necessários ao piloto `admin-paginas-v2` e expandir o catálogo por demanda das ondas de módulos.
