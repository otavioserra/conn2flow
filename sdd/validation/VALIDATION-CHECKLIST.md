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

---
## BATCH-106 - Opções de Exibição, Sidebar de CSS, Barra de Navegação e Ação Substituir (req-106, 2026-08-06)

- [x] **Painel de Opções de Exibição (`c2f-view-options-panel`)**:
  - [x] Botão `c2f-tb-view-options` na Editbar (`c2f-toolbar-editbar`, pt-br e en) e na topbar do editor visual dos módulos (componente `html-editor-visual-modal`, pt-br e en).
  - [x] Painel flutuante no padrão do `c2f-add-panel`, com os toggles nomeados `Sidebar Lateral de CSS` e `Barra de Navegação de Elementos`.
  - [x] Os dois nascem **desativados**; a escolha é persistida (`localStorage['c2f-he-view-options']`) e recuperada no boot do motor, com o painel refletindo o estado real ao abrir.
- [x] **Sidebar Lateral Fixa de CSS (`c2f-he-css-sidebar`)**:
  - [x] Coluna fixa à esquerda de 240px, cabeçalho fixo "Sidebar Lateral de CSS" e corpo rolável na altura útil da viewport.
  - [x] Encaixa abaixo da Barra de Navegação e do offset da Editbar, sem sobreposição (`top` = offset + altura da barra; `height: calc(100vh - top)`).
  - [x] O `#html-editor-tailwind-styler` é realocado para dentro dela (mesmo nó/handlers, sem duplicação) e devolvido ao `document.body` ao desligar.
  - [x] Classes Tailwind ativas agrupadas por variante (`base`, `sm:`, `md:`, `hover:`…) com remoção pelo "x".
  - [x] Bloco dedicado às classes customizadas do projeto (não-Tailwind), também removíveis.
  - [x] Autocomplete instantâneo sobre o dicionário expandido (a partir de 2 caracteres do último token).
  - [x] Seção "Valores manuais" no `he-styler-col-visual` aceitando `25px`, `1.5rem`, `#123456` (vazio remove a propriedade).
  - [x] Campo de CSS inline customizado lendo/gravando o atributo `style` inteiro.
  - [x] Inspetor de estilos computados (`getComputedStyle`, 20 propriedades).
  - [x] `tailwindSuggestions` expandido (flexbox, grid, tamanhos, espaçamentos, bordas, sombras, opacidade, posicionamento, transições, paleta completa, arbitrários `w-[350px]`/`bg-[#1a2b3c]` e variantes) alimentando datalist e autocomplete.
- [x] **Barra Superior Fixa de Navegação (`c2f-he-element-navbar`)**:
  - [x] 44px abaixo da Editbar, colunas 20% (rótulo fixo) / 80% (área útil).
  - [x] Breadcrumb de ancestrais e lista de filhos realocados para a coluna de 80% e devolvidos ao `document.body` ao desligar.
  - [x] Desativada por padrão.
- [x] **Toolbar flutuante e ação "Substituir" (`.he-tb-replace`)**:
  - [x] O toolbar contextual continua acompanhando o elemento selecionado.
  - [x] Botão ao lado de Copiar/Colar, visível só com cópia guardada **e** elemento selecionado.
  - [x] Substitui o elemento pelo bloco da área de transferência, renumera ids de widget e seleciona automaticamente o novo objeto.
- [x] **Isolamento da UI nova**: `isEditorOwned` (clique não vaza para o conteúdo atrás), `extractUserHtml` e o fallback de salvamento do `html-editor-interface.js` removem os painéis; `disable()`/`enable()` escondem e devolvem os painéis fixos.
- [ ] **Homologação runtime (deploy `Update => Core`)**: pendente com o operador — os dois botões vêm de página/componente lidos do banco.

### Evidência de Validação (BATCH-106)

Reportada pelo executor em 2026-08-06:
- `node --check` → `html-editor.js`, `html-editor-interface.js`, `html-editor-visual-controls.js`, `dashboard.toolbar.js`, `dashboard.iframe-toolbar.js` → **5/5 OK**.
- `php -l gestor/bibliotecas/html-editor.php` → **OK**. Parse de `dashboard.json`, `resources/pt-br/components.json` e `resources/en/components.json` → **OK**.
- `npx vitest run` → **118/118** em 12 arquivos (antes 93/93), com o novo `tests/Unit/JS/html-editor-view-options.test.js` **21/21** (painéis desligados por padrão, encaixe/desencaixe dos três painéis, persistência entre instâncias, encaixe sem sobreposição com offset da Editbar, `disable`/`enable`, isolamento na extração, aviso de vazio, chave desconhecida, botão e comportamento do Substituir, renumeração de ids, separação Tailwind × customizadas, variantes, autocomplete, valores manuais, CSS inline, computados e dicionário expandido), 3 casos novos em `dashboard.toolbar.test.js` (montagem, sincronização e tradução do painel) e 1 em `dashboard.iframe-toolbar.test.js` (mensagem do botão da Editbar).
- `composer test` (PHPUnit) → **172/172** (707 assertions, 4 skipped preexistentes) sem regressão.
- Ajuste de teste existente: `html-editor.live.test.js` passou a mirar `#html-editor-tailwind-styler .he-class-input` — o styler deixou de ter um único `<input>` (os campos de valores manuais também são inputs).
- Sem cobertura automatizada: o painel da janela pai (`html-editor-visual-controls.js`) depende de jQuery/Fomantic reais; o stub de teste do repositório não cobre `is`/`css`/`outerWidth`. Verificação registrada como manual, conforme a regra final do checklist.
- Cache-bust: `biblioteca-html-editor` `1.5.6`→`1.5.7`, `dashboard.json` `1.0.15`→`1.0.16`, motor no Live Editor `?v=c2f15`→`?v=c2f16`. Checksums dos recursos alterados esvaziados para o pipeline recalcular.

### Pendências

- **Deploy obrigatório**: a página `dashboard-site-toolbar` (Editbar) e o componente `html-editor-visual-modal` (editor dos módulos) são lidos do BANCO — os botões só aparecem após o `Update => Core`. Os assets JS são físicos e passam a valer com o core sincronizado.
- Runtime: ligar/desligar os dois toggles nos dois editores; conferir a persistência após recarregar; conferir o encaixe sem sobreposição; testar valores manuais, autocomplete, CSS inline e computados; testar Substituir (copiar A, selecionar B, substituir → B vira A e fica selecionado); salvar e conferir que o HTML persistido não contém a UI dos painéis; repetir em `/en/`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

### Rodada 2 do BATCH-106 — homologação do Chefe (mesma data)

Quatro ajustes de usabilidade reportados no teste da rodada 1:

- [x] **Painéis flutuantes legados aposentados**: trilha de ancestrais, lista de filhos e caixa de estilização não acompanham mais o elemento selecionado — existem apenas dentro da Barra de Navegação e da Sidebar de CSS. Com o painel correspondente desligado, nada é exibido (o empilhamento flutuante segue no arquivo, inativo). A barra flutuante de AÇÕES continua acompanhando o elemento.
- [x] **Botão de fechar (`c2f-he-panel-close`)** no canto superior direito da área do título dos dois painéis: desliga o painel, exatamente como desmarcar o toggle nas Opções de Exibição (o painel de opções reflete o estado ao reabrir).
- [x] **Botão de ancoragem (`c2f-he-panel-side`)** à esquerda do fechar: setas horizontais na sidebar (esquerda ⇄ direita) e verticais na barra de navegação (topo ⇄ base).
- [x] **Ancoragem persistida com o padrão original**: `cssSidebarRight`/`elementNavbarBottom` no mesmo registro de `localStorage`, ambos `false` (sidebar à esquerda, barra no topo). O encaixe é recalculado nos quatro arranjos — com a barra embaixo, a sidebar sobe até a Editbar e encurta na base.
- [x] **Clique na Editbar fecha TODA a UI flutuante**: o clique ocorre dentro do iframe da barra, então o `mousedown` da página hospedeira nunca disparava e nenhum backdrop era atingido. A barra publica `c2f-toolbar:ui-dismiss` a cada `mousedown` (em captura) e o host responde com `dismissHostPanels()` — painéis desta página (Opções de Exibição, "+", Backups) e, por delegação a `c2fEditor.dismissFloatingUi()`, os do motor (Modelos, IA, Código Customizado, modal de embed, seletor de arquivos e modal de edição). É o mesmo conjunto que já fechava ao clicar fora na área editável.
- [x] **Clique DENTRO de painel/modal não fecha nada**: o aviso só nasce de cliques na barra; na área editável cada backdrop/`closest()` continua responsável. Como o `mousedown` precede o `click`, o botão que abre um painel continua funcionando.
- [x] **Regressão corrigida**: `closeEmbedModal()` zera `isModalActive` incondicionalmente — lido depois dele, o modal de edição nunca seria fechado por `dismissFloatingUi`. O estado passou a ser lido antes, e embed/picker só são fechados quando de fato estão abertos.

Evidência (2026-08-06): `node --check` em `html-editor.js`, `dashboard.toolbar.js` e `dashboard.iframe-toolbar.js` → **3/3 OK**; `php -l` e parse do `dashboard.json` → OK; `npx vitest run` → **130/130** (`html-editor-view-options.test.js` 21→**29**: legado oculto, exibição ao ligar, fechar pelo cabeçalho, alternância esquerda/direita, alternância topo/base com reencaixe da sidebar, persistência da ancoragem, `dismissFloatingUi` fechando os painéis do motor sem perder a seleção e o guard do modal de edição; +3 em `dashboard.toolbar.test.js` — `dismissHostPanels` nos painéis do host, delegação ao motor e ausência de editor; +1 em `dashboard.iframe-toolbar.test.js` — aviso e ordem `mousedown` → `click`); `composer test` → **181/181** (a suíte PHP cresceu com o BATCH-107, em paralelo; sem regressão). Cache-bust: `biblioteca-html-editor` `1.5.7`→`1.5.8`, `dashboard.json` `1.0.16`→`1.0.17`, motor `?v=c2f16`→`?v=c2f17`. Nenhum recurso do banco foi alterado nesta rodada.

Pendência runtime da rodada 2: conferir que nada flutua com os painéis desligados; alternar a ancoragem nos quatro arranjos; fechar cada painel pelo ✕; abrir cada painel (Opções, "+", Backups, Modelos, IA, Código Customizado) e clicar na Editbar confirmando o fechamento; conferir que clicar dentro do painel não o fecha.

### Rodada 3 do BATCH-106 — homologação do Chefe (mesma data)

- [x] **CSS inline com CodeMirror**: o campo `he-inline-css` da Sidebar de CSS virou editor CodeMirror (`mode: css`, tema `tomorrow-night-bright`, `indentUnit` 4, 110px — a largura da sidebar não comporta os 800px do padrão de abas), criado sob demanda apenas com a sidebar ligada e idempotente (dedup por `.CodeMirror` irmão).
- [x] **Degradação graciosa preservada**: sem a biblioteca (ou sem `getValue`/`setValue`), o `<textarea>` continua funcionando; o listener de `blur` só é registrado se a build expuser `on()`.
- [x] **Leitura/escrita coerentes**: `syncInlineCss` usa `setValue` + `refresh` (o editor pode nascer oculto) e `applyInlineCss` lê de `inlineCssValue()`; valor idêntico ao `style` atual não empilha passo de undo.
- [x] **Crescimento dinâmico do editor preservado**: arrastar o canto inferior direito continua aumentando/diminuindo o CodeMirror junto com a caixa (comportamento do BATCH-081).
- [x] **`#c2f-ai-status` deixa de sumir ao redimensionar a caixa**: o cálculo descontava só o topo do editor, fazendo o CodeMirror tomar o espaço de quem vinha depois. Agora desconta `alturaAposElemento(editor, corpo)` — irmãos posteriores do editor e dos seus ancestrais até o corpo — com folga de 8px.
- [x] **Editor da aba "Modo" nasce na altura certa**: o ajuste vivia apenas no `ResizeObserver` (que reage a mudanças da CAIXA). Extraído para `syncLiveBoxCodeMirrors(panel)` e chamado também na troca de abas e na abertura dos painéis de IA e Código Customizado.
- [x] **Correção dentro da própria rodada**: a primeira tentativa usou `clientHeight − (scrollHeight − altura dos editores)`. Como `scrollHeight` nunca é menor que `clientHeight`, com o conteúdo cabendo a conta virava "altura atual − folga" e o editor encolhia a cada disparo até o mínimo, deixando de acompanhar o mouse. A conta voltou a partir do fundo do corpo e o desconto passou a independer da altura do editor (sem realimentação; guarda anti-loop por igualdade exata).
- [x] **Painéis fixos permanecem no preview de dispositivo**: `enterDevicePreview()` chama `disable()`, que escondia a Sidebar e a Barra. `disable({ manterPaineis: true })` é usado só nesse caminho — trocar a largura de visualização não é sair do modo de edição. Sair da edição e salvar continuam escondendo os painéis.

Evidência (2026-08-06): `node --check` em `html-editor.js` e `dashboard.toolbar.js` → **2/2 OK**; `php -l` e parse do `dashboard.json` → OK; `npx vitest run` → **137/137** (`html-editor-view-options.test.js` 29→**36**: conversão em CodeMirror idempotente, leitura/escrita do `style` e aplicação no `blur` sem undo redundante, degradação graciosa sem a lib, painéis mantidos no preview de dispositivo e três casos de resize com medidas stubadas — desconto do status, acompanhamento do arraste nos dois sentidos e ausência de encolhimento espontâneo em disparos repetidos); `composer test` → **181/181** sem regressão. O stub de CodeMirror dos testes (`tests/Unit/JS/setup.js`) ganhou `on()` e um `__emit()` auxiliar. Cache-bust: `biblioteca-html-editor` `1.5.8`→`1.5.9`, `dashboard.json` `1.0.17`→`1.0.18`, motor `?v=c2f17`→`?v=c2f18`. Nenhum recurso do banco foi alterado nesta rodada.

Pendência runtime da rodada 3: editar CSS inline pelo CodeMirror (botão e saída do campo); no Assistente IA, arrastar o canto inferior direito conferindo que o `#c2f-ai-status` continua visível e abrir a aba "Modo" conferindo a altura inicial do editor; trocar desktop/tablet/mobile conferindo que os painéis fixos permanecem.

## BATCH-108 — Desacoplar a linha 2.x do código PHP 8.5 (req-108)

Contexto: o deploy falhava com `HTTP 429 "Rate limit excedido"` e a tabela `api_rate_limits` vazia. Causa: `api_rate_limit_check()` carregava `banco-v2.php` (sintaxe PHP 8.5) dentro de um `try`; o `require_once` lançava `ParseError` — que é `Throwable` — o catch devolvia `false` e o roteador traduzia isso para 429.

- [x] Causa-raiz reproduzida no container: `try { require 'banco-v2.php'; } catch (Throwable $e)` → `CAPTURADO: ParseError :: syntax error, unexpected identifier "with"`.
- [x] Ambiente confirmado em **PHP 8.3.32** (`apache2handler` e CLI); `banco-v2.php` não compila em nenhuma branch (`main`/`2.9.x` linha 173, `3.0.x` linha 188).
- [x] Defeito confirmado nas três branches (mesmo `api.php`, mesma linha 87) — não era problema da branch de desenvolvimento.
- [x] `api_rate_limit_check()` reescrita: contagem via `banco.php` (v1), **sem `PHP_VERSION_ID`, sem fallback, sem condicional** — a linha 2.x não conhece PHP 8.5.
- [x] Três desfechos separados: `true` (dentro do limite), `false` (excedido → 429) e `null` (falha de infraestrutura → 503 com log da exceção original, classe, arquivo e linha).
- [x] Contagem validada contra o banco real: chamadas consecutivas devolveram 1, 2, 3; `total <= max` deu `true` com `max=100` e `false` com `max=2` (429 legítimo preservado). Registros de teste removidos ao final.
- [x] Removidos da linha 2.x: `bibliotecas/banco-v2.php`, `bibliotecas/interface-v2.php`, `modulos/admin-paginas-v2/` (2 arquivos) e os registros `'interface-v2'`/`'banco-v2'` em `config.php`.
- [x] Dados de seed órfãos do módulo removido eliminados **pelo gerador** (`atualizacao-dados-recursos.php`), não à mão: Páginas 264→256, Variáveis 1668→1610, Componentes 120→118. Diff de 717 deleções e 1 inserção (apenas o `generated_at` de `schema-metadata.json`); gerador reportou "Nenhum problema detectado" e nenhum órfão.
- [x] `bibliotecas/ftp.php:139`: parêntese excedente removido (defeito independente, mesmo sintoma).
- [x] **Lint completo do core sob PHP 8.4 (excluindo `vendor/`, `temp/`, PHPMailer): 100% dos arquivos compilam** — antes, 4 falhavam.
- [x] Nenhuma ocorrência de `PHP_VERSION_ID` ou `80500` introduzida em `api.php`.
- [ ] Deploy `Update => Core` na instalação local e reexecução de `deploy-project-v2.sh --project transformamp-local --contents Sim` **(pendente operador)** — o `api.php` que roda vem da instalação, não do repositório.
- [ ] Verificar após o deploy que `api_rate_limits` passa a acumular `request_count` na janela corrente **(pendente operador)**.
- [ ] Linhas órfãs de `admin-paginas-v2` nos bancos `conn2flow`/`transformamp` (8 páginas + 58 variáveis) **não** foram deletadas; a cargo do próximo sync ou de limpeza manual **(pendente operador)**.
- [ ] `3.0.x` permanece intocada, com `banco-v2` e requisito de PHP 8.5 **(por decisão; nada a validar aqui)**.

---
## BATCH-109 — Cookies, Isenção de Crawlers, Auditoria de CSRF e Correções do Editor Visual (req-109, 2026-08-13)

### Módulo 1 — Crawlers e cookie silencioso

- [x] `gestor_crawler_detectar()` reconhece WhatsApp, `facebookexternalhit`, `meta-externalagent`, Twitterbot, LinkedInBot, TelegramBot, Discordbot, Slackbot, Pinterest, Googlebot, bingbot, YandexBot e mais (29 tokens), ignorando caixa.
- [x] Navegador humano (Chrome/Safari/Firefox, desktop e iOS) **não** é classificado como bot; User-Agent ausente/vazio/não-string também não.
- [x] Crawler não recebe cookie de verificação nem redirecionamento — `gestor_cookie_verificacao()` retorna antes de qualquer efeito.
- [x] Página pública recebe o cookie por `Set-Cookie` na PRÓPRIA resposta, sem `Location: _gestor-cookie-verify/`.
- [x] O redirecionamento sobrevive apenas onde a sessão é exigida: `gestor_permissao()` (fora de AJAX) e signin/signup em `perfil-usuario.php`.
- [x] O valor emitido é registrado em `$_COOKIE` no processo — segunda chamada no mesmo request não reemite outro token.
- [x] `setcookie` protegido por `headers_sent()`.
- [x] Crawler que caia em `_gestor-cookie-verify/` volta para a URL de origem, e não para `cookies-is-mandatory/`.

### Módulo 2 — Páginas de sistema sem rastreamento

- [x] `gestor_pagina_sistema_sem_rastreamento()` reconhece `cookies-is-mandatory`, `_gestor-cookie-verify`, `404`, `403`, `500` e `503` (exato ou prefixo de segmento), ignorando caixa e barra inicial.
- [x] Página de conteúdo com nome parecido (`cookies-is-mandatory-explicacao/`, `blog/artigo-404-…/`) **não** é afetada.
- [x] `gestor_rastreamento_remover()` retira `<script>`/`<noscript>`/`<iframe>` de GTM, GA e Meta Pixel e preserva o resto (meta, link, script do próprio site, iframe de vídeo).
- [x] Nas páginas de sistema, `project-javascript` é zerado e as três filas (`html-extra-head`, `javascript`, `javascript-fim`) passam pelo filtro.
- [x] `global.js` neutraliza `fbq`, `dataLayer.push` e `gtag` quando `gestor.rastreamentoBloqueado` está ligado, e **não** sobrescreve coletor já carregado pela página.
- [ ] **Fora do alcance do core**: a deduplicação do ID do Meta Pixel (`Duplicate Pixel ID`) no snippet do projeto — não há nenhuma linha de GTM/Pixel no repositório `conn2flow` (varredura em `gestor/`, `conn2flow-site` e `transformamp`); o snippet vive no banco/JS do deploy.

### Módulo 3 — Logs e warning do editor

- [x] `entrypoint.sh`: `chown`/`chmod 777` nos diretórios `logs` de `/var/www/sites` e criação de `gestor/logs` para sites que não o tenham.
- [x] `gerenciar-sites.sh criar` já nasce com `gestor/logs` gravável e alinha permissões dentro do container quando ele está de pé.
- [x] `log.php` cria diretórios com `0777` + `@chmod` defensivo e aplica `0666` na criação do arquivo.
- [x] Falha de escrita não imprime aviso no meio do HTML — vai para `error_log` e a função retorna.
- [x] `html_editor_ia_prompt()`: `default` do switch sem a referência a `$modelo_texto` (variável inexistente); nenhum modo de IA do repositório usa o marcador `<!-- publisher -->` que o resíduo tentava remover.

### Módulo 4 — CSRF

- [x] Causa-raiz confirmada: `$(form).submit()` usa a propagação simulada do jQuery e cai em `HTMLFormElement.prototype.submit()`; **nenhum dos dois dispara evento `submit`**, então o listener nativo de captura nunca era acionado no salvamento do Editor Visual.
- [x] `global.js` cobre os três caminhos: captura nativa, handler delegado do jQuery e envelope do prototype (instalado uma única vez — segunda carga do asset não empilha outro).
- [x] Token lido de `<meta name="csrf-token">`, `gestor.csrfToken` e `parent.gestor.csrfToken` (iframe `srcdoc`).
- [x] Formulário GET, de outra origem, ou sem token disponível não recebe campo; campo existente é atualizado, nunca duplicado.
- [x] `moduloUrl()` elimina `admin-paginas/editar//` nas três montagens do editor, sem colapsar `https://`.
- [x] `renderWidgets` no `srcdoc` envia `X-CSRF-Token` no cabeçalho e `_csrf_token` no corpo; sem token, dispara sem campo vazio (o backend decide).
- [x] `previsualizarConfirmar` anexa o token e, sem token, **bloqueia** o envio com aviso amigável em pt-br/en.
- [x] `gestor_csrf_resposta_invalida()` preserva o JSON para AJAX e devolve página HTML ("Sessão expirada" + voltar) em navegação normal.
- [x] Campo mantido como `_csrf_token` (contrato do BATCH-107 em `seguranca_csrf_token_requisicao()`), e não `_gestor-csrf-token` como cita o intake — desvio registrado em DEC-104.

### Módulo 5 — OpenGraph

- [x] As seis tags são montadas com escape de aspas/`&` e normalização de espaços/quebras.
- [x] Valor vazio **não** vira tag (um `og:image` vazio faz o WhatsApp exibir card sem imagem).
- [x] `twitter:card` acompanha: `summary_large_image` com imagem, `summary` sem.
- [x] Página/layout com OpenGraph próprio no `html_extra_head` não recebe o conjunto do core (`gestor_open_graph_existe`).
- [x] Fallback gracioso: `pagina#og` → nome/título da página → `config.php` (`site-name`, novas `site-description` e `site-og-image`); imagem relativa é absolutizada com `url-full-http`.
- [x] Injeção pulada em `paginaIframe`.
- [x] Crawler sem sessão em página protegida recebe `200` com apenas `<head>` (título + `noindex` + OpenGraph), sem incluir o módulo da página; humano sem login continua indo para `/signin/`.

### Evidência de Validação (BATCH-109)

Reportada pelo executor em 2026-08-13:

- `php -l` → `gestor/gestor.php`, `gestor/bibliotecas/gestor.php`, `gestor/bibliotecas/log.php`, `gestor/bibliotecas/html-editor.php`, `gestor/config.php`, `gestor/modulos/perfil-usuario/perfil-usuario.php` → **6/6 OK**.
- `node --check` → `gestor/assets/global/global.js`, `gestor/assets/interface/html-editor-interface.js` → **2/2 OK**.
- `bash -n` → `dev-environment/docker/entrypoint.sh`, `dev-environment/docker/gerenciar-sites.sh` → **2/2 OK**.
- `git diff --check` → **OK**.
- `composer test` (PHPUnit) → **200/200** (816 assertions, 4 skipped pré-existentes), com o novo `CrawlersOpenGraphTest` **15/15**.
- `npx vitest run` → **171/171** em 15 arquivos (antes 143/143), com os novos `global-csrf.test.js` **14/14** e `html-editor-csrf-url.test.js` **14/14**.
- Ajuste em teste existente: o stub de jQuery de `global-auth-redirect.test.js` ganhou `on()` — o `global.js` passou a registrar um handler delegado de `submit`.
- Avisos de rede do Happy DOM em `html-editor-embed.test.js` são pré-existentes (registrados desde o BATCH-105) e não exercitam arquivos deste batch.

### Pendências

- **Deploy `Update => Core` + homologação runtime (operador)**:
  - compartilhar uma URL pública no WhatsApp/Telegram/Twitter e conferir que o card traz título, descrição e imagem (e não a página de cookies obrigatórios);
  - abrir uma página pública em janela anônima e conferir que **não há** 302 para `_gestor-cookie-verify/`, mas o cookie chega no `Set-Cookie` da resposta;
  - conferir que login/signup continuam fazendo o round-trip de cookie e que a área restrita segue exigindo sessão;
  - compartilhar URL de página protegida e conferir o card sem conteúdo privado; abrir a mesma URL como humano deslogado e conferir o `/signin/?url=…`;
  - abrir `cookies-is-mandatory/` e conferir no DevTools que nenhuma requisição a `googletagmanager.com`/`connect.facebook.net` acontece;
  - no Editor Visual: renderizar widgets no preview (sem 403) e salvar uma página (sem JSON cru na tela);
  - conferir a gravação dos logs pelo Apache e pelo CLI após recriar o container.
- **Configuração opcional**: definir `SITE_DESCRIPTION` e `SITE_OG_IMAGE` no `.env` do projeto para o fallback de compartilhamento.
- **Observação não alterada**: todos os `setcookie` do core usam `'secure' => true`. Em HTTP puro o navegador descarta o cookie; a verificação silenciosa remove o impacto em páginas públicas, mas os fluxos de autenticação sobre HTTP continuam dependendo de HTTPS. Mudar isso é decisão de postura de segurança do Chefe.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-110 — Metadados da Página, Imagem de Destaque, UI na Editbar/Editor e Sitemap XML (req-110, 2026-08-13)

### Módulo 1 — Colunas e CRUD

- [x] Migração `20260813120000_add_seo_metadata_to_paginas.php` acrescenta `imagem_destaque` (varchar 500), `og_titulo` (varchar 255) e `og_descricao` (text), com `hasColumn` idempotente e `down()` simétrico.
- [x] Grava-se o CAMINHO do arquivo, não o `id_arquivos` — coerente com a árvore física do BATCH-090 e já aceito pelo `imagepick`.
- [x] `admin-paginas`: as três colunas entram em `adicionar`, `editar` e `clonar` (campos de banco, gravação e leitura).
- [x] Na edição, limpar um campo GRAVA vazio e devolve o fallback (comparação sobre o valor do request, não `isset`).
- [x] O roteador seleciona as colunas na montagem da página completa e preenche `$_GESTOR['pagina#og']`.
- [x] `gestor_pagina_og_do_registro()` devolve apenas chaves preenchidas — chave presente e vazia venceria o nome da página em `gestor_open_graph_dados()`.
- [x] Registro sem as colunas novas (base ainda não migrada) não quebra: devolve array vazio.

### Módulo 2 — Aba "SEO & Compartilhamento"

- [x] Componente `html-editor-seo` criado em pt-br e en, com título social, descrição social e o placeholder da imagem.
- [x] A aba entra no `html-editor` entre blocos removíveis; sem o parâmetro `seo`, menu e conteúdo são removidos e o conjunto de abas fica idêntico ao de antes (layouts, componentes, demais alvos).
- [x] A imagem usa o `imagepick` de `interface_formulario_campos()` — nenhum código novo de seletor foi escrito.
- [x] 9 variáveis novas em `variables.json` (pt-br/en); componente registrado com `version 1.0` e checksums vazios; `html-editor` com checksums esvaziados e versão bumpada (pt-br 1.24→1.25, en 1.10→1.11).
- [x] Diffs dos JSON de recursos limpos (sem reescrita em massa e preservando o escape `\/` do `json_encode` do PHP no `dashboard.json`).

### Módulo 3 — Botão de Configurações da Página na Editbar

- [x] Botão `c2f-page-config-btn` ao lado do `c2f-ai-btn` nos dois idiomas.
- [x] A Editbar posta `c2f-toolbar:page-config` com posição e `page_id`; o painel é montado na página hospedeira.
- [x] O painel carrega os valores atuais, mostra a imagem quando existe e avisa quando não existe.
- [x] "Escolher…" abre o `admin-arquivos` em overlay; a seleção chega pelo contrato do picker do editor e vira a imagem de destaque.
- [x] Seleção recebida com o seletor FECHADO é ignorada (não sequestra outro picker da página).
- [x] "Remover" limpa só a imagem, preservando o que já foi digitado.
- [x] "Salvar" envia POST com `page_id`, `og_titulo`, `og_descricao` e `imagem_destaque`, e confirma na interface.
- [x] Erro do backend aparece para o usuário; falha ao carregar não deixa o painel travado em "Carregando".
- [x] Clique na Editbar fecha o painel, EXCETO com o seletor de arquivos aberto.
- [x] Endpoints atrás de `gestor_acesso('editar','admin-paginas')` e do isolamento multiusuário `dashboard_site_toolbar_verificar_permissao_pagina()`; caminho da imagem saneado por `arquivo_caminho_relativo_seguro()`.

### Módulo 4 — Sitemap XML

- [x] Biblioteca `sitemap.php` registrada em `config.php`; arquivo gravado na raiz pública, servida direto pelo `.htaccess` (`RewriteCond %{SCRIPT_FILENAME} !-f`).
- [x] Elegibilidade exclui: `tipo != 'pagina'`, `sem_permissao` falso, `status != 'A'`, rotas utilitárias (`cookies-is-mandatory`, `404`, `_gestor-cookie-verify`, signin/signup/dashboard) e páginas fora da janela de publicação.
- [x] Data zerada do MySQL (`0000-00-00 00:00:00`) não invalida a página.
- [x] `sitemap_xml_montar()` produz XML válido (`simplexml_load_string`), com `lastmod` só quando há data.
- [x] `upsert` acrescenta URL nova, ATUALIZA a existente sem duplicar e preserva as demais entradas byte a byte.
- [x] `upsert` sobre arquivo corrompido gera um sitemap novo e válido.
- [x] Remoção tira só a URL alvo, inclusive quando a URL tem querystring escapada (`&amp;`); URL inexistente não altera nada.
- [x] Acionamento: chamada direta em adicionar/editar/clonar e `callbackFunction` em status/excluir.
- [x] Falha de gravação não interrompe o CRUD (vai para o log).

### Evidência de Validação (BATCH-110)

Reportada pelo executor em 2026-08-13:

- `php -l` → `gestor.php`, `config.php`, `bibliotecas/gestor.php`, `bibliotecas/sitemap.php`, `bibliotecas/html-editor.php`, `bibliotecas/log.php`, `admin-paginas.php`, `dashboard.php`, `perfil-usuario.php` e a migração → **10/10 OK**.
- `node --check` → `global.js`, `html-editor-interface.js`, `dashboard.toolbar.js`, `dashboard.iframe-toolbar.js` → **4/4 OK**. Parse de `dashboard.json`, `components.json` e `variables.json` (pt-br/en) → OK.
- `git diff --check` → **OK**.
- `composer test` (PHPUnit) → **223/223** (878 assertions, 4 skipped pré-existentes), com o novo `SitemapTest` **19/19** e 4 casos novos em `CrawlersOpenGraphTest`.
- `npx vitest run` → **181/181** em 16 arquivos, com o novo `dashboard.page-config.test.js` **10/10**.
- Armadilha registrada no caminho: o stub de `fetch` do teste precisou casar o valor EXATO de `ajaxOpcao` — `site-toolbar-page-config` é prefixo de `site-toolbar-page-config-save`, e o match por substring devolvia a resposta errada, mascarando o teste de erro.

### Pendências

- **Migração obrigatória (operador)**: rodar o Phinx para criar as três colunas. Sem elas, o `SELECT` do roteador falha — a atualização de banco precisa vir ANTES do deploy do core.
- **Deploy `Update => Core` (operador)**: o componente `html-editor`, o novo `html-editor-seo`, as variáveis e a página `dashboard-site-toolbar` vêm do BANCO.
- Runtime, após migração + deploy:
  - editar uma página em `/admin-paginas/editar/`, abrir a aba "SEO & Compartilhamento", preencher os três campos (escolhendo a imagem pelo gerenciador) e salvar; recarregar e conferir a persistência;
  - conferir no HTML público que `og:title`/`og:description`/`og:image` refletem os valores gravados, e que limpar os campos devolve o fallback do `config.php`;
  - compartilhar a URL no WhatsApp e conferir o card com a imagem de destaque;
  - no Live Editor, abrir "Configurações", trocar a imagem pelo seletor, salvar e conferir a persistência sem sair da página;
  - conferir a criação do `sitemap.xml` na raiz e o acesso direto por `https://<host>/sitemap.xml`;
  - criar, despublicar e excluir uma página conferindo entrada, saída e ausência de duplicatas no sitemap;
  - conferir que páginas do painel e páginas com permissão NÃO aparecem no sitemap.
- **Configuração opcional**: `SITE_DESCRIPTION` e `SITE_OG_IMAGE` no `.env` continuam sendo o fallback global (BATCH-109).
- **Fora de escopo, registrado**: o formulário do `publisher-pages` não recebeu a aba de SEO. As publicações gravam na MESMA tabela `paginas`, então as colunas já valem para elas e o roteador já as lê; o painel da Editbar também as edita. Falta apenas a aba no CRUD daquele módulo — slice pequeno e isolado, melhor num batch próprio.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-111 — Reversão do bloqueio de analytics e fim do laço de verificação de cookie (CR-001, 2026-08-13)

### Diagnóstico (medido, não inferido)

- [x] Medição em produção com `curl` em 2026-08-13, **antes de qualquer deploy** das correções: `snapphoton.com` (2.9.34) e `conn2flow.com` (2.9.33).
- [x] Navegador real (com cookie jar): 2 saltos → **200 OK** na home.
- [x] Cliente stateless (sem cookies): **laço infinito**, `curl` aborta com "too many redirects".
- [x] **Googlebot** (não persiste cookie entre requisições): mesmo laço — os sites estavam praticamente invisíveis para o buscador.
- [x] Cadeia observada: `/` → `_gestor-cookie-verify/<id>/?url=` → `cookies-is-mandatory/` → `_gestor-cookie-verify/<id>/?url=cookies-is-mandatory%2F` → `cookies-is-mandatory/` → …
- [x] Causa isolada: a própria `cookies-is-mandatory/` é uma página e reentra em `gestor_cookie_verificacao()` ao ser renderizada.
- [x] User-Agent do WhatsApp deu o MESMO laço, confirmando que o BATCH-109 não estava deployado.
- [x] `git show HEAD:gestor/gestor.php` confirma o `header("Location: …"); exit;` incondicional — **o defeito está na `main` de hoje**, não só nas versões antigas dos domínios.
- [x] HTTPS conferido nos dois domínios: `http://` → 301 para `https://` nos quatro hostnames, HSTS `max-age=31536000`, cookies com `secure; HttpOnly; SameSite=Lax`. **A hipótese do cookie `secure` derrubado em HTTP foi descartada.**

### Reversão do bloqueio de analytics (req-109 §3/§4)

- [x] Removido o bloco que zerava `project-javascript` e filtrava `html-extra-head`/`javascript`/`javascript-fim`.
- [x] Removida a função `gestor_rastreamento_remover()` (código morto após a reversão).
- [x] Removido o neutralizador de `fbq`/`dataLayer`/`gtag` e o flag `gestor.rastreamentoBloqueado` do `global.js`.
- [x] Removidos os 6 casos de teste que cobriam o bloqueio (3 PHP + 3 JS).
- [x] **Nenhuma página do sistema bloqueia coletor de analytics** — os coletores voltam a receber tudo, sem exceção.
- [x] `gestor_pagina_sistema_sem_rastreamento()` renomeada para `gestor_pagina_rota_sistema()` e preservada: o `sitemap_pagina_elegivel()` do BATCH-110 depende dela.

### Fim do laço

- [x] A decisão virou `gestor_cookie_verificacao_desfecho()`, função PURA, com três desfechos: `ignorar`, `emitir`, `redirecionar`.
- [x] Página pública sem cookie → `emitir` (Set-Cookie e segue renderizando; zero redirecionamento).
- [x] **Rota de sistema → `emitir` MESMO com `exigir_sessao = true`** — é a linha que fecha o laço.
- [x] Fluxo de login/cadastro → `redirecionar` (a prova de cookie continua existindo onde ela importa).
- [x] Robô ou cookie já presente → `ignorar`.
- [x] Entrada vazia/inválida → `emitir` (desfecho seguro: servir a página).
- [x] `gestor_permissao()` deixou de chamar `gestor_cookie_verificacao(true)` — redundante, custava um salto a mais antes do login.

### Rotas de sistema fora do índice

- [x] `<meta name="robots" content="noindex, nofollow">` no `<head>` das rotas de sistema.
- [x] `X-Robots-Tag: noindex, nofollow` no cabeçalho HTTP, com guarda de `headers_sent()`.

### Tokens de robô em duas camadas

- [x] `gestor_crawler_tokens_padrao()` — baseline embutido, sempre ativo, de 29 para **50 tokens**.
- [x] Entraram os bots de anúncio e auditoria: `adsbot-google`, `mediapartners-google`, `googleother`, `google-extended`, `storebot-google`, `chrome-lighthouse`, `gtmetrix`, `ahrefsbot`, `semrushbot`, `mj12bot`, `dotbot`, `screaming frog`, `petalbot`, `amazonbot`, `uptimerobot`, `pingdom`, `statuscake`, `better uptime`.
- [x] `gestor_crawler_tokens_extra()` — lida do `.env`, **desligada por padrão**.
- [x] `gestor_crawler_tokens_normalizar()` — aceita vírgula, `;` e quebra de linha; minúsculas, sem vazios e sem duplicatas.
- [x] Baseline continua valendo com a lista extra desligada — o OpenGraph de página protegida (req-109 §10) não regride.
- [x] UI em Ambiente → Configurações do Site (pt-br/en): toggle, textarea e baseline como referência somente leitura.
- [x] Navegador humano continua NÃO sendo classificado como robô.

### Evidência de Validação (BATCH-111)

Reportada pelo executor em 2026-08-13:

- `php -l` → `gestor.php`, `bibliotecas/gestor.php`, `bibliotecas/sitemap.php`, `config.php`, `admin-environment.php` → **OK**.
- `node --check` → `global.js`, `admin-environment.js` → **OK**. Parse do `admin-environment.json` → OK.
- `git diff --check` → **OK**.
- `composer test` (PHPUnit) → **229/229** (893 assertions, 4 skipped pré-existentes). `CrawlersOpenGraphTest` foi de 15 para **25 casos**, com 5 dedicados à blindagem do laço.
- `npx vitest run` → **178/178** em 16 arquivos (eram 181; os 3 casos do bloqueio de rastreamento saíram junto com o código).

### Pendências

- **Deploy `Update => Core` — prioritário.** É ele que tira os sites do laço. Enquanto não subir, todo cliente sem cookie continua rodando em círculo.
- Após o deploy, repetir a medição que expôs o defeito:
  - `curl -s -o /dev/null -L --max-redirs 8 -w "%{num_redirects} %{url_effective} %{http_code}"` sem cookie jar → esperado **0 saltos, 200** na home;
  - o mesmo com User-Agent do Googlebot → **200**;
  - com cookie jar → **200** (comportamento humano preservado);
  - `/signin/` num navegador sem cookies → deve chegar em `cookies-is-mandatory/` e **parar ali**, renderizando a página (sem laço).
- Conferir no DevTools que GTM/Meta Pixel voltam a disparar em `404` e em `cookies-is-mandatory/`.
- Conferir a seção "Robôs e Rastreadores" em Ambiente → Configurações do Site, salvar com o toggle ligado e um token de teste, e confirmar a persistência no `.env`.
- **Fora do código**: pedir a remoção da `cookies-is-mandatory/` já indexada pelo Search Console, se houver pressa; o `noindex` resolve sozinho na próxima passagem do robô.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-112 — Sitemap em assets, 301, aba SEO no publisher-pages, isolamento da Editbar e meta tags (req-112, 2026-08-14)

### Módulo 1 — Sitemap

- [x] `sitemap_caminho_arquivo()` aponta para `assets/sitemap.xml`, criando o diretório se faltar.
- [x] `arquivo-estatico.php` já serve o arquivo sem alteração: o `default:` do switch resolve extensão desconhecida contra `assets-path` e `xml` já está na tabela de Content-Type (`application/xml`). Nenhuma mudança no controlador nem no `.htaccess`.
- [x] `sitemap_sincronizar_pagina()` e `sitemap_sincronizar_por_id()` aceitam `$caminhoAntigo`; a URL antiga sai ANTES de a nova entrar.
- [x] Elegibilidade não olha mais `tipo`: `/signin/`, `/signup/` e `/forgot-password/` entram.
- [x] Painel administrativo continua fora — o critério passou a ser exclusivamente a permissão.
- [x] `sitemap_caminho_nao_indexavel()` barra as rotas públicas que não são conteúdo (callbacks de OAuth, `social-login`, `signin-2fa`, `validate-user`, `email-confirmation`, `forms-submissions-process`, `pagina-de-impressao`, `dashboard-site-toolbar`, `*-confirmation`, `*/success`, `admin-*`).
- [x] `sitemap_gerar_completo()` deixou de filtrar por `tipo='pagina'` no SQL; a triagem fina fica na função de elegibilidade.

### Módulo 2 — Registro 301

- [x] Causa isolada: `interface_modulo_variavel_valor()` chama `gestor_redirecionar_raiz()` quando não encontra o registro — `exit` no meio da gravação — e aplica filtro por `id_hosts` que não vale para estes módulos.
- [x] Substituída por `banco_select_name` direto (id textual atual + idioma + status), no `admin-paginas` e no `publisher-pages`.
- [x] Sem id numérico, o 301 é pulado e o motivo vai para `log_disco` — a requisição não é derrubada.
- [x] Anti-duplicata: o mesmo caminho antigo não gera segunda linha em `paginas_301` (A → B → A → B).

### Módulo 3 — publisher-pages

- [x] Cinco colunas de SEO em `camposBanco` (editar e clonar), gravação (adicionar e clonar), atualização (editar) e leitura.
- [x] Aba "SEO & Compartilhamento" nas três telas, com o campo `imagepick` de imagem de destaque.
- [x] Sitemap sincronizado em adicionar, editar (com limpeza da URL antiga), clonar, status e excluir (os dois últimos por `callbackFunction`).
- [x] Vazio GRAVA vazio na edição (mesmo contrato do `admin-paginas`).

### Módulo 4 — Image Picker

- [x] `$fileId` inicializado no ramo de fallback físico, eliminando o `PHP Warning: Undefined variable $fileId`.
- [x] Valor adotado é o caminho relativo — desde o BATCH-090 é ele o identificador do arquivo, e é o que o picker devolve em `id`.
- [x] O ramo com registro no banco continua usando `id_arquivos`, sem alteração.

### Módulo 5 — Isolamento do painel da Editbar

- [x] **Causa-raiz**: `c2f-page-config-panel` e `c2f-page-config-picker` não estavam em `isEditorOwned()` — omissão do BATCH-110.
- [x] Registrados nos três pontos do contrato de UI do editor: `isEditorOwned` por id, `isEditorOwned` por `closest` e `extractUserHtml`.
- [x] `z-index` elevado, `pointer-events: auto` e `isolation: isolate` explícitos no painel e no overlay do seletor.
- [x] Propagação barrada na fase de **bolha** — em captura o `stopPropagation()` impediria o evento de chegar ao próprio botão dentro do painel.
- [x] Teste de regressão cobrindo os dois lados: evento não vaza para o documento, e o botão dentro do painel continua funcionando.

### Módulo 6 — Meta description e keywords

- [x] Migração `20260814100000` com `meta_descricao` (text) e `meta_keywords` (varchar 500), idempotente e com `down()` simétrico.
- [x] `gestor_meta_seo_tags()` emite `description` e `keywords` com escape e normalização de espaços.
- [x] `keywords` vazia não emite tag.
- [x] `gestor_meta_keywords_normalizar()` aceita vírgula, `;` e quebra de linha; remove vazios e duplicatas (comparadas sem caixa, preservando a grafia da primeira ocorrência).
- [x] `gestor_meta_seo_existe()` impede duplicação quando a página/layout já traz `description` própria.
- [x] Fallback em cascata: metadado próprio → `og_descricao` da própria página → `config.php` (`site-description` / nova `site-keywords`).
- [x] Campos presentes nos três formulários: `admin-paginas`, `publisher-pages` e painel da Editbar.

### Extra — sitemap no `c2f-editbar-save`

- [x] Verificado que `dashboard_ajax_site_toolbar_save()` grava só `html`/`css`/`css_compiled`/`html_extra_head`: **não sobrescreve os metadados** de SEO nem toca no `caminho` — sem risco de perda de dados e sem necessidade de 301 nesse caminho.
- [x] Como ele atualiza `data_modificacao`, o `<lastmod>` ficava defasado; a sincronização foi acrescentada, com a falha isolada em `try/catch`.

### Evidência de Validação (BATCH-112)

Reportada pelo executor em 2026-08-14:

- `php -l` → `gestor.php`, `config.php`, `bibliotecas/gestor.php`, `bibliotecas/sitemap.php`, `bibliotecas/interface.php`, `bibliotecas/html-editor.php`, `admin-paginas.php`, `publisher-pages.php`, `dashboard.php` e a migração → **10/10 OK**.
- `node --check` → `html-editor.js`, `dashboard.toolbar.js` → **2/2 OK**. Parse de `dashboard.json`, `publisher-pages.json`, `components.json` e `variables.json` (pt-br/en) → OK.
- `git diff --check` → **OK**.
- `composer test` (PHPUnit) → **241/241** (938 assertions, 4 skipped pré-existentes). `SitemapTest` 19→24 e `CrawlersOpenGraphTest` 25→31.
- `npx vitest run` → **181/181** em 16 arquivos; `dashboard.page-config.test.js` 10→13.

### Pendências

- **Migração Phinx obrigatória, ANTES do deploy** — sem `meta_descricao`/`meta_keywords` o `SELECT` do roteador falha.
- **Deploy `Update => Core`** — o componente `html-editor-seo`, as variáveis novas e a página do toolbar vêm do banco.
- Runtime, após migração + deploy:
  - acessar `https://dominio/sitemap.xml` e conferir que responde `200` com `application/xml`;
  - renomear o caminho de uma página e conferir: URL antiga **fora** do XML, nova dentro, e uma linha nova em `paginas_301` (repetir a troca e conferir que não duplica);
  - conferir que `/signin/` e `/signup/` aparecem no sitemap e que `oauth-callback/`, `signin-2fa/` e as páginas de confirmação **não** aparecem;
  - preencher Meta Descrição e Palavras-chave numa página e conferir `<meta name="description">` e `<meta name="keywords">` no HTML público, além do fallback quando vazios;
  - repetir o ciclo completo no `publisher-pages` (adicionar, editar com troca de slug, clonar, desativar, excluir) conferindo o sitemap a cada passo;
  - no Live Editor: passar o mouse sobre o painel de Configurações e confirmar que **nada** da página atrás é realçado; clicar em "Escolher Imagem" e "Remover" e confirmar resposta no primeiro clique;
  - salvar pela Editbar (`c2f-editbar-save`) e conferir que o `<lastmod>` da página foi atualizado no XML;
  - conferir que o `PHP Warning: Undefined variable $fileId` sumiu do log ao abrir um formulário com imagem escolhida.
- **Configuração opcional**: `SITE_KEYWORDS` no `.env` como fallback global de palavras-chave.
- **Observação para o Chefe**: o intake pedia o Módulo 5 em `dashboard.iframe-toolbar.js`, mas o painel `c2f-page-config-panel` é injetado na página hospedeira por `dashboard.toolbar.js` — foi lá (e no `html-editor.js`) que a correção entrou. O arquivo do iframe não precisou de alteração.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

### Rodada 2 do BATCH-112 — cache-bust do motor no Live Editor (mesma data)

Reportado pelo Chefe na homologação: o painel continuava selecionando o elemento atrás, e bumpar
`biblioteca-html-editor` para `1.5.10` não surtiu efeito.

- [x] **Causa**: o `html-editor.js` tem DOIS consumidores com cache-bust independente — o editor
      clássico (via `biblioteca-html-editor.versao`) e o Live Editor (via a string FIXA `?v=c2f18` em
      `dashboard.toolbar.js:176`). Só o primeiro havia sido bumpado, então o Live Editor seguia
      servindo o arquivo em cache, sem o `isEditorOwned` do M5.
- [x] `?v=c2f18` → `?v=c2f19`.
- [x] **Segundo defeito encontrado no caminho**: `dashboard.toolbar.js` era incluído sem a chave
      `versao`, caindo em `$_GESTOR['versao']` (versão do SISTEMA, que só muda em release) — toda
      alteração nele entre releases ficava presa no cache mesmo após o deploy.
- [x] **Cache-bust unificado (decisão do Chefe)**: a versão passou a ser a da biblioteca
      `html-editor`, e não a do módulo `dashboard` — Editbar e motor mudam juntos, então um número só
      governa os três consumidores. A string fixa `?v=c2fNN` foi ELIMINADA do `dashboard.toolbar.js`
      (a função `versaoHtmlEditor()` lê `gestor.htmlEditorVersao`, degradando para `gestor.versao`).
      Biblioteca bumpada para `1.5.11`. 3 casos de teste cobrindo a resolução da versão.
- [x] `gestor_modulos_dados()` ganhou guarda de `is_file` (id inválido emitia warning no HTML).
- [x] Lint OK; `composer test` **241/241** e `npx vitest run` **181/181** sem regressão.

Pendência runtime da rodada 2: após o deploy, recarregar o Live Editor com cache limpo e conferir no
DevTools que os pedidos usam `?v=1.5.11` (motor e toolbar); então
passar o mouse sobre o painel de Configurações (nada da página atrás pode ser realçado) e clicar em
"Escolher Imagem"/"Remover" (resposta no primeiro clique).

### Rodada 3 do BATCH-112 — modos de IA na Editbar (fora do escopo, pedido do Chefe)

- [x] `#c2f-ai-mode` da Editbar passa a listar apenas `paginas-editbar`.
- [x] O modo clássico `paginas` **continua registrado** e disponível no editor dos módulos — a remoção
      é pontual, só neste select.
- [x] Fallback para a lista completa quando `paginas-editbar` não existe (instalação com deploy
      pendente) — sem ele o Assistente de IA ficaria sem opção selecionável.
- [x] Decisão extraída para o método puro `aiModosVisiveis()`, com 3 casos em
      `html-editor-view-options.test.js` (filtra, faz fallback, tolera entrada vazia/inválida).
- [x] `node --check` OK; Vitest **184/184**.

## BATCH-115 — Rodada 2: bridges privados

- [x] `busca-clinica-runtime-tailwind` removido de pt-br/en e do `ComponentesData.json`.
- [x] Sete estados da busca clínica existem como `<template>` no componente localizado
  `busca-clinica-runtime-fragments`.
- [x] JavaScript preenche texto e atributos pelo DOM; HTML oficial de protocolos continua vindo do
  renderizador do servidor.
- [x] Componente da busca é anexado pelo PHP sem depender de alteração na página `user_modified`.
- [x] `subscriptions-runtime-tailwind` removido de pt-br/en e do `ComponentesData.json`.
- [x] Cards de preço gratuito, sob medida e pago existem como seis recursos localizados com
  `css_precompiled` não vazio.
- [x] Controlador de assinaturas não contém as classes Tailwind desses cards.
- [x] Gerador privado concluiu 66/66 compilações, zero erros e reportou somente dois recursos com
  fontes adicionais (`snapphoton-system`, pt-br/en).
- [x] Escala global restaurada ao padrão de 16px após confirmação de zoom de 110% no navegador.
- [x] Testes Node estruturais: **4/4**; `node --check`, dois `php -l` e `git diff --check`: OK.
- [ ] Publicar/sincronizar os novos componentes no banco local e homologar busca clínica e checkout.
- [ ] Migrar `snapphoton-system` por famílias de tela antes de remover sua ponte.
