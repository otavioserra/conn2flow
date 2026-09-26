---
title: "Biblioteca oauth.php"
label: "Login social"
description: "Login social com Google e Meta (Facebook): URL de autorização com state, troca do código e leitura do perfil."
section: reference
order: 230
sources:
  - gestor/bibliotecas/oauth.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: f35b22ad
---

# Biblioteca `oauth.php`

Cliente OAuth 2.0 para **entrar com Google ou Meta**. Não confundir com a [oauth2.php](oauth2.md), que é o **servidor** de tokens da API do Gestor. As telas e a decisão de login ficam no `perfil-usuario` (opções `social-login` e `oauth-callback`).

## Configuração

No `.env`: `OAUTH_GOOGLE_CLIENT_ID`/`OAUTH_GOOGLE_CLIENT_SECRET` e `OAUTH_META_APP_ID`/`OAUTH_META_APP_SECRET`. Sem o identificador, o provedor fica indisponível (`oauth_redirect_url()` devolve `false`).

No console do provedor, cadastre a URL de retorno que `oauth_redirect_uri()` gera: `https://<dominio>/<raiz>/oauth-callback/?provider=google` (ou `meta`). Ela usa `url-full-http`, sempre com `https`.

As chaves são lidas só de `$_ENV`, não de `$_CONFIG`.

## Fluxo

1. `oauth_redirect_url($provider)` sorteia um `state` (128 bits), guarda-o na sessão com o provedor e devolve a URL de autorização. Escopos: `openid email profile` (Google) e `email public_profile` (Meta, Graph API v19.0).
2. O provedor volta com `code` e `state`. `oauth_validate_state($state)` compara com a sessão (`hash_equals`).
3. `oauth_authenticate_code($provider, $code)` troca o código pelo token e lê o perfil. Devolve `['provider', 'uid', 'email', 'nome']` ou `false`.

`oauth_http_post()` e `oauth_http_get()` são os auxiliares de cURL (20 s de tempo limite).

## O que o `perfil-usuario` faz com o perfil

- Com um vínculo em `usuarios_provedores` (provedor + `uid`), entra como esse usuário.
- **Sem vínculo, procura um usuário ativo com o mesmo e-mail e cria o vínculo sozinho.**
- Pela tela de segurança, o usuário logado pode vincular uma conta social à sua.
- Depois do login social, o 2FA do usuário continua valendo.

> [!WARNING]
> O vínculo automático por e-mail não confere `email_verified` (Google) nem se o e-mail foi confirmado no provedor. Uma conta de provedor com um e-mail não verificado igual ao de um usuário do site entraria como esse usuário (req-181, item A8). O `state` também não é apagado depois de usado.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `oauth_config()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/oauth.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `oauth_config(string $provider): array|false` — [linha 30](../../../../../gestor/bibliotecas/oauth.php#L30)
  Retorna a configuração de endpoints/credenciais de um provedor.
  Parâmetros:
  - `$provider`: 'google' ou 'meta'.
  Retorno: Configuração ou false se o provedor não for suportado.
- `oauth_redirect_uri(string $provider): string` — [linha 64](../../../../../gestor/bibliotecas/oauth.php#L64)
  Monta a URI de callback do provedor (calculada a partir do domínio atual).
  Parâmetros:
  - `$provider`: 'google' ou 'meta'.
  Retorno: URI de redirecionamento (ex.: https://dominio/_api/auth/callback/google).
- `oauth_redirect_url(string $provider): string|false` — [linha 84](../../../../../gestor/bibliotecas/oauth.php#L84)
  Gera o URL de redirecionamento para a tela de consentimento do provedor. Grava o `state` (proteção CSRF) e o provedor na sessão.
  Parâmetros:
  - `$provider`: 'google' ou 'meta'.
  Retorno: URL de autorização ou false se não configurado.
- `oauth_validate_state(string $state): bool` — [linha 114](../../../../../gestor/bibliotecas/oauth.php#L114)
  Valida o parâmetro `state` retornado pelo provedor contra o gravado na sessão.
  Parâmetros:
  - `$state`: Valor recebido no callback.
  Retorno: true se conferir.
- `oauth_http_post($url, $data)` — [linha 123](../../../../../gestor/bibliotecas/oauth.php#L123)
- `oauth_http_get($url, $bearer = null)` — [linha 139](../../../../../gestor/bibliotecas/oauth.php#L139)
- `oauth_authenticate_code(string $provider, string $code): array|false` — [linha 168](../../../../../gestor/bibliotecas/oauth.php#L168)
  Troca o código de autorização por um token de acesso e retorna o perfil verificado do usuário.
  Parâmetros:
  - `$provider`: 'google' ou 'meta'.
  - `$code`: Código de autorização recebido no callback.
  Retorno: ['provider','uid','email','nome'] ou false em erro.

<!-- c2f:extract:end -->
