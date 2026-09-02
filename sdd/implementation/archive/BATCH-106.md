# BATCH-106 — Opções de Exibição, Sidebar Lateral de CSS, Barra de Navegação de Elementos e Ação Substituir

## Origem

- Intake humano: [req-106.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/archive/req-106.md).
- Decisão: DEC-103.
- Status: `complete`.
- Data de fechamento: 2026-08-06.

## Objetivo

Tirar da flutuação sobre o elemento selecionado os painéis que mais poluem o canvas (estilização,
trilha de ancestrais e lista de filhos), oferecendo lugares fixos e opcionais para eles; ampliar o
que a caixa de estilização entrega (classes, valores livres, CSS inline, inspeção); e acrescentar a
troca direta de um elemento pelo bloco da área de transferência.

Vale nos dois contextos do editor: a Editbar do Live Editor (`c2f-toolbar-editbar`) e o editor
visual dos módulos administrativos (`/admin-paginas/` e afins).

## Contrato entregue

### 1. Painel de Opções de Exibição (`c2f-view-options-panel`)

- Botão `c2f-tb-view-options` ("Opções de Exibição" / "Display Options") na Editbar e na topbar do
  editor visual dos módulos.
- Painel flutuante no padrão do `c2f-add-panel`, com dois *toggles* nomeados: `Sidebar Lateral de
  CSS` e `Barra de Navegação de Elementos`.
- Ambos **desativados por padrão**; a escolha é persistida em `localStorage['c2f-he-view-options']`
  e recuperada no boot do editor (o painel reflete o estado real do motor ao abrir).

### 2. Sidebar Lateral Fixa de CSS (`c2f-he-css-sidebar`)

- Coluna fixa à esquerda (240px) com cabeçalho fixo rotulado "Sidebar Lateral de CSS" e corpo
  rolável ocupando a altura útil da viewport.
- Encaixa **abaixo** da Barra de Navegação quando as duas estão ligadas (sem sobreposição) e respeita
  o offset da Editbar no Live Editor.
- O `#html-editor-tailwind-styler` é REALOCADO para dentro dela (mesmo nó, mesmos handlers) e volta
  ao `document.body` ao desligar.
- Expansões da coluna de classes:
  - **Classes Tailwind ativas**, agrupadas por variante (`base`, `sm:`, `md:`, `hover:`…), com
    remoção pelo "x" — atende também a sugestão §5.2 do intake.
  - **Classes customizadas** do projeto em bloco próprio (etiqueta âmbar), com remoção.
  - **Autocomplete instantâneo** (§5.1) filtrando o dicionário a partir de 2 caracteres do último
    token digitado; clicar aplica a classe.
  - **CSS inline customizado**: campo textual que lê e grava o atributo `style` inteiro.
- Expansões da coluna visual (`he-styler-col-visual`), como seções do accordion existente:
  - **Valores manuais**: 10 campos livres (largura, altura, padding, margem, fonte, entrelinha, cor,
    fundo, cantos, gap) aceitando `25px`, `1.5rem`, `#123456`; vazio remove a propriedade.
  - **Estilos computados** (§5.3): 20 propriedades reais via `getComputedStyle()`.
- **Dicionário de sugestões expandido** (`tailwindSuggestions`, que alimenta o datalist
  `html-editor-tw-classes` e o autocomplete): escala completa de espaçamento, larguras/alturas,
  grid (`grid-cols-*`, `col-span-*`), flexbox, bordas, sombras, opacidade, z-index, posicionamento,
  transições/transformações, paleta completa de cores, valores arbitrários (`w-[350px]`,
  `bg-[#1a2b3c]`, `w-[calc(100%-2rem)]`) e variantes (`md:`, `hover:`…).

### 3. Barra Superior Fixa de Navegação de Elementos (`c2f-he-element-navbar`)

- Barra fixa de 44px abaixo da Editbar, em duas colunas: 20% com o rótulo fixo "Barra de Navegação
  de Elementos" e 80% com a área útil.
- `#html-editor-selection-breadcrumb` e `#html-editor-selection-children` são realocados para a
  coluna de 80% (mesmos nós) e voltam ao `document.body` ao desligar.
- Desativada por padrão.

### 4. Toolbar flutuante e ação "Substituir" (`.he-tb-replace`)

- A toolbar contextual continua acompanhando o elemento selecionado.
- Novo botão ao lado de Copiar/Colar, visível apenas quando há cópia guardada **e** elemento
  selecionado.
- `replaceSelected()` insere o bloco da área de transferência na posição do elemento, remove o
  original, renumera ids de widget (`remapPastedIds`) e **seleciona automaticamente** o novo objeto.

## Implementação

- `gestor/assets/interface/html-editor.js` (motor, roda no iframe do editor clássico e na própria
  página no Live Editor):
  - CSS dos painéis fixos e dos estados encaixados (`he-styler-docked`, `he-nav-docked`).
  - `readViewOptions`/`writeViewOptions`/`getViewOption`/`setViewOption`, `createViewPanels`,
    `applyViewOptions`, `dockCssSidebar`, `dockElementNavbar`, `layoutViewPanels`,
    `chromeTopOffset`, `syncViewPanelsEmpty`.
  - `updateSelectionUI` deixa de posicionar o que está encaixado; `clearSelection` mantém os painéis
    fixos na tela em estado vazio; `enable`/`disable` mostram e escondem os painéis com o editor.
  - `isEditorOwned` e `extractUserHtml` reconhecem os três novos ids (seleção e persistência).
  - `replaceSelected` + visibilidade do botão em `updatePasteButton`.
  - Styler: `renderClassTags`, `classVariant`, `isTailwindClass`, `renderClassSuggestions`,
    `addClassFromSuggestion`, `manualStyleFields`, `applyManualStyle`, `syncManualFields`,
    `syncInlineCss`, `applyInlineCss`, `renderComputedStyles` e `tailwindSuggestions` ampliado.
  - Mensagens novas no barramento: `c2f-he:view-option` e `c2f-he:replace`.
- `gestor/modulos/dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/…html`: botão
  `c2f-tb-view-options` na Editbar.
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`: publica `c2f-toolbar:edit-view-options`
  com a posição do botão.
- `gestor/modulos/dashboard/dashboard.toolbar.js`: painel `#c2f-view-options-panel` (construção,
  sincronização com o motor, fechamento ao clicar fora) e o novo `case` do barramento.
- `gestor/resources/{pt-br,en}/components/html-editor-visual-modal/…html`: botão
  `.c2f-tb-view-options` na topbar do editor visual dos módulos.
- `gestor/assets/interface/html-editor-visual-controls.js`: painel equivalente na janela pai,
  lendo o mesmo `localStorage` (o iframe usa `srcdoc`, herdando a origem) e postando
  `c2f-he:view-option` ao motor.
- `gestor/assets/interface/html-editor-interface.js`: os painéis fixos entram no seletor de limpeza
  do caminho de fallback do salvamento.
- Cache-bust: `biblioteca-html-editor` `1.5.6`→`1.5.7`, `dashboard.json` `1.0.15`→`1.0.16`, motor no
  Live Editor `?v=c2f15`→`?v=c2f16`. Checksums dos recursos alterados foram esvaziados para o
  pipeline de deploy recalcular.

## Evidência automatizada

- `node --check`: `html-editor.js`, `html-editor-interface.js`, `html-editor-visual-controls.js`,
  `dashboard.toolbar.js`, `dashboard.iframe-toolbar.js` → **5/5 OK**.
- `php -l gestor/bibliotecas/html-editor.php` → OK.
- Parse de `dashboard.json` e dos dois `components.json` → OK.
- `npx vitest run` → **118/118** em 12 arquivos (antes 93/93), com o novo
  `tests/Unit/JS/html-editor-view-options.test.js` **21/21**, 3 casos novos em
  `dashboard.toolbar.test.js` e 1 em `dashboard.iframe-toolbar.test.js`.
- `composer test` (PHPUnit) → **172/172** (707 assertions, 4 skipped preexistentes) sem regressão.

## Rodada 2 — homologação do Chefe (2026-08-06)

Quatro ajustes de usabilidade reportados durante o teste da rodada 1:

1. **Painéis flutuantes legados aposentados**: a trilha de ancestrais, a lista de filhos e a caixa de
   estilização deixaram de acompanhar o elemento selecionado. Elas só existem dentro da Barra de
   Navegação e da Sidebar de CSS; com o painel correspondente desligado, nada aparece. O código de
   empilhamento flutuante permanece no arquivo, inativo (desabilitado, não removido).
   A barra flutuante de AÇÕES (`html-editor-floating-toolbar`) continua acompanhando o elemento.
2. **Botões no cabeçalho dos dois painéis fixos** (canto superior direito da área do título):
   - `c2f-he-panel-close` (✕) desliga o painel — equivale a desmarcar o toggle nas Opções de Exibição.
   - `c2f-he-panel-side` (setas) alterna a ancoragem sem desligar o painel: **↔** na sidebar
     (esquerda ⇄ direita) e **↕** na barra de navegação (topo ⇄ base).
3. **Ancoragem persistida, com o padrão original**: `cssSidebarRight` e `elementNavbarBottom` entram
   no mesmo registro de `localStorage`, ambos `false` por padrão — sidebar à esquerda, barra no topo.
   O encaixe é recalculado nos dois sentidos: com a barra embaixo, a sidebar sobe até a Editbar e
   encurta na base (`height: calc(100vh - topo - altura da barra)`).
4. **Clique na Editbar fecha TODA a UI flutuante**: o clique acontece dentro do iframe da barra,
   então o `mousedown` da página hospedeira nunca disparava e nenhum backdrop era atingido — o painel
   de Opções (ou "+" / Backups / Modelos / IA / Código Customizado / modais) ficava aberto. A barra
   passa a publicar `c2f-toolbar:ui-dismiss` a cada `mousedown` (em captura) e o host responde com
   `dismissHostPanels()`, que fecha os painéis desta página e delega ao motor
   (`c2fEditor.dismissFloatingUi()`) o fechamento dos painéis/modais dele — exatamente o mesmo
   conjunto que já fechava ao clicar fora na área editável. Clique DENTRO de um painel/modal não
   fecha nada (o aviso só nasce de cliques na barra; na página, cada backdrop continua responsável).
   Como o `mousedown` precede o `click`, o botão que abre um painel continua funcionando: fecha o que
   estava aberto e abre o pedido.
   - Regressão corrigida no caminho: `closeEmbedModal()` zera `isModalActive` incondicionalmente, o
     que faria o modal de edição deixar de ser fechado por `dismissFloatingUi`. O estado passou a ser
     lido antes, e embed/picker só são fechados quando de fato estão abertos.

Arquivos da rodada 2: `html-editor.js` (aposentadoria do legado, botões de cabeçalho, ancoragem,
`layoutViewPanels` nos quatro arranjos, ícone `close`, `dismissFloatingUi`),
`dashboard.iframe-toolbar.js` (aviso de dismiss) e `dashboard.toolbar.js` (`dismissHostPanels` +
`case` novo). Nenhum recurso do banco foi alterado nesta rodada. Cache-bust:
`biblioteca-html-editor` `1.5.7`→`1.5.8`, `dashboard.json` `1.0.16`→`1.0.17`, motor
`?v=c2f16`→`?v=c2f17`.

Evidência da rodada 2: `node --check` 3/3 OK, `php -l` OK, JSON OK, `npx vitest run` **130/130**
(`html-editor-view-options.test.js` 21→**29**, `dashboard.toolbar.test.js` +3,
`dashboard.iframe-toolbar.test.js` +1), `composer test` **181/181** (a suíte PHP cresceu com o
BATCH-107, que roda em paralelo; sem regressão).

## Rodada 3 — homologação do Chefe (2026-08-06)

Três ajustes reportados na sequência dos testes:

1. **CSS inline com CodeMirror** (`he-inline-css` na Sidebar de CSS): o `<textarea>` virou editor
   CodeMirror (`mode: css`, tema `tomorrow-night-bright`, `indentUnit` 4, 110px de altura — a largura
   da sidebar não comporta os 800px do padrão de abas). Criado sob demanda e só com a sidebar ligada,
   idempotente (dedup por `.CodeMirror` irmão) e com **degradação graciosa**: sem a biblioteca (ou
   sem `getValue`/`setValue`), o textarea continua valendo. `syncInlineCss` passa a usar
   `setValue` + `refresh` (o editor pode ter sido criado oculto) e `applyInlineCss` lê de
   `inlineCssValue()`. O `blur` do editor aplica, e um valor idêntico ao `style` atual não empilha
   passo de undo. Os dois contextos já carregam a lib com `mode/css/css.js` (srcdoc do editor
   clássico e `dashboard.toolbar.js` no Live Editor).
2. **Resize das caixas `c2f-he-live-box` (Assistente IA / Código Customizado)** — dois defeitos na
   mesma função, agora extraída para `syncLiveBoxCodeMirrors(panel)`. O **crescimento dinâmico do
   BATCH-081 é preservado**: o editor continua acompanhando o arraste do canto inferior direito.
   - O `#c2f-ai-status` sumia ao arrastar. A conta era `fundo do corpo − topo do editor`, ou seja, o
     CodeMirror tomava tudo até a borda do corpo e empurrava para fora o que vinha DEPOIS dele. Agora
     desconta `alturaAposElemento(editor, corpo)` — a soma dos irmãos posteriores do editor e dos
     seus ancestrais até o corpo (hoje, o status; amanhã, qualquer bloco novo).
   - O editor da aba "Modo" nascia pequeno (a altura fixa do `setSize` inicial) e só se acertava ao
     arrastar a caixa, porque o ajuste vivia apenas no `ResizeObserver` — que reage a mudanças da
     CAIXA. A função passou a ser chamada também na troca de abas e na abertura dos dois painéis.
   - **Correção dentro da própria rodada**: a primeira versão calculava o espaço livre por
     `clientHeight − (scrollHeight − altura dos editores)`. Como `scrollHeight` nunca é menor que
     `clientHeight`, com o conteúdo cabendo na caixa a conta virava "altura atual do editor − folga"
     — o editor encolhia a cada disparo do observador até o mínimo e parava de acompanhar o mouse,
     exatamente o recurso que a rodada queria preservar. A conta voltou a partir do fundo do corpo
     (que cresce com a caixa) e o desconto do que vem depois é independente da altura do editor,
     então não há realimentação e a guarda anti-loop por igualdade exata continua suficiente.
3. **Painéis fixos somem ao trocar desktop/tablet/mobile**: `enterDevicePreview()` chama
   `c2fEditor.disable()`, que na rodada 1 escondia a Sidebar e a Barra de Navegação. Trocar a largura
   de visualização não é sair do modo de edição, então `disable()` passou a aceitar
   `{ manterPaineis: true }` — usado apenas pelo preview de dispositivo. Sair da edição e salvar
   continuam escondendo os painéis.

Arquivos da rodada 3: `html-editor.js` e `dashboard.toolbar.js`. Nenhum recurso do banco foi
alterado. Cache-bust: `biblioteca-html-editor` `1.5.8`→`1.5.9`, `dashboard.json` `1.0.17`→`1.0.18`,
motor `?v=c2f17`→`?v=c2f18`.

Evidência da rodada 3: `node --check` 2/2 OK, `php -l` OK, JSON OK, `npx vitest run` **137/137**
(`html-editor-view-options.test.js` 29→**36**), `composer test` **181/181**. O stub de CodeMirror
dos testes (`tests/Unit/JS/setup.js`) ganhou `on()` e um `__emit()` auxiliar para exercitar o `blur`.
Os três casos novos de resize usam medidas stubadas (o ambiente de teste não calcula layout) e
travam a aritmética: o desconto do status, o **acompanhamento do arraste em ambos os sentidos** e a
ausência de encolhimento espontâneo em disparos repetidos.

## Homologação manual pendente (com o operador)

Requer deploy `Update => Core`: o botão da Editbar vem da PÁGINA `dashboard-site-toolbar` e o botão
do editor clássico vem do COMPONENTE `html-editor-visual-modal`, ambos lidos do banco.

- Abrir o Painel de Opções de Exibição na Editbar e em `/admin-paginas/`; ligar e desligar os dois
  toggles; recarregar e conferir que a escolha persiste.
- Com a Barra de Navegação ligada, conferir o rótulo na coluna de 20%, o breadcrumb e os filhos na de
  80%, e que nada sobrepõe a Editbar.
- Com a Sidebar de CSS ligada, conferir o encaixe abaixo da barra, a digitação de valores manuais, o
  autocomplete de classes, o agrupamento por variante, as classes customizadas, o CSS inline e o
  inspetor de estilos computados.
- Copiar um elemento A, selecionar B e clicar em Substituir: B deve virar A e o novo bloco deve
  ficar selecionado. Salvar e conferir que o HTML persistido não contém a UI dos painéis.
- Repetir em `/en/` conferindo os textos em inglês.
- (Rodada 2) Com os dois painéis desligados, selecionar um elemento e confirmar que NADA flutua além
  da barra de ações; alternar a ancoragem da sidebar (esquerda/direita) e da barra (topo/base) pelo
  botão de setas, conferindo o encaixe nos quatro arranjos; fechar cada painel pelo ✕ e conferir que
  o toggle correspondente aparece desmarcado ao reabrir as Opções de Exibição; abrir cada painel
  (Opções, "+", Backups, Modelos, IA, Código Customizado) e clicar em qualquer ponto da Editbar,
  conferindo que fecha; conferir que clicar DENTRO do painel não o fecha.
- (Rodada 3) Editar o CSS inline pelo CodeMirror da sidebar (aplicar pelo botão e saindo do campo);
  no Assistente IA, arrastar o canto inferior direito e conferir que o `#c2f-ai-status` continua
  visível, e abrir a aba "Modo" conferindo que o editor já nasce na altura certa; trocar
  desktop/tablet/mobile e conferir que a Sidebar e a Barra de Navegação permanecem na tela.

Nenhum `git commit`/`git push` foi executado.
