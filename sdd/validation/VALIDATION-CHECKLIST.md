# Validation Checklist

Use este checklist para validar batches no conn2flow sem perder de vista o baseline operacional do repositÃ³rio.

## Onboarding SDD repo-wide

- [x] CLAUDE.md instalado na raiz do repositÃ³rio
- [x] .claude/ instalado com agents, rules, skills e settings do Claude Code
- [x] .github/copilot-instructions.md instalado
- [x] .github/instructions/, .github/prompts/, .github/skills/ e .github/agents/ com artefatos SDD do Copilot
- [x] sdd/scripts/hooks/ criado com hooks de sessÃ£o SDD
- [x] sdd/human-requests/ ativo
- [x] sdd/README.md, process/, implementation/, validation/ e decisions/ criados
- [x] sdd/00-baseline-architecture.md criado com preservaÃ§Ã£o do legado

## Checklist mÃ­nimo por batch

- [ ] O batch estÃ¡ registrado em sdd/implementation/BATCH-INDEX.md
- [ ] O impacto foi comparado contra sdd/00-baseline-architecture.md
- [ ] A menor validaÃ§Ã£o executÃ¡vel do slice foi definida antes de editar mais do que o necessÃ¡rio
- [ ] Scripts, tasks ou paths alterados continuam coerentes com dev-environment/data/environment.json
- [ ] NÃ£o houve reescrita ampla do legado sem mudanÃ§a normativa aprovada
- [ ] O review findings-first foi feito quando a mudanÃ§a ficou pronta para avaliaÃ§Ã£o

## Quando o batch tocar operaÃ§Ã£o local

- [ ] Validar a task do VS Code mais prÃ³xima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execuÃ§Ã£o correspondente
- [ ] Se tocar sincronizaÃ§Ã£o de projeto, validar source/target/path no environment.json
- [ ] Se tocar plugins, validar o fluxo na Ã¡rvore dev-plugins/

## EvidÃªncia mÃ­nima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendÃªncias ou riscos restantes

## Regra final

Se nÃ£o houver validaÃ§Ã£o executÃ¡vel no slice atual, o batch deve registrar explicitamente por que a validaÃ§Ã£o ficou documental ou manual.

## ValidaÃ§Ãµes de Batches Arquivados

Para manter o checklist de validaÃ§Ãµes leve e eficiente (teto de 25 blocos ativos na REQ-051), as validaÃ§Ãµes anteriores foram arquivadas:
- **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)** (BATCH-001 a BATCH-017)
- **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)** (BATCH-018 a BATCH-053)
- **[validation-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-054-093.md)** (BATCH-054 a BATCH-093)
- **[validation-094-110.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-094-110.md)** (BATCH-094 a BATCH-110)
- **[validation-111-134.md](archive/validation-111-134.md)** (17 blocos históricos entre BATCH-111 e BATCH-134; ordem documental preservada)

---
## BATCH-136 — Mirrors de projeto em `auth:cookie` e `env:*` (req-134)

- [x] Resolução compartilhada de `devProjects[ID]` com precedência `path_tests → target → path`.
- [x] Conversão de paths MSYS (`/c/...`) para paths nativos no host Windows.
- [x] Derivação do mount `/var/www/sites/...` a partir do mirror em `dev-environment/data/sites`.
- [x] Busca do `.env` em `autenticacoes/<host>/.env`, com fallback para `<gestor>/.env`.
- [x] `env:status --project=snapphoton-local` apontou para o `.env` do mirror Photon.
- [x] `env:set development --project=snapphoton-local` alterou somente o mirror; restauração do valor
      original foi confirmada no mesmo ciclo de teste.
- [x] `auth:cookie --project=snapphoton-local` detectou `conn2flow-app`, executou o gerador dentro do
      container e gravou `temp/agent-cookies.txt` no host.
- [x] Temporários do gerador removidos em sucesso e erro; zero resíduos `.c2f-auth-cookie-*`.
- [x] `php -l`: 7/7 arquivos limpos; `git diff --check`: limpo.
- [x] Testes focados: 14 testes, 36 asserções.
- [x] Suíte PHPUnit: **765/765**, 3.285 asserções, 4 skips de ambiente e 1 depreciação preexistente.
- [x] Review findings-first: sem findings.
- [x] Memória de execução medida e podada no fechamento; métricas registradas no BATCH-136.
- [x] Nível 1 respeitado: nenhum commit, push ou deploy.

---

## BATCH-137 — Controle de animações do SO via CLI (req-135)

- [x] `motion:status`, `motion:on`, `motion:off` e `motion:toggle` registrados no dispatcher.
- [x] Aliases `motion:get` e `anim:status|on|off|toggle` cobertos por teste e help real.
- [x] Windows usa somente `SystemParametersInfoW`, com `SPI_GETCLIENTAREAANIMATION` (`0x1042`),
      `SPI_SETCLIENTAREAANIMATION` (`0x1043`) e flags `SPIF_UPDATEINIFILE | SPIF_SENDCHANGE` (`3`).
- [x] Guardas automatizadas confirmam ausência de `MinAnimate`, `UserPreferencesMask` e Registro.
- [x] Linux usa `gsettings org.gnome.desktop.interface enable-animations`; macOS usa
      `defaults com.apple.universalaccess reduceMotion`, com inversão semântica coberta.
- [x] Plataforma não suportada emite mensagem informativa e retorna `0` sem executar processo.
- [x] `php -l`: 3/3 arquivos limpos.
- [x] PHPUnit focado: **9/9**, 39 asserções.
- [x] PHPUnit completo: **774/774**, 3.324 asserções, 4 skips e 1 depreciação preexistente.
- [x] Runtime Windows real: estado inicial `ON`; toggle confirmou `OFF`; restauração em `finally`
      confirmou `ON` novamente. O navegador recebe lembrete de recarregar a aba com `F5`.
- [x] `git diff --check` limpo; review findings-first sem findings.
- [ ] Runtime Linux/GNOME e macOS — indisponíveis na estação Windows atual; contratos nativos estão
      cobertos por runner injetado sem mutar a preferência do operador durante o PHPUnit.
- [x] Nível 1 respeitado: nenhum commit, push ou deploy.

---

## BATCH-138 — Preservação do HTML no Live Editor e widgets vazios (req-111)

- [x] Gate PHP desliga a higienização quando a Dashboard Site Toolbar está ativa.
- [x] Visitante/fluxo sem toolbar mantém o contrato vigente de `HTML_SANITIZE`.
- [x] Widget vazio delimitado por comentários é mapeado no contêiner pai e reconstruído no save.
- [x] Widget diretamente no `#c2f-layout-root` é selecionável e reconstruído sem atributos internos.
- [x] Testes focados PHP (**37/37**) e JS (**31/31**) aprovados.
- [x] Suítes PHPUnit (**776/776**) e Vitest (**345/345**) aprovadas.
- [x] Runtime Photon em produção: anônimo sem comentários/marcadores/indentação; administrador com
      HTML original, toolbar e marcadores; Playwright abriu o modo de edição sem erros de console.
- [x] Screenshot: `temp/req-111-live-editor.png`.
- [x] `git diff --check` limpo e review findings-first sem findings.
- [x] Ambiente Photon restaurado e confirmado em produção (`DEVELOPMENT_ENV=false`).
- [x] Nível 1 respeitado: nenhum commit, push ou deploy remoto.

---

## BATCH-139 — Limpeza pós-sucesso de releases antigas (req-136)

- [x] Os cinco scripts locais não excluem tags ou releases antigas antes da publicação.
- [x] Branch atual e nova tag são enviadas juntas por `git push --atomic`.
- [x] Os workflows de Gestor e Instalador carregam o histórico completo de tags.
- [x] A limpeza aparece imediatamente após `Create Release` e está protegida por `if: success()`.
- [x] A tag atual é preservada e somente tags/releases anteriores dos padrões
      `gestor-v${TAG_SERIES}.*` ou `instalador-v${TAG_SERIES}.*` são removidas.
- [x] Simulação multi-série preserva outros minors, majors e famílias de tags.
- [x] `bash -n` aprova os cinco scripts e os dois YAMLs são parseados com sucesso.
- [x] `git diff --check` limpo.
- [x] Memória de execução medida em **3.820 bytes / 49 linhas**; nenhuma poda necessária.
- [x] Nível 1 respeitado: nenhum commit, push, deploy ou mutação remota.

---

## BATCH-140 — Seleção em lote no picker, overlay fixo e tamanhos no galleries (req-137)

- [x] `#c2f-pick-selected` existe nas duas páginas (`pt-br` e `en`) e o rótulo vem do sistema de
      variáveis (`pick-selected`), sem literal hardcoded.
- [x] Rótulo definido pela chefia: `"Incluir Selecionados"` / `"Add Selected"` — pareado com
      `"Excluir Selecionados"` e lido do DOM na revalidação visual.
- [x] Fora do iframe o botão permanece oculto **mesmo com item marcado e a barra visível**
      (`display: none`, barra `flex`, contador `1`).
- [x] No picker o botão só aparece depois de haver ao menos um **arquivo** marcado.
- [x] "Selecionar Todos" no picker (1 pasta + 1 arquivo) revela o botão; o despacho emite
      **1 mensagem** — a pasta fica de fora.
- [x] Uma mensagem `postMessage` **por arquivo**, preservando o contrato dos seis consumidores
      (`galleries`, `html-editor`, `html-editor-interface`, `interface-v2`, `dashboard.toolbar`).
- [x] Payload idêntico ao envio individual: `{id, caminho, imgSrc, nome, data, tipo}` com o MIME
      em `tipo`; campos ausentes viram string vazia (não somem no `JSON.stringify`).
- [x] Após o despacho a seleção é zerada no estado e no DOM, e o botão recolhe.
- [x] Modo `medium`: card **208x174** com `width`/`height`/`x`/`y` idênticos com e sem hover.
- [x] Modo `small`: card **120x98** com geometria idêntica com e sem hover.
- [x] Overlay `position: absolute`, `display: flex`, `z-index: 4`, `opacity` 1 no hover e 0 fora,
      com `pointer-events: none` no contêiner (a miniatura e o checkbox seguem clicáveis).
- [x] `.c2f-check` em `z-index: 5`, acima do overlay — o item continua marcável sob o cursor.
- [x] Marcação dos checkboxes sobrevive à troca de modo (2 marcados antes e depois, contador `2`).
- [x] `galleries`: três botões `large`/`medium`/`small`, `type="button"` (não submetem o formulário)
      e tooltips resolvidas pelas variáveis `view-*-title` nos dois idiomas.
- [x] Miniaturas medidas no runtime: **140px** (grande), **85px** (médio) e **50px** (pequeno).
- [x] Preferência persiste em `localStorage` e é restaurada no reload; valor corrompido cai em
      `view-large` sem gerar classe inválida.
- [x] Console **sem erros** em todas as telas inspecionadas; HTTP 200.
- [x] Lint: `node --check` 2/2, `php -l` 2/2, JSON 4/4.
- [x] Testes focados novos **12/12**; Vitest **357/357**; PHPUnit **776/776**.
- [x] Review findings-first: leitura de `estado.selecionados[caminho]` por truthiness trocada por
      `hasOwnProperty` — um arquivo chamado `constructor` nasceria marcado; coberto por teste.
- [x] `c2f resources:sync` 2.660 recursos / 0 erros; `c2f manager:update-all` com 0 órfãos.
- [x] Evidências: `temp/req-137-picker-selecao.png`, `temp/req-137-overlay-medium.png`,
      `temp/req-137-galleries-views.png`, `temp/req-137-admin-arquivos-picker.png`.
- [x] Ambiente local restaurado (`DEVELOPMENT_ENV=false` confirmado por `c2f env:status`).
- [x] `git diff --check` limpo.
- [x] Nível 1 respeitado: nenhum commit, push ou deploy remoto.

---

## BATCH-141 — MIME real e guarda de imagem no interface-v2 (req-138)

- [x] `arquivo_mime_por_extensao()` criada na biblioteca `arquivo.php`, ao lado de
      `arquivo_tipo_por_extensao()`, resolvendo só por extensão (sem tocar no disco).
- [x] `admin-arquivos.php:183` deixou de concatenar rótulo interno com extensão.
- [x] MIMEs que divergiam agora corretos: `image/jpeg`, `image/svg+xml`, `image/x-icon`,
      `audio/mpeg`, `video/quicktime`, `application/pdf`, `application/json`.
- [x] Casos que já coincidiam preservados sem regressão (`image/png`, `video/mp4`, `image/webp`).
- [x] **Invariante de prefixo** verificada por teste sobre as **29 extensões** reais de
      `arquivo_tipo_por_extensao()`: família `image`/`video`/`audio` sempre devolve o mesmo prefixo.
- [x] Fallback `application/octet-stream` para extensão desconhecida, ausente ou arquivo sem ponto.
- [x] Caixa da extensão ignorada (`FOTO.JPG`) e caminho completo aceito (`docs/2026/Manual.PDF`).
- [x] Nenhum consumidor depende do prefixo `file/` (verificado por varredura antes da troca).
- [x] `interface-v2.js`: guarda trocada por `/^image\//.test(dados.tipo)`, igual aos demais.
- [x] Teste demonstra que a guarda antiga era sempre falsa e que a nova aceita os 9 MIMEs de imagem.
- [x] Guarda estática impede reintroduzir a comparação quebrada nos **4** consumidores do canal.
- [x] Comentário do `interface-v2.js` reescrito em prosa: citar o código defeituoso literalmente
      disparava o próprio teste de regressão.
- [x] Lint: `php -l` 2/2, `node --check` 1/1.
- [x] Testes focados novos **14/14** (PHP 8/8 com 97 asserções + JS 6/6).
- [x] Suítes: PHPUnit **784/784** e Vitest **363/363**, sem regressão.
- [x] Runtime local: `asset-version.json` sai como `application/json` (antes `file/json`); nenhum
      item com prefixo `file/` na listagem; despacho em lote do BATCH-140 intacto; console limpo.
- [x] `git diff --check` limpo.
- [x] Nível 1 respeitado: nenhum commit, push ou deploy remoto.

### BATCH-140 — segunda rodada: a grade que faltava no item 3 (retorno de homologação)

- [x] Modos compactos usam `flex-direction: row` + `flex-wrap: wrap`: a lista vira GRADE.
- [x] A **caixa** encolhe, não só a miniatura: `1222x158` → `300x190` (médio) → `147x97` (pequeno).
- [x] Densidade medida no runtime: 1 por linha (grande), **4 por linha** (médio), **8 por linha** (pequeno).
- [x] Modo grande preservado como lista de curadoria detalhada (sem `flex-wrap`).
- [x] Ordem visual da grade = ordem do DOM: esquerda → direita, descendo ao fim da linha.
- [x] Arraste real reordena na grade (`f1,f2,f3,f4…` → `f2,f3,f4,f1…`).
- [x] Arraste atravessando a quebra de linha (último → primeiro) funciona.
- [x] **Ordem serializada para o servidor idêntica à exibida** após o arraste, verificada
      interceptando o payload (`f8,f1,f2,f3,f4,f5,f6,f7`) — o DOM sozinho não provaria isso.
- [x] Controles (handle e remover) em overlay `position: absolute`, revelados no hover.
- [x] A grade não estremece no hover: 10 caixas com `w`/`h`/`x`/`y` idênticos.
- [x] `.sortable-chosen` mantém os controles visíveis durante o arraste.
- [x] Modo pequeno esconde legenda e painel de link; valores preservados no array `items`.
- [x] Colunas reduzem em telas médias (3/5) e estreitas (2/3).
- [x] Guardas de regressão: `galleries.view-modes.test.js` **8/8**.
- [x] Suítes após a rodada: Vitest **367/367**, PHPUnit **784/784**; console limpo.
- [x] Screenshots: `temp/req-137-galleries-grid-medium.png`, `temp/req-137-galleries-grid-small.png`.

---

## BATCH-142 — Densidade, configuração rápida e alinhamento vertical no galleries (req-139)

- [x] Modo médio: **6 cards por linha** em 1920 px e miniatura com **110 px**.
- [x] Modo pequeno: **10 cards por linha** em 1920 px e miniatura com **65 px**.
- [x] Geometria dos 18 cards idêntica antes/depois do hover nos dois modos (zero layout shift).
- [x] Hover medido: borda azul, sombra `rgba(33,133,208,.22) 0 4px 14px`, backdrop
      `rgba(0,0,0,.55)`, raio 4 px, overlay absoluto e `opacity:1`/`pointer-events:auto`.
- [x] Overlay exibe handle, engrenagem e remover; o modo grande mantém a curadoria detalhada.
- [x] Modal rápido pt-BR abriu no card correto e persistiu legenda, `link-custom`, URL e `_blank`.
- [x] `image_position` aceita só `top|center|bottom`; valor inválido/legado cai em `center`.
- [x] Preview real recebeu `top` e `bottom`; estilo computado nas 18 imgs foi `50% 0%` e
      `50% 100%`. Os oito templates (`pt-br`/`en`, quatro modelos) estão cobertos pelo renderer PHP.
- [x] Seis CRUDs localizados e 18 variáveis novas (nove por idioma), sem texto novo literal no modal.
- [x] Testes focados: Vitest **7/7**; PHPUnit **5/5**, **74 asserções**.
- [x] Suítes: Vitest **366/366**; PHPUnit **789/789**, **3.497 asserções**, 1 depreciação e
      4 skips preexistentes.
- [x] `node --check` 2/2; `php -l` 3/3; JSON válido; `git diff --check` limpo.
- [x] `resources:sync`: **2.678** recursos, 233/233 Tailwind em cache na rodada final, zero erros.
- [x] Screenshots: `temp/req-139-medium-overlay.png`, `temp/req-139-quick-settings.png`,
      `temp/req-139-small-bottom.png`; console e `pageerror` vazios.
- [x] Review findings-first: `pointer-events` do overlay no hover corrigido e coberto; nenhum finding
      aberto.
- [x] A base Photon estava vazia/stale e o dry-run avançou checksums sem aplicar recursos. Para não
      forçar recursos do tenant, a prova carregou no editor os arquivos versionados deste lote e usou
      o endpoint PHP/widget JS reais; nenhuma galeria foi persistida.
- [x] Tear down: `snapphoton-local` confirmado em `PRODUCTION` (`DEVELOPMENT_ENV=false`).
- [x] Nível 1: nenhum commit, push ou deploy remoto.

---
## BATCH-143 — 404 em imagens estáticas com hífen/espaço e colisão de upload sem espaço (req-140, 2026-08-28)

### Reprodução do defeito (medida, não inferida)

- [x] Fixtures gravadas com o nome legado (`contents/Ela (1).webp` e `contents/mini/Ela (1).webp`) no
      ambiente local `transformamp` (`http://localhost/transformamp/`).
- [x] Com o controlador **no estado do HEAD**: `GET /Ela-(1).webp` → **404**, `GET /mini/Ela-(1).webp`
      → **404**, `GET /Ela%20(1).webp` → **200**. É exatamente a assinatura relatada em produção.
- [x] Com o controlador corrigido, as mesmas URLs: **200 / 200**, `Content-Type: image/webp`.

### Módulo 1 — fallback no controlador estático

- [x] `/Ela-(1).webp` → **200**; `/mini/Ela-(1).webp` → **200** (critérios de aceite 1 e 2).
- [x] `/Ela%20(1).webp` e `/mini/Ela%20(1).webp` seguem **200**: nenhuma regressão no que já servia.
- [x] Multi-segmento: `Minha Pasta/foto teste.webp` no disco, pedido como
      `/Minha-Pasta/foto-teste.webp` → **200**. Diretório com espaço cai no mesmo mecanismo.
- [x] Cache e streaming preservados NO caminho de fallback: `ETag: "6a91a577-23"`,
      `Cache-Control: public, max-age=86400, stale-while-revalidate=604800`, `Accept-Ranges: bytes`,
      `Last-Modified` presente; `If-None-Match` → **304**; `Range: bytes=0-9` → **206** com
      `Content-Range: bytes 0-9/35`.
- [x] 404 legítimo continua 404 (`/nao-existe-mesmo.webp`), sem 500 e sem mudança de corpo.
- [x] Home do site segue **200** depois da mudança.

### Módulo 2 — colisão de upload

- [x] Loop real da colisão executado dentro do container contra disco de verdade: `Ela.webp` →
      `Ela-(1).webp` → `Ela-(2).webp` → `Ela-(3).webp`, todos sem espaço e todos idempotentes sob a
      sanitização.
- [x] **Upload real autenticado** (cookie de `c2f auth:cookie --project=transformamp` + token CSRF da
      página do módulo, `POST multipart` em `admin-arquivos/`): três envios do mesmo `Ela.webp`
      responderam `caminho` = `req140/Ela.webp`, `req140/Ela-(1).webp`, `req140/Ela-(2).webp`.
- [x] Os três nomes conferidos no disco e servidos por HTTP em **200** pelo caminho DIRETO, sem
      passar pelo fallback (critério de aceite 3).

### Blindagem de regressão

- [x] Os 18 casos das funções novas falham contra o HEAD, mas por função ausente — falha fraca. Por
      isso foi acrescentado `testArquivoDeColisaoContinuaAlcancavelPelaUrlQueOSistemaPublica`, que
      grava o arquivo com o nome do desempate e o procura pelo caminho que os consumidores publicam.
- [x] Reintroduzindo o comportamento do HEAD (sufixo com espaço e sem sanitização final):
      **5 falhas**, com a mensagem `A URL publicada (files/2026/captura-de-tela-(1).png) nao encontrou
      o arquivo gravado (captura-de-tela (1).png).` — o defeito descrito por si mesmo.
- [x] Correção restaurada e suíte reconferida.

### Suítes e lint

- [x] `php -l`: 3 arquivos de produção e 2 de teste, sem erro.
- [x] Testes focados novos: **19** (`AdminArquivosSegurancaTest` 22/22 com os 5 novos;
      `ArquivoEstaticoNomeSanitizadoTest` 14/14).
- [x] PHPUnit: **808/808**, 3.589 asserções (789 antes do lote), 1 depreciação e 4 skips
      preexistentes — a depreciação é do próprio `--filter` do PHPUnit 11 e aparece também em lotes
      não relacionados.
- [x] Vitest: **366/366**, 25/25 arquivos (nenhum JavaScript ou JSON foi tocado neste lote). O
      runner imprime `Unhandled 'error' event` vindo do `happy-dom` ao tentar rede real em
      `BrowserWindow.fetch`/`BrowserFrameNavigator` nos testes de iframe — ruído de ambiente,
      preexistente e sem teste falhando.

### Segurança

- [x] Traversal recusado nas duas portas novas: `../Ela-(1).webp`, `mini/../../Ela-(1).webp` e a
      variante `%2e%2e%2f` decodificada — esta última só entra na lista de candidatos depois de passar
      pela mesma guarda do caminho original.
- [x] `pasta%5carquivo.webp` (barra invertida percent-encoded) também recusado.
- [x] O fallback não autoriza envio: o caminho encontrado continua passando por
      `arquivo_estatico_resolver_autorizado()`, coberto por teste com base autorizada e não autorizada.
- [x] O fallback não alcança `assets/` nem `modulos/`, que não recebem nome de usuário.
- [x] Custo de I/O contido: sem hífen no caminho o fallback nem carrega a biblioteca, e cada segmento
      repete a checagem antes do `scandir`.

### Review findings-first

- [x] **Finding tratado**: a guarda `strpos($caminhoTotal, '-')` olhava só o caminho ORIGINAL, então
      `%2D` (hífen percent-encoded) escaparia dela e o fallback nunca rodaria. Passou a testar todas
      as variantes.
- [x] **Finding documentado, não corrigido**: a guarda por hífen não alcança o nome cuja única
      divergência é a APARA das pontas (`-foto.webp` no disco publicado como `foto.webp`). Esse nome
      não nasce do upload, que já grava sanitizado, e cobri-lo exigiria listar diretório em todo 404
      do site. Registrado no comentário da função e nas limitações do BATCH-143.
- [x] Ambiguidade de duas entradas físicas para a mesma URL resolve pela ordem do `scandir`
      (alfabética): determinística, e a ambiguidade é do dado, não da resolução.
- [x] Nenhum finding aberto.

### Pendências e limitações

- Arquivos legados seguem no disco com espaço; cada requisição a eles paga uma listagem de
      diretório. Normalizá-los em massa é mudança de DADOS e fica para intake próprio.
- Miniatura automática não foi exercitada no upload real porque o WebP sintético de 35 bytes usado
      como payload não é decodível pelo GD; o caminho `/mini/...` foi validado pela fixture física.

### Tear down

- [x] Fixtures e a pasta `req140/` removidas do `contents` local; `contents` conferido de volta ao
      conteúdo original; cookie de teste apagado.
- [x] Espelho local `dev-environment/.../transformamp` sincronizado com a fonte (não versionado).
- [x] Nível 1: nenhum commit, push ou deploy remoto.

---
## BATCH-144 — Procedência do CSS derivado, Quill e auditoria/regeneração (req-141 / CR-002, 2026-08-28)

### Diagnóstico medido (ambiente local, `DEVELOPMENT_ENV=false` = caminho de produção)

- [x] Runtime lê do BANCO (`gestor.php:2782`); disco só em modo de desenvolvimento. Confirmado no
      código e pelo `.env` do `transformamp`.
- [x] Cobertura na resposta HTTP: **80/248** classes sem CSS na home; **85/182** no artigo.
- [x] `c2f css:audit`: **1.279 de 1.410** recursos Tailwind com ao menos uma classe sem regra.
- [x] O HTML da página de publicação JÁ contém as classes do template (expandido no salvamento) —
      o Módulo 1 do intake (injetar CSS do template) não resolveria; falta o CSS da própria página.
- [x] `paginas` de publicação nascem com `css_precompiled=0` e `css_compiled=0`.
- [x] 40 recursos declaram `tailwind_sources` de PHP/JS, **todos do `perfil-usuario`**.

### Quill

- [x] Reprodução: `/artigos/teste-de-artigo/` entregava `.ql-indent-1` **sem nenhuma regra**, e não
      carregava `quill.snow.css`. As classes que funcionavam vinham de um `css_compiled` contaminado.
- [x] Datação: todos os registros contaminados são de junho/julho; os papéis entraram em 17/08
      (req-117). É resíduo histórico, **não** bug ativo — correção da minha hipótese inicial.
- [x] `quill-content.css`: 134 regras da fonte oficial v2.0.3, sem `!important`, sem UI da toolbar.
- [x] Injeção condicional verificada em runtime: página COM Quill inclui o asset; página SEM Quill
      não inclui (0 ocorrências). Asset servido em **HTTP 200**, `text/css`, 10.861 bytes.
- [x] Depois: as 4 classes `ql-*` da página passaram a estar **todas cobertas**.

### Procedência

- [x] Testado contra o banco real: assinar → **COERENTE**; editar HTML → **STALE**; recompilar o
      layout com HTML intacto → **STALE** (o cenário que não tinha sinal algum).
- [x] Coluna `css_source_hash` criada nas 4 tabelas (migração + aplicada no ambiente local).
- [x] Política de atualização: assinatura invalidada quando o deploy toca autoria ou derivado, sem
      apagar CSS. Coberta nos três caminhos de update.

### Regeneração

- [x] `/artigos/teste-de-pagina/` medido por HTTP: **85 → 13** classes sem CSS.
- [x] Lote de 80 páginas: **1.538 → 291 (-81%)**; 25 melhoraram, 0 pioraram após o ajuste dos
      marcadores.
- [x] `layouts` stale: **14 → 0**.
- [x] As 13 restantes são `prose-*`: o projeto usa as classes mas **não tem `@tailwindcss/typography`
      instalado** — dependência ausente do projeto, não falha do mecanismo.
- [x] Modo `--url` (HTML renderizado) produz CSS mais completo (13.745 vs 9.033 bytes na mesma
      página) sem depender de `tailwind_sources`.

### Correções de rumo durante o lote

- [x] A primeira versão do auditor contava recursos Fomantic (que recebem a folha por CDN) e o
      número saía inflado e falso; corrigido antes de reportar.
- [x] `group`/`peer` são marcadores sem regra própria: excluídos da métrica, senão 4 páginas
      apareciam como "pioradas" sem nada ter piorado.
- [x] Em `classList.toggle(classe, condicao)`, só o primeiro argumento é classe — ler o segundo
      inventava `data-perfil-painel` como se fosse classe.
- [x] `regenerarFontesDeclaradas()` chegou a ficar CHAMADA sem estar definida (um assert abortou a
      gravação); detectado pelo próprio teste do `perfil-usuario` e corrigido.

### Dívida identificada

- [x] `perfil-usuario.js`: **26 classes Tailwind montadas em runtime** — a razão das 40 declarações
      do módulo e do seu histórico de quebrar a cada atualização.
- [x] `perfil-usuario.php`: **0 classes** — a declaração apontando para ele é obsoleta.
- [x] Outros 6 arquivos com classes semânticas próprias (menos críticas), listados por `css:audit`.

### Suítes e lint

- [x] `php -l` em todos os arquivos tocados; `node --check` no `html-editor-interface.js`.
- [x] Testes focados novos: **46** (`QuillConteudoTest` 11, `CssProcedenciaTest` 19,
      `CssProcedenciaPoliticaTest` 9, `CssRegeneracaoTest` 8, mais um de marcadores).
- [x] PHPUnit **855/855**; Vitest **366/366**.


### Fase 4 — carimbo no salvamento (2026-08-28)

- [x] 10 pontos de gravação carimbados em 5 módulos; teste estrutural garante que ponto novo sem
      carimbo quebra a suíte.
- [x] `publisher-pages` herda o `css_precompiled` do template no adicionar, editar e clonar.
- [x] Helper degrada com segurança sem schema/banco (devolve string vazia, não explode).
- [x] Nenhum módulo grava `css_precompiled` vindo do formulário — o derivado continua sendo escrito
      só pelo compilador e pela herança.
- [x] PHPUnit **861/861** após a fase 4.
- [ ] **Não verificado em runtime**: o carimbo pelo salvamento da TELA. O POST reconstruído por
      script não representa o formulário fielmente (o `admin-paginas/editar` gravou um HTML menor
      que o enviado e não passou pelo carimbo), então a cobertura da fase 4 é unitária e estrutural.
      Precisa de homologação com salvamento manual pela interface.

### Incidente durante o teste E2E

- [x] Ao restaurar o HTML da home após o teste, uma variável de ambiente não exportada fez o script
      ler um arquivo inexistente e **zerar o campo `html`** de `transforma-mp-raiz`. Detectado na
      mesma execução (o próprio script imprimiu "0 bytes") e restaurado do backup tirado antes do
      teste.
- [x] Conferido ao final: `html` byte a byte idêntico ao backup (md5 `9053904728759271`, 5.392
      bytes), sem resíduo do marcador de teste, home e artigo em HTTP 200.


### Pipeline oficial executado e defeito capturado em flagrante (2026-08-29)

- [x] `c2f manager:update-all`: +18 variáveis, ~16 atualizações, 0 órfãos.
- [x] `c2f project:update-all transformamp-local` (alvo `local: true`): ~20 atualizações, 0 órfãos.
- [x] Consistência core↔mirror: 11 divergências → **6**, todas com o MIRROR mais novo (comportamento
      pretendido do `rsync -u`); 0 arquivos ausentes.
- [x] Migração aplicada pelo caminho oficial: `phinxlog 20260828100000 AddCssSourceHashToResourceTables`
      (substituiu o `ALTER TABLE` manual que eu havia feito).
- [x] **O deploy oficial reproduziu o híbrido**, medido logo após o pipeline no `template-artigo`:

      user_modified   = 1                                (editado online)
      html            = 5890 bytes,  TEM border-r-2      (autoria do usuário, preservada)
      css_precompiled = 8286 bytes,  SEM border-r-2      (derivado do sistema, sobrescrito)
      arquivo no disco:              SEM border-r-2      (fonte do CSS que entrou)
      css_source_hash = (nenhuma)                        (política M2 marcou STALE)

      É a demonstração direta do CR-002: autoria preservada + derivado sobrescrito = estado que
      ninguém compilou. A diferença em relação a antes é que agora ele fica REGISTRADO como stale,
      em vez de ser servido em silêncio.
- [x] Conclusão operacional: **todo deploy que preserva autoria precisa ser seguido de
      `c2f css:rebuild`**, senão o acervo volta ao híbrido.

### Trava de alvo por `local` (environment.json)

- [x] `devProjects.<id>.local` passou a ser a autoridade sobre gravação sem autorização.
- [x] `c2f css:rebuild` imprime `projeto | local | url` antes de qualquer escrita.
- [x] Verificado nos dois sentidos: `transformamp-local` (local=true) prossegue;
      `transformamp` (local=false, `https://novo.transformamp.com/`) é **recusado** com instrução de
      pedir autorização e usar `--confirmar-remoto`.
- [x] Motivo: os pares `<projeto>` / `<projeto>-local` compartilham o mesmo `path_tests`, então só a
      configuração os distingue — e `deploy-project-v2.sh` envia para `PROJECT_URL/_api/project/update`.

### Correção de rumo registrada

- [x] Antes desta rodada eu havia sincronizado o core **copiando arquivos à mão**, o que não executa
      compilação de recursos, migrações nem sincronização de banco. As medições anteriores foram
      feitas sobre um ambiente que o pipeline oficial nunca teria produzido.


### Rebuild completo do acervo e estado final (2026-08-29)

- [x] `c2f css:rebuild --project=transformamp-local` sobre todo o acervo: **1.412 analisados,
      1.285 regenerados, 127 já coerentes, 362 fora do Tailwind, 0 erros**.
- [x] Auditoria final: **stale = 0** e **sem CSS próprio = 0** em paginas, layouts, componentes e
      templates. Antes: 1.114 páginas e 177 templates stale.
- [x] Página do operador (`/artigos/teste-de-pagina-do-quill/`): as 8 classes da legenda
      (`ml-auto`, `text-right`, `border-r-2`, `pr-4`, `italic`, `opacity-70`, `leading-relaxed`,
      `font-medium`) e as 7 classes `ql-*` estão **todas cobertas**.
- [x] Das 12 classes órfãs que restam na página, **nenhuma é utility**: 11 são `prose-*`
      (`@tailwindcss/typography` não instalado no projeto) e 1 é hook semântico.
- [x] Ressalva honesta sobre a métrica agregada: 11.416 → 11.378 classes sem definição no acervo
      (só 4 recursos melhoraram). NÃO é o rebuild falhando — o ganho real ocorreu quando os recursos
      passaram a ter CSS próprio; o que resta é majoritariamente hook semântico e `prose-*`, que
      nenhuma regeneração resolve. A métrica a acompanhar daqui em diante é `stale` e a auditoria por
      página (`css:audit --url=`), não o total agregado.

### Bug encontrado por execução em foreground

- [x] `regenerarCompilar()` estava sem o parâmetro `$fontesExtras`; o `foreach` usava variável
      indefinida e as `tailwind_sources` nunca eram aplicadas. O PHP só emitia warning, que o log em
      background engolia. Apareceu quando o operador apontou que execuções concorrentes travavam o
      processo e passei a rodar em foreground. Corrigido e coberto por teste da assinatura.

### Suítes

- [x] PHPUnit **865/865**; Vitest **366/366**; `php -l` limpo nos 5 arquivos desta rodada.

### Pendências

- ~~Regenerar o acervo completo~~ (feito: 1.285 recursos)
- Regenerar o acervo completo (105 recursos feitos; o restante é tempo de CLI).
- Criação de página pela UI não foi concluída no E2E: o POST de `admin-paginas/adicionar` responde
  302 de volta ao formulário (validação do form), então o ciclo foi provado contra o banco e por
  HTTP nas páginas existentes, não pela tela de criação.
- Fases seguintes do CR-002 (publisher-pages extrair CSS; baseline com dependências).
- Nível 1: nenhum commit, push ou deploy remoto.

## BATCH-146 — Assets de terceiros servidos do disco (req-143)

- [x] `c2f assets:vendor` baixa **28/28** arquivos (2,9 MB), nenhum corpo HTML de erro gravado
      (varredura por `<!doctype`/`<html>` nos 28 arquivos: 0 suspeitos).
- [x] Verificação de certificado ligada: o PHP cURL falhou com `unable to get local issuer
      certificate` e a cadeia caiu para o `curl` do sistema. Nenhum `VERIFYPEER => false` no código.
- [x] URLs locais resolvem no runtime: `vendor/jquery/3.7.1/jquery.min.js` 200/87.533 B,
      `vendor/codemirror/5.65.20/codemirror.min.js` 200/170.536 B,
      `vendor/quill/2.0.3/quill.snow.css` 200/24.606 B.
- [x] O pipeline propaga `vendor/` para o espelho do projeto (7 bibliotecas presentes).
- [x] Tela `variables/?id=usuarios-perfis`: **26 assets do disco, 0 do CDN**.
- [x] Home pública: nenhum CDN de biblioteca; resta `fonts.googleapis.com` (fontes).
- [x] 161 tags de CodeMirror removidas de 7 arquivos PHP, com **paridade blocos↔chamadas** conferida
      por arquivo (2 blocos → 2 chamadas em `admin-atualizacoes`, `admin-modos-ia`,
      `admin-prompts-ia`).
- [x] PHPUnit **902/902** (5 novos em `AssetsExternosTest`); Vitest **378/378**.
- [ ] Homologação visual do operador (editor HTML, telas com CodeMirror, modais do Fomantic).

## BATCH-147 — Cadeia de recursos no galleries, tela de variáveis e alvo do css:rebuild (req-144)

- [x] Home local: **6 âncoras** com `pointer-events-none cursor-default` **e** a regra
      `.cursor-default{cursor:default}` entregue na página (antes: 0 e 0).
- [x] `galleries-estados` sincronizado nos dois idiomas com `target='galleries-estados'` (fora do
      dropdown de modelos) e `css_precompiled` de 156 B contendo as duas regras.
- [x] `variables/?id=usuarios-perfis` HTTP **200**, com **0** links para `adicionar/`, `editar/`,
      `opcao=status` ou `opcao=excluir`; card de inclusão de variável preservado.
- [x] Tipo renomeado ponta a ponta: `<option value="editor-texto">Editor de texto`,
      `class="campo editor-texto"`, **0** ocorrências de `tinymce` no HTML servido.
- [x] Migração Phinx aplicada: 1 rótulo por idioma, 0 órfãos.
- [x] Alias de leitura coberto por teste (`configuracao_campo_tipo('tinymce') === 'editor-texto'`).
- [x] Estágio 6/6 do `project:update-all` grava em `base 'transformamp'` com `local: true` (antes
      gravava em `conn2flow` reportando sucesso).
- [x] Nenhum byte de controle nos arquivos tocados (armadilha recorrente do heredoc de Python).
- [x] PHPUnit **902/902** (16 novos); Vitest **378/378**.
- [ ] Homologação visual do operador (galeria da home, tela de variáveis, campo de editor de texto).
- [ ] Deploy em produção — a home publicada ainda tem o comportamento antigo.

## BATCH-148 — Fontes do projeto e minificação de JavaScript (req-143, req-145)

- [x] Regressão dos ícones do gestor corrigida: 22 arquivos de fonte do Fomantic baixados; as quatro
      principais (`icons`, `outline-icons`, `brand-icons`, `LatoLatin-Regular`) respondem **200**.
- [x] `/transformamp/dashboard/` HTTP 200 com 211 ícones no HTML.
- [x] Google Fonts: 78 faces devolvidas → **28 mantidas**, 50 descartadas por subset; 28 arquivos
      (1,1 MB) + `fonts.css` gravados na FONTE do projeto.
- [x] `<link>`/`preconnect` removidos de 7 layouts e `@import` removido de 1 CSS; **0** referências a
      `fonts.googleapis.com`/`fonts.gstatic.com` nos recursos do projeto.
- [x] `project/fonts/fonts.css` responde 200 `text/css`; as fontes respondem 200 `font/woff2`.
- [x] Varredura de páginas (`/`, `/home-alternativa/`, `/artigos/`, `/dashboard/`): **zero**
      requisição a domínio externo.
- [x] Minificação: **64/64** arquivos, 0 falhas, **1.588,3 KB → 739,5 KB (-53%)**.
- [x] Garantias HTTP preservadas em `interface.js`: `Content-Length: 17305` (bate byte a byte com o
      derivado), `ETag: "6a931b9f-4399"` (0x4399 = 17305), **304** com `If-None-Match`, **206** com
      100 bytes em `Range: bytes=0-99`.
- [x] `node --check` em todos os 7 JS entregues no dashboard: 7/7 válidos.
- [x] Telas em 200: `/dashboard/`, `/variables/?id=usuarios-perfis`, `/admin-paginas/`, `/`.
- [x] Etapa integrada aos pipelines (manager 5/5, project 7/7), não fatal sem Node.
- [x] PHPUnit **918/918** (16 novos); Vitest **378/378**.
- [ ] Homologação visual do operador: ícones do gestor, tipografia do site público, editor de texto.
- [ ] Commit no repositório `transformamp` (7 layouts, 1 CSS, 29 arquivos de fonte) — autorização de
      commit foi dada para o `conn2flow`, não para ele.

## BATCH-149 — Escopo do css:rebuild e assets locais no layout Tailwind

- [x] `/perfil-usuario/` recuperada: CSS entregue **8.757 → 26.053 B**, classes sem regra
      **192 → 96** (as 96 restantes são o layout `layout-administrativo-tailwind`, cujo
      `css_precompiled` nunca foi entregue em páginas de módulo — **pré-existente**, confirmado por
      `/restrict-area/` ter o mesmo sintoma e por `gestor.php` não ter sido tocado nesse ponto).
- [x] `paginas.css_precompiled` de `perfil-usuario` bate **byte a byte** com o disco: 25.276.
- [x] Escopo do regenerador: 17 de 1.446 recursos têm `user_modified = 1`; os outros 1.429 deixaram
      de ser reescritos.
- [x] `fomantic-icon` e `lucide` registrados e baixados (8 arquivos); layout aponta para o disco.
- [x] Fontes de ícone do componente respondem 200 (`icon.min.css` 108.483 B, `icons.woff2` 78.268 B).
- [x] Varredura de sete telas (`/`, `/artigos/`, `/dashboard/`, `/perfil-usuario/`, `/restrict-area/`,
      `/admin-paginas/`, `/variables/`): **zero** requisição a domínio externo.
- [x] Galeria da home segue correta: 6 âncoras + regra `.cursor-default`.
- [x] PHPUnit **928/928** (8 novos); Vitest **378/378**.
- [ ] Homologação visual: `/perfil-usuario/` e `/restrict-area/`.
- [ ] Pendente separado: o `css_precompiled` do layout não é entregue em páginas de módulo.

### BATCH-149 (parte 2) — Scroll horizontal na listagem (req-147)

Medido em navegador real (Playwright) em `/admin-paginas/`:

- [x] **1400px**: caixa criada, rola na horizontal, tabela **2819px** em caixa de **1092px**,
      **0 botões "+"** (nenhuma coluna colapsada).
- [x] **1400px**: `overflow-y` computado é `hidden`, a caixa **não** rola na vertical e o scroll
      vertical da **página** continua funcionando — o defeito reportado na 1ª rodada.
- [x] Filhos da caixa: `["TABLE"]`. Busca, seletor de quantidade, informação e paginação ficam
      **fora** dela (confirmado elemento a elemento).
- [x] **900px**: caixa não é criada e o colapso responsivo é preservado (**25 botões "+"**).
- [x] Vale para as duas interfaces (`interface` e `interface-v2`).
- [x] PHPUnit **938/938** (10 novos); Vitest **378/378**.
- [ ] Homologação visual do operador em telas de listagem variadas.

### BATCH-149 (parte 3) — Rolagem na janela e ações na primeira coluna (req-147, 2ª rodada)

- [x] A rolagem é da **JANELA**: documento 1920px contra viewport de 1400px; a caixa em volta da
      tabela deixou de existir.
- [x] Primeira coluna é **Opções**, com os botões, e `position: sticky`.
- [x] Destaque visual: cabeçalho `rgb(234,238,243)` contra `rgb(249,250,251)` das demais; célula
      `rgb(244,246,249)` contra transparente.
- [x] **Alinhamento em 7 telas** (`admin-paginas`, `usuarios`, `publisher`, `modulos`,
      `admin-componentes`, `admin-layouts`, `menus`): número de cabeçalhos igual ao de células.
- [x] Correção do deslocamento: `interface.php` monta a tabela em DOIS lugares (cabeçalho/`columns` e
      o `<tbody>` do `deferLoading`); os dois foram reordenados.
- [x] Botão de status recuperado (`editar`, `clonar`, `desativar` presentes).
- [x] Ordenação preservada: `Opções` como `sorting_disabled` e `Nome` como `sorting_asc`.
- [x] A/B contra o commit anterior: a sobreposição do controle `+` com os botões no mobile **não é
      regressão deste lote** — o baseline se comporta igual.
- [x] `responsive.details.type = 'column'` foi tentado e **revertido**: quebrava a expansão.
- [x] PHPUnit **942/942** (14 no arquivo do lote); Vitest **378/378**.
- [ ] Homologação visual do operador em telas de listagem variadas.

## BATCH-150 — Modais de sistema nas páginas Tailwind (req-148)

- [x] Reproduzido em navegador real (Playwright), com senha inválida em `/photon/signin/`:
      **7 de 12 classes do modal sem regra** antes da correção.
- [x] Depois: **0 classes sem regra**; `shadow-xl` com sombra completa, `bg-sky-600` em
      `oklch(0.588 0.158 241.966)`, `max-w-md` em **448px** (era 361px sem limite).
- [x] Confirmação visual: cartão branco com título, mensagem e botão azul legível.
- [x] 34 recursos recompilados no core; `manager:update-all`, `project:update-all snapphoton-local`
      e `project:update-all transformamp-local` passam.
- [x] Dependência automática é `opcional`: a versão estrita abortou a compilação do Photon com
      `Dependência Tailwind do Gestor não encontrada: interface-alerta-modal-tailwind` (o projeto
      traz os componentes em `pt-br` e não em `en`).
- [x] Dependência **declarada** continua falhando alto — coberto por teste.
- [x] Guarda de sincronia: teste compara o switch de `interface_componentes_incluir()` com a lista do
      compilador, para um modal novo não nascer sem estilo.
- [x] PHPUnit **955/955** (4 novos + 1 existente atualizado); Vitest **378/378**.
- [ ] Homologação visual do operador: alerta, dimmer de carregamento e confirmação de exclusão.
- [ ] Observação: o Photon não tem os componentes do core em `en`. Se for usar inglês, a sincronização
      de core precisa trazê-los.

## BATCH-153 — Poda e Modularização dos READMEs e CHANGELOGs da Raiz (req-151)

- [x] Contagem de linhas dos arquivos da raiz abaixo do teto de 150 linhas:
      - `README.md`: 91 linhas (redução de 605 para 91, -85.0%)
      - `README-PT-BR.md`: 91 linhas (redução de 612 para 91, -85.1%)
      - `CHANGELOG.md`: 128 linhas (redução de 409 para 128, -68.7%)
      - `CHANGELOG-PT-BR.md`: 128 linhas (redução de 730 para 128, -82.5%)
      - Total da raiz: 2.356 -> 438 linhas (-81.4%).
- [x] Manuais detalhados de desenvolvimento e arquitetura do repositório criados e indexados:
      - `ai-workspace/en/docs/CONN2FLOW-DEVELOPMENT-ENVIRONMENT.md`
      - `ai-workspace/pt-br/docs/CONN2FLOW-AMBIENTE-DESENVOLVIMENTO.md`
      - Catálogos `ai-workspace/{en,pt-br}/docs/README.md` atualizados.
- [x] Arquivamento de versões legadas de changelog realizado:
      - `ai-workspace/en/docs/changelogs/CHANGELOG-archive-v2-legacy.md` (`[2.8.4]` a `[2.0.21]`)
      - `ai-workspace/en/docs/changelogs/CHANGELOG-archive-v1.md` (`[1.16.0]` a `[1.0.0]`)
      - `ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v2-legacy.md` (`[2.8.4]` a `[2.0.21]`)
      - `ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v1.md` (`[1.16.0]` a `[1.0.0]`)
      - Raiz retém apenas a linha corrente (`2.10.x`) e a anterior (`2.9.x`).
- [x] Release gates validados sem regressão:
      - `grep -Fq "v2.10.0" README.md` (OK, linha 11)
      - `grep -Fq "v2.10.0" README-PT-BR.md` (OK, linha 11)
      - `grep -Fq "[2.10.0]" CHANGELOG.md` (OK, linha 8)
      - `grep -Fq "instalador-v2.0.0" README.md` (OK)
      - `grep -Fq "instalador-v2.0.0" README-PT-BR.md` (OK)
      - `php ai-workspace/en/scripts/releases/version.php patch --dry-run`: 2.10.1 (OK)
- [x] Links relativos entre a raiz e `ai-workspace/` verificados (24/24 links existentes).
- [x] Suítes automatizadas executadas:
      - PHPUnit (`composer test`): 965/965 testes passando, 4.192 asserções, 0 falhas.
      - Vitest (`npm run test`): 26 arquivos, 378/378 testes passando, 0 falhas.


---

## BATCH-154 — Paridade bare-metal no `project:update-all` (req-152)

- [x] `updates-manager-database.sh` aprovado por `bash -n` no Windows/Git Bash e no Lab Linux.
- [x] Modo Docker preservado quando `dockerPath` é explícito ou derivável.
- [x] Execução direta `docker exec conn2flow-app php ... --help` aprovada no container local.
- [x] Modo host condicionado à existência do atualizador PHP em `target/path_tests`.
- [x] PHP host executado em subshell a partir da raiz do Gestor.
- [x] Identificador validado antes das expressões jq; argumentos separados em array Bash.
- [x] Bootstrap `phinx.php` compartilha `$_GESTOR` global no contexto CLI.
- [x] Testes focados: 10/10, 30 asserções.
- [x] PHPUnit completa: 1008/1008, 4.347 asserções, 4 skips esperados, 0 falhas.
- [x] Lab HestiaCP: `c2f project:update-all snapphoton-lab` concluiu 5/5.
- [x] Repetição idempotente: `+0 ~0 =19`, `TRANSACAO_COMMIT`, pipeline 5/5.
- [x] Backups reversíveis do script e do `phinx.php` preservados em `/root/` no Lab.
- [ ] Homologação humana do diff do BATCH-154.

---

## BATCH-047 — Preflight do Instalador, Sonda HTTP Anti-Deadlock e Contrato CLI (req-045)

> Evidências completas, decisões técnicas e tabelas de simulação em [batch-047.md](../implementation/batch-047.md).

### 1. Checklist de Aceite Técnico

- [x] `releaseManager.ts` lê a versão do `gestor-instalador` a partir de `InstallerGuard.php` sem falha de preflight.
- [x] Fallback retrocompatível para o literal do `index.php` (instalador v1) preservado e testado.
- [x] `version-installer.php` (en) e `version-instalador.php` (pt-br) incrementam `const VERSION` em `InstallerGuard.php` e sincronizam o comentário do `index.php`.
- [x] `release-installer.sh` / `release-instalador.sh` aceitam os dois caminhos no staging e barram caminhos alheios.
- [x] Regra Anti-Deadlock de Sonda HTTP formalizada em `c2f-html-css-pages-and-components` §3 e `c2f-dev-scripts` §4.
- [x] Contrato `Conn2Flow\Cli\Contracts\CommandInterface` / `BaseProcessCommand` formalizado em `c2f-dev-scripts` §3 e em `cli/CLAUDE.md`.
- [x] Suíte automatizada da extensão 100% verde.
- [x] Formulário "Preparar Release do Instalador" monta com `canPrepare: true` e exibe versão atual `2.1.0`, próximo incremento `2.1.1` e tag `instalador-v2.1.1` (validado headless — Teste 8).
- [ ] Conferência visual da pintura do painel no VS Code — resíduo que exige a janela da IDE, não exercitável headless.

### 2. Evidências de Validação

#### Teste 1: Suíte da extensão
* **Comando**: `npm test` em `vscode-extension/`
* **Evidência**: 76/76 testes passando, 0 falhas, compilação TypeScript limpa (66 anteriores + 10 novos em `test/releaseVersionSource.test.cjs`).

#### Teste 2: Preflight real contra o Core
* **Evidência**: `installer` resolve `2.1.0` em `gestor-instalador/src/InstallerGuard.php` → tag `instalador-v2.1.1`; `manager` resolve `2.10.1` em `gestor/config.php` → tag `gestor-v2.10.2`. Ambos com preflight OK.

#### Teste 3: Scripts de versão do Core em sandbox isolado
* **Comandos**: `php -l`; `version-installer.php {patch,minor,major} --dry-run`; execução real em sandbox.
* **Evidência**: dry-run `2.1.1` / `2.2.0` / `3.0.0` sem escrita; escrita real muda `const VERSION` **e** o comentário `(x.y.z)` do `index.php`, com `diff` integral confirmando que nenhuma outra linha foi tocada; fallback v1 `1.9.4` → `1.9.5`; tipo inválido e ausência de fonte retornam `exit=1`.

#### Teste 4: Guarda de caminhos do release
* **Comando**: `bash -n` + simulação da lista de permissão.
* **Evidência**: libera `{guard+index}`, `{index}` e `{guard}`; bloqueia caminho alheio (`gestor/config.php`) e árvore limpa.

#### Teste 5: Integridade das skills
* **Comando**: `php cli/c2f.php ai:sync` no Core.
* **Evidência**: 36/36 skills verificadas em `.claude`, `.cursor`, `.gemini`, `.github` e `.codex` — `✔ Verified` em todos, exit 0.

#### Teste 6: Paridade das cópias propagadas
* **Evidência**: `c2f-dev-scripts` (21 cópias) e `c2f-html-css-pages-and-components` (24 cópias) com 1 hash único cada; `cli/CLAUDE.md` com 1 hash único em 4 cópias; nenhum resíduo de "Padrão Symfony Console" nos dois repositórios.

#### Teste 7: Empacotamento
* **Comando**: `npx vsce package`
* **Evidência**: `conn2flow-tools-1.0.0.vsix` (67 arquivos, 159.96 KB) gerado para homologação local. Nenhum commit, push, deploy ou release executado.

#### Teste 8: Harness headless do formulário de release
* **Método**: reprodução do caminho de dados de `ReleaseManager.prepare('installer')` + `diagnose()` sobre `out/releasePolicy.js` e o repositório Core real.
* **Evidência**: `currentVersion=2.1.0`, `nextVersion=2.1.1`, `tag=instalador-v2.1.1`, `command=./c2f installer:release patch ...`, `requiredFilesReady=true`, `canPrepare=true`; reatividade do `semverPreview` confere em `patch` (2.1.1), `minor` (2.2.0) e `major` (3.0.0). Blockers residuais (`permission-unknown`, `dirty-tree`, `draft-missing`) são estados de ambiente esperados.

### 3. Achado fora do escopo — drift dos READMEs do Core

O gate documental passou a acusar `README:installer-version`. **Não é regressão deste lote.** Com `readVersion` devolvendo `undefined`, `inspectReleaseDocumentContents()` recebia `installerVersion = undefined` e pulava a checagem do README; com a versão resolvendo, a checagem ativou e revelou drift pré-existente:

- Tag `instalador-v2.1.0` publicada no repositório.
- `README.md` (155/159/163) e `README-PT-BR.md` (160/164/168) ainda apontam downloads para `instalador-v2.0.0`.

Enquanto não sincronizados, o gate `documentation-outdated` bloqueia a **execução** da release do instalador (a preparação do formulário não é afetada). Como a mudança altera URLs de download voltadas ao usuário final e a curadoria dos READMEs é do Arquiteto (`MEMORIA-ENGENHARIA-CHEFIA.md` §1), **nenhum README foi alterado**. Decisão pendente do Humano-no-Loop.

## BATCH-155 - req-153 / REQ-034 (transporte SSH e bootstrap CLI por host)

### Automatizado

- [x] `bash -n` em project-transport.sh, sync-core-to-project.sh, synchronize-project.sh e updates-manager-database.sh
- [x] `php -l` em config.php, ProjectEnvironmentResolver.php, CssRebuildCommand.php e AuthCookieCommand.php
- [x] `ProjectSshDeployReq034Test` — 19/19, 64 assercoes
- [x] `AdminCronReq032Test` — 44/44 (req-153)
- [x] PHPUnit completa — **1071/1071**, 4.717 assercoes, 4 skips esperados, 0 falhas

### Runtime no Lab (`conn2flow-site-local` -> 192.168.1.108)

- [x] `project:sync-core` via SSH — 17 MB transferidos, posse devolvida a admin:admin
- [x] `project:sync-db` via SSH — TRANSACAO_COMMIT, `+166 ~247 =2434`
- [x] `project:update-all` etapas 1 a 5 em SUCCESS, executado duas vezes (`+5 ~6 =2952` na repeticao)
- [x] `auth:cookie --project=conn2flow-site-local` gerando sessao pela VM com sufixo de cookie correto
- [x] Modo local preservado: `PT_RSYNC_OPTS` vazio mantem a linha de rsync anterior (coberto por teste)

### Ressalvas registradas no batch

- [ ] `css:rebuild` e `assets:publish` nao alcancam a VM: operam sobre Gestor em disco local. O
      pipeline avisa e segue. A VM nao tem `tailwindcss` nem `terser` instalados.
- [ ] req-153: o checksum zerado e repreenchido pelo compilador (`ORIGIN_UPDATE_MODULE`) a cada
      `project:update-all`. `md5_file(admin-cron.html)` e exatamente o valor que o teste rejeita —
      ele nunca foi escrito a mao. Teste e compilador estao em conflito; decisao normativa pendente.

## BATCH-156 — Integridade visual dos templates Tailwind (req-154)

### Automatizado

- [x] 72/72 registros por idioma com HTML e thumbnail válidos; 36/36 templates Tailwind por idioma
      com sidecar não vazio.
- [x] Utilities essenciais usadas (`p-*`, `m-*`, `gap-*`, `space-*`, `bg-*`, `rounded-*`,
      `shadow-*`) presentes nos sidecars: 2/2 testes, 2.695 asserções.
- [x] Preview Tailwind sem `semantic.min.css`, mantendo jQuery/Fomantic JS para widgets legados.
- [x] Inserção de seção concatena o novo sidecar ao baseline; substituição integral troca o baseline.
- [x] `node --check` e `php -l` aprovados.
- [x] `assets:minify --verificar`: 65 fontes, 0 derivados desatualizados.
- [x] `resources:sync --force`: 237/237 recursos recompilados, 0 erros; repetição com 237/237 em cache.
- [x] Vitest completo: 27/27 arquivos, 382/382 testes.
- [x] PHPUnit completo: 1.073/1.073 testes, 7.418 asserções, 4 skips esperados.

### Visual

- [x] A/B Chromium reproduziu a colisão: com Fomantic, `py-20` = 70px, `gap-12` = 42px e CTA
      transparente; isolado, os valores voltam a 80px, 48px e fundo branco.
- [x] Amostras conferidas contra thumbnails: CTA gradiente, seção de 3 colunas, hero moderno,
      hero banner e landing de alta conversão do projeto.
- [ ] Homologação autenticada em `https://conn2flow.local/admin-paginas/adicionar/`; criação da sessão
      administrativa temporária não foi autorizada pelo gate de permissão.

## BATCH-157 — Checksum idempotente e independente de plataforma (REQ-035 / req-155)

- [x] Falha reproduzida antes da correção: `[pt-br] checksum html não pode ser escrito à mão`,
      esperado `''`, obtido `387ee81b1f9dd115a8d96c7ca2b92d72`
- [x] O valor rejeitado é exatamente `md5_file()` do `admin-cron.html`, nos dois idiomas —
      produzido pelo compilador (`ORIGIN_UPDATE_MODULE`), não digitado
- [x] `AdminCronReq032Test`: 44/44, 302 asserções
- [x] PHPUnit completo: 1.073/1.073, 7.418 asserções, 4 skips esperados
- [x] Vitest completo: 27/27 arquivos, 382/382 testes
- [x] **Idempotência em execução**: com a árvore limpa, `php cli/c2f.php resources:sync` (2.844
      recursos) não alterou `admin-cron.json`; único diff foi o `generated_at` de
      `schema-metadata.json`, revertido. Teste segue verde depois do sync.

### Segunda causa: o md5 dependia do fim de linha (req-155)

- [x] Falha do CI da release v2.10.3 reproduzida: esperado `6a4af6df…`, obtido `387ee81b…`
- [x] Provado que são o MESMO arquivo em duas formas: `git ls-files --eol` → `i/lf w/crlf`;
      md5 com CRLF = `387ee81b…`, md5 com LF = `6a4af6df…`, 233 quebras de linha de diferença
- [x] **Simulação do runner Linux**: HTML convertido para LF em disco → `AdminCronReq032Test` 44/44;
      arquivos restaurados por `git checkout` depois
- [x] Manifesto recompilado da forma canônica: `checksum.html` = `6a4af6df…` nos dois idiomas
- [x] `PaginasData.json`: 2 páginas alteradas (`admin-cron` pt-br/en), HTML verificado campo a campo
      como idêntico após normalizar CRLF; mudaram só checksum e o bump de versão que ele provoca
- [x] PHPUnit completo após a recompilação: 1.073/1.073
- [x] Revalidação final: `php cli/c2f.php resources:sync --force` sincronizou 2.844 recursos sem
      problemas; o teste focal posterior passou em 44/44 (302 asserções) e `composer test` em
      1.073/1.073 (7.418 asserções, 0 falhas, 4 skips e 2 deprecações).
- [x] Os checksums `html` e `combined` de `admin-cron` em pt-br/en foram conferidos como
      `6a4af6df04b77f8693400757bc7858df`, idênticos ao MD5 dos HTMLs físicos.
- [x] A publicação opcional de `dist/` foi avisada, mas não executada por falta de `PUBLIC_PATH`;
      o CLI confirmou que isso não afeta a sincronização dos recursos nem as URLs pelo controlador.

## BATCH-159 — Recompilação redundante de recursos no release do Gestor (req-156)

- [x] Removidas de `.github/workflows/release-gestor.yml` as etapas `Generate resources and
      per-resource Tailwind CSS` e `Commit Resources Updates`.
- [x] O job parte de Playwright para `Remove resource files` e para a criação de `gestor.zip`, usando
      diretamente os derivados commitados pelo release local.
- [x] `.github/workflows/release-instalador.yml` conferido: não contém chamada a compilador de
      recursos nem commit de recursos no runner.
- [x] Validação YAML: `npx --yes yaml-lint .github/workflows/release-gestor.yml
      .github/workflows/release-instalador.yml` — sucesso.
- [x] Busca negativa pelos dois nomes de etapa e por `atualizacao-dados-recursos.php` nos dois
      workflows — nenhuma ocorrência.
- [x] `composer test` — **1.073/1.073** testes, **7.418** asserções, 4 skips e 2 deprecações,
      sem falhas.
- [x] `npm run test` — **27/27** arquivos, **382/382** testes, exit code 0. Avisos conhecidos de
      `ECONNREFUSED 127.0.0.1:3000` no teardown do happy-dom não falharam a suíte.
- [x] `git diff --check` — sem erros de espaço em branco.
- [x] Nenhum commit, push, deploy ou release executado.

## BATCH-158 — Paridade visual estrita entre página pública, pré-visualizador e editor visual (req-158)

- [x] Diagnóstico das duas contaminações: folha Fomantic sem camada no editor vencendo utilities em `@layer`, e `html{font-size:14px}` encolhendo `rem` por 0,875.
- [x] Isolamento do Fomantic em `@layer c2f-editor-chrome` e restauração de `html{font-size:16px}` fora de camada no editor visual.
- [x] Ordem de camadas declarada formalmente no editor e no preview (`@layer c2f-editor-chrome, properties, theme, base, components, utilities;`).
- [x] Eliminação de 24 tags em 4 CDNs no cliente (iframes, Editbar, previews de widget).
- [x] Inclusão de `@tailwindcss/browser` e addons do CodeMirror no registro local de assets (DEC-122).
- [x] Fingerprint de procedência de CSS derivado avançado para `v2:` com carimbo de compilador.
- [x] Paridade nos 3 ambientes contra rota pública real: Editor vs Preview 15/15, Pública vs Preview 15/15.
- [x] `c2f assets:vendor`: 0 falhas; `c2f assets:minify --verificar`: 0 desatualizados.
- [x] `c2f resources:sync`: 2.844 recursos, 0 problemas.
- [x] Homologação visual no CRUD (`admin-paginas/editar/`) confirmada pelo operador.

## BATCH-160 — Animações de entrada dos templates de sessão (req-159)

- [x] Diagnóstico: 4 templates aplicando `animate-fade-in-up` sem definição de `--animate-fade-in-up` no `@theme` v4 (descarte silencioso).
- [x] Definição central de `--animate-fade-in` e `--animate-fade-in-up` com keyframes correspondentes em `gestor/assets/tailwindcss/system-input.css`.
- [x] Recompilação dos contratos derivados (`browser-contract.css`) e recursos afetados via `resources:sync --force`.
- [x] Verificação em Chromium: 14/14 animações ativas nos 4 templates, sem regressão nas nativas (`animate-pulse`, `animate-bounce`).
- [x] Guarda automatizada contra utilities de animação sem regra compilada, validada por mutação.
- [x] Homologação visual confirmada pelo operador.

## BATCH-161 — CSS dos templates inseridos no runtime público (req-160)

- [x] Diagnóstico de transição: sidecars de templates somados ao baseline entravam no conjunto de filtro de `HtmlEditorCssCapture`, descartando utilities exclusivas do template no `css_compiled`.
- [x] Abordagem com persistência em POST de `css_precompiled` barrada corretamente por `CssProcedenciaGravacaoTest` (contrato CR-002 preservado).
- [x] Separação do baseline em duas folhas no iframe: `baseline` (o que runtime público entrega, alvo do filtro) e `session-overlay` (sidecars da sessão, fora do filtro).
- [x] Injeção do motor de captura no preview sem ativar UI de edição, com callback de conclusão para salvamento seguro.
- [x] Observação e recarga dinâmica das folhas ao alternar layouts no select do formulário.
- [x] PHPUnit completo: 1.096/1.096 testes, 7.547 asserções, 0 falhas.
- [x] Vitest completo: 28/28 arquivos, 405/405 testes, 0 falhas.
- [x] Homologação visual ponta a ponta confirmada pelo operador.

## BATCH-162 — Requisição espúria de `{{thumbnail}}` no Editor HTML (req-161)

- [x] Guarda focal reproduz a falha estrutural com a `<div>` ativa e passa após a migração para
      `<template>` nos componentes `pt-br` e `en`.
- [x] `$('#modelo-card-template').html()` continua disponibilizando o card e todos os placeholders
      usados na interpolação JavaScript.
- [x] `resources:sync --force` recompila 2.844 recursos sem erros e atualiza os dois registros para
      `file_version=1.6` em
      `gestor/db/data/ComponentesData.json`.
- [x] Vitest completo: 28/28 arquivos, 408/408 testes, exit 0.
- [x] PHPUnit completo: 1.096/1.096 testes, 7.547 asserções, exit 0.
- [x] Sincronização executada no Lab com `project:sync-core` e `project:sync-db`: componente
      `html-editor-modelos` atualizado para `file_version=1.6` com tag `<template>` no filesystem e
      no MariaDB (`TEMPLATE_OK`), eliminando a ativação antecipada de `{{thumbnail}}` e o erro 404.

## BATCH-165 — Controlador do painel Admin Cron nunca chegava a rodar (REQ-038 / Pilar 4)

- [x] Diagnóstico corrigido em relação ao intake: `admin_cron_painel()` **já** enfileirava o script
      (`admin-cron.php:181`, desde o commit que criou o módulo). O defeito era de ORDEM: o marcador
      `<!-- pagina#js -->` vive no `<head>` de todos os layouts (linha 30/102 no
      `layout-administrativo-tailwind`) e a IIFE lia o DOM na primeira instrução — `#admin-cron-painel`
      era sempre `null` e o painel nascia inerte.
- [x] Regressão nova `tests/Unit/JS/admin-cron.painel.test.js` avalia o arquivo REAL com
      `document.readyState === 'loading'` e `<body>` vazio; validada por mutação: **3 de 5 falhavam**
      contra o arquivo anterior (tabela vazia, "Nova tarefa" inerte, "Sincronizar" sem requisição).
- [x] Correção: espera por `DOMContentLoaded` com `{ once: true }`, preservando o caminho síncrono
      quando o documento já está montado. Diff de 8 linhas, sem reindentar o arquivo.
- [x] `assets:minify` regenerou `admin-cron.min.js` (19,8 KB -> 10,1 KB); a variante minificada é a
      preferida em runtime, então a correção só tem efeito depois deste passo.
- [x] Guardas em `AdminCronReq032Test`: a página continua enfileirando o script, a espera pelo DOM
      precede a leitura do painel e os dois layouts mantêm `<!-- pagina#js -->`. 44 -> **46/46**.
- [x] `php -l` em `admin-cron.php` sem erros.
- [x] Vitest completo: 29/29 arquivos, **417/417** testes, 0 falhas.
- [x] PHPUnit completo: **1.123/1.123** testes, 7.638 asserções, 0 falhas.
- [ ] Homologação em runtime em `/admin-cron/`: pendente do operador. O mirror local
      `conn2flow-gestor` (projeto `project-test`) não está montado em `public_html`, e nenhum banco
      local tem a página `admin-cron` instalada — a validação de tela precisa da VM.

## BATCH-166 — Disparo desacoplado do "Disparar agora" (REQ-039, Pilar 5)

- [x] Causa confirmada: a rotina de provisionamento chama `systemctl restart php8.5-fpm` e, disparada
      pelo painel, roda dentro do pool FPM — a reinicialização derruba o worker da própria requisição
      (`502 Bad Gateway`) e o provisionamento morre no meio, deixando tenant parcial no HestiaCP.
- [x] Decisão de desacoplar declarada pela tarefa (`parametros.execucao = "desacoplada"`), não por
      lista fixa no núcleo. Teste `testNucleoNaoConheceModuloDeProjetoPeloNome` guarda a ausência do
      id `host-manager-provisionamento` no código do núcleo.
- [x] Manifesto do módulo como segunda fonte: `parametros` só é ressincronizado com `user_modified`
      vazio (D-036), então uma tarefa já pausada pelo operador congelaria a versão antiga e reexporia
      o 502 em silêncio. Nome do módulo validado por regex antes de virar caminho de arquivo.
- [x] `setsid` no comando: sem sessão nova o filho continua no grupo de processos do pool e é morto
      junto pelo restart. `escapeshellarg` em todos os argumentos.
- [x] `PHP_BINARY` só é usado quando o processo já é CLI; sob FPM ele aponta para o binário do pool.
- [x] Nenhum `cron_tarefa_registrar()` no caminho desacoplado — quem grava duração e status é o
      processo CLI ao terminar.
- [x] Ambiente sem CLI (Windows, `proc_open` bloqueado, `cron.php` ausente) degrada para o caminho
      síncrono anterior, com o motivo em log.
- [x] Funções extraídas para `includes/admin-cron-dispatch.php`, include sem efeito colateral:
      `admin-cron.php` termina em `admin_cron_start()` e abrir a interface num teste produziu
      `Call to undefined function hook_do_action()`.
- [x] Guarda validada por mutação: removido o fallback de manifesto,
      `testManifestoDoModuloValeQuandoOBancoEstaDesatualizado` acusa.
- [x] `php -l` nos 2 arquivos PHP tocados: OK.
- [x] `AdminCronReq039Test`: **13/13**, 24 asserções.
- [x] PHPUnit completo: **1.142/1.142**, 7.690 asserções, 4 skipped, 0 falhas.
- [x] Vitest completo: 29/29 arquivos, **417/417** testes, 0 falhas.
- [x] `c2f resources:sync`: 2.846 recursos, 0 problemas. (A publicação em `dist/` não completou por
      ausência de `PUBLIC_PATH` no ambiente — condição pré-existente; o próprio aviso confirma que os
      recursos foram sincronizados e as URLs seguem resolvendo pelo `arquivo-estatico`.)
- [ ] Homologação na VM Lab: disparar `host-manager-provisionamento` em `/admin-cron/` e confirmar
      resposta imediata sem `502`, com o resultado surgindo na listagem ao fim do processo CLI.
      Depende do `BATCH-032` do `conn2flow-site` implantado — é ele que declara o desacoplamento.
