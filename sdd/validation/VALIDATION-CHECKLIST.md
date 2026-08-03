# Validation Checklist

Use este checklist para validar batches no `conn2flow` sem perder de vista o baseline operacional do repositório.

## Onboarding SDD repo-wide

- [x] `CLAUDE.md` instalado na raiz do repositório
- [x] `.claude/` instalado com agents, rules, skills e settings do Claude Code
- [x] `.github/copilot-instructions.md` instalado
- [x] `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/` com artefatos SDD do Copilot
- [x] `sdd/scripts/hooks/` criado com hooks de sessão SDD
- [x] `sdd/human-requests/` ativo
- [x] `sdd/README.md`, `process/`, `implementation/`, `validation/` e `decisions/` criados
- [x] `sdd/00-baseline-architecture.md` criado com preservação do legado

## Checklist mínimo por batch

- [ ] O batch está registrado em `sdd/implementation/BATCH-INDEX.md`
- [ ] O impacto foi comparado contra `sdd/00-baseline-architecture.md`
- [ ] A menor validação executável do slice foi definida antes de editar mais do que o necessário
- [ ] Scripts, tasks ou paths alterados continuam coerentes com `dev-environment/data/environment.json`
- [ ] Não houve reescrita ampla do legado sem mudança normativa aprovada
- [ ] O review findings-first foi feito quando a mudança ficou pronta para avaliação

## Quando o batch tocar operação local

- [ ] Validar a task do VS Code mais próxima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execução correspondente
- [ ] Se tocar sincronização de projeto, validar source/target/path no `environment.json`
- [ ] Se tocar plugins, validar o fluxo na árvore `dev-plugins/`

## Evidência mínima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendências ou riscos restantes

## Regra final

Se não houver validação executável no slice atual, o batch deve registrar explicitamente por que a validação ficou documental ou manual.

## Validações de Batches Arquivados

Para manter o checklist de validações leve e eficiente, as validações e evidências dos lotes `BATCH-001` a `BATCH-017` foram movidas para **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)**, as dos lotes `BATCH-018` a `BATCH-053` para **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)** e as dos lotes `BATCH-054` a `BATCH-093` para **[validation-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-054-093.md)**.

---
## BATCH-094 - Tradução Completa dos Templates de Páginas do Módulo "publisher-index" para o Inglês (req-094)

- [x] **Tradução dos Arquivos HTML de Recursos**:
  - [x] `publisher-index-adicionar.html` totalmente traduzido do português para o inglês.
  - [x] `publisher-index-editar.html` totalmente traduzido do português para o inglês.
  - [x] `publisher-index-clonar.html` totalmente traduzido do português para o inglês.
- [x] **Validação**:
  - [x] Estrutura HTML, IDs e classes CSS preservados de forma íntegra.
  - [x] Interface administrativa do módulo em inglês livre de termos em português ao acessar em `/en/`.

### Evidência de Validação (BATCH-094)

Evidência visual e automatizada reportada em 2026-07-21:
- `git diff --check` → Passou sem erros.
- Lint estático de HTML e JS → OK.
- `composer test` (PHPUnit) → **110 testes passados** (474 assertions).
- `npm run test` (Vitest) → **29 testes passados** (todos aprovados).
- Nenhuma string acentuada remanescente em português nas páginas em inglês.

---
## BATCH-095 - Tradução Completa da Editbar, Painéis e Overlays do Editor Visual para o Inglês (req-095)

- [x] **Tradução no Frontend (`html-editor.js`)**:
  - [x] Painel de IA (`#c2f-ai-panel`) traduzido dinamicamente se o idioma for inglês.
  - [x] Painel de Modelos (`#c2f-tpl-panel`) traduzido dinamicamente se o idioma for inglês.
  - [x] Floating Toolbar e Breadcrumb traduzidos dinamicamente se o idioma for inglês.
  - [x] Styler do Tailwind (`tailwindHelperConfig`) traduzido dinamicamente se o idioma for inglês.
- [x] **Tradução na Toolbar e Backups (`dashboard.toolbar.js`)**:
  - [x] Painel de Adição (`#c2f-add-panel`) traduzido dinamicamente se o idioma for inglês.
  - [x] Painel de Backups (`#c2f-backup-panel`) traduzido dinamicamente se o idioma for inglês.
  - [x] Mensagens de alerta e erro traduzidas se o idioma for inglês.
- [x] **Tradução nos Controles Estáticos (`html-editor-visual-controls.js`)**:
  - [x] Painel de inclusão estático (`html-editor-add-panel`) e lista de elementos HTML traduzidos se o idioma for inglês.
- [x] **Validação**:
  - [x] Suíte Vitest (`npm run test`) passa inteira sem erros.
  - [x] Verificação visual do editor visual e da editbar no idioma inglês (exibindo toda a interface em inglês).

### Evidência de Validação (BATCH-095)

Evidência visual e automatizada reportada em 2026-07-21:
- `git diff --check` → Passou sem erros.
- Lint estático de JS (`node --check`) → Passou com sucesso.
- `composer test` (PHPUnit) → **110 testes passados** (474 assertions).
- `npm run test` (Vitest) → **31 testes passados** (incluindo 2 novos testes focados em internacionalização desenvolvidos para garantir a robustez da solução).
- Destaque para detecção dinâmica flexível de variantes do inglês (`en`, `en-us`, `en-gb`) e herança cross-origin blindada para o iframe (`window.parent.gestor.language`).

---
## BATCH-096 - Mapeamento Visual de Embeds, Proteção de Eventos, Suporte Híbrido a PDF e Modal Estruturado (req-096)

- [x] **Invólucro atômico e proteção de eventos (`html-editor.js`)**:
  - [x] `object`, `iframe`, `embed`, `video`, `audio` e o contêiner `.conn2flow-pdfjs` envolvidos em `.conn2flow-embed-wrapper` (badge de tipo + escudo `.c2f-embed-shield` + 4 alças de redimensionamento).
  - [x] Conteúdo embutido inerte a ponteiro (`.conn2flow-embed-inner > *`), acabando com o *event swallowing*.
  - [x] `resolveEditable` devolve o invólucro para escudo/alça/embed (bloco atômico) e `getEditType` devolve `embed`.
  - [x] `wrapEmbeds` idempotente; ignora UI do editor, iframes de sistema do Live Editor e embeds dentro de blocos dinâmicos (widget/`.c2f-dyn-box`).
  - [x] Duplo clique no escudo abre o modal já na aba correspondente ao tipo.
  - [x] Redimensionamento por alça grava `style.width/height` e remove os atributos legados `width`/`height`.
- [x] **Reversão limpa na persistência**:
  - [x] `extractUserHtml` remove o invólucro SEMPRE (save e snapshots de undo) — o banco recebe apenas a tag limpa.
  - [x] `applyState` (undo/redo) reconstrói os invólucros a partir da tag limpa.
- [x] **Modal `#c2f-he-embed-modal` em 4 abas**:
  - [x] Aba 1: fonte (`src`/`data`) + seletor de arquivo do servidor (aceita PDF/vídeo/documento), largura/altura com unidade (`px`/`%`/`vh`) e `title`.
  - [x] Aba 2: 3 motores de PDF com parâmetros do PDF.js (zoom, barra de ferramentas, página inicial, modo de rolagem) e aviso quando a fonte não é PDF.
  - [x] Aba 3: `allowfullscreen`, `sandbox`, `controls`, `autoplay`, `loop`, `muted`, `poster`.
  - [x] Aba 4: gerenciador dinâmico de `<param>`, HTML de fallback customizado, estilos inline e classes extras.
  - [x] Detecção automática de PDF ao selecionar arquivo `.pdf` (abre a aba de motores) ou ao digitar/colar um link `.pdf`.
- [x] **3 motores de exibição de PDF**:
  - [x] A — `<object type="application/pdf">` + fallback amigável (mensagem + botão estilizado, com estilos inline para funcionar no site publicado).
  - [x] B — `<div class="conn2flow-pdfjs" data-pdf-*>` renderizado em canvas pelo runtime novo `gestor/assets/interface/pdf-viewer.js`.
  - [x] C — `<iframe src="https://docs.google.com/viewer?url=…&embedded=true">` com URL absolutizada e codificada.
- [x] **Injeção de assets do PDF.js**:
  - [x] Core: `gestor_pdf_viewer_detectar()`/`gestor_pdf_viewer_assets()` (biblioteca `gestor`) + `gestor_pagina_pdf_viewer()` no pipeline (`gestor/gestor.php`), após os widgets e antes da serialização de JS — inclui os assets só nas páginas que usam o leitor.
  - [x] Editor clássico: `montarPdfViewerHead()` injeta o runtime no `srcdoc` do preview e do editor visual (página e layout).
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador.

### Evidência de Validação (BATCH-096)

Reportada pelo executor em 2026-07-29:
- `php -l` → `gestor/gestor.php`, `gestor/bibliotecas/gestor.php`, `gestor/bibliotecas/html-editor.php`, `tests/Unit/PHP/PdfViewerAssetsTest.php` → **4/4 OK**.
- `node --check` → `html-editor.js`, `html-editor-interface.js`, `pdf-viewer.js`, `dashboard.toolbar.js` → **4/4 OK**. `dashboard.json` válido.
- `npx vitest run` → **50/50** (8 arquivos), incluindo o novo `tests/Unit/JS/html-editor-embed.test.js` **18/18**: envelopamento das 5 tags + PDF.js, atomicidade (escudo/alça/embed → invólucro), idempotência, exclusão de UI/sistema/blocos dinâmicos, reversão no `getCleanHtml`, round-trip de snapshot/`applyState`, detecção de tipo, geração dos 3 motores, roteamento do `buildEmbedMarkup`, modal (4 abas, campos populados, troca de motor preservando fonte/dimensões, aviso de não-PDF), `applyEmbedSize`, layout do invólucro (block com largura fluida × fit-content com largura fixa) e tradução para inglês.
- `composer test` (PHPUnit) → **158/158** (660 assertions, 4 skipped) com o novo `PdfViewerAssetsTest` **5/5**. A única deprecation reportada é pré-existente (`TwoFactorTest`, metadados em doc-comment).
- Cache-bust: `biblioteca-html-editor` `1.4.12`→`1.5.0`, `dashboard.json` `1.0.11`→`1.0.12`, motor no Live Editor `?v=c2f10`→`?v=c2f11`.

### Pendências

- Runtime (após deploy `Update => Core`):
  - Inserir um PDF local em `/admin-paginas/` (Editor Visual) e no Live Editor; alternar os 3 motores e conferir a renderização.
  - Conferir em desktop e em simulação mobile (Android/iOS) a exibição inline (motores B/C) e o fallback amigável (motor A).
  - Conferir que o HTML salvo no banco contém apenas a tag limpa (sem `conn2flow-embed-wrapper`/`c2f-embed-shield`).
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-097 - Correções de Homologação do BATCH-096, Edição Avançada Separada e Embeds no Painel "+" (req-097)

- [x] **Fix 1 — duplicação/vazamento de embeds no save/reload e na Editbar**:
  - [x] Causa-raiz confirmada pela evidência ([temp/html-output.html](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/temp/html-output.html)): a 2ª cópia do `<object>` estava FORA do `#c2f-page-content`, como filho de `#c2f-layout-root` — ou seja, salva em `layouts.html`. Como o layout é compartilhado por todas as páginas e pela página da Editbar (renderizada no iframe da barra), o embed aparecia 2–3 vezes e "vazava" para dentro da barra.
  - [x] `insertionRoot()`: inserção sem alvo (código customizado e painel "+") vai para `#c2f-page-content` quando ele existe; o fallback do modo de inserção deixou de usar `document.body`.
  - [x] `isEditorOwned` reconhece `#c2f-site-toolbar`, `#c2f-device-preview` (e descendentes) e `#c2f-save-loader` — blinda `wrapEmbeds`, `resolveEditable`, `findEditableFromPoint` e a extração de uma só vez.
  - [x] `extractUserHtml` remove os elementos de sistema do clone, limpa o leitor PDF.js renderizado (canvas/toolbar + `data-c2f-pdfjs-ready`) e apaga resíduos órfãos de invólucro persistidos por versão anterior (extração idempotente).
- [x] **Fix 2 — interatividade do embed no site publicado**:
  - [x] `disable()` desembrulha os embeds no DOM vivo (sem escudo e sem `pointer-events:none`) e `enable()` reconstrói — o escudo passa a existir só enquanto o editor está habilitado (vale para sair da edição, preview de dispositivo e salvamento).
  - [x] O markup persistido nasce com `position:relative;z-index:1` (estilos extras do usuário têm precedência), para o embed não ficar sob camadas decorativas absolutas do template (`absolute inset-0 z-0`), que capturavam o ponteiro.
- [x] **Fix 3 — z-index do seletor de arquivos**: `#c2f-he-embed-modal` em `1000000` e overlay do gerenciador (`#c2f-he-imagepick-overlay`) em `1000060`.
- [x] **Fix 4 — preview ao vivo do Motor B**: `ensurePdfViewer()` carrega `interface/pdf-viewer.js` sob demanda (o runtime busca a lib do PDF.js) e `refreshPdfJsViewers()` dispara a renderização ao aplicar o modal, ao envolver embeds e no `applyState`. O runtime deixou de escrever `display/flex-direction` inline no contêiner (foi para CSS), preservando o `style` que será salvo.
- [x] **Item 5 — dropdown "Página"**: "Editar Avançado" sempre em `/admin-paginas/editar/?id=…`; novo "Editar Publicação Avançado" (`/publisher-pages/editar/?id=…&publisher_id=…`) exibido só quando há publicação vinculada (bloco `<!-- dropdown-page-publisher -->` removido por `modelo_tag_del`), nos templates pt-br e en.
- [x] **Item 6 — painel "+"**: `object`, `iframe`, `embed`, `video` e `audio` nas listas de elementos do Live Editor e do editor clássico; `buildElement` cria a estrutura limpa e a inserção abre o modal de embed automaticamente.
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador.

### Evidência de Validação (BATCH-097)

Reportada pelo executor em 2026-07-30:
- `php -l` → `gestor/modulos/dashboard/dashboard.php`, `gestor/bibliotecas/html-editor.php` → **OK**.
- `node --check` → `html-editor.js`, `dashboard.toolbar.js`, `pdf-viewer.js`, `html-editor-visual-controls.js` → **4/4 OK**. `dashboard.json` válido.
- `npx vitest run` → **58/58** (8 arquivos); `tests/Unit/JS/html-editor-embed.test.js` passou de 19 para **27 casos**, com 8 novos cobrindo: destino da inserção (conteúdo × layout), isolamento do iframe da Editbar/preview de dispositivo (wrap, `resolveEditable` e extração), limpeza do leitor PDF.js renderizado, limpeza de resíduos de invólucro, `disable()`/`enable()` do escudo, `position:relative;z-index:1` com precedência dos estilos do usuário, z-index picker > modal e os 5 tipos de embed do painel "+".
- `composer test` (PHPUnit) → **158/158** (660 assertions, 4 skipped) sem regressão.
- Cache-bust: `biblioteca-html-editor` `1.5.0`→`1.5.1`, `dashboard.json` `1.0.12`→`1.0.13`, motor no Live Editor `?v=c2f11`→`?v=c2f12`.

### Pendências

- Runtime (após deploy `Update => Core`):
  - Inserir `<object>` pelo painel "+" e por código customizado, salvar e recarregar: confirmar UMA ocorrência, dentro do conteúdo, sem cópia no layout nem dentro da Editbar.
  - Sair do modo de edição e conferir que o PDF rola/amplia normalmente; repetir na página publicada como visitante.
  - Abrir o modal de embed → seletor de arquivos deve ficar À FRENTE; escolher um `.pdf` e confirmar a abertura automática da aba de motores.
  - Selecionar o Motor B e conferir o canvas renderizando ao vivo no editor; salvar e conferir no banco que o contêiner foi gravado vazio (só com os `data-pdf-*`).
  - Dropdown "Página": "Editar Avançado" → `/admin-paginas/`; em página de publicação, "Editar Publicação Avançado" → `/publisher-pages/` com `publisher_id`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-098 - Área de Transferência Persistente do Editor Visual (demanda direta do Chefe, 2026-07-30)

- [x] **Persistência da cópia**:
  - [x] `copySelected()` grava o bloco em `localStorage['c2f-he-clipboard']` (`{html, ts, origem}`) e SUBSTITUI a cópia anterior.
  - [x] `initClipboard()` recupera a cópia no boot do editor — o botão `he-tb-paste` já nasce visível numa página nova.
  - [x] Listener de `storage`: cópia feita em outra aba atualiza o estado do botão.
  - [x] `localStorage` indisponível ou cota estourada não quebra o editor (fallback silencioso para memória).
- [x] **Sanitização do bloco copiado** (`buildClipboardMarkup`): remove invólucro de embed e leitor PDF.js renderizado (UI de runtime), remove `data-c2f-variable`/`contenteditable` (dependem do `varMap` da página de origem) e **preserva** `data-c2f-marker` (widget colado continua widget).
- [x] **Colagem segura**:
  - [x] `remapPastedIds()` renumera `data-c2f-widget-id` e reregistra wrappers clássicos no `widgetsMap` — colar na mesma página do original não faz o `reconstructOriginal` descartar um dos grupos no save.
  - [x] Colar SEM seleção insere no fim do conteúdo editável (`insertionRoot()`), nunca na raiz do layout.
  - [x] Ctrl+V passa a funcionar sem seleção (usa `hasClipboard()`).
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador.

### Evidência de Validação (BATCH-098)

Reportada pelo executor em 2026-07-30:
- `node --check gestor/assets/interface/html-editor.js` → **OK**.
- `npx vitest run` → **64/64**, com 6 casos novos em `tests/Unit/JS/html-editor.live.test.js`: gravação saneada no `localStorage`, recuperação por uma instância nova (outra página), substituição ao copiar de novo, colagem sem seleção indo para o conteúdo, renumeração de ids de widget e visibilidade do botão "Colar".
- `composer test` (PHPUnit) → **158/158** sem regressão.
- Cache-bust: `biblioteca-html-editor` `1.5.1`→`1.5.2`, motor no Live Editor `?v=c2f12`→`?v=c2f13`.

### Pendências

- Runtime (após deploy `Update => Core`): copiar uma seção numa página, sair da edição, abrir outra página, entrar em edição e colar (com e sem elemento selecionado); conferir que copiar de novo substitui a cópia e que o bloco colado é salvo corretamente.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-099 - Upload e Gestão de Pastas Liberados no Modo Picker do Admin-Arquivos (demanda direta do Chefe, 2026-07-30)

- [x] **Ferramentas visíveis no picker**: removida a ocultação do bloco `<!-- normal-tools -->` (botões "Adicionar" e "Nova pasta" + checkbox "Selecionar todos") quando `paginaIframe` está ativo.
- [x] **Retorno da tela de envio**: o botão voltar deixou de ser removido no picker — sem ele o usuário ficaria preso na tela de upload dentro do iframe. O destino já preservava o modo (`admin-arquivos/?paginaIframe=sim`).
- [x] **Impacto verificado antes da remoção**:
  - [x] O href do botão "Adicionar" já recebia `?paginaIframe=sim` no PHP (`#paginaIframe#`) e `&paginaIframe=sim` no JS (`atualizarAddHref()`), com a pasta corrente — ou seja, o fluxo de upload no iframe já estava implementado e apenas inacessível.
  - [x] "Nova pasta", renomear, excluir, seleção em lote e a barra de seleção não são condicionados ao picker no JS — passam a funcionar sem alteração.
  - [x] O upload é assíncrono e não redireciona, então o modo picker não se perde durante o envio.
  - [x] Diferenciações legítimas do picker preservadas: célula de arquivo com botão "Selecionar" (em vez de "Copiar URL"), galeria com a mesma regra e ausência do cache de última pasta (`localStorage`).
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador.

### Evidência de Validação (BATCH-099)

Reportada pelo executor em 2026-07-30:
- `php -l gestor/modulos/admin-arquivos/admin-arquivos.php` → **OK**.
- `composer test` (PHPUnit) → **158/158** (660 assertions, 4 skipped) sem regressão.
- `npx vitest run` → **64/64** sem regressão.
- Sem cobertura automatizada específica: a mudança é de renderização condicional de template (exige banco + componentes), então a verificação é manual/runtime — registrado aqui conforme a regra final do checklist.

### Pendências

- Runtime (após deploy `Update => Core`): abrir o seletor de arquivos por um campo de imagem e pelo modal de embed; enviar um arquivo novo (PDF e imagem) direto no iframe, criar uma pasta, voltar pela seta e selecionar o arquivo recém-enviado.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-100 - Mídia Embutida: 403 em Arquivos com Espaço, Streaming (HTTP Range) e Dimensionamento do Áudio (2026-07-30)

- [x] **Diagnóstico do 403 (medido, não inferido)**:
  - [x] `curl` no container isolou a variável: `mp4`/`ogg` SEM espaço → 404 normal do gestor; qualquer extensão COM `%20` → **403 do Apache**.
  - [x] `localhost-error.log`: `AH10411: Rewritten query string contains control characters or spaces` — o `.htaccess` injeta o caminho decodificado na query (`index.php?_gestor-caminho=$1`) e o Apache ≥2.4.53 recusa espaços.
  - [x] Confirmado que codificar a URL no picker NÃO resolveria: o navegador já enviava `%20` (visível no console) e o 403 ocorria mesmo assim — a decodificação acontece dentro do mod_rewrite.
  - [x] `.htaccess` do instalador (`gestor-instalador/public-access/.htaccess`) já traz `[B,L,QSA]`, assim como sites locais criados recentemente → novas instalações não têm o defeito; o mirror local antigo foi alinhado (arquivo em `dev-environment/data/`, git-ignored).
  - [x] `Installer.php` (§713-755) apenas ajusta o `RewriteBase` e, sem SSL, remove a regra de redirect HTTPS (regex ancorada em `https://`) — a `RewriteRule` do roteador e sua flag `[B]` são preservadas na cópia. Nenhuma correção necessária no instalador.
  - [x] Fora do core e do ambiente local (git-ignored), as únicas ocorrências do padrão antigo estão em prompts HISTÓRICOS de `ai-workspace/` (registro de uma tarefa passada sobre o `RewriteBase`), deliberadamente não reescritos.
- [x] **Correções de core (origem)**:
  - [x] `arquivo_nome_sanitizar()` troca espaços por hífen na entrada (upload e renomeação) — arquivos novos nascem com nome URL-safe.
  - [x] `urlDeArquivo()` no motor codifica a URL devolvida pelo gerenciador (`encodeURI(decodeURI(...))`, idempotente) nos 3 pontos de montagem (image-picker do modal, fundo do styler e fonte do embed). O caminho continua cru como identificador, sem afetar a resolução no servidor.
- [x] **Streaming de mídia (`arquivo-estatico.php`)**:
  - [x] `Accept-Ranges: bytes` + `Content-Length` em todas as respostas (antes era `Transfer-Encoding: chunked` sem tamanho).
  - [x] `Range` atendido com **206 Partial Content** + `Content-Range`, enviando em blocos de 8 KB; faixa inválida responde **416**.
  - [x] `Content-Type` sem `charset` para binários (vídeo/áudio/imagem/PDF); formatos de texto (js/css/svg/json/html/xml) mantêm `charset=UTF-8`.
  - [x] Buffers de saída limpos antes do envio (binário íntegro e `Content-Length` correto).
- [x] **Dimensionamento do áudio**:
  - [x] `embedDefaultSize()` por tipo: áudio sem altura, vídeo 360px, iframe/embed 400px, documento 600px (antes todo embed herdava 600px, esticando o player de áudio).
  - [x] `buildMediaMarkup()` nunca emite `height` para `<audio>`; `applyEmbedSize()` redimensiona áudio só na largura.
- [ ] **Homologação runtime**: pendente com o operador.

### Evidência de Validação (BATCH-100)

Reportada pelo executor em 2026-07-30:
- Verificação HTTP real no container `conn2flow-app`, após a correção:
  - `GET` do vídeo → `HTTP/1.1 200`, `Accept-Ranges: bytes`, `Content-Length: 2069867`, `Content-Type: video/mp4` (sem charset).
  - `GET` com `Range: bytes=0-99` → `HTTP/1.1 206 Partial Content`, `Content-Range: bytes 0-99/2069867`, `Content-Length: 100`.
  - `Range: bytes=99999999-` → `HTTP 416`.
  - `interface.css` → `Content-Type: text/css; charset=UTF-8` (texto preservado).
- `php -l` → `arquivo-estatico.php`, `arquivo.php`, `ArquivoEstaticoRangeTest.php` → **3/3 OK**. `node --check` no motor → OK.
- `composer test` (PHPUnit) → **165/165** (684 assertions, 4 skipped), com o novo `ArquivoEstaticoRangeTest` **6/6** (faixa fechada, aberta, sufixo, limite, inválidas→416 e Content-Type) e o caso novo de sanitização de espaços em `AdminArquivosSegurancaTest`.
- `npx vitest run` → **66/66**, com 2 casos novos (`urlDeArquivo` e dimensionamento do áudio).
- Cache-bust: `biblioteca-html-editor` `1.5.2`→`1.5.3`, motor no Live Editor `?v=c2f13`→`?v=c2f14`.

### Pendências

- Runtime (após deploy `Update => Core`): reproduzir vídeo e áudio na página publicada (inclusive arrastar a linha do tempo e testar em iOS/Safari); enviar um arquivo com espaço no nome e conferir que ele é gravado com hífen; conferir o player de áudio com a altura natural.
- Arquivos JÁ enviados com espaço no nome continuam dependendo do `.htaccess` com `[B]` — em instalações antigas, atualizar o arquivo ou renomear os arquivos pelo gerenciador.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-101 - Embed sem Arquivo Escolhido não Emite Atributo de Fonte Vazio (homologação, 2026-07-30)

- [x] **Sintoma**: `<audio>` inserido pelo painel "+" aparecia colapsado (~11px de largura, ~70px de altura = padding do badge + player em erro).
- [x] **Diagnóstico por comparação dos dois HTMLs enviados pelo Chefe** (um quebrado, um correto, com invólucros idênticos e `display: block` nos dois — descartando contexto de flex/grid como causa):
  - [x] O quebrado tinha `src=""`; o correto não tinha o atributo.
  - [x] O quebrado trazia `position:relative; z-index:1` **com espaço** após o `;` — assinatura do `styleExtra` reconstruído em `embedReadConfig` (`join('; ')`), provando que ele passou pelo "Aplicar" do modal, enquanto o correto veio apenas do `buildElement`. A section escolhida era coincidência.
  - [x] `src=""` faz o navegador resolver para a URL da própria página e tentar carregá-la como mídia: erro de decodificação, player colapsado e uma requisição inútil ao HTML.
- [x] **Correção**: os geradores omitem o atributo de fonte quando vazio — `src` (mídia/iframe), `data` (`<object>`), `data-pdf-src` (PDF.js) e o `src` do Google Viewer (que apontaria para um viewer com `url=` vazia).
- [x] **Hipótese descartada e revertida**: chegou-se a aplicar `width:100%` no invólucro de largura fluida (suspeita de contexto shrink-to-fit em flex/grid). A evidência mostrou invólucros idênticos nos dois casos, então a mudança foi revertida em vez de mantida "por precaução".
- [ ] **Homologação runtime**: pendente com o operador.

### Evidência de Validação (BATCH-101)

Reportada pelo executor em 2026-07-30:
- `node --check gestor/assets/interface/html-editor.js` → **OK**.
- `npx vitest run` → **68/68**, com 2 casos novos: fonte vazia omite o atributo nos 4 geradores; fonte preenchida continua emitindo normalmente.
- `composer test` (PHPUnit) → **165/165** sem regressão.
- Cache-bust: `biblioteca-html-editor` `1.5.3`→`1.5.4`, motor `?v=c2f14`→`?v=c2f15`.

### Pendências

- Runtime: inserir áudio/vídeo pelo painel "+", aplicar o modal SEM escolher arquivo e confirmar que o player mantém a largura normal; depois escolher o arquivo e confirmar a reprodução.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-102 - Memória da Última Pasta no Modo Picker do Admin-Arquivos (demanda direta do Chefe, 2026-07-30)

- [x] **Diagnóstico**: o cache da pasta corrente (`localStorage['adminArquivosDir']`) era ignorado nas DUAS pontas quando `paginaIframe` estava ativo — não era lido no boot (`if (!cfg.paginaIframe)`) nem gravado após navegar (BATCH-090). O seletor abria sempre na raiz.
- [x] **Leitura**: o cache passa a valer também no picker, com precedência `?dir=` explícito > cache > raiz.
- [x] **Gravação**: a pasta corrente é salva em toda navegação, inclusive no picker (mesma árvore de arquivos).
- [x] **Contrato alinhado entre as telas**: a listagem passou a expor `dirExplicito`/`dirInicial` (a partir de `?dir=`, saneado por `arquivo_caminho_relativo_seguro`), exatamente como a tela de envio já fazia — as duas telas agora decidem a pasta inicial pela mesma regra, e um chamador pode abrir o seletor numa pasta específica.
- [x] **Fluxo completo do picker coerente**: navegar até a pasta → "Adicionar" leva `?dir=` (explícito) → enviar → voltar pela seta cai na listagem sem `dir` → o cache devolve a mesma pasta.
- [x] **Pasta apagada não quebra**: `admin_arquivos_ler_pasta` já cai para a raiz quando a pasta do cache não existe mais (self-heal do BATCH-090).
- [ ] **Homologação runtime**: pendente com o operador.

### Evidência de Validação (BATCH-102)

Reportada pelo executor em 2026-07-30:
- `php -l gestor/modulos/admin-arquivos/admin-arquivos.php` → **OK**; `node --check admin-arquivos.js` → **OK**; `admin-arquivos.json` válido.
- `composer test` (PHPUnit) → **165/165** sem regressão.
- Cache-bust: módulo `admin-arquivos` `1.1.6`→`1.1.7` (o `?v=` do `js.js` vem da chave `versao` do manifesto).
- Sem cobertura automatizada: o comportamento é estado de front (localStorage) + renderização condicional; verificação manual registrada nas pendências, conforme a regra final do checklist.
- Módulo espelhado no mirror local (`dev-environment/data/.../modulos/admin-arquivos/`) para teste imediato antes do deploy.

### Pendências

- Runtime: navegar até uma subpasta pelo gerenciador normal, abrir o seletor por um campo de imagem/embed e confirmar que ele abre na MESMA pasta; navegar para outra pasta dentro do picker, fechar, reabrir e confirmar a persistência; conferir que o botão "Adicionar" (com `?dir=`) continua abrindo na pasta corrente.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-103 - Filtro de Módulos no Menu Principal do Gestor (demanda direta do Chefe, 2026-07-30)

- [x] **Campo de filtro no menu**: input `#gestor-menu-filtro` no topo do componente `menu-principal-sistema` (pt-br e en), com ícone de busca e placeholder já traduzido por idioma (o componente é versionado por idioma, dispensando texto condicional no PHP).
- [x] **Comportamento espelhado da Editbar**: itens que não casam somem; o bloco do grupo é ocultado junto com o cabeçalho quando fica sem itens visíveis.
- [x] **Busca tolerante**: comparação ignora caixa e acentuação (`normalize('NFD')` + remoção das marcas combinantes) — 'usuarios' encontra 'Usuários', 'indice' encontra 'Páginas Índice'.
- [x] **Itens fixos também filtrados** (Dashboard e Sair), conforme decidido pelo Chefe; limpar o campo devolve o menu completo.
- [x] **Extras de usabilidade**: aviso 'Nenhum módulo encontrado' (texto do componente, por idioma) e tecla Esc para limpar o filtro.
- [x] **JS em `gestor/assets/global/admin.js`** (arquivo indicado pelo Chefe, até então vazio), com API pública `window.gestorMenuFiltro.aplicar/iniciar` e `iniciar()` idempotente.
- [x] **Inclusão do asset**: a tag `<script>` acompanha o HTML retornado por `gestor_pagina_menu()`. A fila de assets NÃO funciona nesse ponto: `gestor_pagina_menu()` é chamada por `gestor_pagina_variaveis()` (gestor.php:619), que roda DEPOIS de `gestor_pagina_extra_head_e_javascript()` — o marcador de JS já foi resolvido e o item enfileirado ficaria órfão.
- [x] **Fonte 100% ASCII no regex de acentos**: o range é montado com `String.fromCharCode`, imune à normalização Unicode do arquivo por editores.
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador.

### Evidência de Validação (BATCH-103)

Reportada pelo executor em 2026-07-30:
- `php -l gestor/gestor.php` → **OK**; `node --check gestor/assets/global/admin.js` → **OK**.
- `npx vitest run` → **76/76** (9 arquivos), com o novo `tests/Unit/JS/admin-menu-filtro.test.js` **8/8**: API exposta, filtro por texto, ocultação do bloco/cabeçalho, tolerância a acento e caixa, filtragem dos itens fixos, aviso de vazio, reação ao evento `input`, Esc limpando e `iniciar()` idempotente.
- `composer test` (PHPUnit) → **165/165** sem regressão.

### Pendências

- **Deploy obrigatório**: o componente `menu-principal-sistema` é lido do BANCO (`gestor_componente`), então o campo só aparece após o pipeline de resources do `Update => Core`. O `admin.js` é asset físico e passa a ser servido assim que o core for sincronizado.
- Runtime: digitar no campo e conferir filtragem, ocultação de grupos vazios, aviso de vazio, Esc e o menu em `/en/`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

### Rodada 2 do BATCH-103 — correções de consistência nas buscas (mesma sessão)

Reportado pelo Chefe na homologação: o filtro NOVO do menu encontrava itens acentuados, mas o filtro da
Editbar — usado como base — não. A comparação foi então unificada.

- [x] **Editbar (`dashboard.iframe-toolbar.js`)**: `#c2f-modules-filter` passou a normalizar acento e caixa. Digitar 'pa' encontra 'Páginas' (antes o `indexOf` cru falhava no 2º caractere acentuado).
- [x] **Busca de modelos (`html-editor-interface.js`)**: mesmo defeito no `modelosFiltrar`, corrigido com o helper `htmlEditorNormalizarBusca` (aplicado a header, meta e id do modelo).
- [x] **Botão 'x' de limpar na aba Modelos**: `#modelos-search-input` era o único campo de busca sem o atalho; ganhou o par lupa/x no padrão do gerenciador de arquivos (`.modelos-search-icon` / `.modelos-search-clear`), nos componentes pt-br e en, com clique limpando + refiltrando + devolvendo o foco (Esc também sincroniza os ícones).
- [x] **Salto de rolagem no 'Carregar Mais'**: `modelosCarregar` escondia `#modelos-cards` durante o AJAX — a página perdia toda a altura da lista e o navegador voltava ao topo. Agora a lista só é escondida na PRIMEIRA página (quando é de fato substituída); na paginação o loading aparece abaixo dos cards, sem deslocar a leitura.
- [x] **Helper duplicado deliberadamente** nos três contextos (painel, iframe da Editbar e editor clássico), sempre com o range de acentos montado por `String.fromCharCode` (fonte ASCII).

Evidência: `node --check` 3/3 OK; `npx vitest run` **83/83**, com o novo `tests/Unit/JS/dashboard.iframe-toolbar.test.js` **5/5** (regressão do acento, termo acentuado, caixa, cabeçalho de grupo vazio e limpeza do campo) e 2 casos novos em `html-editor-vars.test.js` (normalização e alternância dos ícones); `composer test` **165/165**. Cache-bust: `dashboard.json` `1.0.13`→`1.0.14`, `biblioteca-html-editor` `1.5.4`→`1.5.5`.

Pendência runtime: conferir na Editbar que 'pa' lista as Páginas; na aba Modelos, o 'x' limpando a busca e o 'Carregar Mais' mantendo a posição da rolagem.

---
## BATCH-104 - Checkout Transparente e Tokenização PayPal (req-098, 2026-07-31)

- [x] `paypal_gerar_client_token()` chama `POST /v1/identity/generate-token`, aceita `customer_id` string opcional, retorna apenas `client_token` e falha para payload/HTTP inválido.
- [x] `paypal_criar_pedido()` preserva o fluxo anterior e acrescenta `payment_source`, `intent` CAPTURE/AUTHORIZE, `Prefer: return=representation` e `PayPal-Request-Id` em chamadas one-step.
- [x] Respostas idempotentes 200 e criações 201 são aceitas em Orders.
- [x] `paypal_criar_assinatura()` recebe `$params['payment_source']` e o encaminha no nesting oficial `subscriber.payment_source`.
- [x] `paypal_processar_pagamento_transparente()` valida tipo, fonte e valor/plano antes da API e cobre captura por `order_id`, ordem por token/vault e assinatura.
- [x] Versão PHP `3.1.0` e novas funções expostas por `paypal_info()`.
- [x] Loader JS deduplica chamadas, monta query do SDK e mantém o client token somente em `data-client-token`.
- [x] Card Fields renderiza número, validade, CVV e titular, com seletores customizáveis e fallback para Hosted Fields legado.
- [x] Eventos do SDK aplicam classes de foco/válido/inválido, `aria-invalid` e mensagens customizáveis.
- [x] Submit consulta `getState()`, bloqueia formulário inválido e normaliza `order_id`/`payment_source` sem converter dados mascarados em token fictício.
- [x] APIs globais: `paypalCarregamentoSDK`, `paypalCardFieldsInit`, `paypalCardFieldsSubmit` e `conn2flowPaypal`.
- [x] Cobertura JS nova 7/7: query/atributo seguro, deduplicação, renderização, validação, retorno de fonte, retorno de ordem e bloqueio de submit.
- [x] Cobertura PHP nova 7/7: fonte card/token, rejeições, helper, nesting, idempotência e metadados.
- [ ] Homologação runtime com credenciais Sandbox e conta elegível (não automatizada para evitar chamadas financeiras externas).

### Evidência de Validação (BATCH-104)

- `php -l gestor/bibliotecas/paypal.php` e no teste PHP: **OK**.
- `node --check gestor/assets/paypal/paypal.js` e no teste JS: **OK**.
- `git diff --check`: **OK**.
- `npx vitest run`: **90/90** em 11 arquivos; `paypal.test.js` **7/7**.
- `composer test`: **172/172**, 707 assertions e 4 testes preexistentes pulados; `PaypalTransparentCheckoutTest` **7/7**.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

### Pendências

- Em Sandbox: conferir elegibilidade, estados visuais, captura, autorização e 3DS quando aplicável.
- Assinatura transparente por cartão depende do produto, região e conta habilitados pelo PayPal; validar antes de Live.
- Revisar CSP da aplicação hospedeira para os domínios exigidos pelo SDK/iframes PayPal.

---
## BATCH-105 - Navegacao por Teclado nos Filtros de Modulos (2026-08-03)

- [x] `ArrowDown` no input foca o primeiro resultado visivel nos dois menus.
- [x] `ArrowDown`/`ArrowUp` percorrem somente resultados visiveis.
- [x] `ArrowUp` no primeiro resultado devolve o foco ao input correspondente.
- [x] `ArrowDown` no ultimo resultado nao cria ciclo.
- [x] Sem resultados, o foco permanece no input.
- [x] Enter permanece sob o comportamento nativo do link focado.
- [x] Escape do filtro do menu principal continua limpando o termo.
- [x] Estrutura PHP e componentes por idioma permanecem compativeis e sem texto novo.
- [ ] Homologacao manual na Editbar e no menu principal apos sincronizacao do core.

### Evidencia de Validacao (BATCH-105)

- `node --check gestor/modulos/dashboard/dashboard.iframe-toolbar.js`: **OK**.
- `node --check gestor/assets/global/admin.js`: **OK**.
- Parse de `gestor/modulos/dashboard/dashboard.json`: **OK**.
- Testes focados (`dashboard.iframe-toolbar.test.js` + `admin-menu-filtro.test.js`): **16/16**.
- Suite Vitest completa: **93/93** em 11 arquivos.
- `git diff --check` no slice: **OK**.
- Avisos de rede do Happy DOM em `html-editor-embed.test.js` sao preexistentes; nao alteram o
  resultado aprovado da suite nem exercitam os arquivos deste batch.
