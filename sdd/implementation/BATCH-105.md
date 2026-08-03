# BATCH-105 — Navegacao por Teclado nos Filtros de Modulos

## Origem

- Demanda direta do Engenheiro Chefe em 2026-08-03.
- Extensao incremental do filtro aprovado no BATCH-103.
- Decisao: DEC-101.
- Status: `complete`.
- Data de fechamento: 2026-08-03.

## Objetivo

Permitir que os resultados dos filtros de modulos da Editbar (`#c2f-modules-filter`) e do menu
principal do gestor (`#gestor-menu-filtro`) sejam percorridos pelo teclado, sem alterar a filtragem,
os links ou a estrutura de grupos existente.

## Contrato entregue

- `ArrowDown` no input move o foco para o primeiro resultado visivel.
- `ArrowDown` em um resultado move o foco para o proximo resultado visivel.
- `ArrowUp` em um resultado move o foco para o resultado visivel anterior.
- `ArrowUp` no primeiro resultado devolve o foco ao respectivo input de filtro.
- No ultimo resultado, `ArrowDown` mantem o foco nele; a lista nao faz ciclo.
- Itens ocultados pelo filtro sao ignorados na navegacao.
- Sem resultados, `ArrowDown` nao tira o foco do input.
- O foco e aplicado ao link real; por isso Enter conserva a ativacao nativa da navegacao.

## Implementacao

- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`: delegacao de `keydown` no dropdown da
  Editbar e calculo dos links dentro de `.c2f-menu-item` que continuam visiveis.
- `gestor/assets/global/admin.js`: navegacao equivalente nos links `a.item` visiveis do menu do
  gestor, preservando o comportamento existente de Escape.
- `gestor/modulos/dashboard/dashboard.json`: versao `1.0.14` para `1.0.15`, invalidando o cache do
  asset `iframe-toolbar.js`.
- Os arquivos PHP e os componentes pt-br/en foram inspecionados, mas nao precisaram de alteracao:
  os resultados ja sao links focaveis e o comportamento nao introduz texto traduzivel novo.

## Evidencia automatizada

- `node --check` nos dois JavaScripts: OK.
- Parse de `gestor/modulos/dashboard/dashboard.json`: OK.
- Testes focados: **16/16** em `dashboard.iframe-toolbar.test.js` e
  `admin-menu-filtro.test.js`.
- Suite Vitest completa: **93/93** em 11 arquivos.
- `git diff --check` no slice: OK.

## Homologacao manual recomendada

- Na Editbar e no menu principal, filtrar por um termo com mais de um resultado e conferir a ordem
  de foco com setas para baixo/cima, o retorno ao input e a abertura do item com Enter.
- Repetir com um unico resultado e com nenhum resultado.

Observacao: a suite completa continua emitindo avisos preexistentes de rede do Happy DOM nos testes
de embeds, mas finaliza com 93/93 aprovados. Nenhum commit ou push foi executado.
