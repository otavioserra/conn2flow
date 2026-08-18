# Validation Checklist

Use este checklist para validar batches no conn2flow sem perder de vista o baseline operacional do repositÃ³rio.

## Onboarding SDD repo-wide

- [x] CLAUDE.md instalado na raiz do repositÃ³rio
- [x] .claude/ instalado com agents, rules, skills e settings do Claude Code
- [x] .github/copilot-instructions.md instalado
- [x] .github/instructions/, .github/prompts/, .github/skills/ e .github/agents/ com artefatos SDD do Copilot
- [x] sdd/scripts/hooks/ criado com hooks de sessÃ£o SDD
- [x] sdd/human-requests/ ativo
- [x] sdd/README.md, process/, implementation/, alidation/ e decisions/ criados
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

Para manter o checklist de validaÃ§Ãµes leve e eficiente (teto de 10 ativos), as validaÃ§Ãµes anteriores foram arquivadas:
- **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)** (BATCH-001 a BATCH-017)
- **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)** (BATCH-018 a BATCH-053)
- **[validation-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-054-093.md)** (BATCH-054 a BATCH-093)
- **[validation-094-110.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-094-110.md)** (BATCH-094 a BATCH-110)

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
- [x] Deploy local HTTP 200 e sincronização `componentes --force-all`: 43 registros sem divergência.
- [x] Base `photon`: oito componentes novos, `user_modified=0`, precompiled entre 1.383 e 5.036 bytes.
- [ ] Homologar visualmente busca clínica e checkout no navegador a 100%.
- [ ] Migrar `snapphoton-system` por famílias de tela antes de remover sua ponte.

---

## BATCH-117 — Paridade do Tailwind Browser CDN, Painel de Código na Editbar e Correção de Race Condition na Extração do CSS Compilado (req-117)

- [x] `hasGeneratedUtilities(rules)` implementada para inspecionar recursivamente regras de utilitários no CSSOM.
- [x] `updateCSSCompiled` em `html-editor-interface.js` aguarda a prontidão real dos utilitários antes de encerrar o polling (40 tentativas × 100 ms).
- [x] **Correção além do intake**: a folha do Tailwind passou a ser identificada pelo FORMATO (camadas nomeadas da v4), não pela posição. O critério antigo ("última `<style>` com regras") escolhia qualquer folha injetada em runtime — inclusive o CSS da UI do editor.
- [x] `CodeMirrorCssCompiled` no Editor HTML recebe as utilitárias da página; janela esgotada PRESERVA o valor anterior em vez de gravar captura incompleta.
- [x] Injeção dinâmica do runtime do `@tailwindcss/browser` e contrato `@theme static` na ativação do Live Editor (`dashboard.toolbar.js`), com o contrato viajando pelo `site-toolbar-render`.
- [ ] Renderização visual imediata na tela ao adicionar novas classes Tailwind no Styler ou DOM durante a edição in-place — **homologação do operador**.
- [x] Botão "Código" (`#c2f-code-btn`) presente na Editbar (PT-BR e EN) ao lado do Assistente IA, aplicado ao banco local (`file_version` 1.19 → 1.21).
- [x] Painel flutuante `#c2f-code-panel` abre sem vazar cliques para a página de trás (`isEditorOwned` nos três pontos + barreira de eventos na fase de bolha).
- [x] 4 Sub-abas funcionais com CodeMirror (HTML, HTML Extra Head, CSS, CSS Compilado) no padrão do sistema (`tomorrow-night-bright`, indentUnit 4, refresh na troca de aba).
- [x] Sincronização com o DOM vivo — **com uma diferença deliberada em relação ao intake**: o CSS é aplicado ao vivo (debounce de 400 ms), o HTML por botão "Aplicar ao conteúdo". Reescrever `#c2f-page-content` a cada tecla recriaria os nós e derrubaria as anotações do mapeamento in-place (`data-c2f-variable`, `.c2f-dyn-box`), destruindo a edição em curso.
- [x] Extração de `html`, `css`, `html_extra_head` e `css_compiled` no salvamento da Editbar (`performSave`); campos intocados NÃO vão no POST, para não gerar versão e backup a cada salvamento.
- [x] Testes automatizados: Vitest **220/220** (novo `html-editor-css-capture.test.js` 21/21 + 12 casos novos em `dashboard.toolbar.test.js`); PHPUnit **297/297** (novos `TailwindGuardasTest` 16/16 e `HtmlEditorBaselineTest` 9/9).
- [x] `node --check` sem erros nos JS modificados; `php -l` OK nos PHP modificados; JSON válido.
- [ ] Verificação manual: reabrir e salvar `/photon/sobre/` no Editor Clássico e na Editbar, confirmando as utilitárias no banco e a fidelidade visual — **homologação do operador**.

### Findings do review de 2026-08-15 incluídos nesta rodada

- [x] **F1** — `tailwind_recursos_tokens_ausentes()`: o build passou a comparar os `var(--…)` do CSS autoral com os tokens presentes na saída e avisar quando o `@theme` podou algum.
- [x] **F4c(a)** — `tailwind_recursos_html_usa_tailwind()`: avisa quando o HTML tem utilities mas o recurso não declara `framework_css=tailwindcss` (o defeito do `form-ui`).
- [x] **F4c(b)** — `tailwind_recursos_utilities_removidas()`: recusa utilities da v3 removidas na v4 (`bg-opacity-*`, `flex-shrink-*`, …).
- [x] Avisos são informativos por padrão; `--tailwind-strict` os promove a erro de build, para o CI apertar quando o inventário estiver limpo.

### Evidência de Validação (BATCH-117)

Reportada pelo executor em 2026-08-17.

**Diagnóstico medido (antes de qualquer alteração), base `photon`:**

| Página | `css_compiled` | Sintoma |
| --- | --- | --- |
| `sobre` | 5.708 B | CSS autoral re-serializado, **zero** `@layer` |
| `pagina-raiz-do-sistema` | 5.690 B | idem |
| `teste-de-pagina` | 4.894 B | `@layer properties;` + declaração de ordem, **`@layer utilities` vazio** |

As duas primeiras são a captura da folha ERRADA; a terceira é a race condition do intake. Os dois
modos de falha estavam materializados no banco ao mesmo tempo.

**Runtime do `@tailwindcss/browser@4.3.0` (lido do bundle da unpkg):** ele concatena os
`<style type="text/tailwindcss">` e **prefixa `@import "tailwindcss";` sozinho quando não encontra um
`@import`** — o `browser-contract.css` sem import está correto, não era essa a falha. A folha de saída
é criada por `document.head.append(<style>)` **vazia**, e o `html-editor.js` injeta 4 `<style>` próprios
no mesmo `<head>`.

**Verificação com Chromium real** (Playwright + bundle 4.3.0 + HTML real da `sobre` + contrato do
projeto), comparando o antes/depois da política de baseline:

| Cenário | Bytes | Camadas gravadas | Utilities |
| --- | ---: | --- | --- |
| Sem cascata pré-compilada | 8.774 | properties, theme, base, utilities | presentes |
| Com o `layout-precompiled` do `photon-public` | 3.086 | properties, utilities | presentes |

Economia de **65%**, batendo com os 62% previstos na análise. `.text-3xl`, `.space-y-5`,
`.leading-relaxed` e `.max-w-4xl` presentes nos dois; CSS da UI do editor ausente nos dois.

**Correção de desenho descoberta na medição:** o filtro por assinatura de regra NÃO segura camadas de
fundação. O Preflight do browser 4.3.0 emite `*, ::after, ::before, ::backdrop, ::file-selector-button`
e o bundle offline do layout emite `*, ::after, ::before, ::backdrop` — nenhuma assinatura casa e o
Preflight inteiro era regravado (economia de apenas 17%). Como o `css_compiled` entra DEPOIS do
pré-compilado na cascata, a versão do editor venceria a do build em produção. `theme` e `base` passaram
a ser decididas por CAMADA; `utilities`, `properties` e `components` seguem no filtro fino.

**Pipeline e ambiente:**

- gerador do core: 175 recursos, 2 compilados (os dois idiomas da Editbar), 173 em cache, 0 erros;
- guardas novas calibradas contra o inventário real: de **176 avisos para 4** (com `flex`/`grid`/`hidden`
  isolados na heurística, todo recurso Fomantic disparava — `ui grid`, `ui items`, `left floated`);
- `sync-core-to-project.sh --project snapphoton-local`: 6 arquivos-chave conferidos por MD5, todos iguais;
- `atualizacoes-banco-de-dados.php --tables=paginas --force-all`: +2 ~60 =194, sem tocar em `sobre`,
  `teste-de-pagina` nem `home-alternativa` (confirmado por `--dry-run` antes de aplicar);
- `GET http://localhost/photon/sobre/` → **HTTP 200**, com `data-tailwind-role="layout-precompiled"`,
  2× `data-c2f-css-role="authored"` e 1× `data-c2f-css-role="compiled"` no `<head>`.

### Pendências

- **O dado gravado continua errado até um novo salvamento.** As três páginas acima só recuperam as
  utilitárias quando reabertas e salvas no editor — a correção é do caminho de gravação, não uma
  migração. Vale conferir o mesmo inventário nos demais projetos.
- Homologação runtime com o operador: salvar no editor clássico e na Editbar; conferir efeito visual
  imediato de classe nova no Styler; abrir o painel de Código nas 4 abas; repetir em `/en/`.
- Achado do F4c aguardando decisão (fora do escopo aprovado desta rodada): o componente do core
  `html-editor-publisher-simulation` (pt-br e en) usa Tailwind puro (`text-3xl`, `bg-gradient-to-r`,
  `bg-indigo-50`, `px-2`, `py-1`) e **não declara `framework_css`** — nunca é compilado. É o mesmo
  defeito do `form-ui`. O outro aviso (`sessao-com-2-colunas-fomantic-ui`, `font-light text-primary
  mb-8`) é limítrofe e provavelmente falso positivo.
- Restrição respeitada: nenhum `git commit`/`git push` executado; `sdd/human-requests/` não foi tocada.

---
## BATCH-118 - Findings restantes do review de 2026-08-15 (F2, F3, F4, F7-F10) + homologação do BATCH-117

- [x] **F2** — descarte de `resource-precompiled` em modo bundle registrado em `log_disco(..., 'tailwind')`, com rota e contagem, uma vez por requisição. Layout e dependências continuam descartados em silêncio, por desenho.
- [x] **F3** — `tailwind_recursos_layout_display_sensivel()` (pura) + aviso de build para página sem `tailwind_bundle` sob layout que emite `display` responsivo com concorrente incondicional.
- [x] **F4** — os dois pontos de `bibliotecas/gestor.php` declaram `layout-precompiled`. **Correção ao review**: eles não são "includes de template", estão dentro de `gestor_layout()`.
- [x] **F7** — `confirmation`/`success`/`error`/`failure`/`cancel` como sufixo (`-`, `_`, `/`); `payment`/`checkout`/`processing` como segmento final de caminho composto.
- [x] **F8** — `sitemap_legado_remover()` + trava `sitemap_conteudo_proprio()` (arquivo de terceiro e índice de sitemaps são preservados e logados).
- [x] **F9** — `sitemap_robots_montar()`/`sitemap_robots_gravar()` gerando `assets/robots.txt` na geração completa.
- [x] **F10** — `gestor_pagina_301_registrar()` com dedup por (caminho, id_paginas), substituindo o bloco duplicado nos dois módulos; roteador varre candidatos do mais recente ao mais antigo.
- [x] Correções de homologação do BATCH-117: fechamento por clique fora, centralização do painel e o `@layer theme` por declaração.
- [ ] Homologação runtime com o operador (ver pendências).

### Evidência de Validação (BATCH-118)

Reportada pelo executor em 2026-08-17.

**Verificação HTTP no `snapphoton-local`**, após disparar `sitemap_gerar_completo()`:

| Item | Antes | Depois |
| --- | --- | --- |
| `assets/sitemap.xml` | 36 URLs | **31 URLs** |
| `contacts-success`, `checkout/error`, `checkout/payment` | indexadas | **0 ocorrências** |
| `sitemap.xml` na raiz | 1.161 bytes (9 URLs, de 14/08) | **removido** |
| `/robots.txt` | 404 | **200 `text/plain`**, com 11 `Disallow:` + `Sitemap:` |
| `/photon/sobre/` | 200 | 200 |

**Calibração do aviso F3** contra layouts reais: nenhum layout do core dispara (nenhum tem a
combinação); `photon-admin` e `photon-public` do lumix disparam — e `photon-admin` é exatamente o
layout onde a inversão desktop/mobile foi observada na Busca Clínica.

**Regressão do BATCH-117 corrigida nesta rodada** — medida com Chromium real, comparando estilos
computados entre "durante a edição" e "depois de salvar", na página `sobre`:

| | Elementos divergentes | `css_compiled` |
| --- | ---: | ---: |
| Antes (theme descartado por camada) | **13 de 21** | 3.086 B |
| Depois (theme por declaração) | **0 de 21** | 3.695 B |

Divergências observadas antes da correção: `font-size` 48px → 16px, `font-weight` 800 → 400,
`letter-spacing` -1.2px → normal, `color` → preto. Causa: o `@layer theme` do layout só contém os
tokens que o LAYOUT usa (F1), então `var(--text-3xl)` e `var(--color-slate-300)` das utilities novas
ficavam indefinidas e invalidavam a declaração.

**Suítes**: `composer test` **315/315** (4 skipped e 1 deprecação preexistentes) — `SitemapTest`
24→38 e `TailwindGuardasTest` 16→20; `npx vitest run` **228/228** — `html-editor-css-capture.test.js`
21→25 e `dashboard.toolbar.test.js` 25→29. `php -l`, `node --check`, JSON e `git diff --check` OK.
Gerador do core: 175 recursos, 0 erros, 4 avisos (os mesmos do BATCH-117).

### Pendências

- Homologação runtime com o operador: conferir o painel de Código centralizado e fechando por clique
  fora; salvar `/photon/sobre/` e confirmar que o resultado é idêntico ao visto na edição; renomear
  uma página em dois idiomas e conferir os dois 301; conferir `/robots.txt` em produção (a URL do
  sitemap sai de `url-full-http-sem-lang`, e no local aparece como `https://localhost/photon/`).
- Dívida registrada: dependência de template por `target` (sugestão 2 do F2) e a decisão de
  arquitetura sobre Tailwind no painel administrativo (bloqueia o `html-editor-publisher-simulation`).
- Restrição respeitada: nenhum `git commit`/`git push` executado; `sdd/human-requests/` não foi tocada.

---
## BATCH-119 - Layout administrativo Tailwind, menu interativo e painel de perfil com sessões (req-118, 2026-08-18)

- [x] **Resolução de framework por layout + página**:
  - [x] `gestor_framework_css_resolver()` (pura) decide a partir das DUAS colunas `framework_css`.
  - [x] Modos `fomantic-ui`, `tailwindcss` e `hibrido`; nada declarado continua caindo no legado.
  - [x] Bloco duplicado de decisão removido de `gestor_pagina_css()` e `gestor_pagina_extra_head_e_javascript()`.
- [x] **Variantes de componente**:
  - [x] `interface_componente_variante()` aplica o sufixo `-tailwind` só em Tailwind puro.
  - [x] Em `hibrido` o componente legado é preservado (com Fomantic na página, é o único com estilo).
  - [x] `interface_componente_canonico()` não amputa id que apenas termina com `tailwind` (`layout-iframe-tailwindcss`).
  - [x] Nenhum componente legado foi alterado.
- [x] **Runtime `interface-tailwind.js`**:
  - [x] Interpreta o MESMO dicionário de regras do validador do Fomantic (`notEmpty`, `minLength[n]`, `maxLength[n]`, `email`, `match[campo]`, `regExp[/…/]`) — nenhuma função PHP de validação mudou.
  - [x] Loader com contador: duas chamadas AJAX simultâneas não apagam o loader na primeira resposta.
  - [x] Modal de Área Restrita abre sozinho e NÃO fecha por Esc nem por clique no fundo.
  - [x] Query string do momento do envio anexada ao POST (contrato herdado).
- [x] **Layout `layout-administrativo-tailwind`**:
  - [x] `layout-administrativo-do-gestor` inalterado.
  - [x] Somente `components/icon.min.css` do Fomantic (folha de ícones, não o framework).
  - [x] Sidebar, topo com avatar, overlay mobile, container fluido e badge de versão.
- [x] **Menu (`menu-principal-sistema-tailwind` + `admin-tailwind.js`)**:
  - [x] Mesma árvore de permissões/grupos do legado; só o vocabulário do estado ativo mudou.
  - [x] Abrir/fechar com overlay em mobile, sem empurrar o conteúdo.
  - [x] Resize por arraste com limites 220–450px, persistência e duplo clique restaurando o padrão.
  - [x] Filtro tolerante a acento/caixa, aviso de vazio, Esc e navegação por teclado (BATCH-105).
- [x] **Painel do perfil**:
  - [x] Três abas sem recarregamento; aba resolvida por querystring → hash → `localStorage`.
  - [x] Medidor de força de senha alinhado ao piso do backend (12 caracteres).
  - [x] Trava de Área Restrita preservada nos quatro fluxos de alteração.
  - [x] QR Code e chave manual continuam só sob `?configurar-seguranca=sim`.
- [x] **Sessões ativas**:
  - [x] `usuario_user_agent_analisar()` e `usuario_sessao_formatar()` puras e cobertas por teste.
  - [x] Revogações exigem `id_usuarios` no WHERE; revogar as outras exige o token atual.
  - [x] Sessão atual marcada com etiqueta e sem botão de revogar.
- [x] **Pré-compilação**: `tailwind_bundle` + dependências declaradas; aviso do F3 eliminado.
- [ ] **Deploy `Update => Core` + homologação runtime**: pendente com o operador.

### Evidência de Validação (BATCH-119)

Reportada pelo executor em 2026-08-18:
- `php -l` → `gestor.php`, `bibliotecas/gestor.php`, `bibliotecas/interface.php`, `bibliotecas/usuario.php`, `perfil-usuario.php` e os 2 testes novos → **OK**.
- `node --check` → `interface-tailwind.js`, `admin-tailwind.js`, `perfil-usuario.js` → **3/3 OK**.
- Compilador de recursos → **191 encontrados, 16 compilados, 0 erros**. Os 2 avisos de F3 (`display` sob variante responsiva no layout novo) desapareceram ao declarar `tailwind_bundle`; restaram os 4 avisos pré-existentes do inventário calibrado no BATCH-117.
- `composer test` (PHPUnit) → **353/353** (1214 assertions, 4 skipped preexistentes), com `PerfilUsuarioSessoesTest` **24/24** e `FrameworkCssResolverTest` **14/14**.
- `npx vitest run` → **309/309** em 20 arquivos, com `perfil-usuario.painel.test.js` **25/25**, `admin-tailwind.test.js` **24/24** e `interface-tailwind.test.js` **32/32**.
- Persistência conferida nos `Data.json`: layout (18.386 B pt-br / 18.406 B en), componente do menu (6.336 B) e bundle da página (21.835 B pt-br / 21.855 B en).
- Cache-bust: tokens determinísticos regenerados (`global`, `interface`, `system` e `asset_version` do módulo `perfil-usuario`).

### Pendências

- Deploy `Update => Core` obrigatório: layout, componentes e página vêm do BANCO.
- Runtime: abrir `/perfil-usuario/` no layout novo; navegar entre as três abas; alterar nome/e-mail/usuário/senha conferindo o modal de Área Restrita; ativar e desativar 2FA; revogar uma sessão e depois "desconectar outros dispositivos"; redimensionar e filtrar o menu; repetir em `/en/` e em mobile.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-120 - Personal Access Tokens e códigos de recuperação de 2FA (req-119, 2026-08-18)

- [x] **Banco**: migração `20260818100000` cria `usuarios_api_tokens` (índice único em `token_hash`) e a coluna `two_factor_recovery_codes` em `usuarios`, ambas idempotentes.
- [x] **Segredos gravados só como hash**: token e recovery codes nunca são recuperáveis do banco.
- [x] **Biblioteca core**:
  - [x] `usuario_api_token_formato()` separa PAT de token OAuth no mesmo `Authorization: Bearer`.
  - [x] `usuario_api_token_situacao()` distingue `ativo`, `revogado` e `expirado`; status desconhecido falha fechado, data ilegível falha aberto.
  - [x] `usuario_api_token_gerar()` usa CSPRNG e devolve o token uma única vez.
  - [x] `usuario_api_token_validar()` recusa token de usuário inativo e registra `ultimo_uso`.
  - [x] `usuario_api_token_revogar()` exige `id_usuarios` no WHERE.
  - [x] Recovery codes: alfabeto sem caracteres ambíguos, normalização antes do hash e consumo de uso único.
- [x] **API**: `api_token_validar_memoizado()` desempata pelo formato e devolve o mesmo contrato do OAuth 2.0 — nenhum endpoint precisou mudar.
- [x] **Aba de Chaves de API**: formulário, exibição única do token com cópia, listagem com situação e revogação; aba removida do HTML para perfis não autorizados.
- [x] **Recovery codes na ativação do 2FA**: 10 códigos exibidos sem recarregar a página.
- [x] **Login 2FA**: código de recuperação tentado só depois de o segundo fator normal falhar; código consumido é invalidado.
- [ ] **Deploy `Update => Core` + homologação runtime**: pendente com o operador (a migração roda sozinha no pipeline).

### Evidência de Validação (BATCH-120)

Reportada pelo executor em 2026-08-18:
- `php -l` → `bibliotecas/usuario.php`, `perfil-usuario.php`, `controladores/api/api.php`, a migração e os 2 testes novos → **OK**.
- `node --check gestor/modulos/perfil-usuario/perfil-usuario.js` → **OK**.
- Compilador de recursos → **0 erros** (apenas os 4 avisos pré-existentes).
- `composer test` (PHPUnit) → **387/387** (1286 assertions, 4 skipped preexistentes), com `UsuarioApiTokensTest` **20/20** e `UsuarioRecoveryCodesTest` **14/14**.
- `npx vitest run` → **328/328** em 21 arquivos, com `perfil-usuario.api-tokens.test.js` **19/19**.

### Pendências

- A migração é aplicada pelo próprio pipeline de deploy. Se ela falhar ou for pulada, o BATCH-122 garante que a aba de chaves simplesmente não aparece, em vez de erro.
- Runtime: criar uma chave e conferir que o valor aparece uma vez e a página não recarrega; usar a chave em `Authorization: Bearer` num endpoint da API e conferir o "Último uso"; revogar e confirmar o 401; ativar o 2FA e conferir os 10 códigos; entrar com um deles e confirmar que ele não funciona duas vezes.
- Escopos são gravados e devolvidos em `scope`, mas ainda não são aplicados por nenhum endpoint (registrado como fora de escopo).
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-121 - Layout público Tailwind e migração das 15 telas de identidade (req-120, 2026-08-18)

- [x] **Layout legado preservado**: `layout-pagina-sem-permissao` inalterado (critério de aceite 1), com teste que o exige.
- [x] **Layout novo** `layout-pagina-sem-permissao-tailwind` (pt-br/en) com header, card central e rodapé; marcadores do pipeline (`pagina#css`, `pagina#js`, `pagina#corpo`, `gestor-listener`) presentes e verificados por teste.
- [x] **15 telas migradas nos dois idiomas** (30 arquivos), todas com `framework_css = tailwindcss` e layout Tailwind.
- [x] **Zero Fomantic**: varredura por classe (`class="ui …"`) e por ícone de webfont (`<i class="… icon">`) em todos os arquivos.
- [x] **Contratos do backend preservados**: ids de formulário, `name` de todos os campos POST, blocos `<!-- x < --> … > -->` e placeholders `#var#`, cobertos caso a caso.
- [x] **Contrato com o runtime**: `data-c2f-form="tailwind"` em todos os formulários validados, caixa `data-c2f-form-erros` e medidor de senha nas telas com senha.
- [x] **HTML gerado em PHP migrado**: alternador de método, botões sociais, campo de código 2FA, reenvio de e-mail e etiqueta de status.
- [x] **Runtime correto por tela**: as 10 inclusões diretas de `interface.js` passaram por `interface_assets_incluir()`.
- [x] **Área Restrita**: migrada para o layout administrativo Tailwind com `tailwind_bundle` (aviso F3 eliminado).
- [ ] **Deploy `Update => Core` + homologação dos fluxos**: pendente com o operador.

### Evidência de Validação (BATCH-121)

Reportada pelo executor em 2026-08-18:
- `php -l gestor/modulos/perfil-usuario/perfil-usuario.php` e no teste novo → **OK**; `node --check` → **OK**.
- Compilador de recursos → **32 compilados, 0 erros**; os avisos de F3 em `Area-restrita` desapareceram com o bundle, restando os 4 pré-existentes.
- Varredura direta nos 30 arquivos de página → **nenhuma** classe do Fomantic.
- `composer test` (PHPUnit) → **494/494** (1807 assertions, 4 skipped preexistentes), com `PerfilUsuarioTelasPublicasTest` **107/107**. Deprecations mantidas em 1 (a pré-existente): o data provider novo usa atributos, não doc-comment.
- `npx vitest run` → **328/328** sem regressão.

### Pendências

- Deploy `Update => Core` obrigatório: layout e páginas vêm do BANCO.
- Runtime: percorrer login (senha e código por e-mail), login social, 2FA (app e e-mail), recovery code, cadastro, esqueci minha senha, redefinição, confirmação de e-mail, logout e Área Restrita — em desktop e mobile, nos dois idiomas.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-122 - Degradação graciosa quando a migração não rodou (req-119, 2026-08-18)

- [x] **Detectores no core** (`bibliotecas/gestor.php`), memoizados por requisição e silenciosos:
  - [x] `gestor_schema_tabela_existe()` — um único `SHOW TABLES` por requisição, resultado inteiro em cache.
  - [x] `gestor_schema_campo_existe()` — confere a TABELA antes, para nunca emitir `SHOW COLUMNS` sobre tabela inexistente.
  - [x] Ambos falham fechado: banco indisponível ou exceção resultam em `false`, nunca em erro propagado.
- [x] **Personal Access Tokens**: gerar, validar, revogar e listar recusam sem a tabela; a aba do perfil não é renderizada (mesmo caminho do perfil não autorizado).
- [x] **Códigos de recuperação**: sem a coluna, o 2FA é ativado normalmente e apenas não gera códigos; o resgate no login não valida; o rótulo do campo não é exibido.
- [x] **API**: token com formato de PAT recebe "credencial inválida" (401), não erro 500.
- [x] **Guarda de carregamento**: os dois gates verificam `function_exists` antes de chamar o detector.

### Evidência de Validação (BATCH-122)

Reportada pelo executor em 2026-08-18:
- `php -l` → `bibliotecas/gestor.php`, `bibliotecas/usuario.php`, `perfil-usuario.php`, teste novo → **OK**.
- `composer test` → **508/508** (novo `SchemaDegradacaoTest` **14/14**, simulando os dois mundos — com e sem migração — pelo cache de schema, sem tocar o banco).
- `npx vitest run` → **328/328** sem regressão.
- Compilador de recursos → **0 erros**.

### Pendências

- Runtime: com a tabela ausente, abrir `/perfil-usuario/` e confirmar que a aba de Chaves de API não aparece e que nenhuma outra aba quebra; ativar o 2FA e confirmar que funciona sem os códigos.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-123 - HTML para o Sistema de Recursos e classes para as Variáveis (correção do Chefe, 2026-08-18)

- [x] **Zero markup em PHP**: as abas de Segurança, Sessões e Chaves de API, os blocos das telas de 2FA e os botões sociais saíram do `perfil-usuario.php` e viraram componentes em `resources/<lang>/components/` (5 componentes × 2 idiomas).
- [x] **Zero constante de classe em PHP**: as 15 classes utilitárias viraram VARIÁVEIS do sistema, consumidas como `@[[classe-…]]@` nos componentes.
- [x] **PHP só decide**: escolhe qual bloco entra e com que valores, via `modelo_tag_val` / `modelo_tag_in` / `modelo_var_in`.
- [x] **Compilador enxerga as classes das variáveis**: `perfil-usuario.json` declarado em `tailwind_sources` (verificado com probe real antes de adotar o desenho).
- [x] **Bundle declara os componentes do módulo**: sem isso, classe que só existe no componente fica fora do CSS.
- [x] **Contrato com o JS preservado**: ids, classes de gancho e `data-*` conferidos caso a caso.
- [ ] **Deploy `Update => Core`**: pendente — componentes e variáveis vêm do banco.

### Evidência de Validação (BATCH-123)

Reportada pelo executor em 2026-08-18:
- `php -l` e `node --check` → **OK**.
- Compilador de recursos → **40 compilados, 0 erros**, 150 componentes no inventário; apenas os 4 avisos pré-existentes.
- Conferência direta no bundle da página (24.114 bytes): `divide-slate-100`, `bg-red-700`, `ring-emerald-500`, `bg-amber-50` e `font-mono` presentes. Antes de declarar os componentes como dependência, `divide-slate-100` estava **ausente** — foi o que provou a necessidade da declaração.
- `composer test` → **520/520** (novo `PerfilUsuarioRecursosTest` **12/12**, 380 assertions).
- `npx vitest run` → **328/328** sem regressão.

### Pendências

- Runtime: abrir o perfil e conferir as três abas com o visual íntegro; alterar uma variável `classe-*` pelo gestor e confirmar que o painel muda sem deploy de código.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

