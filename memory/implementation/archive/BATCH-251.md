# BATCH-251 — REQ-242: refinamentos visuais, usabilidade e controles do painel Tailwind (Core)

- Status: implemented-pending-homologation. Os quatro blocos implementados, publicados no Lab e validados em 2026-10-06.
- Projeto: conn2flow
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow` (execução na worktree `conn2flow-req242`, branch `feat/req-242`)
- Requisição: [REQ-242](../../human-requests/archive/req-242.md). Coordenação: conn2flow-site, REQ-107 / [BATCH-101](../../../../conn2flow-site/sdd/implementation/archive/BATCH-101.md).
- Autonomia: `autonomo_monitorado`. Commit e push na branch de trabalho; `main` não foi tocada.

## Live Todo List

- [x] Bloco 1: abas limpas, botão copiar, campos extras com select flutuante e dicas em `forms-search` e `forms`.
- [x] Bloco 1: CodeMirror na aba Dados JSON, dicas e `c2fc-campo-selecao` em `forms-submissions`.
- [x] Bloco 2: hover simétrico das abas do Dashboard.
- [x] Bloco 2: capa ou ícone nos cards de módulos, nunca os dois.
- [x] Bloco 2: menu de opções dos widgets e tela cheia no documento isolado do widget.
- [x] Bloco 2: malha pontilhada no redimensionamento, passo vertical de 20 px e mínimo de 120 px.
- [x] Bloco 3: favoritos da TopBar à direita em todos os módulos; tipografia do shell igual em todos.
- [x] Bloco 3: `cursor: pointer` em rótulos de checkbox e rádio.
- [x] Bloco 4: `galleries` (ajuda em linha, abas, sub-aba Variáveis, controles em grade).
- [x] Bloco 4: seletor `admin-arquivos` com título de seleção múltipla e bandeja de miniaturas.
- [x] Compilação oficial (`c2f resources:sync`), Vitest, PHPUnit, pipeline do Lab e roteiro de navegador.
- [ ] Homologação humana.

## O que a auditoria mostrou (causa de cada defeito)

| Defeito relatado | Causa |
| --- | --- |
| Abas com fundo azul | Regra `.req222-page .c2fc-aba.active { background: #e0f2fe }` em 40 folhas de página (forms, forms-search, forms-submissions, galleries, menus, cookie-consent). |
| Select cortado e container com barra de rolagem | O painel do select é absoluto e a tabela vive em `overflow-x: auto`, que também recorta na vertical. |
| "Mais atalhos" pulando para a esquerda | `justify-end sm:justify-start` na `nav` da TopBar: a ordem dos bundles de cada módulo decidia qual vencia. Em parte dos módulos o parágrafo de status (`sr-only` ausente do bundle) virava item do flex e deslocava o perfil 12 px. |
| Fonte da sidebar maior e sem peso em módulos do site | O tema Tailwind do site define `--font-sans: Inter`, que o painel não carrega; a página caía em `sans-serif`. |
| Capa e ícone sobrepostos nos cards | `display: flex !important` inline no ícone vencia a regra `.has-cover .dashboard-card-svg { display: none !important }`. |
| Hover diferente entre as abas do Dashboard | As utilities de estado só existiam no botão que nascia inativo. |
| Controles da galeria empilhados à esquerda | A classe-gancho `inline fields` (Fomantic) coincide com a utility `inline` do Tailwind: a grade ficava em `display: inline`. O mesmo ocorria em `menus`. |
| Modal do logotipo com rolagem | O iframe tinha altura fixa `calc(100vh - 200px)` dentro de um modal que já limita a própria altura. |
| "orang / e" na coluna de cor | `.c2fc-rotulo` quebra em qualquer letra (`overflow-wrap: anywhere`) e a coluna é estreita. |

## Implementação

### Biblioteca de controles (`gestor/assets/interface/controles.js` e `controles.css`)

- **Select flutuante**: ao abrir, se algum ancestral recorta (`overflow` diferente de `visible`), o painel passa a `position: fixed` (`c2fc-flutuante`) na medida do gatilho, abre para cima quando falta espaço e é reposicionado na rolagem e no redimensionamento enquanto aberto. Fora desses containers nada muda. Vale para todo o painel, inclusive `subscriptions/view` do site.
- `label:has(input[type=checkbox|radio])`, rótulo logo depois do campo, `.checkbox > label` e `.c2fc-chave-rotulo` com `cursor: pointer`.
- `.c2fc-botao` não quebra o rótulo; `.c2fc-botao-compacto` (copiar ao lado do campo), `.c2fc-botao-destaque` (padrão ativo, usado pelo site), `.c2fc-botao-perigo-suave`, `.c2fc-selo-recusado`.
- Modal de tela cheia com iframe: o iframe ocupa o espaço entre título e ações; quem rola é o conteúdo do iframe.
- Listagem do `interface`: cabeçalho numa linha e rótulos que não são link quebram só em espaço.

### Layout administrativo (`layout-administrativo-tailwind`, pt-br e en)

- `nav` da TopBar sempre `justify-content: flex-end`; respiro da barra fixo a partir de 640 px; avisos invisíveis fora do fluxo.
- Família de fonte do painel fixada em `body:has(> #c2f-admin-shell)`; item do menu com tamanho, altura de linha e família determinísticos.

### Formulários

- `forms` e `forms-search` (adicionar, editar, clonar; pt-br e en): dicas em todas as abas e botões, copiar compacto ao lado do campo, respiro nas células dos campos extras.
- `forms-submissions/view`: dicas nas três abas, JSON em CodeMirror somente leitura (`application/json`, tema claro, números de linha, botão copiar), select de status sem estilo inline. O JSON, que vem do visitante, agora entra na página com `htmlspecialchars` e com o marcador de variável desarmado.
- Texto "NÃ£o se aplica" do manifesto do `forms-submissions` corrigido para "Não se aplica".

### Dashboard

- Abas com a mesma marcação; repouso, hover, foco e ativa na folha do componente.
- Capa ou ícone por CSS (o `display` saiu do inline) e, se a imagem falhar ao carregar, o card volta ao ícone.
- Menu "Opções" em linhas com ícone (`rounded-lg`, `p-1`, `text-xs`).
- Documento isolado do widget: `allow="fullscreen"` e `allowfullscreen`, mantendo `sandbox="allow-scripts"`; o corpo ocupa a altura do cartão.
- Redimensionamento: malha pontilhada de 20 px no lugar das linhas de coluna, passo vertical de 20 px, de 120 a 960 px, com a medida mostrada durante o arrasto.

### Galerias e seletor de arquivos

- `galleries` (três telas, dois idiomas): textos de ajuda com ícone e texto lado a lado, controles de exibição em grade de até quatro colunas, dicas nas abas e botões. Mesma correção de grade em `menus`.
- Sub-aba Variáveis do editor (`html-editor-publisher-controls-tailwind`): barra com espaçamento e botões de opção separados.
- `admin-arquivos`: aberto com `multiplo=sim` (as galerias passam a abrir assim) o título é "Selecione uma ou mais imagens abaixo..." no cabeçalho do modal e dentro do gerenciador. No modo seletor, uma bandeja presa à base mostra as miniaturas marcadas, cada uma com "×" para desmarcar, ao lado de Cancelar e Incluir Selecionados. Botões do gerenciador com canto de 8 px.

### Compilador de recursos

- `atualizacao-dados-recursos.php`: manifesto de módulo regravado com `JSON_UNESCAPED_SLASHES`. O manifesto é fonte do Tailwind quando as utilities vivem nas variáveis (`perfil-usuario`); regravado com `\/`, a classe `focus:ring-sky-600/20` sumia do CSS das telas de login na mesma rodada. Defeito latente, exposto pela compilação completa deste lote.

## Guardas de teste

- `tests/Unit/PHP/RefinamentosReq242Test.php` (16 testes, 248 asserções): dicas nas abas, copiar compacto, JSON em CodeMirror, escape do JSON do visitante, variáveis novas nos dois idiomas, seletor de arquivos, galeria, layout e gravação do manifesto.
- `tests/Unit/JS/req242-refinamentos.test.js` (10 testes): select flutuante (posição, rolagem, abertura para cima), bandeja do seletor (uma miniatura por arquivo, nome escapado), folhas e marcação.
- `tests/Unit/JS/dashboard.widgets-req236.test.js`: teto de altura passa de 780 para 960 px; acrescidos tela cheia e mínimo de 120 px.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `php cli/c2f.php resources:sync` | 511 recursos Tailwind compilados, "Nenhum problema detectado". O aviso de `dist/` sem `PUBLIC_PATH` é o conhecido. |
| Vitest do core | 47 arquivos, 551 testes, sem falhas |
| PHPUnit do core (ordem padrão) | 1.629 testes, 16.199 asserções, sem falhas (5 pulados, 4 depreciações do PHP e 3 do PHPUnit) |
| Pipeline `project:update-all conn2flow-site-local` | 4 rodadas. A primeira parou na etapa 5 com código 23 do `rsync` (ver Limites); as três seguintes terminaram com saída 0 nas 8 etapas |
| Roteiro de navegador `sdd/validation/req242/req242-browser.cjs` | 112 de 112 conferências (core e site), em 1366 px e 390 px. Na primeira rodada, 102 de 109: duas falhas eram defeitos reais (grade das chaves e perfil deslocado 12 px), corrigidos; as demais eram do próprio roteiro |
| `git diff --check` | limpo |

Resultado e capturas: `sdd/validation/req242/evidencias/` (`resultado.json` e 21 imagens).

## Arquivos fora do escopo que a compilação alterou

A compilação completa atualizou versão e checksum de recursos de 18 módulos que este lote não tocou (um arquivo por módulo, o manifesto) e os `*Data.json` correspondentes. O checksum versionado desses recursos não corresponde ao conteúdo versionado, nem em LF nem em CRLF: o HTML foi commitado sem recompilar. O `resources:sync` apenas pôs o registro em dia.

## Limites e observações

- **Árvore principal sem dependências**: `node_modules` e `vendor` de `conn2flow` estão vazios. Usei `conn2flow-req232/node_modules` (Tailwind 4.3.3, a versão dos pré-compilados versionados; o `package-lock.json` registra 4.3.0) e `conn2flow-req234/vendor`.
- **Primeira rodada do pipeline (código 23)**: uma junção criada na worktree do site para o empacotador (`gestor/assets/3d-catalog`) foi tratada como link simbólico pelo `rsync`, que recusou trocar a pasta do Lab. Nada foi apagado; a junção foi removida antes da rodada seguinte.
- **PHPUnit em ordem aleatória**: em 2 de 4 sementes a execução termina com `Cannot redeclare gestor_roteador_layout_perfil()`, entre `Req204LayoutMultiPerfilTest` e outro teste que avalia a mesma função. Não envolve arquivos deste lote.
- **`cursor: pointer`**: a regra cobre rótulo que envolve o campo, rótulo logo depois dele e a marcação `.checkbox`. Rótulo ligado só por `for` já era coberto.
- **Widgets**: a tela cheia foi conferida pela permissão do documento (`document.fullscreenEnabled`), não por um clique no botão de tela cheia da apresentação.
- **Não conferido em navegador**: as telas em inglês (o Lab roda em pt-br; a paridade é coberta pelos testes de contrato) e as telas `clonar` de `forms`, `forms-search` e `galleries`.
- `sdd/human-requests/` não foi alterado: a troca de status em `CURRENT.md` fica com o Arquiteto.
