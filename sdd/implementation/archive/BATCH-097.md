# BATCH-097 - Correções de Homologação do BATCH-096, Edição Avançada Separada e Embeds no Painel "+"

Intake: [req-097.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/archive/req-097.md)
Decisão: DEC-093
Validação: VALIDATION-CHECKLIST.md#batch-097
Evidência do defeito: [temp/html-output.html](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/temp/html-output.html)

## Diagnóstico da evidência (Fix 1)

No HTML de saída há **dois** `<object>`: um dentro de `#c2f-page-content` (`width:500px`) e outro **fora** dele,
como último filho de `#c2f-layout-root` (`width:100%`). Ou seja, uma das cópias foi salva no **LAYOUT**.

Causa-raiz: no Live Editor o `contentRoot` do motor é `#c2f-layout-root` (a edição cobre layout + conteúdo).
`insertCustomHtml()` — e a inserção do painel "+" quando não há alvo — usa `contentRoot.appendChild()` como
fallback, então o elemento nasce **fora** do `#c2f-page-content` e o `performSave` o grava em `layouts.html`.
Como o layout é compartilhado por todas as páginas **e pela própria página da Editbar** (`dashboard-site-toolbar`,
renderizada dentro do iframe da barra), o mesmo embed passa a aparecer 2–3 vezes na tela e "vaza" para dentro
da barra.

## Fatiamento

### PARTE I — Correções (prioridade alta)

1. **Fix 1 — duplicação/vazamento**:
   - `insertionRoot()`: quando existe `#c2f-page-content` dentro da raiz editável, toda inserção sem alvo vai
     para o CONTEÚDO da página, nunca para a raiz do layout.
   - `isEditorOwned` reconhece os elementos de sistema do Live Editor (`#c2f-site-toolbar`, `#c2f-device-preview`,
     `#c2f-save-loader`), o que blinda de uma só vez `wrapEmbeds`, `resolveEditable`, `findEditableFromPoint` e
     `extractUserHtml`/`isUserContentNode`.
   - Extração idempotente: `extractUserHtml` limpa o host do PDF.js (canvas/toolbar renderizados + atributo de
     prontidão) e remove resíduos órfãos de invólucro (escudo/badge/alças) que tenham sido persistidos.
2. **Fix 2 — interatividade no site publicado**: escudo e `pointer-events` passam a existir SÓ enquanto o editor
   está habilitado (`disable()` desembrulha o DOM vivo, `enable()` re-envolve). O markup persistido dos embeds
   nasce com `position:relative;z-index:1` para não ficar sob camadas decorativas absolutas do template.
3. **Fix 3 — z-index**: `#c2f-he-embed-modal` em `1000000`; overlay do seletor de arquivos em `1000060`.
4. **Fix 4 — preview ao vivo do Motor B**: `ensurePdfViewer()` carrega `interface/pdf-viewer.js` sob demanda e
   dispara `conn2flowPdfViewerInit()` ao aplicar o modal, ao envolver um contêiner novo e no `applyState`.

### PARTE II — Usabilidade

5. **Dropdown "Página"**: "Editar Avançado" sempre aponta para `/admin-paginas/`; novo "Editar Publicação
   Avançado" (bloco `<!-- dropdown-page-publisher -->`, removido por `modelo_tag_del` quando não há publicação)
   aponta para `/publisher-pages/editar/?id=…&publisher_id=…`.
6. **Painel "+"**: 5 tipos de embed (`object`, `iframe`, `embed`, `video`, `audio`) nas duas listas de elementos
   (Live Editor e editor clássico); ao inserir, o motor envolve e abre o modal já na aba adequada.

## Validação

- `php -l` (`dashboard.php`, `html-editor.php`), `node --check` (`html-editor.js`, `dashboard.toolbar.js`,
  `pdf-viewer.js`, `html-editor-visual-controls.js`).
- Vitest: casos novos em `tests/Unit/JS/html-editor-embed.test.js` (destino da inserção, isolamento da toolbar,
  idempotência do save, desembrulho no `disable`, tipos novos do painel).
- PHPUnit sem regressão.
