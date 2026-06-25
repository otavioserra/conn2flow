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
| 1 | Estilização & UI: Preparação dos novos contêineres e estilos CSS no iframe (`html-editor.js`) | complete | `node --check` OK |
| 2 | Toolbar à direita: Ajuste na lógica de posicionamento do toolbar (`html-editor.js`) | complete | `node --check` OK |
| 3 | Seletor de Filhos: Lógica de renderização, hover e clique dos filhos (`html-editor.js`) | complete | `node --check` OK |
| 4 | Empilhamento dinâmico: Correção do cálculo do topo cumulativo dos overlays (`html-editor.js`) | complete | `node --check` OK |
| 5 | Destaque de Hover: Implementação do overlay de hover roxo tracejado nos breadcrumbs (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. Estilização & UI (`html-editor.js`)
- [x] Adicionar `#html-editor-selection-children` e `#html-editor-breadcrumb-hover-overlay` na função `createOverlays()`.
- [x] Injetar as regras CSS em `injectStyles()`:
  - Definir estilo para `#html-editor-selection-children` (similar ao breadcrumb, mas com fundo contrastante ligeiramente mais claro, ex: `#1f2937` ou similar).
  - Incluir estilos para as labels `.he-crumb-label` (cor cinza mais clara, largura fixa ou margem à direita para alinhamento).
  - Configurar separador `/` entre os filhos com a classe `.he-child-sep`.
  - Definir estilo do `#html-editor-breadcrumb-hover-overlay`: borda tracejada `dashed 2px rgba(124,58,237,0.95)`, fundo roxo bem claro transparente `rgba(124,58,237,0.06)`, z-index similar ao do hover.

### 2. Posicionamento da Barra de Ferramentas (`html-editor.js`)
- [x] No método `updateSelectionUI()`, ajustar a propriedade `left` da toolbar para se alinhar ao canto direito do contêiner selecionado:
  - `left = (rect.right + scrollLeft - toolbar.offsetWidth) + 'px'`.
  - Garantir que não estoure a margem esquerda se o contêiner for muito pequeno.

### 3. Mecanismo de Seleção de Filhos (`html-editor.js`)
- [x] Criar a função `renderChildren(element)` que:
  - Limpa o contêiner de filhos.
  - Varre os filhos diretos do elemento (`element.children`).
  - Descarta aqueles ignorados (`ignoredTags`) ou de controle do sistema (`isEditorOwned`).
  - Se houver filhos editáveis válidos, prefixa o contêiner com a label "Filhos:".
  - Para cada filho, adiciona um span interativo `.he-crumb-child` com a formatação: tag + ID/Classe.
  - Implementa listeners de `mouseenter` (ativa o `#html-editor-breadcrumb-hover-overlay` no respectivo elemento), `mouseleave` (esconde o overlay roxo tracejado) e `click` (seleciona o filho).
  - Se nenhum filho editável for encontrado, oculta o contêiner de filhos.

### 4. Empilhamento e Labels no Breadcrumb de Ancestrais (`html-editor.js`)
- [x] Atualizar o método `renderBreadcrumb(element)` para:
  - Injetar o label prefixado **"Ancestrais:"** antes de renderizar os itens do caminho.
  - Configurar os listeners de `mouseenter` nos itens de ancestrais para ativar o `#html-editor-breadcrumb-hover-overlay` (em vez do hover azul `#html-editor-hover-overlay`) e `mouseleave` para ocultá-lo.
- [x] No método `updateSelectionUI()`:
  - Posicionar ancestrais (`#html-editor-selection-breadcrumb`) no topo inferior: `rect.bottom + scrollTop`.
  - Se a barra de filhos estiver visível, posicioná-la logo abaixo de ancestrais: `top = rect.bottom + scrollTop + breadcrumb.offsetHeight`.
  - Posicionar o styler de classes Tailwind (`#html-editor-tailwind-styler`) dinamicamente abaixo dos elementos anteriores, somando a altura de ancestrais e filhos se estiverem ativos.

---

## Evidência de Validação (BATCH-035) — 2026-06-13

- Validação estática: `node --check gestor/assets/interface/html-editor.js` → OK; `node --check gestor/assets/interface/html-editor-interface.js` → OK.
- Arquivos alterados (todos no editor do iframe + 1 ajuste no orquestrador):
  - `gestor/assets/interface/html-editor.js`:
    - `createOverlays()`: novos `#html-editor-selection-children` e `#html-editor-breadcrumb-hover-overlay`.
    - `injectStyles()`: estilos do seletor de filhos (fundo `#1f2937`, mais claro que os ancestrais `#111827`), labels `.he-crumb-label`, separador `.he-child-sep` (`|`), e overlay roxo tracejado `dashed 2px rgba(124,58,237,0.95)`.
    - `updateSelectionUI()`: toolbar ancorada à **borda direita** (`rect.right - offsetWidth`, com clamp à esquerda); empilhamento cumulativo ancestrais → filhos → styler (soma de `offsetHeight`).
    - `renderBreadcrumb()`: label **"Ancestrais:"** + hover roxo (`showBreadcrumbHover`/`hideBreadcrumbHover`) no lugar do azul.
    - **Novo** `renderChildren()`: filhos diretos editáveis (descarta `ignoredTags`/`isEditorOwned` e o caso de widget atômico), label **"Filhos:"**, separador `|`, hover roxo e clique para selecionar; oculta-se se não houver filhos.
    - `onHoverMove()`: guarda para não desenhar o hover azul quando o cursor está sobre o chrome do editor (evita conflito com o hover roxo dos breadcrumbs).
    - `isEditorOwned()`, `hideChrome()`, `clearSelection()` e `extractUserHtml()` atualizados com os 2 novos ids.
  - `gestor/assets/interface/html-editor-interface.js`: `sistemaSel` (fallback do save) inclui os 2 novos ids.
- Decisão: [DEC-049](../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` (sincroniza o asset) + validação runtime no navegador:
  - Toolbar aparece no canto **superior direito** do elemento selecionado.
  - Breadcrumb "Ancestrais:" e barra "Filhos:" empilhados abaixo do elemento (filhos com fundo mais claro, separador `|`).
  - Hover sobre itens de ambos os breadcrumbs desenha caixa **roxa tracejada** no elemento correspondente (independente do hover azul direto no preview).
  - Clicar num filho/ancestral transfere a seleção; barra de filhos some quando o elemento não tem filhos editáveis.

