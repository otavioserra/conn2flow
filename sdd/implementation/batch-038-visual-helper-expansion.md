# BATCH-038 - Inversão e Expansão do Painel Auxiliar de Formatação Visual

## Escopo do Lote
Este lote inverte a ordem das colunas no styler (Coluna Esquerda = Visual Helper; Coluna Direita = Tags/Input) e adiciona mais 7 grupos de controle visual: Tamanho de Fonte, Peso de Fonte, Margem, Espessura de Borda, Cor de Borda, Opacidade e Display no editor visual (`html-editor.js`).

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Inversão de Colunas: Ajustar markup no JS e CSS (`html-editor.js`) | complete | `node --check` OK |
| 2 | Expansão de Controles I: Margens, Font-Size e Font-Weight (`html-editor.js`) | complete | `node --check` OK |
| 3 | Expansão de Controles II: Border-width, Border-color, Opacidade e Display (`html-editor.js`) | complete | `node --check` OK |
| 4 | Sincronização & Limpeza: Sincronização bidirecional e limpeza de conflitos (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. Inversão de Colunas (`html-editor.js`)
- [x] No construtor ou em `createToolbar()`, inverter a ordem de renderização das colunas:
  - Inserir `.he-styler-col-right` (tags e input) após a renderização da coluna visual.
  - Ajustar o CSS correspondente em `injectStyles()` para manter o alinhamento adequado.
  - Verificar se no empilhamento vertical responsivo (`.he-styler-stacked`) a coluna visual fica acima das tags.
- [x] No método `updateSelectionUI()`, caso a barra de ferramentas flutuante seja posicionada na borda inferior do elemento (quando `tbTop < scrollTop`), adicionar um deslocamento cumulativo (`toolbar.offsetHeight + 12`) ao `stackTop` inicial dos painéis de suporte para que não fiquem cobertos pela toolbar.


### 2. Expansão de Controles I: Margens, Font-Size e Font-Weight (`html-editor.js`)
- [x] Adicionar definições declarativas em `tailwindHelperConfig()` para os novos grupos:
  - Tamanho da Fonte: `text-sm`, `text-base`, `text-lg`, `text-xl`.
  - Peso da Fonte: `font-normal`, `font-medium`, `font-bold`.
  - Margem Externa: `m-0`, `m-2`, `m-4`, `m-8` (mapear de forma mutuamente exclusiva; remover classes conflitantes como `mx-`, `my-`, `mt-`, etc.).
- [x] Atualizar `buildHelperPanelHtml()` para renderizar esses novos seletores com labels claras.

### 3. Expansão de Controles II: Borders, Colors, Opacidade e Display (`html-editor.js`)
- [x] Adicionar definições declarativas em `tailwindHelperConfig()` para os seguintes grupos:
  - Espessura de Borda: `border-0`, `border`, `border-2`, `border-4`.
  - Cor de Borda: Paleta circular com 8 cores (`border-transparent`, `border-gray-300`, `border-red-500`, `border-blue-500`, `border-green-500`, `border-yellow-400`, `border-purple-500`, `border-black`).
  - Opacidade: `opacity-100`, `opacity-75`, `opacity-50`, `opacity-25`.
  - Layout / Display: `block`, `inline-block`, `flex`, `grid`.
- [x] Atualizar `buildHelperPanelHtml()` para renderizar as paletas e botões correspondentes.

### 4. Sincronização & Limpeza (`html-editor.js`)
- [x] Atualizar `applyHelperClass(group, cls)`:
  - Estender a remoção inteligente de classes conflitantes para as novas propriedades (especialmente regex para cores de borda `border-[cor]`, margens `m-` e opacidades `opacity-`).
- [x] Atualizar `syncHelperButtons(element)`:
  - Mapear a leitura das classes vigentes para as novas propriedades.
  - Garantir o comportamento de fallback (marcar o botão padrão correspondente quando nenhuma classe do grupo for encontrada, ex: `text-base` para tamanho de fonte, `opacity-100` para opacidade, `border-0` para espessura de borda).

---

## Evidência de Validação (BATCH-038) — 2026-06-13

- Validação estática: `node --check gestor/assets/interface/html-editor.js` → OK.
- Arquivo alterado: apenas `gestor/assets/interface/html-editor.js`.
  - **Inversão das colunas**: `.he-styler-col-visual` (esquerda, painel visual) + `.he-styler-col-classes` (direita, tags+input); no empilhamento `.he-styler-stacked` o visual fica em cima e as tags abaixo. Binds e `syncHelperButtons` apontam para `.he-styler-col-visual`.
  - **Deslocamento de overlays**: em `updateSelectionUI()`, quando a toolbar é desenhada na borda inferior (`toolbarEmbaixo`), o `stackTop` dos painéis (ancestrais/filhos/styler) recebe `+ toolbar.offsetHeight + 12` para não ficarem sob a barra.
  - **Limpeza estendida** em `applyHelperClass()`: além da lista fechada do grupo e da `cleanRe`, agora há `cleanList` (variantes de palavra isolada: displays, sombras, transform, decoração, flex-dir, justify, items, width). Regexes novas: `fontSize` (`^text-(xs|sm|base|lg|xl|[2-9]xl)$`), `fontWeight`, `margin` (`^m[xytblr]?-\\d+$`, preserva `min-h-*`/`mx-auto`), `borderWidth` (`^border-\\d+$`), `borderColor`, `opacity`, `gap`. Cores de texto/fundo/borda continuam não afetando alinhamento/tamanho nem utilitários (`bg-cover`).
  - **`syncHelperButtons`**: genérico, cobre todos os grupos; defaults destacados quando nada está aplicado.
- **Expansão (liberdade criativa autorizada)**: 20 grupos em 4 seções — **Texto** (Alinhamento, Tamanho, Peso, Caixa, Decoração, Cor), **Layout** (Exibição, Direção flex, Justificar, Alinhar itens, Gap), **Caixa** (Largura, Padding, Margem), **Aparência** (Fundo, Cantos, Borda, Cor da borda, Sombra, Opacidade). Os 7 pedidos + 8 extras (caixa de texto, decoração, direção flex, justify, align-items, gap, largura, sombra). Cabeçalhos de seção (`.he-helper-section`); cores de borda renderizadas como anel (`.he-helper-bordercolor`); styler com `max-height:72vh` + scroll.
- Decisão: [DEC-052](../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` + validação runtime (colunas invertidas; empilhamento <400px; toolbar embaixo não cobre painéis; todos os grupos aplicam/sincronizam sem afetar classes de outros grupos).
