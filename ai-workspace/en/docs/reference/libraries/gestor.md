---
title: "gestor.php library"
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
- `gestor_asset_version($owner = null, $fallback = null)` — [line 69](../../../../../gestor/bibliotecas/gestor.php#L69)
- `gestor_modulo_asset_version($modulo)` — [line 80](../../../../../gestor/bibliotecas/gestor.php#L80)
- `gestor_framework_css_resolver(string|null $layoutFramework = null, string|null $paginaFramework = null): array{fomantic:bool,tailwind:bool,modo:string}` — [line 112](../../../../../gestor/bibliotecas/gestor.php#L112)
- `gestor_framework_css_atual(): array{fomantic:bool,tailwind:bool,modo:string}` — [line 139](../../../../../gestor/bibliotecas/gestor.php#L139)
- `gestor_schema_tabela_existe(string $tabela): bool` — [line 175](../../../../../gestor/bibliotecas/gestor.php#L175)
- `gestor_schema_campo_existe(string $campo, string $tabela): bool` — [line 211](../../../../../gestor/bibliotecas/gestor.php#L211)
- `gestor_css_precompiled_ordenar($styles)` — [line 247](../../../../../gestor/bibliotecas/gestor.php#L247)
- `gestor_pagina_recursos_incluir(array $params = false)` — [line 315](../../../../../gestor/bibliotecas/gestor.php#L315)
- `gestor_crawler_detectar(string|null $userAgent = null, $tokensExtra = null): bool` — [line 399](../../../../../gestor/bibliotecas/gestor.php#L399)
- `gestor_crawler_tokens_padrao(): array` — [line 430](../../../../../gestor/bibliotecas/gestor.php#L430)
- `gestor_crawler_tokens_extra(): array` — [line 504](../../../../../gestor/bibliotecas/gestor.php#L504)
- `gestor_crawler_tokens_normalizar(string $bruto): array` — [line 525](../../../../../gestor/bibliotecas/gestor.php#L525)
- `gestor_pagina_rota_sistema(string $caminho = ''): bool` — [line 555](../../../../../gestor/bibliotecas/gestor.php#L555)
- `gestor_site_acesso_restrito_ativo(): bool` — [line 586](../../../../../gestor/bibliotecas/gestor.php#L586)
- `gestor_site_acesso_restrito_perfis(string|array|null $bruto = null): array` — [line 607](../../../../../gestor/bibliotecas/gestor.php#L607)
- `gestor_site_acesso_restrito_rota_isenta(string $caminho = ''): bool` — [line 645](../../../../../gestor/bibliotecas/gestor.php#L645)
- `gestor_site_acesso_restrito_perfil_autorizado(string|int $perfilId, array $perfisAutorizados): bool` — [line 698](../../../../../gestor/bibliotecas/gestor.php#L698)
- `gestor_roteador_erro_terminal(int|string $codigo, mixed $caminho = ''): bool` — [line 716](../../../../../gestor/bibliotecas/gestor.php#L716)
- `gestor_roteador_pagina_status_http(mixed $caminho = ''): int|null` — [line 728](../../../../../gestor/bibliotecas/gestor.php#L728)
- `gestor_pagina_301_registrar(int|string $id_paginas, string $caminho): bool` — [line 754](../../../../../gestor/bibliotecas/gestor.php#L754)
- `gestor_open_graph_tags(array $params = false): array` — [line 801](../../../../../gestor/bibliotecas/gestor.php#L801)
- `gestor_cookie_verificacao_desfecho(array $params = false): string` — [line 860](../../../../../gestor/bibliotecas/gestor.php#L860)
- `gestor_pagina_og_do_registro(array $pagina = Array()): array` — [line 885](../../../../../gestor/bibliotecas/gestor.php#L885)
- `gestor_meta_seo_tags(array $params = false): array` — [line 927](../../../../../gestor/bibliotecas/gestor.php#L927)
- `gestor_meta_keywords_normalizar(string|array $bruto): string` — [line 955](../../../../../gestor/bibliotecas/gestor.php#L955)
- `gestor_meta_seo_existe(string|array $html): bool` — [line 988](../../../../../gestor/bibliotecas/gestor.php#L988)
- `gestor_open_graph_existe(string|array $html): bool` — [line 1006](../../../../../gestor/bibliotecas/gestor.php#L1006)
- `gestor_pdf_viewer_detectar(string $html): bool` — [line 1025](../../../../../gestor/bibliotecas/gestor.php#L1025)
- `gestor_pdf_viewer_assets(string $urlRaiz = '', string $versao = ''): array` — [line 1054](../../../../../gestor/bibliotecas/gestor.php#L1054)
- `gestor_css_classes_usadas(string $html): array` — [line 1073](../../../../../gestor/bibliotecas/gestor.php#L1073)
- `gestor_css_classes_definidas(string $css): array` — [line 1103](../../../../../gestor/bibliotecas/gestor.php#L1103)
- `gestor_css_classes_descobertas(string $html, string $css): array` — [line 1130](../../../../../gestor/bibliotecas/gestor.php#L1130)
- `gestor_css_classes_em_codigo(string $codigo): array` — [line 1170](../../../../../gestor/bibliotecas/gestor.php#L1170)
- `gestor_css_procedencia_assinatura(array $params = false): string` — [line 1249](../../../../../gestor/bibliotecas/gestor.php#L1249)
- `gestor_css_compilador_versao(): string` — [line 1288](../../../../../gestor/bibliotecas/gestor.php#L1288)
- `gestor_css_procedencia_para_recurso(string $html, string $css, string $layout_id = '', string $tabela = 'paginas'): string` — [line 1323](../../../../../gestor/bibliotecas/gestor.php#L1323)
- `gestor_css_procedencia_valida(string $assinaturaGravada, array $params = false): bool` — [line 1370](../../../../../gestor/bibliotecas/gestor.php#L1370)
- `gestor_componente_ids_condicao($ids, $escape = null)` — [line 1382](../../../../../gestor/bibliotecas/gestor.php#L1382)
- `gestor_componente(array|false $params = false): string|array|false` — [line 1416](../../../../../gestor/bibliotecas/gestor.php#L1416)
- `gestor_layout(array|false $params = false): string|array|false` — [line 1648](../../../../../gestor/bibliotecas/gestor.php#L1648)
- `gestor_incluir_bibliotecas(): void` — [line 1910](../../../../../gestor/bibliotecas/gestor.php#L1910)
- `gestor_incluir_biblioteca(string $biblioteca): void` — [line 1937](../../../../../gestor/bibliotecas/gestor.php#L1937)
- `gestor_variaveis(array|false $params = false): string|array` — [line 1994](../../../../../gestor/bibliotecas/gestor.php#L1994)
- `gestor_variaveis_globais(array|false $params = false): string|null` — [line 2083](../../../../../gestor/bibliotecas/gestor.php#L2083)
- `gestor_variaveis_alterar(array|false $params = false): void` — [line 2142](../../../../../gestor/bibliotecas/gestor.php#L2142)
- `gestor_redirecionar_raiz(): void` — [line 2191](../../../../../gestor/bibliotecas/gestor.php#L2191)
- `gestor_reload_url(): void` — [line 2222](../../../../../gestor/bibliotecas/gestor.php#L2222)
- `gestor_csrf_rotas_identidade(): array<int,string>` — [line 2243](../../../../../gestor/bibliotecas/gestor.php#L2243)
- `gestor_csrf_destino_recarregamento(string $caminhoTotal, string|null $referer, string $urlRaiz): string` — [line 2271](../../../../../gestor/bibliotecas/gestor.php#L2271)
- `gestor_pagina_menu_icone_lucide_valido(string $nome): bool` — [line 2330](../../../../../gestor/bibliotecas/gestor.php#L2330)
- `gestor_pagina_menu_icone_lucide_atributo(string $nome): string` — [line 2350](../../../../../gestor/bibliotecas/gestor.php#L2350)
- `gestor_querystring_remover_variavel(string $queryString, string $removerVariavel = ''): string` — [line 2368](../../../../../gestor/bibliotecas/gestor.php#L2368)
- `gestor_querystring_variavel(string $queryString, string $variavel = ''): string` — [line 2397](../../../../../gestor/bibliotecas/gestor.php#L2397)
- `gestor_querystring_before_submit(string $fieldName = '_c2f_query_string_before_submit', string $default = ''): string` — [line 2422](../../../../../gestor/bibliotecas/gestor.php#L2422)
- `gestor_querystring(string $removerVariavel = ''): string` — [line 2445](../../../../../gestor/bibliotecas/gestor.php#L2445)
- `gestor_redirecionar(string|false $local = false, string $queryString = '', bool $externo = false): void` — [line 2477](../../../../../gestor/bibliotecas/gestor.php#L2477)
- `gestor_redirecionar_montar_url(string $local, string $queryString = ''): string` — [line 2517](../../../../../gestor/bibliotecas/gestor.php#L2517)
- `gestor_pagina_variaveis_globais(array|false $params = false): string` — [line 2542](../../../../../gestor/bibliotecas/gestor.php#L2542)
- `gestor_js_variavel_incluir(string $variavel, mixed $valor): void` — [line 2637](../../../../../gestor/bibliotecas/gestor.php#L2637)
- `gestor_componentes_incluir(array|false $params = false): void` — [line 2667](../../../../../gestor/bibliotecas/gestor.php#L2667)
- `gestor_componentes_incluir_pagina(array|false $params = false): void` — [line 2708](../../../../../gestor/bibliotecas/gestor.php#L2708)
- `gestor_cookie_is_secure(): bool` — [line 2760](../../../../../gestor/bibliotecas/gestor.php#L2760)
- `gestor_sessao_iniciar()` — [line 2782](../../../../../gestor/bibliotecas/gestor.php#L2782)
- `gestor_sessao_id(): int` — [line 2816](../../../../../gestor/bibliotecas/gestor.php#L2816)
- `gestor_sessao_del(): void` — [line 2892](../../../../../gestor/bibliotecas/gestor.php#L2892)
- `gestor_sessao_variavel(string $variavel, mixed $valor = NULL): mixed` — [line 2943](../../../../../gestor/bibliotecas/gestor.php#L2943)
- `gestor_sessao_variavel_del(string $variavel): void` — [line 3011](../../../../../gestor/bibliotecas/gestor.php#L3011)
- `gestor_sessao_del_all(): void` — [line 3044](../../../../../gestor/bibliotecas/gestor.php#L3044)
- `gestor_modulos_dados(string $modulo_id = ''): array|null` — [line 3067](../../../../../gestor/bibliotecas/gestor.php#L3067)
- `gestor_pagina_higienizar_ativo()` — [line 3100](../../../../../gestor/bibliotecas/gestor.php#L3100)
- `gestor_html_higienizar($html)` — [line 3139](../../../../../gestor/bibliotecas/gestor.php#L3139)
- `gestor_js_higienizar($js)` — [line 3245](../../../../../gestor/bibliotecas/gestor.php#L3245)
- `gestor_js_barra_inicia_regex($anterior)` — [line 3350](../../../../../gestor/bibliotecas/gestor.php#L3350)
- `gestor_pagina_higienizar_js_ativo()` — [line 3368](../../../../../gestor/bibliotecas/gestor.php#L3368)
- `gestor_html_script_e_javascript($tagCompleta)` — [line 3389](../../../../../gestor/bibliotecas/gestor.php#L3389)

<!-- c2f:extract:end -->
