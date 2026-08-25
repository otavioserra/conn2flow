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


---
## BATCH-126 - Layout administrativo Tailwind, ícones do menu, histórico do perfil e paleta azul Conn2Flow (req-124, 2026-08-21)

- [x] **F1 — Editbar sem sobreposição**: `gestor_dashboard_toolbar()` marca o `<body>` com `c2f-toolbar-ativa` no mesmo passo em que injeta o iframe; o CSS autoral do layout desce a barra lateral (`fixed`), o overlay e o cabeçalho (`sticky`) em 30px e devolve a altura ao shell (`min-height: calc(100vh - 30px)`).
- [x] **F2 — Ícones completos no menu**: causa-raiz era vocabulário, não cadastro. O menu Tailwind recebia nomes **Lucide** (`modulos.icone_tailwind`) e o componente os desenhava contra a folha do **Fomantic** — 19 dos 33 módulos do menu ficavam sem glifo (os 8 relatados entre eles). O item passa a nascer `<i data-lucide="X" class="X icon">` e o layout carrega o Lucide com `defer`.
- [x] **F2b — Catálogo saneado**: `modulos-operacoes` usava `settings2`, que não existe no Lucide (o nome é `settings-2`); `interface` e `ftp` eram os únicos módulos sem par de ícones e foram preenchidos.
- [x] **F3 — Conteúdo alcança a borda ao recolher o menu**: `marginLeft = ''` devolvia o recuo à utility `lg:ml-[260px]` do layout em vez de zerá-lo. O runtime passa a zerar explicitamente e a liberar a largura de leitura do `<main>` (`max-w-7xl` → `none`) enquanto a barra está recolhida.
- [x] **F4 — Botão "Sair" alcançável**: o container do menu usava `h-full`, e `min-height:auto` o impedia de encolher — o rodapé saía do viewport. Agora é `min-h-0 flex-1`, o `<nav>` ganhou `min-h-0` e a folga inferior subiu de `pb-4` para `pb-16`.
- [x] **F5 — Histórico saneado**: a troca casava a string literal `<td>#historico#</td>`, que só existe no componente Fomantic; no Tailwind (`<td class="px-4 py-2 …">`) não casava e o marcador cru era impresso. A troca passa a mirar só o token. O bloco ganhou `data-c2f-historico` e o painel do perfil o mantém restrito à aba de Dados.
- [x] **F6 — Paleta azul Conn2Flow**: botões primários, links, anéis de foco, cor de controle marcado e sublinhado da aba ativa migraram de `emerald` para `sky` nas 16 telas do módulo (× 2 idiomas), nas variáveis `classe-botao-ok` e `classe-publico-campo` e nos componentes globais de edição/alerta. O verde ficou reservado à SEMÂNTICA: banners de sucesso, etiquetas de "ativo" (2FA, social vinculado, sessão atual) e a faixa "Forte" da força de senha.
- [x] **Bônus de correção**: `perfil-usuario.js` entrou em `tailwind_sources` — as classes que o runtime aplica não eram escaneadas, e `bg-emerald-500` (força "Forte") estava fora do bundle.
- [ ] **Deploy `Update => Core` + homologação visual**: pendente com o operador — layout, componentes, variáveis e `modulos` vêm do banco.

### Evidência de Validação (BATCH-126)

Reportada pelo executor em 2026-08-21:
- `php -l` → **OK** em todos os PHP alterados e nos 2 arquivos de teste novos/editados.
- `node --check` (via `new Function`) → **OK** em `admin-tailwind.js` e `perfil-usuario.js`.
- Compilador de recursos → **50 compilados na 1ª passada, 0 erros**; apenas os 4 avisos pré-existentes (`html-editor-publisher-simulation`, `sessao-com-2-colunas-fomantic-ui`), não relacionados ao lote.
- `composer test` → **581/581** (2760 assertions). Antes do lote: 537. Novos: `LayoutAdministrativoTailwindTest` **13/13** (254 assertions) e 2 casos de paleta em `PerfilUsuarioTelasPublicasTest`.
- `npm run test` (Vitest) → **331/331** em 21 arquivos. Antes do lote: 328, com **4 falhas** nos testes que codificavam o comportamento antigo (3 de `marginLeft === ''`, 1 de `border-emerald-600`) — atualizados para o novo contrato, mais 3 casos de regressão novos.
- Conferência direta nos bundles: `min-h-0`, `pb-16` e `size-4` presentes no layout; `bg-sky-600`/`border-sky-600`/`text-sky-700` nas telas públicas; `bg-emerald-500` passou a existir no bundle do perfil após a inclusão do `.js` em `tailwind_sources`.
- Validação cruzada de catálogos: os 33 módulos do menu foram conferidos contra o `icon.min.css` real do Fomantic 2.9.4 e contra os 1.857 ícones do bundle Lucide 0.544.0 — foi assim que os 19 sem glifo e o `settings2` inválido apareceram.

### Pendências

- Runtime: abrir o painel com a Editbar ativa; recolher o menu e conferir a borda esquerda; rolar a barra até o botão "Sair"; percorrer as 4 abas do perfil observando o histórico; revisar as telas públicas de identidade no azul Conn2Flow.
- **Banco local sem a migração `icone_tailwind`**: `modulos` ainda não tem a coluna no ambiente de dev. Enquanto ela não rodar, o menu cai no vocabulário Fomantic legado — coberto pela dupla camada `data-lucide` + `class`, mas a homologação de F2 só é conclusiva após `db:update`.
- **Impacto cruzado no `lumix` (SnapPhoton), tratado no mesmo dia**: os três botões primários do Core em Tailwind (`interface-formulario-edicao-tailwind`, `interface-alerta-modal-tailwind`, `interface-formulario-autorizacao-provisoria-tailwind`) migraram para `sky`, e o painel do SnapPhoton é verde. O guard de paridade byte a byte do projeto (`admin-menu-config.test.php`) acusou os dois espelhos divergentes na primeira execução; foram ressincronizados e o painel voltou ao verde por CSS autoral do layout `photon-admin`, sem bifurcar componente. Detalhe no BATCH-176 do `lumix`.
- **Variável do Core adicionada pelo lote irmão**: `restrict-area-back-label` (`pt-br` = Voltar, `en` = Back) entrou no módulo `perfil-usuario` por conta da req-087 do SnapPhoton, ao lado das irmãs `restrict-area-info*`. Ela viaja para os ambientes junto com esta release.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-127 - Reload limpo em CSRF, ícones dos módulos de projeto, alternância dos botões de menu e saneamento do Lucide (req-125, 2026-08-21)

- [x] **F1 — Reload limpo em CSRF / sessão expirada**: `gestor_csrf_destino_recarregamento()` (pura, em `gestor/bibliotecas/gestor.php`) resolve a tela de origem por DUAS fontes, nesta ordem: o caminho da requisição que falhou — que é o que existe quando o navegador não manda referer — e, só depois, o `HTTP_REFERER`. Quando a origem é uma rota de identidade (`signin`, `signin-2fa`, `signup`, `forgot-password`, `reset-password`, `validate-user`), o botão "Voltar" faz `location.replace()` para ela: GET novo, token novo, e a tela de erro sai do histórico. Fora dessas rotas o destino é vazio e o botão segue em `history.back()`, que é o comportamento desejável no resto do gestor. A resposta ganhou `Cache-Control: no-store` para ela própria não voltar do bfcache. O destino vai no HTML como DADO (`data-c2f-destino`), não interpolado dentro do JavaScript.
- [x] **F2 — Ícones dos módulos servidos por projeto**: os pares foram gravados onde os módulos realmente existem — o `ModulosData.json` do `conn2flow-site` — usando os ids REAIS: `3d-catalog`/`box`, `3d-catalog-groups`/`boxes`, `3d-catalog-items`/`box`, `social-connections`/`share-2`, `gateways-pagamentos`/`credit-card`, `publisher-social-media`/`megaphone`, `social-apps`/`smartphone`, `arquivos`/`folder-open` e `modulos-grupos-distribuido`/`network`. A migração `20260821100000_alter_modulos_update_icones_projetos` (núcleo) é o que alcança bancos já existentes; ela cobre os ids reais e também os apelidos em português, porque um `UPDATE` sem correspondência custa zero. Guarda de coluna (`hasColumn('icone_tailwind')`) para poder rodar em qualquer ordem de catch-up, e valores por bind.
- [x] **F3 — Alternância dos botões abrir/fechar**: `sincronizarBotoes()` no `admin-tailwind.js` deixa em tela só o botão contextual, chamado por `abrir()` e `fechar()` — o que cobre também o estado inicial, já que o boot passa por um dos dois. O botão "abrir" nasce com `lg:hidden` no layout (desktop nasce com o menu expandido) e o runtime **remove a utility no boot**, no mesmo padrão que a barra lateral já usa com `lg:translate-x-0`.
- [x] **F4 — Saneamento de `data-lucide`**: o ATRIBUTO passou a ser montado no backend por `gestor_pagina_menu_icone_lucide_atributo()`, e o template do `menu-principal-sistema-tailwind` recebe `#icon-lucide#` / `#icon-2-lucide#` em vez de `data-lucide="#icon#"`. Segunda camada no `admin-tailwind.js`: `sanearIcones()` remove `data-lucide` inválido de qualquer origem antes de `createIcons()`.
- [x] **Compilação de recursos**: `c2f resources:sync` → **2652 recursos, 0 erros**; `menu-principal-sistema-tailwind` de `1.4` para `1.5` nos dois idiomas, com checksum recalculado pelo pipeline. Token do asset `global` renovado (o `admin-tailwind.js` mudou).
- [x] **Testes**: `composer test` **630/630** (2925 asserções, 4 skips de ambiente), `npm run test` **337/337** em 21 arquivos, `php -l` verde em todos os PHP tocados e `node --check` verde no JS.

### O que estava errado no rascunho anterior deste lote

Uma sessão anterior deixou a F2 apontando para o lugar errado, e isso vale registro porque o sintoma seria nulo — não há erro, não há log:

- **Nove módulos foram INSERIDOS no `ModulosData.json` do núcleo** com ids em português (`catalogo-3d`, `conexoes-sociais`, `publicador-midias-sociais`, `modulos-grupos-distribuidos`…). Nenhum desses ids existe: os módulos vivem em `conn2flow-site/gestor/modulos/` e são registrados com id em inglês — e `modulos-grupos-distribuido` é singular. O núcleo não hospeda nenhum deles (só `admin-arquivos`), e os registros não vinham com página associada, então `gestor_pagina_menu()` os descartaria em silêncio; o efeito real seria linha órfã em `modulos` em TODO ambiente do núcleo. Os 18 registros (9 módulos × 2 idiomas) foram removidos.
- **A migração fazia `UPDATE` sobre esses mesmos ids inexistentes** e interpolava valores direto no SQL. Reescrita com os ids reais, bind de parâmetros e guarda de coluna.
- **O saneamento do `data-lucide` era um `str_replace` da marcação já renderizada** (`str_replace('data-lucide="'.$icone.'"', '', $html)`) — o antipadrão que o BATCH-126 registrou com o `#historico#`. Substituído pelo marcador dedicado no template.
- **A alternância usava só `classList.add('hidden')`**, que NÃO esconde estes dois botões: ambos são `inline-flex` e, no bundle do layout, `.inline-flex` é emitida depois de `.hidden` — mesma especificidade, mesma camada, ganha a última. Quem apaga é o atributo booleano `hidden`, servido pelo preflight como `display:none!important` em `@layer base` (e `!important` inverte a ordem das camadas). A classe continua sendo escrita como estado declarado; o atributo é o mecanismo.

### Evidência de Validação (BATCH-127)

- `php -l` → **OK** em `gestor/gestor.php`, `gestor/bibliotecas/gestor.php`, `gestor/bibliotecas/interface.php`, a migração e os dois arquivos de teste.
- `node --check` → **OK** em `gestor/assets/global/admin-tailwind.js`.
- `c2f resources:sync` → **2652 recursos, 0 erros**; os 4 avisos são os pré-existentes (`html-editor-publisher-simulation`, `sessao-com-2-colunas-fomantic-ui`), não relacionados ao lote.
- `composer test` → **630/630**, 2925 asserções. Antes do lote: 581.
- `npm run test` → **337/337**. Antes do lote: 331.
- **Catálogos conferidos contra as fontes reais, não contra memória**: os 8 nomes Fomantic foram casados com as 1.941 combinações de `i.icon.*` do `icon.min.css` 2.9.4, e os 11 nomes Lucide com os 1.862 ícones do bundle UMD 0.544.0. Todos existem.
- Testes novos: `CsrfReloadIconesMenuTest` (43 casos) exercita as funções puras de verdade — foi para isso que elas foram movidas do bootstrap para `gestor/bibliotecas/gestor.php`, já que `gestor/gestor.php` termina em `gestor_start()` e não pode ser incluído por um caso de teste. Inclui dois guardas de dados: o núcleo não pode registrar módulo de projeto, e todo `icone_tailwind` do núcleo tem que ser endereçável no Lucide. Em `admin-tailwind.test.js`, 2 casos novos cobrem o mecanismo real (atributo `hidden`) e a liberação do `lg:hidden` no boot.
- **Falsificação verificada**: com o teste do `lg:hidden` escrito com `\b` no padrão PCRE, o `\b` foi interpretado como backspace e o teste falhou contra um arquivo correto — o erro apareceu como falha, não como falso verde, e o padrão foi corrigido.

### Pendências

- **Homologação visual (operador)**: recolher e expandir o menu no desktop e no mobile conferindo que só um dos dois botões aparece; abrir o painel com o console aberto e confirmar zero `icon name was not found`; forçar um CSRF inválido no `/signin/` (aguardar a sessão expirar ou limpar o cookie) e clicar em "Voltar" verificando que a tela de login recarrega com token novo em vez de repetir o erro.
- **Ícones do projeto dependem de dois passos fora deste repositório**: o `ModulosData.json` alterado é o do `conn2flow-site`, e o par só aparece no menu depois do deploy de recursos daquele projeto; para bancos já existentes, depois de `db:update` rodar a migração `20260821100000`. Enquanto nenhum dos dois rodar, o menu segue no vocabulário Fomantic — que agora é o caminho silencioso e correto, sem warning.
- **Módulos do `conn2flow-site` fora do escopo do intake**: `checkout`, `host-manager`, `host-user-manager`, `pedidos`, `pro-manager` e `produtos` seguem sem `icone_tailwind`. Não geram mais warning (F4 cobre), mas desenham pelo Fomantic. O intake não os nomeia; ficam registrados aqui.
- **Documentação de release neste mesmo working tree**: `CHANGELOG*`, `README*` e os dois workflows de release foram atualizados por uma sessão anterior e não fazem parte do escopo do req-125. As entradas de changelog que descreviam a F2 com os ids em português foram corrigidas para os ids reais.
- Restrição Nível 1 respeitada: nenhum `git commit`, `git push` ou deploy executado.

---

## BATCH-130

### Critérios automatizados

- [x] `banco_num_rows(false)`, `banco_num_rows(null)` e objeto inválido retornam `0` sem `TypeError`.
- [x] `banco_select()` valida `$res` antes de chamar `banco_num_rows()`.
- [x] A redefinição de senha inicializa `$id_hosts = null` e protege a consulta com
  `gestor_schema_tabela_existe('usuarios_gestores_hosts')`.
- [x] `raiz` e `sem_permissao` usam `!empty()` nos fluxos de inclusão e clonagem em
  `admin-paginas.php`.
- [x] Layout sem `css_compiled` assume `''` em `gestor/gestor.php`.
- [x] `php -l` limpo nos quatro arquivos de produção e em `Req128HardeningTest.php`.
- [x] Teste focado: 5 testes, 8 asserções, sem warnings.
- [x] Suíte completa: 685 testes, 3.094 asserções, 4 skips de ambiente. A única depreciação do
  PHPUnit é preexistente em `TwoFactorTest::testHotpBateComVetoresRfc4226()`.
- [x] `git diff --check` limpo.

### Homologação pendente

- [ ] Em instalação dedicada sem `usuarios_gestores_hosts`, redefinir uma senha e confirmar que a
  senha é atualizada, o histórico é gravado sem host e o e-mail de confirmação é enviado.
- [ ] Criar/editar página sem enviar `raiz` e `sem_permissao` e confirmar ausência de warnings no log.
- [ ] Renderizar layout sem `css_compiled` e confirmar ausência de warning no log.
- Restrição Nível 1 respeitada: nenhum `git commit`, `git push` ou deploy executado.

---

## BATCH-129 — Extrator Semântico de Tokens do Tailwind para o Assistente de IA (req-127)

Alvo de falsificação do lote: **o payload que sai para a API de IA carrega a paleta e as classes do
projeto ativo, e o acréscimo cabe em ~1,5 KB mesmo contra um contrato de 78 KB.**

- [x] **Extrator (M1)**: `html_editor_ia_extrair_tokens_tema()` resolve o contrato pela mesma ordem
  do runtime do Tailwind Browser (`contents/` do projeto na frente de `assets/` do core) e delega
  para a parte pura `html_editor_ia_tokens_tema_compilar()`.
- [x] **Injeção (M2)**: `{{theme_tokens}}` trocado em `html_editor_ajax_ia_requests()` antes do
  switch de `target`, cobrindo os três escopos de edição pela mesma linha.
- [x] **Modos (M3)**: diretriz nos 6 `.md` de `resources/<lang>/ai_modes/`, `version` 1.0 → 1.1 nos
  `ai_modes` de `admin-paginas` e `admin-componentes`, `ModosIaData.json` recompilado.

### Medição contra os contratos reais do ambiente local

| Projeto | `browser-contract.css` | Extraído | Redução |
| --- | ---: | ---: | ---: |
| `transformamp` | 78.485 B (2.255 linhas) | 1.482 B | **98,11%** |
| `photon` | 3.163 B | 1.307 B | 58,68% |
| `conn2flow-site` | 274 B | 147 B | 46,35% |
| core (`gestor/assets/`) | 143 B (só comentário) | 0 B | — |

O `transformamp` é o caso que motivou o lote e o que prova o critério de aceite 2. Conferido na
saída dele: zero ocorrências de `data:` e de `--art-` (os SVGs embutidos ficaram de fora), e os 3
`--shadow-*` presentes ao lado das cores — que é o round-robin funcionando.

### Evidência de Validação (BATCH-129)

- `php -l` → **OK** em `gestor/bibliotecas/html-editor.php` e nos dois arquivos de teste novos.
- `c2f resources:sync` → **2.652 recursos, 0 erros**. Os 4 avisos são os pré-existentes
  (`html-editor-publisher-simulation`, `sessao-com-2-colunas-fomantic-ui`), sem relação com o lote.
- `composer test` → **680/680**, 3.086 asserções, 4 skips de ambiente. Antes do lote: 630. A única
  deprecation do PHPUnit é pré-existente — aparece igual rodando só `HtmlEditorBaselineTest`.
- `npx vitest run` → **337/337** em 21 arquivos, inalterado. Nenhum JS foi tocado neste lote: o modo
  de IA já viajava do CodeMirror para o backend, e a substituição é inteiramente server-side.
- `ModosIaData.json` conferido depois do sync: os 6 modos (`paginas`, `paginas-editbar`,
  `componentes` × `pt-br`/`en`) carregam `{{theme_tokens}}` e `file_version` 1.1.

### Cobertura nova

- `tests/Unit/PHP/HtmlEditorIaThemeTokensTest.php` (**39 casos**): declarações de tema, descarte de
  data URI e de valor longo, `@theme` com e sem `static`, chave aninhada no bloco, comentários,
  classes de `@layer components` com pseudo-classes colapsadas, orçamento contra um contrato
  sintético de 30 KB+, round-robin, bloco condicional (LF e CRLF), resumo de classes com desescape,
  e dois guardas de dados: os 6 `.md` declaram a seção com o par de marcadores na ordem certa, e o
  `ModosIaData.json` compilado carrega a tag.
- `tests/Integration/HtmlEditorIaRequestsThemeTokensTest.php` (**11 casos**): dispara
  `html_editor_ajax_ia_requests()` de verdade com um dublê de `ia_enviar_prompt()` e inspeciona o
  prompt montado. É o que verifica o critério de aceite 1 sem depender de homologação manual — o
  prompt só existe dentro da função, é entregue ao servidor de IA e descartado. Cobre os três
  escopos (`tudo`, `sessao`, `editbar-element`), o projeto sem contrato, o opt-in do
  `{{css_compiled}}` e a integridade do envelope JSON de resposta.

### Pendências

- **Homologação (operador)**: abrir o Assistente de IA num projeto com tema próprio (`transformamp`
  ou `photon`), pedir um bloco novo e conferir que o HTML gerado usa as classes da marca
  (`bg-mp-red`, `text-mp-gold`) em vez de cores genéricas do Tailwind. Repetir pela Editbar sobre um
  elemento isolado. É o critério de aceite 3, que depende do comportamento do modelo e não é
  falsificável por teste automatizado.
- **O prompt novo só chega ao ambiente depois do sync de banco.** `modos_ia` tem
  `preserve_on_user_modified: []`, então o UPSERT sobrescreve o prompt — inclusive um que o operador
  tenha editado no painel de IA. Comportamento pré-existente da tabela, registrado aqui porque este
  lote é o primeiro a mexer nos prompts padrão desde que a Editbar existe.
- **Bug pré-existente fora do escopo**: `versao` de `ai_modes`/`ai_prompts` nunca incrementa
  (`carregarDadosExistentes()` indexa como `modos_ia`/`prompts_ia`, `versaoChecksumPrompt()` consulta
  como `ai_modes`/`ai_prompts`). Não bloqueia o lote — o sync decide pelo md5 do arquivo e faz UPSERT
  campo a campo. Detalhado em [BATCH-129.md](../implementation/BATCH-129.md).
- Restrição Nível 1 respeitada: nenhum `git commit`, `git push` ou deploy executado.

---

## BATCH-132 — Fallback Automático para Template de E-mail, Blindagem de Configurações e Correção de CSRF/URL no Módulo Forms (req-130)

- [x] Fallback robusto para template padrão `forms-prepared-email` implementado em `formulario.php` quando `message_component` estiver vazio, nulo ou inexistente.
- [x] Processamento correto de tags (`#code#`, `#formName#`) e células repetíveis (`<!-- cel < -->`) com labels e valores sanitizados dos campos enviados.
- [x] Sanitização e fallbacks para assunto (`subject` -> `forms-subject-emails`), `reply_to` e nome do remetente.
- [x] Fallback consistente de `form_action` apontando para `forms-submissions-process/` quando não configurado.
- [x] Normalização de URLs no painel administrativo de formulários (`moduloUrl()` em `forms.js`), eliminando duplicação de barras (`//`).
- [x] Envio explícito do token `_csrf_token` em todas as chamadas AJAX do painel administrativo (`widget-preview`, `buscar-componentes`, `template-load`).
- [x] Limpeza do valor do input hidden `#email_message_component` ao limpar a busca com `.sig-clear` ou apagar o texto no input de busca.
- [x] Testes automatizados executados com 100% de aprovação: PHPUnit **697/697** (novos 7 testes em `Req130FormsHardeningTest.php`), Vitest **342/342** (novos 5 testes em `forms.admin-hardening.test.js`).
- [x] Sintaxe limpa em `php -l`, `node --check` e `git diff --check`.
- [ ] Homologação manual do envio de formulário com recebimento de e-mail e busca de componentes no painel — **operador**.

## BATCH-133

Repasse da identidade do projeto ao atualizador de banco no deploy local (req-131).

- [x] `updates-manager-database.sh` repassa `--project=$PROJECT_TARGET` ao atualizador **apenas
  quando** recebeu `--project`; sem o parametro, a execucao segue sendo atualizacao normal do
  nucleo e nao marca recurso algum.
- [x] O identificador e validado com `^[a-zA-Z0-9_-]+$` antes de compor a linha executada por
  `docker exec`, no mesmo criterio ja aplicado a `--tables`.
- [x] `bash -n` no script: OK.
- [x] `tests/Unit/PHP/ProjectIdentityPassthroughTest.php` (novo): **5 testes, 17 assercoes**.
  - [x] **O defeito reproduzido**: sem identidade, o recurso marcado NAO e atualizado e a linha e
    contada como "sem alteracao" — o relatorio fecha em sucesso sem nenhum aviso.
  - [x] Com identidade, o recurso e atualizado e a marcacao e mantida.
  - [x] Deploy de OUTRO projeto reescreve a marcacao (e quem publicou por ultimo).
  - [x] **Garantia do operador**: com `user_modified = 1`, o conteudo do usuario e preservado e a
    marcacao dele permanece MESMO no deploy de projeto; `system_updated` sobe para 1.
  - [x] O repasse no script e condicionado, e o identificador e validado.
- [x] Suite completa do nucleo: **702 testes, 3.174 assercoes**, sem regressao (1 deprecation e 4
  skipped pre-existentes).
- [x] **Ponta a ponta no ambiente local** (`snapphoton-local`): marcador acrescentado ao CSS de uma
  pagina com `project` preenchido; deploy rodado **sem** `--force-all` e **sem**
  `forcar_atualizacao`; o CSS chegou ao banco (`versao` 2 -> 3), com `project` preservado e
  `user_modified` intacto em 0. O contorno declarativo criado no projeto foi removido.
- [x] Tarefas do VS Code conferidas: `Projects - Update => Core` e
  `Projects - Synchronize => Database -> ID` sao as corrigidas; `Projects - Deploy Project -> ID`
  (caminho remoto) e `Manager - Synchronize => Database - Test Environment` (sem `--project`) nao
  sao afetadas.
- [ ] **Registrado, fora de escopo**: quando um recurso com `user_modified = 1` recebe versao nova,
  o valor novo so vai para `<campo>_updated` se a coluna JA tiver valor (`isset(null)` e falso). Com
  a coluna em `NULL` — o caso comum na primeira divergencia — a versao nova e descartada em vez de
  ficar disponivel para comparacao. O conteudo do usuario segue protegido. O comportamento atual foi
  FIXADO por teste; troca-lo altera o que o deploy grava em toda base em producao e e decisao de
  outra ordem. Candidato a requisicao propria.
- [ ] Homologacao do operador: rodar `Projects - Update => Core` num projeto real e confirmar que os
  recursos alterados chegam ao banco e que paginas editadas pelo cliente permanecem intactas.

## req-132 / BATCH-134 — limpeza do HTML na entrega

- [x] `gestor_html_higienizar()` remove comentarios HTML, comentarios CSS e indentacao; entra na
      ULTIMA etapa antes do `echo`, depois de toda injecao — varios marcadores do core SAO
      comentarios HTML (`<!-- pagina#css -->`).
- [x] Preservados: `<pre>` e `<textarea>` (espaco ali e conteudo), `<script>` (JS nao se limpa com
      regex) e comentarios condicionais (sao instrucao). A quebra de linha permanece: entre
      elementos inline ela e renderizada.
- [x] Gate `HTML_SANITIZE` de tres estados; **valor desconhecido cai em `auto`, nao em `off`** —
      chave digitada errado nao pode desligar em silencio a limpeza de um site em producao.
- [x] Configuravel pela tela do `admin-environment` (aba Site), com gravacao restrita aos tres
      valores validos.
- [x] **Medido na pagina publica real do Photon** (138,7 KB, 490 elementos): 94,1 KB (**-32,1%**) em
      1,06 ms; **arvore DOM identica** (mesmos elementos, ordem e atributos); `<script>` byte a byte
      igual; 95 comentarios HTML e 45 de CSS a zero.
- [x] A prova compara **arvore DOM**, nao contagem de texto: a primeira versao acusou um
      `<template>` "perdido" que era mencao textual dentro de um comentario removido.
- [x] `tests/Unit/PHP/HtmlSanitizeTest.php`: 17 testes, 24 assercoes. Suite **719/719** (702 antes).
      Captura medida: sem a protecao de `<pre>`/`<textarea>`/`<script>`, a suite falha.

### Pendente do operador (req-132)

- [ ] Adicionar `HTML_SANITIZE=auto` ao template `gestor/autenticacoes.exemplo/dominio/.env` (regra
      de permissao impede o agente de escrever `.env*`); `atualizacoes-sistema.php` faz o merge
      aditivo para as instalacoes existentes.
- [ ] Conferir a tela do `admin-environment`, salvar nos tres modos e inspecionar o codigo-fonte de
      uma pagina publica com `on` no ambiente local.

