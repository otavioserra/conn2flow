---
title: "Variáveis globais e configuração"
description: "O que vive em $_GESTOR, $_CONFIG, $_BANCO e $_INDEX, de onde cada valor vem (.env, config.php, roteador, módulo) e como o .env é encontrado para cada domínio."
section: concepts
order: 20
sources:
  - gestor/config.php
  - gestor/gestor.php
  - gestor/autenticacoes.exemplo/dominio/.env
verified_at: 837c383f
---

# Variáveis globais e configuração

O Gestor é procedural e compartilha estado por quatro arrays globais. Dentro de uma função, declare `global $_GESTOR;` (e `$_CONFIG`, `$_BANCO`) antes de usar.

| Global | Conteúdo | Quem escreve |
|---|---|---|
| `$_INDEX` | Só `sistemas-dir`: a pasta do Gestor | `index.php` público |
| `$_GESTOR` | Estado da instalação **e** da requisição: caminhos, idioma, página, módulo, usuário, filas de CSS/JS | `config.php`, roteador, bibliotecas e módulos |
| `$_CONFIG` | Configuração lida do `.env` (sessão, cookies, segurança, e-mail, captcha…) | `config.php` (somente leitura depois) |
| `$_BANCO` | Credenciais e conexão do banco | `config.php` e [banco.php](../reference/libraries/banco.md) |

Há também `$_CRON` (definido por `cron.php` antes do `config.php`, com `SERVER_NAME` e `ROOT_PATH` do domínio agendado) e o próprio `$_ENV`, com as chaves cruas do `.env`.

## Como o `.env` é encontrado

1. `ROOT_PATH` é a pasta do Gestor: `$_INDEX['sistemas-dir']` na web, a pasta do `config.php` no CLI e `$_CRON['ROOT_PATH']` no cron.
2. O `.env` procurado é `autenticacoes/<SERVER_NAME>/.env`, carregado pelo Dotenv. No CLI, sem `SERVER_NAME` definido, usa `localhost`.
3. Se não existir, o `config.php` tenta **cada pasta de `autenticacoes/` em ordem alfabética** e carrega **a primeira que tiver `.env`**.
4. Nenhuma encontrada: **503** com JSON `Configuration file (.env) not found`. Sem o Composer instalado (sem Dotenv): **500**.

> [!WARNING]
> O passo 3 faz uma requisição com um `Host` desconhecido usar a configuração de outro domínio da mesma instalação. Numa instalação com vários domínios em `autenticacoes/`, configure o servidor web para só aceitar os hosts conhecidos.

Os nomes de cookie recebem um **sufixo por domínio** (`_` + os 8 primeiros caracteres, em maiúsculas, do MD5 do nome da pasta do domínio), por exemplo `_C2FCID_05B11065`. Assim, dois projetos no mesmo domínio-pai não trocam cookies. Um script de CLI que gera cookie para um site precisa rodar com o `SERVER_NAME` desse site.

## `$_BANCO`

| Chave | `.env` | Padrão |
|---|---|---|
| `tipo` | `DB_CONNECTION` | `mysqli` |
| `host` | `DB_HOST` | `localhost` |
| `nome`, `usuario`, `senha` | `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | vazio |

A conexão em si (`$_BANCO['conexao']`) é aberta sob demanda por [banco.php](../reference/libraries/banco.md).

## `$_CONFIG`: mapa do `.env`

| `$_CONFIG` | `.env` | Padrão |
|---|---|---|
| `session-authname` / `session-lifetime` | `SESSION_AUTHNAME` / `SESSION_LIFETIME` | `_C2FSID` / 10800 s |
| `session-garbagetime` | `SESSION_GARBAGETIME` | 86400 s (vida dos tokens de sessão) |
| `cookie-authname`, `cookie-authprofile`, `cookie-verify`, `cookie-language` | `COOKIE_AUTHNAME`, `COOKIE_AUTHPROFILE`, `COOKIE_VERIFY`, `LANGUAGE_COOKIE` | `_C2FCID`, `_C2FCP`, `_C2FCVID`, `_C2FCL` |
| `cookie-lifetime` / `cookie-renewtime` | `COOKIE_LIFETIME` / `COOKIE_RENEWTIME` | 1296000 s (15 dias) / 86400 s |
| `openssl-password` | `OPENSSL_PASSWORD` | senha da chave privada RSA |
| `usuario-hash-password` / `usuario-hash-algo` | `USUARIO_HASH_PASSWORD` / `USUARIO_HASH_ALGO` | HMAC dos tokens / `sha512` |
| `captcha-provider` | `CAPTCHA_PROVIDER` | `none`, ou `google-recaptcha` se `USUARIO_RECAPTCHA_ACTIVE=true` |
| `turnstile-*` | `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `TURNSTILE_MODE` | `managed` |
| `usuario-recaptcha-*`, `usuario-recaptcha-v2-*` | `USUARIO_RECAPTCHA_*`, `USUARIO_RECAPTCHA_V2_*` | — |
| `usuario-maximo-senhas-invalidas`, `usuario-autorizacao-lifetime`, `token-lifetime`, `autenticacao-token-lifetime` | `USUARIO_MAXIMO_SENHAS_INVALIDAS`, `USUARIO_AUTORIZACAO_LIFETIME`, `TOKEN_LIFETIME`, `AUTENTICACAO_TOKEN_LIFETIME` | 3, 300, 3600, 15552000 |
| `site-name`, `site-description`, `site-keywords`, `site-og-image` | `SITE_NAME`, `SITE_DESCRIPTION`, `SITE_KEYWORDS`, `SITE_OG_IMAGE` | `Conn2Flow`, vazio… |
| `crawler-tokens-extra-ativo` / `crawler-tokens-extra` | `CRAWLER_TOKENS_EXTRA_ATIVO` / `CRAWLER_TOKENS_EXTRA` | `false` / vazio |
| `site-restricted-access` / `site-restricted-profiles` | `SITE_RESTRICTED_ACCESS` / `SITE_RESTRICTED_PROFILES` | `false` / vazio |
| `security.csp`, `security.csp-report-only`, `security.x-frame-options` | `SECURITY_CSP`, `SECURITY_CSP_REPORT_ONLY`, `SECURITY_X_FRAME_OPTIONS` | vazio, `false`, `SAMEORIGIN` |
| `api.cors-origins`, `api.rate-limit-max`, `api.rate-limit-window` | `API_CORS_ORIGINS`, `API_RATE_LIMIT_MAX`, `API_RATE_LIMIT_WINDOW` | vazio, 100, 3600 s |
| `acessos-*` | `ACESSOS_*` | limites de login e cadastro por IP |
| `formularios-*` | `FORMULARIOS_*` | limites e limpeza de formulários |
| `email.ativo`, `email.server.*`, `email.sender.*` | `EMAIL_ACTIVE`, `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_SECURE`, `EMAIL_PORT`, `EMAIL_FROM(_NAME)`, `EMAIL_REPLY_TO(_NAME)` | desligado, porta 587 |
| `language.widget-active` / `language.auto-detect` | `LANGUAGE_WIDGET_ACTIVE` / `LANGUAGE_AUTO_DETECT` | `false` |
| `paypal.*`, `oauth2.*`, `modulo-distribuido.*` | `PAYPAL_*`, `OAUTH2_*`, `MODULO_DISTRIBUIDO_*` | — |

> [!NOTE]
> Os padrões do `config.php` nem sempre batem com o `.env` de exemplo. `ACESSOS_TEMPO_BLOQUEIO_IP`, por exemplo, vale 86400 se ausente, mas o exemplo traz 900. Na dúvida, o valor efetivo é o do `.env` do domínio.

Leia `$_CONFIG` para configurações do sistema. Para chaves que também podem ser lidas por código fora do bootstrap web, os módulos do core usam a ordem `$_CONFIG` → `$_ENV` → `getenv()`: uma chave lida de um lugar só fica inerte, sem erro, onde aquele lugar não é preenchido.

## `$_GESTOR`: estado da instalação (do `config.php`)

| Chave | Conteúdo |
|---|---|
| `versao`, `id` | Versão do Gestor (`2.10.13`) e prefixo `conn2flow-` |
| `ROOT_PATH`, `AUTH_PATH`, `AUTH_PATH_SERVER` | Pasta do Gestor, `autenticacoes/` e a pasta do domínio efetivamente carregada |
| `bibliotecas-path`, `modulos-path`, `controladores-path`, `assets-path`, `contents-path`, `logs-path`, `plugins-path` | Pastas do sistema |
| `openssl-path` | Pasta das chaves RSA dentro da pasta do domínio (`OPENSSL_KEYS_SUBDIR`, padrão `chaves/gestor/`) |
| `url-raiz`, `url-raiz-sem-lang` | `URL_RAIZ` (`/` ou `/subpasta/`). A primeira ganha o idioma quando ele vem na URL |
| `url-full`, `url-full-http`, `url-full-http-sem-lang` | `//dominio/raiz/` e `https://dominio/raiz/`, este sempre com `https` |
| `linguagem-padrao`, `linguagem-codigo`, `languages` | `LANGUAGE_DEFAULT` (padrão `pt-br`), o idioma da requisição e a lista `LANGUAGES` separada por vírgula. Sem a chave `LANGUAGES` no `.env`, o PHP emite um *warning* (`$_ENV['LANGUAGES']` é lido sem `??`) |
| `development-env` | `DEVELOPMENT_ENV`: lê recursos do disco, relaxa cookie `Secure` em HTTP e desliga a higienização |
| `variavel-global` | Marcadores `@[[`/`]]@` (banco) e `[[`/`]]` (edição) |
| `bibliotecas-dados`, `bibliotecas` | Registro de bibliotecas e as sempre carregadas ([veja](../reference/libraries/index.md)) |
| `asset-versions`, `asset-version`, `project-asset-version` | Tokens de cache de `assets/asset-versions.json` e `contents/asset-version.json` |
| `public-path`, `dist-path`, `dist-url`, `dist-manifest`, `dist-ativo` | Assets publicados em `public_html/dist/` (req-028), com `PUBLIC_PATH` e `ASSETS_DIST` |
| `pagina#contato-url` | `contato/` |

Um projeto pode acrescentar ou sobrescrever chaves em `config-project.php` (na raiz do Gestor), incluído no fim do `config.php`. É ali que vivem, por exemplo:
- `project-css` e `project-javascript`, com as variantes `*-layouts-include` e `*-layouts-remove`;
- `project-page-tailwind-bundles`;
- `project-version`.

## `$_GESTOR`: estado da requisição

| Grupo | Chaves |
|---|---|
| Rota | `caminho` (segmentos), `caminho-total`, `caminho-extensao`, `language-in-url`, `page-languages`, `arquivo-estatico` |
| Controle | `ajax`, `ajax-opcao`, `ajaxPagina`, `ajaxWidgets`, `opcao`, `paginaIframe`, `hotfix` |
| Página | `pagina` (o HTML sendo montado), `pagina#titulo`, `pagina#titulo-extra`, `pagina#id`, `pagina#framework_css`, `pagina#og`, `pagina-alerta`, `pagina-marcadores-finais` |
| Layout | `layout` (definido pelo módulo), `layout#id`, `layout#framework_css` |
| Módulo | `modulo` (da página), `modulo-id` (do controlador), `modulo#<id>` (o JSON do módulo), `modulo-registro-id`, `interface`, `interface-opcao`, `adicionar-banco`, `atualizar-banco` |
| Usuário | `usuario-id`, `usuario-token-id`, `usuario` (cache de `gestor_usuario()`), `session-id` |
| Saída | `css`, `css-precompiled`, `css-compiled`, `css-fim`, `javascript`, `javascript-fim`, `html-extra-head`, `javascript-vars` (vira o objeto JS `gestor`), `componentes`, `recursos-incluidos-hashes` |
| Resposta AJAX | `ajax-json` (o módulo preenche, e o roteador devolve como JSON) |
| Caches | `variaveis[<modulo>]`, `paginas-variaveis`, `schema-tabelas`, `schema-campos`, `bibliotecas-inseridas`, `dashboard-toolbar-ativo`, `permissao-token-resultado`, `requisicao-crawler` |

## Veja também

- [Ciclo de uma requisição](request-lifecycle.md): em que momento cada chave é preenchida.
- [Instalação](../guides/installation.md): como o `.env` nasce.
- Skills `c2f-global-variables` e `c2f-environment-configuration`.
