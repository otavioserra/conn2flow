---
title: "Global variables and configuration"
description: "What lives in $_GESTOR, $_CONFIG, $_BANCO and $_INDEX, where each value comes from (.env, config.php, router, module) and how the .env is found for each domain."
section: concepts
order: 20
sources:
  - gestor/config.php
  - gestor/gestor.php
  - gestor/autenticacoes.exemplo/dominio/.env
verified_at: 837c383f
---

# Global variables and configuration

The Gestor is procedural and shares state through four global arrays. Inside a function, declare `global $_GESTOR;` (and `$_CONFIG`, `$_BANCO`) before using them.

| Global | Content | Written by |
|---|---|---|
| `$_INDEX` | Only `sistemas-dir`: the Gestor folder | public `index.php` |
| `$_GESTOR` | Installation **and** request state: paths, language, page, module, user, CSS/JS queues | `config.php`, router, libraries and modules |
| `$_CONFIG` | Configuration read from the `.env` (session, cookies, security, e-mail, captcha…) | `config.php` (read-only afterwards) |
| `$_BANCO` | Database credentials and connection | `config.php` and [banco.php](../reference/libraries/banco.md) |

There is also `$_CRON` (set by `cron.php` before `config.php`, with the scheduled domain's `SERVER_NAME` and `ROOT_PATH`) and `$_ENV` itself, with the raw `.env` keys.

## How the `.env` is found

1. `ROOT_PATH` is the Gestor folder: `$_INDEX['sistemas-dir']` on the web, the `config.php` folder on the CLI and `$_CRON['ROOT_PATH']` on cron.
2. The `.env` looked up is `autenticacoes/<SERVER_NAME>/.env`, loaded by Dotenv. On the CLI, with no `SERVER_NAME` set, it uses `localhost`.
3. If it does not exist, `config.php` tries **each folder in `autenticacoes/` in alphabetical order** and loads **the first one that has a `.env`**.
4. None found: **503** with JSON `Configuration file (.env) not found`. Without Composer installed (no Dotenv): **500**.

> [!WARNING]
> Step 3 makes a request with an unknown `Host` use the configuration of another domain of the same installation. On an installation with several domains in `autenticacoes/`, configure the web server to accept only the known hosts.

Cookie names get a **per-domain suffix** (`_` + the first 8 characters, uppercase, of the MD5 of the domain folder name), for example `_C2FCID_05B11065`. That way, two projects on the same parent domain do not swap cookies. A CLI script that creates a cookie for a site must run with that site's `SERVER_NAME`.

## `$_BANCO`

| Key | `.env` | Default |
|---|---|---|
| `tipo` | `DB_CONNECTION` | `mysqli` |
| `host` | `DB_HOST` | `localhost` |
| `nome`, `usuario`, `senha` | `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | empty |

The connection itself (`$_BANCO['conexao']`) is opened on demand by [banco.php](../reference/libraries/banco.md).

## `$_CONFIG`: map of the `.env`

| `$_CONFIG` | `.env` | Default |
|---|---|---|
| `session-authname` / `session-lifetime` | `SESSION_AUTHNAME` / `SESSION_LIFETIME` | `_C2FSID` / 10800 s |
| `session-garbagetime` | `SESSION_GARBAGETIME` | 86400 s (session token lifetime) |
| `cookie-authname`, `cookie-authprofile`, `cookie-verify`, `cookie-language` | `COOKIE_AUTHNAME`, `COOKIE_AUTHPROFILE`, `COOKIE_VERIFY`, `LANGUAGE_COOKIE` | `_C2FCID`, `_C2FCP`, `_C2FCVID`, `_C2FCL` |
| `cookie-lifetime` / `cookie-renewtime` | `COOKIE_LIFETIME` / `COOKIE_RENEWTIME` | 1296000 s (15 days) / 86400 s |
| `openssl-password` | `OPENSSL_PASSWORD` | RSA private key password |
| `usuario-hash-password` / `usuario-hash-algo` | `USUARIO_HASH_PASSWORD` / `USUARIO_HASH_ALGO` | token HMAC / `sha512` |
| `captcha-provider` | `CAPTCHA_PROVIDER` | `none`, or `google-recaptcha` when `USUARIO_RECAPTCHA_ACTIVE=true` |
| `turnstile-*` | `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `TURNSTILE_MODE` | `managed` |
| `usuario-recaptcha-*`, `usuario-recaptcha-v2-*` | `USUARIO_RECAPTCHA_*`, `USUARIO_RECAPTCHA_V2_*` | — |
| `usuario-maximo-senhas-invalidas`, `usuario-autorizacao-lifetime`, `token-lifetime`, `autenticacao-token-lifetime` | `USUARIO_MAXIMO_SENHAS_INVALIDAS`, `USUARIO_AUTORIZACAO_LIFETIME`, `TOKEN_LIFETIME`, `AUTENTICACAO_TOKEN_LIFETIME` | 3, 300, 3600, 15552000 |
| `site-name`, `site-description`, `site-keywords`, `site-og-image` | `SITE_NAME`, `SITE_DESCRIPTION`, `SITE_KEYWORDS`, `SITE_OG_IMAGE` | `Conn2Flow`, empty… |
| `crawler-tokens-extra-ativo` / `crawler-tokens-extra` | `CRAWLER_TOKENS_EXTRA_ATIVO` / `CRAWLER_TOKENS_EXTRA` | `false` / empty |
| `site-restricted-access` / `site-restricted-profiles` | `SITE_RESTRICTED_ACCESS` / `SITE_RESTRICTED_PROFILES` | `false` / empty |
| `security.csp`, `security.csp-report-only`, `security.x-frame-options` | `SECURITY_CSP`, `SECURITY_CSP_REPORT_ONLY`, `SECURITY_X_FRAME_OPTIONS` | empty, `false`, `SAMEORIGIN` |
| `api.cors-origins`, `api.rate-limit-max`, `api.rate-limit-window` | `API_CORS_ORIGINS`, `API_RATE_LIMIT_MAX`, `API_RATE_LIMIT_WINDOW` | empty, 100, 3600 s |
| `acessos-*` | `ACESSOS_*` | login and sign-up limits per IP |
| `formularios-*` | `FORMULARIOS_*` | form limits and cleanup |
| `email.ativo`, `email.server.*`, `email.sender.*` | `EMAIL_ACTIVE`, `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_SECURE`, `EMAIL_PORT`, `EMAIL_FROM(_NAME)`, `EMAIL_REPLY_TO(_NAME)` | off, port 587 |
| `language.widget-active` / `language.auto-detect` | `LANGUAGE_WIDGET_ACTIVE` / `LANGUAGE_AUTO_DETECT` | `false` |
| `paypal.*`, `oauth2.*`, `modulo-distribuido.*` | `PAYPAL_*`, `OAUTH2_*`, `MODULO_DISTRIBUIDO_*` | — |

> [!NOTE]
> The `config.php` defaults do not always match the sample `.env`. `ACESSOS_TEMPO_BLOQUEIO_IP`, for example, is 86400 when absent, but the sample has 900. When in doubt, the effective value is the one in the domain's `.env`.

Read `$_CONFIG` for system settings. For keys that code outside the web bootstrap may also read, the core modules use the order `$_CONFIG` → `$_ENV` → `getenv()`: a key read from a single place goes silently inert, with no error, wherever that place is not filled.

## `$_GESTOR`: installation state (from `config.php`)

| Key | Content |
|---|---|
| `versao`, `id` | Gestor version (`2.10.13`) and the `conn2flow-` prefix |
| `ROOT_PATH`, `AUTH_PATH`, `AUTH_PATH_SERVER` | Gestor folder, `autenticacoes/` and the domain folder actually loaded |
| `bibliotecas-path`, `modulos-path`, `controladores-path`, `assets-path`, `contents-path`, `logs-path`, `plugins-path` | System folders |
| `openssl-path` | RSA key folder inside the domain folder (`OPENSSL_KEYS_SUBDIR`, default `chaves/gestor/`) |
| `url-raiz`, `url-raiz-sem-lang` | `URL_RAIZ` (`/` or `/subfolder/`). The first one gets the language when it comes in the URL |
| `url-full`, `url-full-http`, `url-full-http-sem-lang` | `//domain/root/` and `https://domain/root/`, the latter always `https` |
| `linguagem-padrao`, `linguagem-codigo`, `languages` | `LANGUAGE_DEFAULT` (default `pt-br`), the request language and the comma-separated `LANGUAGES` list. Without the `LANGUAGES` key in the `.env`, PHP emits a warning (`$_ENV['LANGUAGES']` is read without `??`) |
| `development-env` | `DEVELOPMENT_ENV`: reads resources from disk, relaxes the `Secure` cookie on HTTP and turns off sanitization |
| `variavel-global` | Markers `@[[`/`]]@` (database) and `[[`/`]]` (editing) |
| `bibliotecas-dados`, `bibliotecas` | Library registry and the always-loaded ones ([see](../reference/libraries/index.md)) |
| `asset-versions`, `asset-version`, `project-asset-version` | Cache tokens from `assets/asset-versions.json` and `contents/asset-version.json` |
| `public-path`, `dist-path`, `dist-url`, `dist-manifest`, `dist-ativo` | Assets published to `public_html/dist/` (req-028), with `PUBLIC_PATH` and `ASSETS_DIST` |
| `pagina#contato-url` | `contato/` |

A project can add or override keys in `config-project.php` (at the Gestor root), included at the end of `config.php`. That is where, for example, these live:
- `project-css` and `project-javascript`, with the `*-layouts-include` and `*-layouts-remove` variants;
- `project-page-tailwind-bundles`;
- `project-version`.

## `$_GESTOR`: request state

| Group | Keys |
|---|---|
| Route | `caminho` (segments), `caminho-total`, `caminho-extensao`, `language-in-url`, `page-languages`, `arquivo-estatico` |
| Control | `ajax`, `ajax-opcao`, `ajaxPagina`, `ajaxWidgets`, `opcao`, `paginaIframe`, `hotfix` |
| Page | `pagina` (the HTML being assembled), `pagina#titulo`, `pagina#titulo-extra`, `pagina#id`, `pagina#framework_css`, `pagina#og`, `pagina-alerta`, `pagina-marcadores-finais` |
| Layout | `layout` (set by the module), `layout#id`, `layout#framework_css` |
| Module | `modulo` (from the page), `modulo-id` (from the controller), `modulo#<id>` (the module JSON), `modulo-registro-id`, `interface`, `interface-opcao`, `adicionar-banco`, `atualizar-banco` |
| User | `usuario-id`, `usuario-token-id`, `usuario` (cache of `gestor_usuario()`), `session-id` |
| Output | `css`, `css-precompiled`, `css-compiled`, `css-fim`, `javascript`, `javascript-fim`, `html-extra-head`, `javascript-vars` (becomes the `gestor` JS object), `componentes`, `recursos-incluidos-hashes` |
| AJAX response | `ajax-json` (the module fills it, and the router returns it as JSON) |
| Caches | `variaveis[<module>]`, `paginas-variaveis`, `schema-tabelas`, `schema-campos`, `bibliotecas-inseridas`, `dashboard-toolbar-ativo`, `permissao-token-resultado`, `requisicao-crawler` |

## See also

- [Request lifecycle](request-lifecycle.md): when each key is filled.
- [Installation](../guides/installation.md): how the `.env` is born.
- Skills `c2f-global-variables` and `c2f-environment-configuration`.
