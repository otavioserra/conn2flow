# BATCH-096 - Mapeamento Visual de Embeds, Proteção de Eventos, Suporte Híbrido a PDF e Modal Estruturado

Intake: [req-096.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/archive/req-096.md)
Decisão: DEC-092
Validação: VALIDATION-CHECKLIST.md#batch-096

## Objetivo

Estender a manipulação visual do Editor HTML (motor `gestor/assets/interface/html-editor.js`, usado tanto
no Editor Visual clássico quanto no Live Editor) para elementos de mídia/documento embutidos
(`<object>`, `<iframe>`, `<embed>`, `<video>`, `<audio>`), resolvendo o *event swallowing* e oferecendo
3 estratégias de renderização de PDF escolhidas visualmente.

## Fatiamento

### Fase 1 — Invólucro atômico e proteção de eventos (motor)

- `config.embedTags` = `object|iframe|embed|video|audio` (+ contêiner `.conn2flow-pdfjs`).
- `wrapEmbeds()` envolve cada elemento em `.conn2flow-embed-wrapper` com:
  - badge `.conn2flow-embed-label` (tipo detectado);
  - escudo `.c2f-embed-shield` (camada transparente que captura o ponteiro no lugar do documento embutido);
  - 4 alças `.c2f-embed-handle` (drag-resize nos cantos);
  - `.conn2flow-embed-inner` com `pointer-events:none` no conteúdo embutido.
- `resolveEditable` trata o wrapper como bloco atômico; `getEditType` devolve `embed`.
- Duplo clique no escudo abre o modal na aba correspondente ao tipo.
- `extractUserHtml` remove SEMPRE o invólucro (inclusive nos snapshots de undo/redo), persistindo só a
  tag limpa; `applyState`/`afterDomMutation` re-envolvem (idempotente).

### Fase 2 — Modal `#c2f-he-embed-modal` (4 abas) e motores de PDF

- Aba 1: fonte (`src`/`data`) + seletor de arquivo do servidor (`_html-editor-imagepick-btn` → admin-arquivos,
  aceitando PDF/vídeo/documento), largura/altura com unidade (`px`/`%`/`vh`) e `title`.
- Aba 2 (PDF): 3 motores — `native` (`<object>` + fallback amigável), `pdfjs` (contêiner `.conn2flow-pdfjs`)
  e `google` (iframe do Google Docs Viewer), com parâmetros do leitor PDF.js.
- Aba 3: `allowfullscreen`, `sandbox`, `controls`, `autoplay`, `loop`, `muted`, `poster`.
- Aba 4: gerenciador dinâmico de `<param>`, HTML de fallback customizado, estilos inline e classes.
- Detecção automática de PDF ao escolher arquivo `.pdf` ou colar link `.pdf`.

### Fase 3 — Runtime PDF.js e injeção de assets

- Novo asset de core `gestor/assets/interface/pdf-viewer.js` (renderiza `.conn2flow-pdfjs` em canvas,
  com barra de ferramentas, zoom, página inicial e modo de rolagem; auto-carrega `pdf.js` da CDN).
- Core: `gestor_pdf_viewer_detectar()`/`gestor_pdf_viewer_assets()` (helpers puros na biblioteca `gestor`)
  + `gestor_pagina_pdf_viewer()` no pipeline de página (`gestor/gestor.php`), após os widgets.
- Editor clássico: `montarPdfViewerHead()` injeta os assets no `srcdoc` do preview e do editor visual.

### Fase 4 — Validação

- `php -l`, `node --check`, Vitest (novo `html-editor-embed.test.js`), PHPUnit (novo teste do detector).

## Riscos e mitigação

- **Persistência suja**: o invólucro é UI de runtime — a remoção acontece em `extractUserHtml` (única
  porta de saída de HTML do motor), garantindo que snapshot e save recebam a tag limpa.
- **Regressão no Live Editor**: o `reconstructOriginal` da Editbar continua vendo apenas variáveis e
  widgets (o motor entrega HTML já sem invólucros).
- **Dimensões**: aplicadas por `style` (com unidade) e atributos `width`/`height` numéricos removidos para
  não conflitar.
