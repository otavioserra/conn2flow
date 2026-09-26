---
title: "gestor.php library"
label: "Gestor core"
description: "The Gestor's support core: components and layouts, per-module texts, page resources, SEO and OpenGraph, restricted access, redirection, database-backed sessions and sanitization of the served HTML."
section: reference
order: 11
sources:
  - gestor/bibliotecas/gestor.php
  - gestor/config.php
verified_at: a6e51e29
---

# The `gestor.php` library

`bibliotecas/gestor.php` is the support library used across the whole Gestor and is always loaded. Do not confuse it with `gestor/gestor.php`, the **router**, which builds the page on every request and calls these functions. When loaded, it also includes `recursos.php` if it is not present yet, because every `<script>`/`<link>` tag resolves its URL through `recursos_url()`.

Many functions are **pure**, with no global state: they were extracted from the router (which ends in `exit` and cannot be included by a test) precisely to be testable.

## Basic utilities

- `existe($dado)` is the "does it have content?" check used across the core: an array with elements, a string with at least one character, or any truthy value. Beware: `existe('0')` is `true`, but `existe(0)` is `false`.
- `gestor_asset_version($owner = null, $fallback = null)` returns the cache token of an assets directory. It looks in `$_GESTOR['asset-versions']['owners'][$owner]`, then in `$_GESTOR['asset-version']`, the `$fallback` and the system version.
- `gestor_modulo_asset_version($modulo)` returns the cache token of a module's JS/CSS: `asset_version` from the module JSON, otherwise `versao`, otherwise the global token.
- `gestor_modulos_dados($modulo_id)` reads and decodes `gestor/modulos/<id>/<id>.json`. It returns `null` when the file does not exist, without emitting a warning.
- `gestor_js_variavel_incluir($variavel, $valor)` publishes a value to the page's JavaScript in `$_GESTOR['javascript-vars']`, which the router serializes as a global object. When the variable already exists and both values are arrays, it runs `array_merge_recursive`; otherwise it overwrites.

## Libraries

`gestor_incluir_bibliotecas()` and `gestor_incluir_biblioteca($name)` load libraries by logical name. See [Gestor libraries](index.md).

## A page's CSS framework

A final page is layout + page, and **both** have `framework_css`.

- `gestor_framework_css_resolver($layout, $pagina)` decides what to load:
  - **Fomantic** is included when either one declares it, or when neither declares anything (legacy);
  - **Tailwind** is included when either one declares it.
  - It returns `['fomantic' => bool, 'tailwind' => bool, 'modo' => 'fomantic-ui'|'tailwindcss'|'hibrido']`.
- `gestor_framework_css_atual()` applies the rule to `$_GESTOR['layout#framework_css']` and `$_GESTOR['pagina#framework_css']`.

## Database schema without a 500 error

New code can reach an old database (a failed migration, a files-only update, `--skip-migrate`, the deploy window). So that the new feature **disappears** instead of breaking the screen:

- `gestor_schema_tabela_existe($tabela)` runs one `SHOW TABLES` per request (memoized) and also answers `false` when the database could not be queried;
- `gestor_schema_campo_existe($campo, $tabela)` checks the table first and memoizes per `table.field`, without throwing.

## Components and layouts

### `gestor_componente($params)`

Fetches components from the `componentes` table, in the current language (or in `linguagem`).

| Parameter | Effect |
|---|---|
| `id` | Textual id, or an **array of ids** (returns `[id => ['html' => …]]`) |
| `id_componentes` | Numeric id (alternative) |
| `modulo` | Restricts to the module |
| `return_css` | Also returns `css`, `css_precompiled`, `css_compiled` and `html_extra_head`, without adding them to the page |
| `modulosExtra` | Modules whose variables will also be resolved on the page |

- Without `return_css`, the component's CSS and `html_extra_head` go into `<head>` through `gestor_pagina_recursos_incluir()`, deduplicated, and only the HTML is returned.
- With `$_GESTOR['development-env']` enabled, the HTML and CSS come **from the files** in `resources/` (core or module), not from the database.
- Not found: `''` (or `[]` with an array of ids). The query **does not filter `status`**.
- `gestor_componente_ids_condicao($ids)` builds the escaped `(id='a' OR id='b')` used by the multiple lookup.

To append components to the end of the page: mark them with `gestor_componentes_incluir(['id' => …])` or `(['componentes' => [...]])`; the router calls `gestor_componentes_incluir_pagina()`, which renders each marked component and appends it to `$_GESTOR['pagina']`.

### `gestor_layout($params)`

Same model, on the `layouts` table (`id`, `id_layouts`, `return_css`, `modulosExtra`). Differences:
- it stores the layout's `framework_css` in `$_GESTOR['layout#framework_css']`;
- it includes the precompiled CSS with the `layout-precompiled` role, so the layout comes first in the cascade;
- in development, it also reads **plugin** layouts (`plugins/<plugin>/modules/<module>/resources/…`);
- when the layout does not exist, it returns a minimal HTML (`<!-- pagina#titulo -->`, `<!-- pagina#css -->`, `<!-- pagina#js -->` and `@[[pagina#corpo]]@`).

> [!WARNING]
> `gestor_layout()` puts `id`/`id_layouts` into SQL **unescaped** and **without filtering `status`**. Only pass values coming from the database or from code, never from the request.

## Page resources (CSS and `<head>`)

`gestor_pagina_recursos_incluir(['css' => …, 'css_precompiled' => …, 'css_precompiled_role' => …, 'css_compiled' => …, 'html_extra_head' => …])` is the **only** right way for widgets and components to bring CSS into `<head>`. Each fragment is included only once, deduplicated by MD5 in `$_GESTOR['recursos-incluidos-hashes']`, and tagged in the DOM:
- `css` becomes `<style data-c2f-css-role="authored">`;
- `css_compiled` becomes `data-c2f-css-role="compiled"`;
- `css_precompiled` becomes `<style data-tailwind-role="…">`, with the role `layout-precompiled`, `dependency-precompiled`, `page-precompiled` or `resource-precompiled` (the default).

`gestor_css_precompiled_ordenar($styles)` orders the precompiled `<style>` blocks by role: layout, dependencies, page, resources and the rest. Tailwind v4 cascade layers are defined on first appearance, and the layout must declare `theme, base, components, utilities` before everything else. With `$_GESTOR['tailwind-page-bundle']` (canonical per-page bundle), only the page's blocks and those without a role are kept. Discarded `resource-precompiled` blocks produce a single `log_disco` entry in the `tailwind` category.

### CSS audit and provenance

Used by the `css:audit` and `css:rebuild` commands and by the CRUDs that store resources:

- `gestor_css_classes_usadas($html)` lists the markup's classes, ignoring template markers (`[`, `{`, `@`). That includes Tailwind arbitrary variants such as `bg-[rgb(…)]`.
- `gestor_css_classes_definidas($css)` lists the classes a stylesheet defines, unescaping `md\:flex`.
- `gestor_css_classes_descobertas($html, $css)` lists the used-but-undefined ones, except the `group` and `peer` markers.
- `gestor_css_classes_em_codigo($codigo)` finds classes built in PHP/JS (`class="…"`, `classList.add/remove/toggle`, `className =`): the debt that forces declaring `tailwind_sources`.
- `gestor_css_procedencia_assinatura(['html','css','baseline','compilador'])` produces `v2:<sha1>`: the stamp of **which input** the derived CSS was generated from. The `baseline` is the layout's CSS and the `compilador` is the Tailwind version.
- `gestor_css_compilador_versao()` reads that version from the `assets-externos.php` registry (`tailwindcss-browser`).
- `gestor_css_procedencia_para_recurso($html, $css, $layout_id, $tabela)` returns the signature ready to store in `css_source_hash`, or `''` when the column does not exist yet.
- `gestor_css_procedencia_valida($stored, $params)` compares. A missing signature counts as **invalid**, on purpose.

## Text variables (i18n)

Interface texts live in the `variaveis` table, per `language` and `modulo`.

- `gestor_variaveis(['modulo' => …, 'id' => …])` returns the text in the current language. Without `modulo`, it uses `_global_` (`modulo IS NULL`). It loads **the whole module** on the first call and caches it in `$_GESTOR['variaveis'][$modulo]`. Missing: `''`. With `conjunto => true` it returns every variable of the module; `padrao` filters the ids by a substring, case-insensitively, and `reset` reloads from the database.
- `gestor_variaveis_globais(['id' => …])` does the single lookup by id and returns `null` when not found.
- `gestor_variaveis_alterar(['modulo', 'id', 'tipo', 'valor', 'linguagem'])` updates the value. With `tipo = 'bool'`, it stores `1`/`NULL`.

> [!WARNING]
> `gestor_variaveis_globais()` **does not filter by module**: it returns the first variable with that id in any module and caches it as global. `gestor_variaveis_alterar()` puts `valor` into SQL **unescaped**, so an apostrophe breaks the query. `gestor_variaveis()` does not escape `modulo` either. None of the three updates the others' cache.

`gestor_pagina_variaveis_globais(['html' => …])` resolves the `@[[…]]@` markers of an HTML, in this order:
1. the current module's variables (`$_GESTOR['modulo-id']`);
2. the extra modules' variables (`$_GESTOR['paginas-variaveis']`);
3. the system markers `pagina#url-raiz`, `pagina#url-full-http`, `pagina#titulo`, `pagina#contato-url` and `pagina#url-caminho`.

Whatever is not resolved stays in the HTML.

## Routes, redirection and query string

- `gestor_redirecionar($local = false, $queryString = '', $externo = false)` sends `Location` and **ends with `exit`**.
  - An internal `$local` is prefixed with `$_GESTOR['url-raiz']`.
  - Without `$local`, it uses the `redirecionar-local` session variable (and deletes it) or the root.
  - The alert in `$_GESTOR['pagina-alerta']` is stored in the session for the next page.
- `gestor_redirecionar_montar_url($local, $queryString)` joins destination and query with `?` or `&` depending on the destination (req-173).
- `gestor_redirecionar_raiz()` goes to the current module's page marked as `raiz` (or to `/`).
- `gestor_reload_url()` reloads the current path.
- `gestor_querystring($remove = '')` returns the current query without the internal `_gestor-caminho` parameter (and without `$remove`).
- `gestor_querystring_variavel($qs, $name)` reads one parameter.
- `gestor_querystring_remover_variavel($qs, $name)` removes one parameter. Values come back **decoded**, without `urlencode`.
- `gestor_querystring_before_submit($field, $default)` reads the query the form stored in a hidden field (`_c2f_query_string_before_submit`), already escaped for SQL.
- `gestor_roteador_erro_terminal($codigo, $caminho)` tells whether the 404 is already on the `404/` route, to avoid a 404 → 404 loop.
- `gestor_roteador_pagina_status_http($caminho)` returns 404 for that page.
- `gestor_pagina_301_registrar($id_paginas, $caminho)` records in `paginas_301` that the old path belongs to the page, deduplicating by the (path, page) pair.
- `gestor_pagina_rota_sistema($caminho)` recognizes the routes that are not content: `cookies-is-mandatory`, `_gestor-cookie-verify`, `404`, `403`, `500` and `503`. Tracking scripts are not added to them.

## Robots, OpenGraph and SEO

- `gestor_crawler_detectar($userAgent, $tokensExtra = null)` compares the lowercased User-Agent, by substring, with:
  - `gestor_crawler_tokens_padrao()`: WhatsApp, Meta, Googlebot, Bing, Lighthouse, Ahrefs, uptime monitors…;
  - `gestor_crawler_tokens_extra()`: the list from *Environment → Site Settings*, which only applies with `crawler-tokens-extra-ativo`.
- `gestor_crawler_tokens_normalizar($raw)` turns text separated by commas, semicolons or line breaks into a list without repetition.
- `gestor_cookie_verificacao_desfecho(['crawler','tem_cookie','exigir_sessao','caminho'])` decides between `ignorar`, `emitir` and `redirecionar` in the cookie check. On a system route it **never redirects**, which avoids the "cookies are mandatory" loop.
- `gestor_open_graph_tags([...])` builds `og:title`, `og:description`, `og:image`, `og:url`, `og:site_name` and `og:type`, plus `twitter:card`. It never emits an empty tag.
- `gestor_open_graph_existe($html)` detects whether the HTML already carries its own; in that case the core does not inject its set.
- `gestor_pagina_og_do_registro($pagina)` extracts the filled `og_titulo`, `og_descricao`, `imagem_destaque`, `meta_descricao` and `meta_keywords` from the page record.
- `gestor_meta_seo_tags(['description','keywords'])` generates the classic meta tags; `gestor_meta_seo_existe($html)` avoids duplicating them; `gestor_meta_keywords_normalizar($raw)` cleans up keywords (no repetition, case preserved).
- `gestor_pdf_viewer_detectar($html)` checks whether the page has the PDF.js viewer (`conn2flow-pdfjs` class), and `gestor_pdf_viewer_assets($urlRoot, $version)` returns the `<script>` tags (PDF.js 3.11.174 from cdnjs and `interface/pdf-viewer.js`).

## Restricted site access (req-163)

With `SITE_RESTRICTED_ACCESS=true` in `.env`, only logged-in users can see the site:
- `gestor_site_acesso_restrito_ativo()` reads the key from `$_CONFIG` and falls back to `$_ENV`/`getenv()`;
- `gestor_site_acesso_restrito_perfis()` returns the `id_usuarios_perfis` allowed in `SITE_RESTRICTED_PROFILES` (positive integers only);
- `gestor_site_acesso_restrito_perfil_autorizado($profile, $list)` lets any logged-in user in when the list is empty; anonymous users never;
- `gestor_site_acesso_restrito_rota_isenta($caminho)` exempts the identity routes (login, sign-up, password recovery, OAuth), `_api`, `api`, `_gateways` and the system routes.

## CSRF and menu icons

- `gestor_csrf_rotas_identidade()` lists the screens with a single-use token (`signin`, `signin-2fa`, `signup`, `forgot-password`, `reset-password`, `validate-user`).
- `gestor_csrf_destino_recarregamento($path, $referer, $urlRoot)` picks which of those screens the CSRF error page should reload, using the current path first and then the referer.
- `gestor_pagina_menu_icone_lucide_valido($name)` only accepts kebab-case names, the only ones Lucide resolves.
- `gestor_pagina_menu_icone_lucide_atributo($name)` returns `data-lucide="…"`, or nothing for an invalid name, which avoids the `icon name was not found` warning.

## Session

The Gestor session lives **in the database**: the `sessoes` and `sessoes_variaveis` tables. It does not use `$_SESSION`.

- `gestor_sessao_iniciar()` creates the `$_CONFIG['session-authname']` cookie with 32 random bytes (`seguranca_token_aleatorio()`), `HttpOnly`, `SameSite=Lax`, domain `SERVER_NAME` and lifetime `session-lifetime` (10800 s by default).
- `gestor_cookie_is_secure()` decides `Secure`: always on HTTPS (including behind a proxy, via `X-Forwarded-Proto`) and always in production; the cookie only goes without `Secure` on HTTP **and** with `development-env`.
- `gestor_sessao_id()` returns the numeric session id, creating the row if needed, and updates `acesso` once per request. In **1 out of every 51 requests** it deletes the sessions (and variables) with no access for longer than `session-lifetime`.
- `gestor_sessao_variavel($name, $value = null)` stores (JSON) or reads a value. Missing: `''`. There is no way to store `null`: it means a read.
- `gestor_sessao_variavel_del($name)` deletes one variable; `gestor_sessao_del()` deletes the current session and expires the cookie.
- `gestor_sessao_del_all()` deletes **every session of every user**.

> [!NOTE]
> The cookie expires `session-lifetime` seconds after it is **created** and is not renewed on each access. That is why the CSRF token (a session variable) can expire in an open tab; `global.js` renews it on its own (req-175).

## Sanitization of the served HTML (req-132)

In production, the delivered HTML goes out without comments and without indentation.
- `gestor_pagina_higienizar_ativo()` reads `HTML_SANITIZE`:
  - `auto` (the default) is on in production and off in development;
  - `on` is always on;
  - `off` is always off;
  - an unknown value becomes `auto`.
  - With the live editing bar (`gestor_dashboard_toolbar_ativo()`), it never sanitizes.
- `gestor_html_higienizar($html)` keeps `<pre>`, `<textarea>`, `<script>` and conditional comments; removes HTML comments and CSS comments/indentation inside `<style>`; replaces indentation with a line break, because whitespace between inline elements is rendered.
- `gestor_js_higienizar($js)` is a *scanner* (not a regex) that respects strings, template literals and regex literals, and keeps line breaks because of ASI. `gestor_js_barra_inicia_regex($previous)` decides whether a `/` opens a regex or is a division.
- `gestor_pagina_higienizar_js_ativo()` is the dedicated `HTML_SANITIZE_JS` key to turn off only the JavaScript part.
- `gestor_html_script_e_javascript($tag)` only accepts inline JavaScript `<script>` tags. It ignores `src`, `application/json`, `text/template` and other types used as data storage.

## See also

- [Gestor libraries](index.md), [modelo.php library](modelo.md), [banco.php library](banco.md)

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/gestor.php` by `c2f docs:extract` — 76 functions. Do not edit inside this block.

- `existe(mixed $dado = false): bool` — [line 43](../../../../../gestor/bibliotecas/gestor.php#L43)
  Verifica se um dado existe e não está vazio.
  Parameters:
  - `$dado`: Dado a ser verificado.
  Returns: True se existe e não está vazio, false caso contrário.
- `gestor_asset_version($owner = null, $fallback = null)` — [line 69](../../../../../gestor/bibliotecas/gestor.php#L69)
  Resolve token de cache de um diretório de assets, com fallback semântico.
- `gestor_modulo_asset_version($modulo)` — [line 80](../../../../../gestor/bibliotecas/gestor.php#L80)
  Resolve token de cache do módulo sem alterar sua versão semântica.
- `gestor_framework_css_resolver(string|null $layoutFramework = null, string|null $paginaFramework = null): array{fomantic:bool,tailwind:bool,modo:string}` — [line 112](../../../../../gestor/bibliotecas/gestor.php#L112)
  Resolve o framework CSS efetivo de uma requisição a partir do layout E da página (req-118).
  Parameters:
  - `$layoutFramework`: Valor de `layouts.framework_css`.
  - `$paginaFramework`: Valor de `paginas.framework_css`.
  Returns: `modo` é `fomantic-ui`, `tailwindcss` ou
- `gestor_framework_css_atual(): array{fomantic:bool,tailwind:bool,modo:string}` — [line 139](../../../../../gestor/bibliotecas/gestor.php#L139)
  Atalho para a resolução de framework da requisição corrente.
- `gestor_schema_tabela_existe(string $tabela): bool` — [line 175](../../../../../gestor/bibliotecas/gestor.php#L175)
  Diz se uma tabela existe no banco corrente.
  Parameters:
  - `$tabela`: Nome da tabela.
  Returns: False também quando o banco não pôde ser consultado (falha fechado: sem certeza de
- `gestor_schema_campo_existe(string $campo, string $tabela): bool` — [line 211](../../../../../gestor/bibliotecas/gestor.php#L211)
  Diz se um campo existe em uma tabela do banco corrente.
  Parameters:
  - `$campo`: Nome da coluna.
  - `$tabela`: Nome da tabela.
- `gestor_css_precompiled_ordenar($styles)` — [line 247](../../../../../gestor/bibliotecas/gestor.php#L247)
  Ordena sidecars Tailwind pela responsabilidade na cascata.
- `gestor_pagina_recursos_incluir(array $params = false)` — [line 315](../../../../../gestor/bibliotecas/gestor.php#L315)
  Inclui recursos de página (CSS, CSS compilado e HTML extra head) no pipeline global. Controla duplicidades via hash MD5 para evitar inclusão redundante quando múltiplos blocos do mesmo widget/componente são inseridos na mesma página (req-028 / DEC-041).
  Parameters:
  - `$params['css_precompiled_role']`: Papel opcional para diagnóstico no DOM.
- `gestor_crawler_detectar(string|null $userAgent = null, $tokensExtra = null): bool` — [line 399](../../../../../gestor/bibliotecas/gestor.php#L399)
  Detecta crawlers/scrapers sociais e de busca pelo User-Agent (req-109 / BATCH-109).
  Parameters:
  - `$userAgent`: User-Agent da requisição ($_SERVER['HTTP_USER_AGENT']).
- `gestor_crawler_tokens_padrao(): array` — [line 430](../../../../../gestor/bibliotecas/gestor.php#L430)
  Lista embutida de tokens de robô (req-109, ampliada no req-111 / CR-001).
- `gestor_crawler_tokens_extra(): array` — [line 504](../../../../../gestor/bibliotecas/gestor.php#L504)
  Tokens adicionais definidos pelo operador em Ambiente → Configurações do Site (req-111 / CR-001).
- `gestor_crawler_tokens_normalizar(string $bruto): array` — [line 525](../../../../../gestor/bibliotecas/gestor.php#L525)
  Converte o texto livre da configuração numa lista de tokens.
- `gestor_pagina_rota_sistema(string $caminho = ''): bool` — [line 555](../../../../../gestor/bibliotecas/gestor.php#L555)
  Identifica páginas de sistema que NÃO devem receber scripts de rastreamento (req-109 / BATCH-109).
  Parameters:
  - `$caminho`: Caminho da página já normalizado (`$_GESTOR['caminho-total']` com barra final).
- `gestor_site_acesso_restrito_ativo(): bool` — [line 586](../../../../../gestor/bibliotecas/gestor.php#L586)
  Acesso Restrito ao Site está ligado? (req-163 / BATCH-168)
- `gestor_site_acesso_restrito_perfis(string|array|null $bruto = null): array` — [line 607](../../../../../gestor/bibliotecas/gestor.php#L607)
  Perfis (`id_usuarios_perfis`) autorizados pelo Acesso Restrito ao Site (req-163).
  Returns: Lista de ids como string.
- `gestor_site_acesso_restrito_rota_isenta(string $caminho = ''): bool` — [line 645](../../../../../gestor/bibliotecas/gestor.php#L645)
  Rotas que o Acesso Restrito ao Site NUNCA bloqueia (req-163).
  Parameters:
  - `$caminho`: Caminho da requisição (`$_GESTOR['caminho-total']`).
- `gestor_site_acesso_restrito_perfil_autorizado(string|int $perfilId, array $perfisAutorizados): bool` — [line 698](../../../../../gestor/bibliotecas/gestor.php#L698)
  Decide se um perfil entra no site restrito (req-163).
  Parameters:
  - `$perfilId`: `id_usuarios_perfis` do usuário autenticado.
  - `$perfisAutorizados`: Saída de `gestor_site_acesso_restrito_perfis()`.
- `gestor_roteador_erro_terminal(int|string $codigo, mixed $caminho = ''): bool` — [line 716](../../../../../gestor/bibliotecas/gestor.php#L716)
  Detecta o fallback terminal da rota de erro para impedir redirecionamento 404 -> 404.
  Parameters:
  - `$codigo`: Codigo HTTP em processamento.
  - `$caminho`: Caminho atual recebido pelo roteador.
- `gestor_roteador_pagina_status_http(mixed $caminho = ''): int|null` — [line 728](../../../../../gestor/bibliotecas/gestor.php#L728)
  Retorna o status HTTP que deve ser preservado ao renderizar uma pagina de erro existente.
  Parameters:
  - `$caminho`: Caminho da pagina encontrada pelo roteador.
  Returns: Status HTTP explicito ou null para paginas comuns.
- `gestor_pagina_301_registrar(int|string $id_paginas, string $caminho): bool` — [line 754](../../../../../gestor/bibliotecas/gestor.php#L754)
  Registra um redirecionamento 301 de um caminho liberado por uma página (F10 do review 2026-08-15).
  Parameters:
  - `$id_paginas`: Id NUMÉRICO da página que liberou o caminho.
  - `$caminho`: Caminho antigo.
  Returns: True quando uma linha foi inserida.
- `gestor_open_graph_tags(array $params = false): array` — [line 801](../../../../../gestor/bibliotecas/gestor.php#L801)
  Monta as metatags OpenGraph do `<head>` (req-109 / BATCH-109).
  Parameters:
  - `$params['title']`: Título da página.
  - `$params['description']`: Descrição/resumo.
  - `$params['image']`: URL absoluta da imagem de compartilhamento.
  - `$params['url']`: URL canônica da página.
  - `$params['site_name']`: Nome do site.
  - `$params['type']`: Tipo OpenGraph (padrão `website`).
  - `$params['twitter']`: Emite também o par mínimo de Twitter Cards (padrão true).
  Returns: Lista de tags `<meta …>`.
- `gestor_cookie_verificacao_desfecho(array $params = false): string` — [line 860](../../../../../gestor/bibliotecas/gestor.php#L860)
  Decide o desfecho da verificação de cookie do navegador (req-111 / CR-001).
  Parameters:
  - `$params['crawler']`: Requisição identificada como robô.
  - `$params['tem_cookie']`: Já existe cookie de verificação ou de autenticação.
  - `$params['exigir_sessao']`: Fluxo que precisa PROVAR o cookie (login/cadastro).
  - `$params['caminho']`: Caminho da requisição corrente.
- `gestor_pagina_og_do_registro(array $pagina = Array()): array` — [line 885](../../../../../gestor/bibliotecas/gestor.php#L885)
  Extrai os metadados OpenGraph gravados no registro da página (req-110 / BATCH-110).
  Parameters:
  - `$pagina`: Linha da tabela `paginas`.
- `gestor_meta_seo_tags(array $params = false): array` — [line 927](../../../../../gestor/bibliotecas/gestor.php#L927)
  Monta as meta tags clássicas de SEO do `<head>` (req-112 / BATCH-112).
  Parameters:
  - `$params['keywords']`: Lista separada por vírgula.
  Returns: Lista de tags `<meta …>`.
- `gestor_meta_keywords_normalizar(string|array $bruto): string` — [line 955](../../../../../gestor/bibliotecas/gestor.php#L955)
  Normaliza a lista de palavras-chave digitada pelo usuário (req-112 / BATCH-112).
- `gestor_meta_seo_existe(string|array $html): bool` — [line 988](../../../../../gestor/bibliotecas/gestor.php#L988)
  Detecta se um HTML já traz metatags de descrição/keywords próprias (req-112 / BATCH-112).
- `gestor_open_graph_existe(string|array $html): bool` — [line 1006](../../../../../gestor/bibliotecas/gestor.php#L1006)
  Detecta se um HTML já traz metatags OpenGraph próprias (req-109 / BATCH-109).
  Parameters:
  - `$html`: HTML (ou lista de trechos) já enfileirado para o `<head>`.
- `gestor_pdf_viewer_detectar(string $html): bool` — [line 1025](../../../../../gestor/bibliotecas/gestor.php#L1025)
  Detecta se um HTML de página usa o motor de exibição PDF.js (req-096 / BATCH-096).
  Parameters:
  - `$html`: HTML da página já montada.
- `gestor_pdf_viewer_assets(string $urlRaiz = '', string $versao = ''): array` — [line 1054](../../../../../gestor/bibliotecas/gestor.php#L1054)
  Tags de inclusão dos assets do motor PDF.js (req-096 / BATCH-096).
  Parameters:
  - `$urlRaiz`: Raiz pública do projeto ($_GESTOR['url-raiz']).
  - `$versao`: Versão do sistema, usada para cache-bust.
  Returns: Lista de tags <script>.
- `gestor_css_classes_usadas(string $html): array` — [line 1073](../../../../../gestor/bibliotecas/gestor.php#L1073)
  Classes efetivamente usadas no markup (BATCH-144 / req-141).
  Parameters:
  - `$html`: HTML da página ou do recurso.
  Returns: Lista de classes distintas, sem repetição.
- `gestor_css_classes_definidas(string $css): array` — [line 1103](../../../../../gestor/bibliotecas/gestor.php#L1103)
  Classes definidas por uma folha de estilo (BATCH-144 / req-141).
  Parameters:
  - `$css`: CSS concatenado das folhas entregues.
  Returns: Lista de classes distintas definidas.
- `gestor_css_classes_descobertas(string $html, string $css): array` — [line 1130](../../../../../gestor/bibliotecas/gestor.php#L1130)
  Classes que o HTML usa e nenhuma folha define (BATCH-144 / req-141).
  Parameters:
  - `$html`: HTML entregue.
  - `$css`: CSS entregue.
  Returns: Classes sem definição, em ordem estável.
- `gestor_css_classes_em_codigo(string $codigo): array` — [line 1170](../../../../../gestor/bibliotecas/gestor.php#L1170)
  Classes de estilo embutidas em código PHP/JS (BATCH-144 / req-141).
  Parameters:
  - `$codigo`: Conteúdo de um arquivo PHP ou JS.
  Returns: Classes distintas encontradas, em ordem estável.
- `gestor_css_procedencia_assinatura(array $params = false): string` — [line 1249](../../../../../gestor/bibliotecas/gestor.php#L1249)
  Assinatura de procedência do CSS derivado (BATCH-144 / req-141 / CR-002).
  Parameters:
  - `$params['html']`: HTML autoral do recurso.
  - `$params['css']`: CSS autoral do recurso.
  - `$params['baseline']`: Cascata sob a qual o derivado foi gerado (CSS do layout).
  - `$params['compilador']`: Versão do Tailwind que gerou o derivado.
  Returns: Assinatura versionada, ou string vazia quando não há autoria nenhuma.
- `gestor_css_compilador_versao(): string` — [line 1288](../../../../../gestor/bibliotecas/gestor.php#L1288)
  Versão do compilador Tailwind vigente, para a assinatura de procedência (req-156).
  Returns: Versão, ou string vazia quando o registro não está disponível.
- `gestor_css_procedencia_para_recurso(string $html, string $css, string $layout_id = '', string $tabela = 'paginas'): string` — [line 1323](../../../../../gestor/bibliotecas/gestor.php#L1323)
  Par `campo=valor` da procedência, pronto para entrar num INSERT/UPDATE de recurso (req-141).
  Parameters:
  - `$html`: HTML autoral que está sendo gravado.
  - `$css`: CSS autoral que está sendo gravado.
  - `$layout_id`: Layout do recurso; vazio para layouts (eles SÃO a base).
  - `$tabela`: Tabela de destino, para checar a coluna.
  Returns: Assinatura, ou string vazia.
- `gestor_css_procedencia_valida(string $assinaturaGravada, array $params = false): bool` — [line 1370](../../../../../gestor/bibliotecas/gestor.php#L1370)
  O CSS derivado corresponde à autoria vigente? (BATCH-144 / req-141)
  Parameters:
  - `$assinaturaGravada`: Valor da coluna `css_source_hash`.
  - `$params`: Mesmas entradas de gestor_css_procedencia_assinatura().
  Returns: true quando o derivado corresponde à autoria.
- `gestor_componente_ids_condicao($ids, $escape = null)` — [line 1382](../../../../../gestor/bibliotecas/gestor.php#L1382)
  Monta a condição agrupada de IDs usada pela inclusão múltipla de componentes.
- `gestor_componente(array|false $params = false): string|array|false` — [line 1416](../../../../../gestor/bibliotecas/gestor.php#L1416)
  Renderiza um componente HTML/CSS dinâmico.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id']`: ID descritivo do componente (ou array de IDs).
  - `$params['id_componentes']`: ID numérico do componente (alternativa ao 'id').
  - `$params['modulo']`: Módulo específico (opcional).
  - `$params['return_css']`: Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
  - `$params['modulosExtra']`: Módulos extras para busca de variáveis.
  - `$params['linguagem']`: Código do idioma (padrão: idioma atual).
  Returns: HTML do componente ou array com HTML+CSS, ou false se não encontrado.
- `gestor_layout(array|false $params = false): string|array|false` — [line 1648](../../../../../gestor/bibliotecas/gestor.php#L1648)
  Renderiza um layout HTML/CSS completo da página.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id']`: ID descritivo do layout (ou array de IDs).
  - `$params['id_layouts']`: ID numérico do layout (alternativa ao 'id').
  - `$params['return_css']`: Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
  - `$params['modulosExtra']`: Módulos extras para busca de variáveis.
  Returns: HTML do layout ou array com HTML+CSS, ou false se não encontrado.
- `gestor_incluir_bibliotecas(): void` — [line 1910](../../../../../gestor/bibliotecas/gestor.php#L1910)
  Inclui todas as bibliotecas do sistema.
- `gestor_incluir_biblioteca(string $biblioteca): void` — [line 1937](../../../../../gestor/bibliotecas/gestor.php#L1937)
  Inclui uma biblioteca específica do sistema.
  Parameters:
  - `$biblioteca`: Nome do arquivo da biblioteca (sem .php).
- `gestor_variaveis(array|false $params = false): string|array` — [line 1994](../../../../../gestor/bibliotecas/gestor.php#L1994)
  Obtém variáveis do sistema por módulo e idioma.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo do sistema (padrão: '_global_').
  - `$params['id']`: Identificador único da variável (obrigatório se não usar 'conjunto').
  - `$params['conjunto']`: Se true, retorna todas as variáveis do módulo.
  - `$params['padrao']`: Filtro de padrão regex para IDs (requer 'conjunto').
  - `$params['reset']`: Se true, força releitura do banco de dados.
  Returns: Valor da variável, array de variáveis (se conjunto), ou string vazia.
- `gestor_variaveis_globais(array|false $params = false): string|null` — [line 2083](../../../../../gestor/bibliotecas/gestor.php#L2083)
  Obtém uma variável global específica do sistema.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id']`: Identificador único da variável (obrigatório).
  - `$params['reset']`: Se true, força releitura do banco de dados.
  Returns: Valor da variável ou NULL se não encontrada.
- `gestor_variaveis_alterar(array|false $params = false): void` — [line 2142](../../../../../gestor/bibliotecas/gestor.php#L2142)
  Altera o valor de uma variável no banco de dados.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo do sistema da variável (obrigatório).
  - `$params['id']`: Identificador único da variável (obrigatório).
  - `$params['tipo']`: Tipo da variável: 'bool' ou outros (obrigatório).
  - `$params['valor']`: Valor que deverá ser alterado.
  - `$params['linguagem']`: Código do idioma (padrão: idioma atual).
- `gestor_redirecionar_raiz(): void` — [line 2191](../../../../../gestor/bibliotecas/gestor.php#L2191)
  Redireciona para a página raiz do módulo atual.
- `gestor_reload_url(): void` — [line 2222](../../../../../gestor/bibliotecas/gestor.php#L2222)
  Recarrega a URL atual.
- `gestor_csrf_rotas_identidade(): array<int,string>` — [line 2243](../../../../../gestor/bibliotecas/gestor.php#L2243)
  Rotas públicas de identidade cujo formulário carrega um token CSRF de uso único.
  Returns: Primeiros segmentos de caminho, sem barras.
- `gestor_csrf_destino_recarregamento(string $caminhoTotal, string|null $referer, string $urlRaiz): string` — [line 2271](../../../../../gestor/bibliotecas/gestor.php#L2271)
  Destino de recarregamento limpo para a tela de erro de CSRF (req-125 / BATCH-127).
  Parameters:
  - `$caminhoTotal`: Caminho da requisição corrente, já sem o prefixo de idioma.
  - `$referer`: Conteúdo de `HTTP_REFERER`, se houver.
  - `$urlRaiz`: Raiz do gestor, com barra final e com idioma quando houver.
  Returns: URL absoluta de destino ou '' quando indeterminado.
- `gestor_pagina_menu_icone_lucide_valido(string $nome): bool` — [line 2330](../../../../../gestor/bibliotecas/gestor.php#L2330)
  Um nome é endereçável no catálogo do Lucide? (req-125 / BATCH-127)
  Parameters:
  - `$nome`: Valor já resolvido do ícone.
- `gestor_pagina_menu_icone_lucide_atributo(string $nome): string` — [line 2350](../../../../../gestor/bibliotecas/gestor.php#L2350)
  Atributo `data-lucide` pronto para interpolação — ou string vazia (req-125 / BATCH-127).
  Parameters:
  - `$nome`: Valor já resolvido do ícone.
  Returns: `data-lucide="…"` ou ''.
- `gestor_querystring_remover_variavel(string $queryString, string $removerVariavel = ''): string` — [line 2368](../../../../../gestor/bibliotecas/gestor.php#L2368)
  Remove uma variável específica da query string.
  Parameters:
  - `$queryString`: Query string completa (formato: var1=val1&var2=val2).
  - `$removerVariavel`: Nome da variável a ser removida.
  Returns: Query string processada sem a variável removida.
- `gestor_querystring_variavel(string $queryString, string $variavel = ''): string` — [line 2397](../../../../../gestor/bibliotecas/gestor.php#L2397)
  Obtém o valor de uma variável específica da query string.
  Parameters:
  - `$queryString`: Query string completa.
  - `$variavel`: Nome da variável a buscar.
  Returns: Valor da variável ou string vazia se não encontrada.
- `gestor_querystring_before_submit(string $fieldName = '_c2f_query_string_before_submit', string $default = ''): string` — [line 2422](../../../../../gestor/bibliotecas/gestor.php#L2422)
  Recupera a query string antes do envio do formulário via campo hidden.
  Parameters:
  - `$fieldName`: Nome do campo hidden enviado pelo formulário.
  - `$default`: Valor padrão se o campo não estiver definido.
  Returns: Query string enviada no formulário, sem o "?" inicial.
- `gestor_querystring(string $removerVariavel = ''): string` — [line 2445](../../../../../gestor/bibliotecas/gestor.php#L2445)
  Obtém a query string atual da requisição.
  Parameters:
  - `$removerVariavel`: Nome da variável adicional a remover (opcional).
  Returns: Query string processada.
- `gestor_redirecionar(string|false $local = false, string $queryString = '', bool $externo = false): void` — [line 2477](../../../../../gestor/bibliotecas/gestor.php#L2477)
  Redireciona para um local específico.
  Parameters:
  - `$local`: Caminho de destino (false = usar sessão ou raiz).
  - `$queryString`: Query string adicional.
  - `$externo`: Se true, trata como URL externa (não adiciona url-raiz).
  Returns: (executa exit após redirecionar)
- `gestor_redirecionar_montar_url(string $local, string $queryString = ''): string` — [line 2517](../../../../../gestor/bibliotecas/gestor.php#L2517)
  Monta a URL de destino de um redirecionamento (req-173).
  Parameters:
  - `$local`: Destino já resolvido (interno ou externo).
  - `$queryString`: Query string a anexar, com ou sem `?`/`&` à frente.
  Returns: URL final; sem query string, o destino sai intacto (nenhum `?` órfão).
- `gestor_pagina_variaveis_globais(array|false $params = false): string` — [line 2542](../../../../../gestor/bibliotecas/gestor.php#L2542)
  Substitui variáveis globais em HTML.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['html']`: HTML que será processado (obrigatório).
  Returns: HTML com variáveis substituídas.
- `gestor_js_variavel_incluir(string $variavel, mixed $valor): void` — [line 2637](../../../../../gestor/bibliotecas/gestor.php#L2637)
  Inclui uma variável JavaScript global na página.
  Parameters:
  - `$variavel`: Nome da variável JavaScript.
  - `$valor`: Valor da variável (será convertido para JSON).
- `gestor_componentes_incluir(array|false $params = false): void` — [line 2667](../../../../../gestor/bibliotecas/gestor.php#L2667)
  Marca componentes para inclusão na página.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id']`: ID do componente individual.
  - `$params['componentes']`: Array de IDs de componentes.
- `gestor_componentes_incluir_pagina(array|false $params = false): void` — [line 2708](../../../../../gestor/bibliotecas/gestor.php#L2708)
  Renderiza componentes marcados na página.
  Parameters:
  - `$params`: Parâmetros da função (atualmente não utilizados).
- `gestor_cookie_is_secure(): bool` — [line 2760](../../../../../gestor/bibliotecas/gestor.php#L2760)
  Determina se cookies devem ser emitidos com o atributo Secure.
- `gestor_sessao_iniciar()` — [line 2782](../../../../../gestor/bibliotecas/gestor.php#L2782)
- `gestor_sessao_id(): int` — [line 2816](../../../../../gestor/bibliotecas/gestor.php#L2816)
  Obtém ou cria o ID numérico da sessão no banco de dados.
  Returns: ID numérico da sessão no banco de dados.
- `gestor_sessao_del(): void` — [line 2892](../../../../../gestor/bibliotecas/gestor.php#L2892)
  Deleta a sessão atual do usuário.
- `gestor_sessao_variavel(string $variavel, mixed $valor = NULL): mixed` — [line 2943](../../../../../gestor/bibliotecas/gestor.php#L2943)
  Obtém ou define uma variável de sessão.
  Parameters:
  - `$variavel`: Nome da variável de sessão.
  - `$valor`: Valor a ser armazenado (NULL para apenas leitura).
  Returns: Valor da variável (em modo leitura) ou void (em modo escrita).
- `gestor_sessao_variavel_del(string $variavel): void` — [line 3011](../../../../../gestor/bibliotecas/gestor.php#L3011)
  Remove uma variável específica da sessão.
  Parameters:
  - `$variavel`: Nome da variável a ser removida.
- `gestor_sessao_del_all(): void` — [line 3044](../../../../../gestor/bibliotecas/gestor.php#L3044)
  Remove TODAS as sessões do sistema.
- `gestor_modulos_dados(string $modulo_id = ''): array|null` — [line 3067](../../../../../gestor/bibliotecas/gestor.php#L3067)
  Pega os dados de um módulo.
  Parameters:
  - `$modulo_id`: ID do módulo.
  Returns: Dados do módulo ou null se não encontrado.
- `gestor_pagina_higienizar_ativo()` — [line 3100](../../../../../gestor/bibliotecas/gestor.php#L3100)
  Decide se o HTML entregue ao navegador deve sair higienizado (req-132).
- `gestor_html_higienizar($html)` — [line 3139](../../../../../gestor/bibliotecas/gestor.php#L3139)
  Remove do HTML o que so interessa a quem escreveu o codigo (req-132).
- `gestor_js_higienizar($js)` — [line 3245](../../../../../gestor/bibliotecas/gestor.php#L3245)
  Remove comentarios e indentacao de JavaScript (req-132, 2a rodada).
- `gestor_js_barra_inicia_regex($anterior)` — [line 3350](../../../../../gestor/bibliotecas/gestor.php#L3350)
  Decide se uma `/` abre um regex literal ou e o operador de divisao (req-132).
- `gestor_pagina_higienizar_js_ativo()` — [line 3368](../../../../../gestor/bibliotecas/gestor.php#L3368)
  Gate proprio para a limpeza do JavaScript (req-132, 2a rodada).
- `gestor_html_script_e_javascript($tagCompleta)` — [line 3389](../../../../../gestor/bibliotecas/gestor.php#L3389)
  Diz se a tag `<script>` carrega JavaScript de verdade (req-132, 2a rodada).

<!-- c2f:extract:end -->
