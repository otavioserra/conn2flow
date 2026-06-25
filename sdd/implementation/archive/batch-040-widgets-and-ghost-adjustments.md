# BATCH-040 - Ajustes Finais no Pré-visualizador de Widgets e Elemento Fantasma do Cursor

## Escopo do Lote
Este lote realiza ajustes finos de usabilidade e fidelidade visual no Editor HTML Visual:
1. Renderização real e dinâmica dos widgets no pré-visualizador da página pai (`#iframe-visualizacao-pagina`) ao voltar da edição visual.
2. Pré-visualização do elemento ou widget real sendo inserido (em vez de marcador sintético) acompanhando o cursor do mouse (`#html-editor-insert-ghost`).

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Renderização dinâmica de widgets no pré-visualizador (`html-editor-interface.js`) | complete | `node --check` OK |
| 2 | Elemento/Widget real no cursor fantasma e estilização do ghost container (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. Renderização de Widgets no Pré-visualizador (`html-editor-interface.js`)
- [x] Modificar a rotina `previewHtmlConteudo()` para injetar um script utilitário de inicialização no cabeçalho ou corpo do documento gerado para o iframe `#iframe-visualizacao-pagina`.
- [x] No script injetado, após a carga do DOM (`DOMContentLoaded` ou `$(document).ready`), varrer recursivamente os comentários de widget no corpo do HTML:
  - Expressão regular para abertura: `/^\s*widgets#(.+?)\s*<\s*$/i`.
  - Expressão regular para fechamento: `/^\s*widgets#\s*(.+?)\s*>\s*$/i`.
- [x] Para cada comentário de abertura identificado, localizar o comentário de fechamento correspondente com a mesma assinatura.
- [x] Substituir o intervalo de elementos compreendido entre os comentários (inclusive os próprios comentários) por um contêiner virtual neutro (ex: `<div class="c2f-preview-widget" style="display: contents;"></div>`).
- [x] Disparar chamadas AJAX assíncronas utilizando a biblioteca jQuery injetada no iframe para obter a renderização de cada widget.
  - A rota AJAX e os parâmetros necessários devem ser consultados síncronamente do escopo global da janela pai (`window.parent.gestor`), utilizando o endpoint `html-editor-widget-render`.
- [x] Ao receber a resposta Ok da requisição, injetar o HTML retornado diretamente dentro do contêiner virtual neutro correspondente, renderizando o widget funcional.

### 2. Elemento Real no Cursor Fantasma (`html-editor.js`)
- [x] Atualizar `createInsertGhost(payload)` para construir o nó DOM real que representa a inserção:
  - Se `payload.kind === 'element'`, invocar `this.buildElement(payload.elementType)` para construir o elemento físico (parágrafo, link, botão, imagem, div, etc.).
  - Se `payload.kind === 'widget'`, invocar `this.buildWidgetWrapper(payload)` para construir o wrapper do widget virtual e imediatamente disparar a chamada de renderização remota `this.requestWidgetRender(innerEl)`.
- [x] Injetar o nó físico construído diretamente como filho de `#html-editor-insert-ghost` em substituição ao rótulo textual sintético (`ghost.textContent = label`).
- [x] Ajustar o CSS de `#html-editor-insert-ghost` em `injectStyles()` para torná-lo um contêiner flutuante elegante:
  - Fundo branco opaco ou semi-transparente de alto contraste (ex: `background: rgba(255,255,255,0.95);`).
  - Borda suave (ex: `border: 1px solid #7c3aed;`).
  - Sombra projetada para dar sensação de flutuação (`box-shadow: 0 4px 12px rgba(0,0,0,0.15);`).
  - Remover restrições de formatação estritas (`white-space: nowrap`, `overflow: hidden`, `padding` pequeno, cor roxa forçada) do contêiner `#html-editor-insert-ghost` que possam comprometer a renderização interna dos elementos HTML injetados.
  - Garantir o estilo `pointer-events: none;` para impedir que o elemento fantasma intercepte eventos de clique ou arraste do cursor.

---

## Evidência de Validação (BATCH-040) — 2026-06-14

- Validação estática: `node --check` OK em `gestor/assets/interface/html-editor-interface.js` e `html-editor.js`.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js`:
    - Nova função autocontida `widgetPreviewBootstrap()` injetada no `srcdoc` do `#iframe-visualizacao-pagina` (via `.toString()` para preservar as regex) nos dois caminhos de `previewHtmlConteudo()` (layout e padrão). No `ready`/`DOMContentLoaded` ela varre os comentários `<!-- widgets#sig < --> ... <!-- widgets#sig > -->`, substitui o intervalo por `<div class="c2f-preview-widget" style="display:contents">` e renderiza cada widget via AJAX `html-editor-widget-render` (rota/credenciais de `window.parent.gestor`, jQuery do próprio iframe).
  - `gestor/assets/interface/html-editor.js`:
    - `createInsertGhost(payload)` passa a construir o **nó real** (`buildElement`/`buildWidgetWrapper`) e anexá-lo ao `#html-editor-insert-ghost`; para widget dispara `requestWidgetRender(node)` (esqueleto segue o cursor já renderizado).
    - CSS do `#html-editor-insert-ghost`: contêiner limpo (fundo branco `rgba(255,255,255,0.95)`, borda sólida roxa, sombra projetada, `max-width:420px`/`max-height:60vh`, `overflow:hidden`); removidas as restrições `white-space:nowrap`/`text-overflow:ellipsis`/`padding` pequeno/cor roxa forçada; `pointer-events:none` no ghost e em todos os descendentes.
- Decisão: [DEC-054](../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` + validação runtime:
  - Voltar do editor visual (`previsualizarVoltar`) e confirmar que os widgets aparecem renderizados no pré-visualizador da página.
  - No modo de inserção, confirmar que o elemento/widget real (renderizado) acompanha o cursor dentro de uma caixa flutuante elegante.
