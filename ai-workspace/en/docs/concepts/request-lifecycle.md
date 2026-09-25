---
title: "Request lifecycle"
description: "The path of a request in Conn2Flow: front controller, gestor.php, session and CSRF, page routing, permission, module execution, HTML assembly and response."
section: concepts
order: 10
sources:
  - gestor/gestor.php
  - gestor/config.php
  - gestor-instalador/public-access/index.php
  - gestor-instalador/public-access/.htaccess
  - gestor/bibliotecas/seguranca.php
verified_at: 82b0b6b8
---

# Request lifecycle

Every request to the site follows the same path. Understanding it explains almost everything about how Conn2Flow behaves: why a page comes from the database, when the module runs, when `@[[…]]@` variables are replaced and why a 404 becomes a redirect.

## 1. Front controller

The public folder has only two files, written by the installer:
- **`.htaccess`** (Apache) forces HTTPS (when chosen) and sends everything that is not a real file or folder to `index.php?_gestor-caminho=<path>`. On Nginx, the sample block generated at installation does the same.
- **`index.php`** sets `$_INDEX['sistemas-dir']` (the Gestor folder, outside the public folder) and includes `gestor.php`.

## 2. `gestor.php` and `config.php`

`gestor.php` includes `config.php`, which:
- reads the `.env` from `autenticacoes/<domain>/`;
- fills `$_GESTOR` (paths, root URL, languages, versions), `$_BANCO` and `$_CONFIG` (session, cookies, security, captcha, e-mail…);
- loads the basic libraries (`banco`, `gestor`, `modelo`, `hooks`).

Then it calls `gestor_start()`, the whole sequence:

```
gestor_start()
 ├─ gestor_cabecalhos_seguranca()   nosniff, Referrer-Policy, X-Frame-Options, HSTS (on HTTPS), CSP (if configured)
 ├─ gestor_config()                 path, language, static files, _api, _gateways
 ├─ gestor_sessao_iniciar()         session cookie
 ├─ seguranca_csrf_requisicao_validar()  → gestor_csrf_resposta_invalida() (403) on failure
 └─ gestor_roteador()               page, permission, module, assembly and output
```

## 3. `gestor_config()`: path and detours

- `_gestor-caminho` is validated by `gestor_caminho_publico_valido()` (no `..`, null byte or `\`, even after up to 3 rounds of decoding). An invalid path ends with **400**.
- **Language in the URL:** when the first segment is a language from `LANGUAGES` (`/en/docs/…`), it becomes `linguagem-codigo`, leaves the path and joins `url-raiz`. Without a language in the URL, the language cookie applies; otherwise, `LANGUAGE_DEFAULT`.
- **Paths with an extension** (`.js`, `.css`, `.png`, `.txt`…) go to the `arquivo-estatico` controller, which serves from `gestor/assets/`, from the modules or from `contents/` (uploaded files), and ends.
- `_api/…` goes to `controladores/api/api.php` and `_gateways/…` to `controladores/plataforma-gateways/`. Both have their own authentication (token or signature), skip the page router and are exempt from CSRF.

## 4. Session and CSRF

- `gestor_sessao_iniciar()` ensures the session cookie. The session lives in the database; see [gestor.php → Session](../reference/libraries/gestor.md).
- **CSRF** applies to POST/PUT/PATCH/DELETE requests from whoever holds the authentication cookie. The token goes into `<meta name="csrf-token">` and into `gestor.csrfToken`, and `global.js` attaches it to `fetch`, `$.ajax` and `XMLHttpRequest` and renews it on its own when it expires (req-175). GET requests are not checked.

## 5. `gestor_roteador()`: from URL to page

1. Reads the control parameters: `ajax`, `ajaxOpcao` (→ `ajax-opcao`), `opcao`, `ajaxPagina`, `ajaxWidgets`, `paginaIframe` and `ajaxRegistroId`.
2. **System routes:** `_gestor-cookie-verify/` (cookie proof) and the route that returns a fresh CSRF token (`gestor_roteador_csrf_token()`).
3. **Restricted site access** (`gestor_roteador_acesso_restrito()`): with `SITE_RESTRICTED_ACCESS` on, anyone not logged in goes to `/signin/`, except on the exempt routes.
4. **Looks up the page** in the `paginas` table: same `caminho`, current language, `tipo` `sistema` or `pagina`, `status='A'`, inside the `data_publicacao_inicio`/`fim` window. If it is not found in the current language, it **accepts the same route in any language**.
5. The `hook_apply_filters('gestor', 'roteador.paginas', $paginas)` filter lets a plugin or project change the result. With the Live Editor, a restored backup can replace the HTML (`gestor_site_toolbar_backup_aplicar()`).
6. **Page not found:** `gestor_roteador_301_ou_404()` looks the path up in `paginas_301`. If it belonged to an active page, it answers **301** to that page's current path; otherwise it **redirects to `/404/`**. The `404/` page itself is served with status 404. On AJAX, the answer is JSON `{"error":"404"}`.

## 6. Permission

When the page does not have `sem_permissao`, the router calls `gestor_permissao()`:
- **`gestor_permissao_token()`** validates the login. The authentication cookie holds a token whose signature is the `header.payload` **encrypted with the site's RSA key**; the server checks it by decrypting with the private key (`gestor_permissao_validar_jwt()`). It then requires the `pubID` to exist in `usuarios_tokens`, with the `USUARIO_HASH_PASSWORD` HMAC, within its validity. It renews the token every `COOKIE_RENEWTIME` and checks User-Agent and IP against session theft. The result is memoized per request.
- Without login: the current route is stored to return to after login, and the answer is **401 → `/signin/`** (on AJAX, JSON with `code: AUTH_REQUIRED` and the `X-Gestor-Auth-Redirect` header).
- **`gestor_permissao_modulo()`** checks whether the user's profile has the page's module (`usuarios_perfis_modulos`, or the host manager profiles). Without permission: 401 → `/dashboard/`.
- Social-network robots on a protected page get only the `<head>` with OpenGraph (`gestor_roteador_crawler_pagina_protegida()`), so the link has a preview without leaking content.

Inside the code, use:
- `gestor_usuario()` for the current user (anonymous: `id` `_anonimo`, `id_usuarios` 0);
- `gestor_acesso('operation', 'module')` for a specific operation. When the operation is not registered in `modulos_operacoes`, having the module is enough.

> [!CAUTION]
> `gestor_usuario_perfil()` returns the value of the `COOKIE_AUTHPROFILE` cookie, which is **not signed**. It serves presentation decisions (such as menu visibility), never authorization. Access is decided by `gestor_permissao_token()` + `gestor_usuario()`.

## 7. Module execution

A page can point to a `modulo` (and a `plugin`). The router then includes `gestor/modulos/<module>/<module>.php` (or the plugin's), which ends by calling `<module>_start()`.

- **AJAX request:** the module answers by filling `$_GESTOR['ajax-json']`, which the router returns as JSON. Without an answer, the error is 500 ("No response data set").
  - On a page **without permission**, the included file is `<module>.ajax.public.php`, the only way to have public AJAX.
  - With `ajaxPagina`, the page's HTML is loaded too.
  - `ajaxWidgets` triggers the widgets' AJAX (`gestor_pagina_widgets_ajax()`).
- **Normal request with `opcao`** (for example, a POST to `?opcao=save`): the module runs and the router **redirects to the module's root page**.
- **Normal request without `opcao`:** the page's HTML (from the database, or from the `resources/` files in development) becomes `$_GESTOR['pagina']`; then the module runs and may change it.
- Without `modulo` but with `opcao`, `gestor/modulos/global.php` runs.

## 8. HTML assembly

After the module, in this order:

1. `gestor_componentes_incluir_pagina()`: marked components.
2. **Layout:** the one in `$_GESTOR['layout']` (set by the module), the page's `layout_id` (`layout-iframes` with `paginaIframe`) or none.
3. `gestor_pagina_recursos_incluir()` for the layout and the page (precompiled, compiled and authored CSS, and `html_extra_head`).
4. Language selector data, when enabled.
5. `gestor_pagina_layout()`: the page goes into the layout's `@[[pagina#corpo]]@`, and `<!-- pagina#titulo -->` becomes `<title>`.
6. `gestor_pagina_widgets()`: each `<!-- widgets#module->function({...}) < -->…<!-- … > -->` (and the old `@[[widgets#…]]@` form) is replaced by the widget's HTML.
7. Live Editor bar (only for logged-in editors), PDF.js and Quill CSS, only on the pages that use them.
8. `gestor_pagina_css()` and `gestor_pagina_extra_head_e_javascript()` fill `<!-- pagina#css -->` and `<!-- pagina#js -->`, with the global `gestor` object (versions, root, language, module, CSRF, `javascript-vars`), OpenGraph, SEO and robots.
9. `gestor_pagina_variaveis()` replaces the `@[[…]]@` markers, in order:
   - the system ones: `pagina#url-raiz`, `pagina#titulo`, `pagina#menu`, `usuario#nome`, `gestor#versao`…;
   - the text variables (global, module, extra modules).
10. `gestor_pagina_ultimas_operacoes()` applies the final markers (`pagina-marcadores-finais`), removes empty lines and, in production, **sanitizes** the HTML (no comments or indentation).
11. Output with `Content-Type: text/html` and `exit`.

> [!IMPORTANT]
> Any `@[[x]]@` left in the HTML is looked up as a text variable in **any module**. To **show** a marker as an example on a page, escape the at sign (`&#64;[[x]]&#64;`). That is what `c2f docs:build` does for the docs.

## Details and legacy

- `?hotfix` on any URL makes the router answer "Hotfix Done!" and stop (`gestor_hotfix()`), a leftover of an old mechanism.
- `gestor_permissao_fingerprint()` exists, but the fingerprint check is commented out in `gestor_permissao()`.
- `gestor_pagina_css_incluir($css)` with an explicit CSS pushes the value onto the **JavaScript** queue (`javascript-fim`), not the CSS one. Only the call without an argument (the module's `css.css`) works as the name suggests.
- `gestor_pagina_menu()` and `gestor_pagina_menu_icone()` build the admin panel side menu (`@[[pagina#menu]]@`) from the modules and groups allowed for the profile.

## See also

- [gestor.php library](../reference/libraries/gestor.md): session, redirection, page resources and sanitization.
- [interface.php library](../reference/libraries/interface.md): what happens inside a CRUD module.
