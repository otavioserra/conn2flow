# BATCH-078 — Correções no Live Editor: Trava de Widgets, Submenu do Painel "+" e Isolamento de Estilos

- **Intake**: [req-078](../human-requests/req-078.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-078](../validation/VALIDATION-CHECKLIST.md#batch-078)
- **Decisão**: [DEC-080](../decisions/DECISION-LOG.md#dec-080---2026-07-10---accepted)
- **Base**: rodada de correções visuais/interação pós-homologação do BATCH-075/BATCH-077 (Dashboard Site Toolbar / Live Editor).

## Escopo (3 correções, motor compartilhado `html-editor.js`)

O Live Editor reutiliza o editor visual admin (`html-editor.js`) escopado a `contentRoot`
na própria página hospedeira (não num iframe). As três correções são pontuais nesse motor
mais o cache-bust.

### Correção 1 — Travar edição/seleção de elementos internos de widgets
No live editor os widgets renderizados são envolvidos por `.c2f-widget-box`/`.c2f-dyn-box`
(mapeamento do BATCH-077), **não** por `.conn2flow-widget-wrapper`. Logo, a proteção atômica
do editor clássico não os alcançava e o conteúdo interno (texto/imagens/links do widget)
continuava editável/selecionável.

- **`resolveEditable`**: passa a reconhecer `.c2f-dyn-box` como bloco atômico
  (`element.closest('.c2f-dyn-box')` → retorna a caixa), paridade com o `.conn2flow-widget-wrapper`.
- **CSS (`injectStyles`)**: `.c2f-dyn-box{user-select:none}` e `.c2f-dyn-box *{pointer-events:none!important;
  user-select:none!important}` deixam o conteúdo interno inerte a clique/seleção; o usuário só
  interage com a caixa externa.

### Correção 2 — Painel "+" expande a categoria sem fechar/roubar o clique
Causa-raiz: `#c2f-add-panel` **não** estava em `isEditorOwned`. O listener de clique em *capture*
do `html-editor.js` usa `elementsFromPoint`, atravessa o painel, encontra o conteúdo por trás,
seleciona-o e faz `stopPropagation()` — matando o `toggleWidgetGroup` do painel.

- **`isEditorOwned`**: reconhece `#c2f-add-panel` e `#c2f-backup-panel` (via `closest`), dando
  early-return no listener de clique/hover — o clique propaga normal para o handler do painel
  (o `toggleWidgetGroup` do `dashboard.toolbar.js` já fazia o toggle correto).

### Correção 3 — Blindagem de CSS dos controles injetados na página hospedeira
As toolbars/styler/modal do editor herdavam CSS do template do site (ícones apagados no
floating toolbar; `textarea`/`input` do modal com texto ilegível; styler desconfigurado).

- **CSS (`injectStyles`)**: bloco de blindagem com `!important` forçando `color`/`background`/
  `-webkit-text-fill-color`/`font`/`caret-color` em `#html-editor-floating-toolbar .he-tb-btn`
  (+ ícones e o `he-tb-deselect` rosa), `#html-editor-tailwind-styler` (+ seus `input`) e
  `#html-editor-modal textarea,input[type="text"],label`. O `!important` no container do styler
  **não** vaza para os filhos com cor própria (herança não propaga `!important`), preservando o
  visual das tags/rótulos coloridos.

### Cache-bust e Versões
- Live: `dashboard.toolbar.js` carrega `interface/html-editor.js?v=c2f1` → `?v=c2f3`.
- Admin: `biblioteca-html-editor` `1.3.30` → `1.4.0` (`html-editor.php`).

## Rodada 2 (R2) — Refinamentos de Mapeamento, Aparência e Ícones SVG
1. **Widgets Sem Wrapper Div (R2-P4)**: A tag `<div>` de wrapper (`.c2f-widget-box`) adicionada no BATCH-077 quebrava regras de herança e seletores estritos do CSS do site hospedeiro. No novo modelo, marcamos os elementos-raiz reais do render do widget com os atributos:
   * `data-c2f-widget-id`: agrupa os elementos pertencentes ao mesmo widget.
   * `data-c2f-widget-root="1"`: sinaliza o nó raiz para a borda/outline.
   * `data-c2f-marker`: carrega o base64 do marcador do banco.
   * `data-widget-type`/`data-widget-slug`: utilizado pelo botão de redirecionamento.
2. **Reconstrução Limpa**: O método `reconstructOriginal` agrupa os elementos por `data-c2f-widget-id`, substitui o primeiro elemento da lista pelo marcador cru decodificado e deleta os nós irmãos correspondentes ao grupo do widget. Assim, o HTML salvo no banco não recebe wrappers indesejados.
3. **Proteção Interna de Widgets (R2-P1)**: `resolveEditable` reconhece `[data-c2f-widget-id]` como bloco atômico. O CSS injetado blinda cliques e seleção nos nós filhos (`[data-c2f-widget-id] * { pointer-events: none!important; user-select: none!important }`).
4. **Outline e Label Flutuante (R2-P1)**: Destaque visual do widget via `outline: 1px dashed #fbbf24!important` e pseudo-elemento `::before` posicionado absolutamente no topo (exibindo "WIDGET: grupo (slug)") sem alterar o layout real (usa `position: relative` no elemento-raiz).
5. **Ícones SVG Autônomos (R2-P3)**: Substituição dos ícones Fomantic `<i class="... icon">` por SVGs inline com `stroke="currentColor"` na toolbar flutuante e no styler, garantindo que renderizem corretamente em páginas públicas.

## Arquivos alterados
- `gestor/assets/interface/html-editor.js` (isEditorOwned, resolveEditable, CSS injectStyles, SVG ícones).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (getComputedStyle robusto, mapTree e reconstructOriginal sem wrapper, cache-bust).
- `gestor/bibliotecas/html-editor.php` (bump da versão da biblioteca).

## Validação
- Estática: `node --check` (3 JS) OK; `php -l` (2 PHP) OK.
- Smoke Test R2: Executado `node _smoke_batch078_r2.mjs` validando a marcação de widgets sem wrapper, anotação fora do widget e salvamento round-trip com **18/18 checks aprovados**.
- Suíte: `composer test` **76/76 testes aprovados** sem regressão.
- Runtime (deploy `Update => Core` + homologação visual das rodadas 1 e 2): **pendente com o operador**.
