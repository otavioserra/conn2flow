# BATCH-039 - Melhorias e Aprimoramentos do Editor HTML Visual

## Escopo do Lote
Este lote implementa melhorias de navegação e visualização no Editor HTML Visual:
1. Nova seção "Fundo" no styler contendo cor de fundo, seleção de imagem (ImagePicker), repetição, tamanho e posição da imagem de fundo.
2. Botão "Deselecionar" na toolbar flutuante e comportamento alternador (toggle) de seleção ao clicar no elemento ativo.
3. Preservação de rolagem vertical (`scrollTop` do iframe) no histórico de Undo/Redo.
4. Quebra de linha e prevenção de transbordo (wrapping e clamp horizontal) nos breadcrumbs (ancestrais e filhos).
5. Exibição de elemento fantasma (ghost element) seguindo o cursor no modo de inserção.
6. Destaque do contêiner alvo completo com borda tracejada amarela de 4 lados (append highlight) se nenhuma posição em linha for localizada.
7. Renderização do esqueleto HTML de widgets inseridos/editados no editor visual via endpoint AJAX dedicado.

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Seção "Fundo" no Styler: cor de fundo, ImagePicker inline, repeat, size e position (`html-editor.js`, `html-editor-interface.js`) | complete | `node --check` OK |
| 2 | Botão Deselecionar e Toggle de Seleção (`html-editor.js`) | complete | `node --check` OK |
| 3 | Scroll de Undo/Redo e Layout dos Breadcrumbs (`html-editor.js`) | complete | `node --check` OK |
| 4 | Elemento Fantasma, Highlight de Append e Renderização de Widgets (`html-editor.js`, `html-editor.php`, `html-editor-visual-controls.js`) | complete | `node --check` / `php -l` OK |

---

## Checklist de Implementação

### 1. Seção "Fundo" (Background) no Styler (`html-editor.js`)
- [x] Criar a seção "Fundo" no `tailwindHelperConfig()`, posicionando-a imediatamente após a seção "Aparência".
- [x] Migrar o grupo `bgColor` (cor de fundo) de "Aparência" para "Fundo".
- [x] Implementar controles de imagem de fundo (`bgImage`) na nova seção "Fundo":
  - Exibir botão "Selecionar Imagem" (com ícone `folder open`) e botão "Limpar" (com ícone `trash` ou `ban`).
  - Ao clicar em "Selecionar", disparar mensagem `html-editor-imagepick-open` para a janela pai passando `html_editor.imagepick`.
  - Escutar o retorno `html-editor-imagepick-selected` no iframe. Se `imagePickerTarget === 'background'`, definir a imagem selecionada como estilo inline `background-image: url('...')` no elemento ativo.
  - Sincronizar o estado: exibir miniatura de preview se houver imagem de fundo ativa e preencher/limpar o estado adequadamente.
- [x] Adicionar grupos mutuamente exclusivos para propriedades de imagem de fundo no Tailwind:
  - Repetição da imagem: `bg-repeat`, `bg-no-repeat`, `bg-repeat-x`, `bg-repeat-y`.
  - Tamanho da imagem: `bg-auto`, `bg-cover`, `bg-contain`.
  - Posicionamento da imagem: `bg-center`, `bg-top`, `bg-bottom`, `bg-left`, `bg-right`.
- [x] Atualizar `applyHelperClass()` e `syncHelperButtons()` para suportar e limpar os novos grupos.

### 2. Botão Deselecionar e Toggle de Seleção (`html-editor.js`)
- [x] Adicionar o botão "Deselecionar" na barra flutuante `#html-editor-floating-toolbar`.
  - Classe: `.he-tb-deselect`.
  - Ícone: Fomantic UI `times circle` ou `ban`.
  - Estilo visual diferenciado (ex: cor vermelha leve, ou borda destacada para indicar cancelamento).
  - Listener: ao clicar, chamar `clearSelection()`.
- [x] Modificar o listener global de clique no iframe. Se o clique for disparado sobre o elemento atualmente selecionado (`el === this.selectedElement`), deselecioná-lo chamando `clearSelection()`.
- [x] Garantir que o teclado `Esc` continue chamando `clearSelection()` normalmente.

### 3. Scroll de Undo/Redo e Layout dos Breadcrumbs (`html-editor.js`)
- [x] Atualizar a estrutura de snapshots em `undoStack` e `redoStack`. Armazenar objetos no formato `{ html: string, scrollTop: number }`.
- [x] No `pushUndo()`, capturar o `scrollTop` atual do iframe e inseri-lo no snapshot.
- [x] No `undo()` e `redo()`, após aplicar o HTML do estado via `applyState()`, restaurar a posição do scroll do viewport (`window.scrollTo`).
- [x] Atualizar CSS de `#html-editor-selection-breadcrumb` e `#html-editor-selection-children` em `injectStyles()` para suportar quebra de linha (`display: flex; flex-wrap: wrap; white-space: normal;`).
- [x] Em `updateSelectionUI()`, calcular se o breadcrumb transborda o limite direito da janela (`left + offsetWidth > window.innerWidth`). Caso positivo, ajustar a posição `left` (clamp horizontal) para manter todo o breadcrumb dentro do viewport.

### 4. Elemento Fantasma, Highlight de Append e Renderização de Widgets
- [x] **Ghost Element**: No `enterInsertMode()`, instanciar o elemento fantasma `insertGhost` (uma réplica visual com opacidade `0.6` e borda tracejada roxa/cinza, `pointer-events: none`).
  - No `onInsertMove()`, atualizar as coordenadas `top`/`left` do `insertGhost` com offset (ex: 15px) em relação ao cursor.
  - No `exitInsertMode()`, destruir o `insertGhost` do DOM.
- [x] **Highlight de Append**: No DnD/modo de inserção, se não for identificada uma posição física em linha (antes/depois), mas for selecionado um contêiner pai, exibir o overlay `#html-editor-parent-highlight-overlay` (borda amarela tracejada de 4 lados) circundando o contêiner alvo completo.
- [x] **Renderizador de Widgets (PHP)**: Em `html-editor.php`, registrar a opção AJAX `html-editor-widget-render`.
  - Função correspondente: `html_editor_ajax_widget_render()`.
  - Deve incluir `gestor/bibliotecas/widgets.php` e retornar o HTML do widget via `widgets_get(['id' => $signature])` burlado (`$_GESTOR['ajax'] = false`).
- [x] **JS do Widget**: Ao injetar ou converter um widget no iframe, realizar chamada AJAX a `html-editor-widget-render` para recuperar o HTML renderizado do widget e preencher `.conn2flow-widget-inner` do wrapper.

---

## Evidência de Validação (BATCH-039) — 2026-06-14

- Validação estática: `php -l gestor/bibliotecas/html-editor.php` OK; `node --check` OK em `gestor/assets/interface/html-editor.js`, `html-editor-interface.js` e `html-editor-visual-controls.js`.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor.js` (todos os slices no iframe):
    - **Seção "Fundo"**: `bgColor` migrado de "Aparência"; novos grupos `bgRepeat`/`bgSize`/`bgPosition` (cleanList) e controle especial `bgImage` (kind `bgimage`) com botão ImagePicker + Limpar + preview; aplica `background-image` inline; `syncBgImagePreview()` no `renderStyler`; resposta `html-editor-imagepick-selected` tratada quando `imagePickerTarget==='background'`.
    - **Deselecionar/Toggle**: botão `.he-tb-deselect` (ícone `times circle`, destaque vermelho) → `clearSelection()`; clique no elemento já selecionado também deseleciona; `Esc` preservado.
    - **Scroll no histórico**: snapshots `{html, scrollTop}` (`captureSnapshot`/`restoreScroll`); `undo`/`redo` restauram a rolagem após `applyState`.
    - **Breadcrumbs**: `display:flex; flex-wrap:wrap; white-space:normal` + `clampLeft()` (mantém ancestrais/filhos/styler dentro da largura do iframe).
    - **Ghost element**: `createInsertGhost`/`moveInsertGhost`/`removeInsertGhost` (segue o cursor com offset 15px, opacidade 0.6, borda tracejada roxa).
    - **Highlight de contêiner**: overlay `#html-editor-parent-highlight-overlay` (amarelo tracejado 4 lados) via `showDropIndicator`/`insertAtTarget` quando a posição é `inside`.
    - **Render de widgets**: `requestWidgetRender`/`applyWidgetRender`; o mockup original é preservado em `data-widget-mockup` (usado no save), o `.conn2flow-widget-inner` recebe só o preview; re-render em insert/edit/convert e após undo/redo (`rerenderVisibleWidgets`).
  - `gestor/bibliotecas/html-editor.php`: rota AJAX `html-editor-widget-render` → `html_editor_ajax_widget_render()` (valida assinatura `modulo->func(...)`, `gestor_incluir_biblioteca('widgets')`, `widgets_get` em modo page-load).
  - `gestor/assets/interface/html-editor-visual-controls.js`: ponte AJAX (`c2f-he:widget-render` → AJAX → `c2f-he:widget-rendered`).
  - `gestor/assets/interface/html-editor-interface.js`: `sistemaSel` do save com os novos ids de overlay.
- Decisão: [DEC-053](../../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` + validação runtime (seção Fundo + imagem de fundo; deselecionar/toggle; scroll preservado no undo/redo; breadcrumbs quebrando linha; ghost seguindo o cursor; highlight amarelo ao soltar dentro de contêiner; esqueleto de widget renderizado no preview).
