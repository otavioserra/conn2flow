# BATCH-037 - Painel Auxiliar de Formatação Visual (Tailwind UI Helper)

## Escopo do Lote
Este lote adiciona uma coluna de auxílio de design visual no editor de classes Tailwind CSS (`#html-editor-tailwind-styler`) para aplicar alinhamento, espaçamento, bordas e cores de forma amigável no editor visual (`html-editor.js`).

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Estilização de Duas Colunas: Ajuste do CSS do Styler e criação do painel visual (`html-editor.js`) | complete | `node --check` OK |
| 2 | Controles de Alinhamento e Espaçamento: Lógica de adição/mutação de classes (`html-editor.js`) | complete | `node --check` OK |
| 3 | Controles de Cores (Texto e Fundo): Paletas interativas e limpeza de conflitos (`html-editor.js`) | complete | `node --check` OK |
| 4 | Sincronização de Estado: Atualização automática dos botões no clique e na seleção (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. Estilização de Duas Colunas (`html-editor.js`)
- [x] Atualizar o markup inicial de `#html-editor-tailwind-styler` em `createToolbar()` para conter uma estrutura flex de duas colunas:
  - `.he-styler-col-left` (editor de classes com datalist).
  - `.he-styler-col-right` (painel de formatação visual).
- [x] Injetar as regras CSS em `injectStyles()`:
  - Definir estrutura flex/grid para as duas colunas.
  - Criar estilos para grupos de botões `.he-helper-group`, linhas e títulos.
  - Estilizar botões de cores (círculos de cores com borda sutil) e botões de ícone (Fomantic UI ou classes compactas).
  - Estilizar a classe `.active` dos botões para indicar aplicação de classe ativa (ex: borda azul forte ou fundo destacado).
  - Criar a classe de empilhamento `.he-styler-stacked` que aplica `flex-direction: column` e ajusta espaçamentos para exibição vertical de ambas as colunas.
- [x] No método `updateSelectionUI()` (ou no `renderStyler()`), ler a largura do elemento selecionado (`rect.width`) e adicionar a classe `.he-styler-stacked` se a largura for inferior a 400px (removendo-a caso contrário).


### 2. Controles de Alinhamento e Espaçamento (`html-editor.js`)
- [x] Desenhar controles para:
  - Alinhamento: `text-left`, `text-center`, `text-right`, `text-justify`.
  - Padding: `p-0`, `p-2`, `p-4`, `p-8` (ou equivalentes).
  - Borders: `rounded-none`, `rounded-sm`, `rounded-lg`, `rounded-full`.
- [x] Implementar a lógica de grupo mutuamente exclusivo: ao aplicar uma classe de alinhamento, remover as outras três classes do mesmo grupo que existam no elemento.
- [x] No clique do botão, atualizar a classe e acionar `afterDomMutation()`.

### 3. Controles de Cores (`html-editor.js`)
- [x] Desenhar botões redondos de cores para Texto e Fundo.
- [x] Implementar a substituição inteligente de cores:
  - Limpar qualquer classe com prefixo `text-[cor]` ou `bg-[cor]` antes de aplicar a nova seleção.
  - Exceção para classes de sistema do editor ou outras não relacionadas à paleta padrão, se aplicável (focar nas classes da paleta: gray, red, blue, green, yellow, purple, black, white, transparent).

### 4. Sincronização de Estado (`html-editor.js`)
- [x] No método `renderStyler(element)`:
  - Ler todas as classes presentes no elemento selecionado.
  - Para cada grupo de controles (alinhamento, padding, bordas, cores), verificar qual classe está presente.
  - Adicionar a classe `.active` no botão correspondente da interface visual do painel.
  - Se nenhuma classe do grupo estiver presente, destacar a opção padrão (ex: `text-left` para alinhamento ou `bg-transparent`/`rounded-none`).

---

## Evidência de Validação (BATCH-037) — 2026-06-13

- Validação estática: `node --check gestor/assets/interface/html-editor.js` → OK.
- Arquivos alterados: apenas `gestor/assets/interface/html-editor.js`.
  - `createToolbar()`: styler reestruturado em `.he-styler-cols` (`.he-styler-col-left` com tags+input; `.he-styler-col-right` com o painel visual gerado por `buildHelperPanelHtml()`) + delegação de clique nos botões do painel.
  - `injectStyles()`: estilos das 2 colunas, `.he-helper-group/-title/-row`, `.he-helper-btn`/`.he-helper-btn.active`, `.he-helper-color`/`.active`, `.he-color-transparent` (xadrez) e `.he-styler-stacked` (empilhamento vertical).
  - Config declarativa `tailwindHelperConfig()` (5 grupos): alinhamento (`text-left/center/right/justify`, ícones), padding (`p-0/2/4/8`), bordas (`rounded-none/sm/lg/full`), cor do texto (8 cores) e fundo (8 cores). Limpeza de conflitos por **lista fechada do grupo** + **regex** específica de cor (preserva `text-left`/`text-lg` ao trocar cor de texto) e de `p-`/`rounded-`.
  - `applyHelperClass(group, cls)`: aplica a classe, remove conflitantes, re-renderiza o styler e dispara `afterDomMutation()`.
  - `syncHelperButtons(element)`: marca `.active` por grupo conforme a classe presente, com destaque de padrão (`text-left`/`rounded-none`/`bg-transparent`) quando nada do grupo está aplicado. Chamado dentro de `renderStyler()`.
  - `updateSelectionUI()`: alterna `.he-styler-stacked` quando `rect.width < 400`.
- Decisão: [DEC-051](../decisions/DECISION-LOG.md).
- Pendência (operador): deploy `🗃️ Projects - Update => Core` + validação runtime no navegador:
  - Painel de duas colunas no styler; empilha verticalmente em elementos estreitos (<400px).
  - Botões de alinhamento/padding/bordas aplicam classe exclusiva; paletas de cor trocam `text-*`/`bg-*` sem afetar alinhamento/tamanho.
  - Botões refletem o estado atual ao selecionar o elemento; clique atualiza as tags da coluna esquerda e o histórico (undo/redo).
