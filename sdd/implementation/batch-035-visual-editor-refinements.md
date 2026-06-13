# BATCH-035 - Refinamentos e Ajustes no Editor HTML Visual

## Escopo do Lote
Este lote refina os controles de layout do Editor HTML Visual (janela do iframe, `html-editor.js`).
1. Move a barra de ferramentas flutuante (`#html-editor-floating-toolbar`) da esquerda para a direita do contêiner selecionado.
2. Adiciona o seletor de filhos (`#html-editor-selection-children`) entre o breadcrumb de ancestrais e o editor de classes Tailwind CSS, adicionando labels identificativos "Ancestrais:" e "Filhos:" em ambos e utilizando um fundo contrastante para diferenciá-los.

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Estilização & UI: Preparação dos novos contêineres e estilos CSS no iframe (`html-editor.js`) | pending | `node --check` |
| 2 | Toolbar à direita: Ajuste na lógica de posicionamento do toolbar (`html-editor.js`) | pending | `node --check` |
| 3 | Seletor de Filhos: Lógica de renderização, hover e clique dos filhos (`html-editor.js`) | pending | `node --check` |
| 4 | Empilhamento dinâmico: Correção do cálculo do topo cumulativo dos overlays (`html-editor.js`) | pending | `node --check` |

---

## Checklist de Implementação

### 1. Estilização & UI (`html-editor.js`)
- [ ] Adicionar `#html-editor-selection-children` na função `createOverlays()`.
- [ ] Injetar as regras CSS em `injectStyles()`:
  - Definir estilo para `#html-editor-selection-children` (similar ao breadcrumb, mas com fundo contrastante ligeiramente mais claro, ex: `#1f2937` ou similar).
  - Incluir estilos para as labels `.he-crumb-label` (cor cinza mais clara, largura fixa ou margem à direita para alinhamento).
  - Configurar separador `/` entre os filhos com a classe `.he-child-sep`.

### 2. Posicionamento da Barra de Ferramentas (`html-editor.js`)
- [ ] No método `updateSelectionUI()`, ajustar a propriedade `left` da toolbar para se alinhar ao canto direito do contêiner selecionado:
  - `left = (rect.right + scrollLeft - toolbar.offsetWidth) + 'px'`.
  - Garantir que não estoure a margem esquerda se o contêiner for muito pequeno.

### 3. Mecanismo de Seleção de Filhos (`html-editor.js`)
- [ ] Criar a função `renderChildren(element)` que:
  - Limpa o contêiner de filhos.
  - Varre os filhos diretos do elemento (`element.children`).
  - Descarta aqueles ignorados (`ignoredTags`) ou de controle do sistema (`isEditorOwned`).
  - Se houver filhos editáveis válidos, prefixa o contêiner com a label "Filhos:".
  - Para cada filho, adiciona um span interativo `.he-crumb-child` com a formatação: tag + ID/Classe.
  - Implementa listeners de `mouseenter` (ativa hover overlay no respectivo elemento), `mouseleave` (esconde hover) e `click` (seleciona o filho).
  - Se nenhum filho editável for encontrado, oculta o contêiner de filhos.

### 4. Empilhamento e Labels no Breadcrumb de Ancestrais (`html-editor.js`)
- [ ] Atualizar o método `renderBreadcrumb(element)` para injetar o label prefixado **"Ancestrais:"** antes de renderizar os itens do caminho.
- [ ] No método `updateSelectionUI()`:
  - Posicionar ancestrais (`#html-editor-selection-breadcrumb`) no topo inferior: `rect.bottom + scrollTop`.
  - Se a barra de filhos estiver visível, posicioná-la logo abaixo de ancestrais: `top = rect.bottom + scrollTop + breadcrumb.offsetHeight`.
  - Posicionar o styler de classes Tailwind (`#html-editor-tailwind-styler`) dinamicamente abaixo dos elementos anteriores, somando a altura de ancestrais e filhos se estiverem ativos.
