# BATCH-109 — Cookies, Isenção de Crawlers, Auditoria de CSRF e Correções do Editor Visual

Intake: [req-109.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/archive/req-109.md)
Validação: [VALIDATION-CHECKLIST.md#batch-109](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md)
Decisão: DEC-104

---

## Módulo 1 — Isenção de crawlers e cookie silencioso

**Diagnóstico.** `gestor_cookie_verificacao()` emitia o cookie de verificação e, no MESMO request,
interrompia a resposta com `Location: _gestor-cookie-verify/<id>/?url=…`. Isso vale para provar que o
navegador aceita cookies antes de autenticar — mas era aplicado a TODA requisição, inclusive GET de
página pública. Scrapers de link (WhatsApp, Meta, Twitter/X, Telegram) buscam a URL uma única vez,
não guardam cookie e não seguem o round-trip: o preview chegava vazio ou com o HTML de
`cookies-is-mandatory/`, e as tags OpenGraph da página real nunca eram lidas.

**Implementação.**

- `gestor_crawler_detectar($userAgent)` (biblioteca `gestor`, função pura): 28 tokens de robôs
  sociais, mensageiros, buscadores e validadores, comparados por substring em caixa baixa — os
  User-Agents desses agentes carregam versão e URL de contato variáveis, então casar o token é mais
  estável que uma lista fechada. `gestor_requisicao_crawler()` memoiza o resultado por request.
- `gestor_cookie_verificacao($exigirSessao = false)` passou a ter três desfechos:
  1. crawler → **nada acontece** (nem cookie, nem redirecionamento);
  2. padrão (página pública) → `Set-Cookie` na própria resposta, **sem 302** — o visitante recebe a
     página pedida já na primeira requisição;
  3. `$exigirSessao = true` → mantém o round-trip original.
- O valor recém-emitido é gravado em `$_COOKIE` no processo, para uma segunda chamada no mesmo
  request não reemitir o cabeçalho com outro token. `setcookie` ganhou guarda de `headers_sent()`.
- Quem passa `true`: `gestor_permissao()` (páginas protegidas, fora de AJAX) e os fluxos de
  `signin`/`signup` em `perfil-usuario.php`. `gestor_permissao_token_processar()` e
  `gestor_usuario_perfil()` passaram a usar o modo silencioso — eles apenas LEEM o cookie de auth.
- Rota `_gestor-cookie-verify`: crawler que caia ali (link antigo compartilhado) volta para a URL de
  origem em vez de receber `cookies-is-mandatory/`.

## Módulo 2 — Sanitização de páginas de sistema

- `gestor_pagina_sistema_sem_rastreamento($caminho)` (pura) reconhece `cookies-is-mandatory`,
  `_gestor-cookie-verify`, `404`, `403`, `500` e `503`, exatos ou como prefixo de segmento.
- `gestor_rastreamento_remover($html)` (pura) remove blocos `<script>`, `<noscript>` e `<iframe>`
  cujo conteúdo casa com assinaturas de GTM/GA/Meta Pixel (`googletagmanager.com`,
  `google-analytics.com`, `gtag(`, `datalayer`, `connect.facebook.net`, `fbevents.js`, `fbq(`,
  `facebook.com/tr`). Remove o BLOCO, e não apenas "deixa de adicionar", porque o snippet costuma
  vir do banco (`html_extra_head` da página/layout), fora do controle do core.
- Em `gestor_pagina_extra_head_e_javascript()`: nas páginas de sistema, `project-javascript` é
  zerado e as três filas de head/JS passam pelo filtro; `gestor.rastreamentoBloqueado = true` vai
  para as variáveis JS.
- `global.js`: com a flag ligada, `fbq`, `dataLayer.push` e `gtag` viram no-op **antes** de o
  snippet do projeto rodar — o que elimina o `Duplicate Pixel ID` e a chamada CAPI disparada sem
  cookie provisionado. Coletor já carregado pela página **não** é sobrescrito.

> **Escopo não atendido, com motivo.** O item 4 do intake pede tratar a inicialização do Meta Pixel
> "no frontend (`gestor/assets/global/global.js`)". Não existe nenhuma linha de GTM/Meta Pixel no
> repositório `conn2flow` (verificado por varredura em `gestor/`, `conn2flow-site` e `transformamp`):
> o snippet vive no banco do projeto ou no JS de projeto do deploy. O core entrega o mecanismo
> genérico acima; a deduplicação do ID em si precisa ser feita onde o snippet é declarado.

## Módulo 3 — Permissões de log e warning do editor

- `dev-environment/docker/entrypoint.sh`: `chown -R www-data:www-data /var/www/sites` + `chmod -R
  777` em todo diretório `logs` encontrado, e criação de `gestor/logs` para os sites que ainda não
  o tenham.
- `dev-environment/docker/gerenciar-sites.sh`: a ação `criar` já nasce com `gestor/logs` gravável e
  alinha as permissões dentro do container quando ele está de pé.
- `gestor/bibliotecas/log.php`: diretórios criados com `0777` + `@chmod` defensivo (o mesmo
  diretório é escrito pelo Apache e pelo CLI — quem cria primeiro define o dono); `file_put_contents`
  silenciado e desviado para `error_log` em caso de falha, para o aviso do PHP não sair no meio do
  HTML; `@chmod($arquivo, 0666)` só na criação.
- `gestor/bibliotecas/html-editor.php`: o `default` de `html_editor_ia_prompt()` operava sobre
  `$modelo_texto`, variável inexistente nesta função, e descartava o retorno — puro resíduo copiado
  de outro contexto, gerando `PHP Warning: Undefined variable`. Removido (os demais alvos não têm
  lista de variáveis a substituir; nenhum modo de IA do repositório usa o marcador `<!-- publisher -->`).

## Módulo 4 — CSRF global e Editor Visual

**Causa-raiz do 403 no salvamento.** `global.js` injetava o campo `_csrf_token` num listener nativo
de `submit` em captura. O salvamento do editor chama `$.formSubmitNormal()` → `$(form).submit()`, e
o jQuery percorre uma propagação **simulada**, que não aciona listeners nativos; a ação padrão dele
é `HTMLFormElement.prototype.submit()`, que também não dispara evento algum. O campo nunca era
anexado e o backend respondia `403 {"status":"error","message":"Token CSRF inválido ou ausente."}`.

- `global.js`: `csrfToken()` agora procura em três lugares — `<meta name="csrf-token">`,
  `window.gestor.csrfToken` e `window.parent.gestor.csrfToken` (o iframe `srcdoc` do editor herda a
  origem, mas não o `<head>` da hospedeira). Novo `aplicarCsrfNoFormulario(form)` cobre os três
  caminhos: listener nativo de captura, handler delegado do jQuery e envelope de
  `HTMLFormElement.prototype.submit` (instalado uma única vez, marcado por `__c2fCsrf`). API pública
  em `window.gestorCsrf`.
- `html-editor-interface.js`:
  - nova `moduloUrl()` é a fonte única da URL do módulo. `gestor.moduloCaminho` já chega do backend
    com barra final (`rtrim($caminho,'/').'/'`), então `+ '/'` produzia `admin-paginas/editar//`;
  - o `renderWidgets` do `srcdoc` usa a mesma normalização e envia o token **no cabeçalho
    `X-CSRF-Token` e no corpo `_csrf_token`** (`seguranca_csrf_token_requisicao()` aceita os dois);
  - `previsualizarConfirmar` anexa o token explicitamente e, sem token, exibe aviso amigável
    (pt-br/en) em vez de disparar um POST condenado.
- `gestor/gestor.php`: `gestor_csrf_resposta_invalida()` preserva o JSON para clientes AJAX e passa
  a devolver uma **página HTML** ("Sessão expirada", com botão de voltar) para navegação normal — o
  usuário via o JSON cru em tela cheia e concluía que perdera o trabalho.

### Nome do campo — desvio deliberado do intake

O intake cita `_gestor-csrf-token`. O contrato real do backend, criado no BATCH-107
(`seguranca_csrf_token_requisicao()`), é `_csrf_token` no corpo e `X-CSRF-Token` no cabeçalho.
Introduzir um segundo nome exigiria mudar o validador e reprocessar todos os formulários existentes,
sem ganho. Mantido `_csrf_token`.

## Módulo 5 — OpenGraph

- `gestor_open_graph_tags($params)` (pura) monta `og:title`, `og:description`, `og:image`, `og:url`,
  `og:site_name` e `og:type`, com escape, normalização de espaços e **omissão de tag vazia** (um
  `og:image` vazio faz o WhatsApp exibir o card sem imagem, em vez de recorrer ao fallback do site).
  Acrescenta `twitter:card` (`summary_large_image` com imagem, `summary` sem).
- `gestor_open_graph_existe($html)` (pura): página/layout que já traga OpenGraph próprio no
  `html_extra_head` **não** recebe o conjunto do core — duas `og:title` fazem o scraper escolher
  arbitrariamente.
- `gestor_open_graph_dados()` resolve os valores na ordem: `$_GESTOR['pagina#og']` (o CRUD dessas
  colunas é escopo do req-110) → nome/título da página → `config.php`. Novas chaves de fallback:
  `site-description` (`SITE_DESCRIPTION`) e `site-og-image` (`SITE_OG_IMAGE`, aceita URL absoluta ou
  caminho relativo, absolutizado com `url-full-http`).
- Injeção em `gestor_pagina_extra_head_e_javascript()`, exceto em `paginaIframe` (ruído em iframes
  de sistema).
- `gestor_roteador_crawler_pagina_protegida()`: crawler sem sessão numa página protegida recebe
  `200` com um documento que tem **apenas `<head>`** — título, `robots: noindex` e as tags
  OpenGraph. O corpo sai vazio e o módulo da página **não chega a ser incluído**. Visitante humano
  sem login continua indo para `/signin/` com o retorno preservado.

---

## Arquivos alterados

| Arquivo | Módulos |
| --- | --- |
| `gestor/bibliotecas/gestor.php` | M1, M2, M5 (5 funções puras novas) |
| `gestor/gestor.php` | M1, M2, M4, M5 |
| `gestor/config.php` | M5 (2 chaves de fallback) |
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | M1 (2 chamadas) |
| `gestor/bibliotecas/log.php` | M3 |
| `gestor/bibliotecas/html-editor.php` | M3 |
| `gestor/assets/global/global.js` | M2, M4 |
| `gestor/assets/interface/html-editor-interface.js` | M4 |
| `dev-environment/docker/entrypoint.sh` | M3 |
| `dev-environment/docker/gerenciar-sites.sh` | M3 |
| `tests/Unit/PHP/CrawlersOpenGraphTest.php` | novo — 15 casos |
| `tests/Unit/JS/global-csrf.test.js` | novo — 14 casos |
| `tests/Unit/JS/html-editor-csrf-url.test.js` | novo — 14 casos |
| `tests/Unit/JS/global-auth-redirect.test.js` | stub de jQuery ganhou `on()` |

## Observação registrada (não alterada nesta rodada)

Todos os `setcookie` do core usam `'secure' => true` — convenção deliberada do projeto. Em HTTP puro
o navegador descarta o cookie, e o fluxo ANTIGO entrava num vai-e-vem até `cookies-is-mandatory/`.
Com a verificação silenciosa isso deixa de afetar páginas públicas; nos fluxos de autenticação sobre
HTTP o comportamento permanece. Mexer nisso é mudança de postura de segurança e exige decisão do
Chefe — não foi feito aqui.
