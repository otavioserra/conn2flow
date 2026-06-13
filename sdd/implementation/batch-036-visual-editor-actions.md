# BATCH-036 - Copiar/Colar e Embrulhar Elementos

## Escopo do Lote
Este lote implementa as operações de Copiar, Colar e Embrulhar (Wrap) na barra de ferramentas do editor visual no iframe (`html-editor.js`).

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | UI do Toolbar: Adicionar botões e ícones de Copiar, Colar e Embrulhar (`html-editor.js`) | complete | `node --check` OK |
| 2 | Copiar/Colar: Lógica do Clipboard e inserção como irmão (`html-editor.js`) | complete | `node --check` OK |
| 3 | Teclado: Listeners de Ctrl+C e Ctrl+V no iframe e encaminhamento do pai (`html-editor.js`) | complete | `node --check` OK |
| 4 | Embrulhar Elemento: Popup de tags, substituição do DOM e foco preservado (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. UI do Toolbar (`html-editor.js`)
- [x] Adicionar os botões HTML na barra flutuante `#html-editor-floating-toolbar`:
  - Botão Copiar (`.he-tb-copy`, ícone `copy`).
  - Botão Colar (`.he-tb-paste`, ícone `paste`).
  - Botão Embrulhar (`.he-tb-wrap`, ícone `box` ou `file archive`).
- [x] Ocultar ou desabilitar o botão Colar por padrão, tornando-o visível/ativo somente quando `this.clipboardElement` contiver um clone válido.
- [x] Injetar os estilos para o popup/menu de tags de embrulho `.he-wrap-menu` em `injectStyles()`.

### 2. Lógica de Copiar/Colar (`html-editor.js`)
- [x] Criar a propriedade `this.clipboardElement = null` no construtor.
- [x] Criar o método `copySelected()`:
  - Clona profundamente o elemento selecionado: `this.clipboardElement = this.selectedElement.cloneNode(true)`.
  - Atualiza a visibilidade/estado do botão Colar na toolbar.
- [x] Criar o método `pasteSelected()`:
  - Se não houver `this.clipboardElement`, abortar.
  - Clonar o elemento do clipboard: `const clone = this.clipboardElement.cloneNode(true)`.
  - Inserir o clone como irmão adjacente do elemento selecionado atual: `this.selectedElement.parentNode.insertBefore(clone, this.selectedElement.nextSibling)`.
  - Selecionar o clone e disparar `afterDomMutation()`.

### 3. Atalhos de Teclado & postMessage (`html-editor.js` & `html-editor-visual-controls.js`)
- [x] Adicionar listeners de teclado para `Ctrl + C` e `Ctrl + V` no iframe (`html-editor.js`):
  - Capturar atalhos quando não houver inputs focados.
  - Chamar `copySelected()` ou `pasteSelected()`.
- [x] Sincronizar atalhos na janela pai (`html-editor-visual-controls.js`) para detectar `Ctrl + C` e `Ctrl + V` e enviá-los via `postMessage` ao iframe.
- [x] Tratar as mensagens `c2f-he:copy` e `c2f-he:paste` na escuta de mensagens do iframe.

### 4. Lógica de Embrulhar Elemento (`html-editor.js`)
- [x] No clique do botão Embrulhar, abrir um menu de tags (`div`, `section`, `a`, `p`, `article`, `aside`) próximo à toolbar.
- [x] Ao clicar em uma tag:
  - Criar o novo contêiner: `const wrapper = document.createElement(tag)`.
  - Substituir o elemento selecionado pelo wrapper: `el.parentNode.replaceChild(wrapper, el)`.
  - Anexar o elemento selecionado original como filho do wrapper: `wrapper.appendChild(el)`.
  - Manter a seleção focada no elemento original (`el`).
  - Chamar `afterDomMutation()`.

---

## Evidência de Validação (BATCH-036) — 2026-06-13

- Validação estática: `node --check` OK em `gestor/assets/interface/html-editor.js` e `html-editor-visual-controls.js`.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor.js`:
    - Toolbar: novos botões `.he-tb-copy` (ícone `copy`), `.he-tb-paste` (ícone `paste`, oculto até haver clipboard) e `.he-tb-wrap` (ícone `box`); o botão Duplicar passou a usar o ícone `clone` para diferenciar de Copiar. Novo popup `#html-editor-wrap-menu` (tags `div`/`section`/`a`/`p`/`article`/`aside`) + estilos `.he-wrap-menu`.
    - Clipboard interno `this.clipboardElement`; métodos `copySelected()`, `pasteSelected()`, `updatePasteButton()` (mostra/oculta Colar e reposiciona a toolbar à direita), `toggleWrapMenu()`/`openWrapMenu()`/`closeWrapMenu()` e `wrapSelected(tag)` (replaceChild + appendChild, mantendo a seleção no elemento original).
    - Atalhos no iframe: `Ctrl/Cmd+C` (copia, só quando não há seleção de texto ativa — preserva a cópia nativa) e `Ctrl/Cmd+V` (cola); mensagens `c2f-he:copy`/`c2f-he:paste` no bus; `#html-editor-wrap-menu` registrado em `isEditorOwned`/`extractUserHtml`; fechamento do menu ao clicar fora, em `selectElement`/`clearSelection`/`hideChrome`/Esc.
  - `gestor/assets/interface/html-editor-visual-controls.js`: ações `ACT.COPY`/`ACT.PASTE` e atalhos `Ctrl+C`/`Ctrl+V` na janela pai (com guarda de input focado e seleção de texto), encaminhados por `postMessage` ao iframe.
- Decisão: [DEC-050](../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` + validação runtime:
  - Copiar (botão/Ctrl+C) habilita o botão Colar; Colar (botão/Ctrl+V) insere o clone como irmão inferior do alvo, repetível em vários alvos.
  - Embrulhar abre o popup de tags; escolher uma envolve o elemento e mantém a seleção no original (breadcrumb passa a mostrar o novo contêiner pai).
