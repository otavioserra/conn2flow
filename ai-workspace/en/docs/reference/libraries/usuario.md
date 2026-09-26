---
title: "usuario.php library"
label: "User and sessions"
description: "The login cookie, the user's active sessions, provisional authorization for sensitive actions, personal API tokens (c2f_pat_) and 2FA recovery codes."
section: reference
order: 55
sources:
  - gestor/bibliotecas/usuario.php
  - gestor/gestor.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: 3dcf5df7
---

# `usuario.php` library

Everything that happens **after** the password is accepted: issuing the login cookie, listing and ending sessions, asking for the password again before sensitive actions, issuing personal tokens for the API and keeping the [2FA](2fa.md) recovery codes. It is used by `perfil-usuario` and the API.

## The login cookie

`usuario_gerar_token_autorizacao(['id_usuarios' => …, 'sessao' => true])`:

1. draws a `pubID` (64 hex) and stores it in `usuarios_tokens` with the `USUARIO_HASH_PASSWORD` HMAC, the expiration, the IP and the User-Agent;
2. builds the token in the Gestor RSA format (`usuario_gerar_jwt()`, see [autenticacao.php](autenticacao.md)) and puts it in the `COOKIE_AUTHNAME` cookie: `HttpOnly`, `SameSite=Lax`, `Secure` except in the development environment over HTTP;
3. writes the `COOKIE_AUTHPROFILE` cookie with the SHA-256 of the profile id (used only for presentation; it is not signed).

With `sessao`, the cookie dies when the browser closes; without it, it lasts `COOKIE_LIFETIME` (15 days). Validation on every request is the router's job (`gestor_permissao_token()`, [request lifecycle](../../concepts/request-lifecycle.md)).

> [!NOTE]
> Here the "signature" is encrypted with the **public** key and checked with the private one (the opposite of the API tokens). What prevents a forged token is not the signature but the random `pubID`, which must exist in `usuarios_tokens` with the right HMAC.

`usuario_app_gerar_token_autorizacao()` (returns the token instead of writing the cookie) and `usuario_openssl_gerar_chaves()` have no callers.

## Active sessions

| Function | |
|---|---|
| `usuario_sessoes_listar($id_usuario, $currentPubId = null)` | The sessions (`usuarios_tokens` rows) already formatted by `usuario_sessao_formatar()`: browser and system (`usuario_user_agent_analisar()`, empty when unrecognized), IP, dates and which one is the current session |
| `usuario_sessao_revogar($pubID, $id_usuario)` | Ends one session; `id_usuario` goes into the WHERE so a guessed id cannot drop another account's session |
| `usuario_sessoes_revogar_outras($currentPubId, $id_usuario)` | Ends all but the current one (requires the current `pubID`) |

## Provisional authorization

Before sensitive actions (changing e-mail, password, 2FA), `perfil-usuario` asks for the password again. `usuario_autorizacao_provisoria()` controls that window, kept in the session for `USUARIO_AUTORIZACAO_LIFETIME` (300 s):
- `['validar' => true]` opens the window; `['invalidar' => true]` closes it; `['verificar' => true]` tells whether it is open;
- `['verificarModal' => ['cancelarUrl', 'confirmarUrl', 'autorizadoUrl', 'autorizadoUrlQuerystring']]` shows the confirmation modal when it is not open.

## Personal API tokens

`c2f_pat_<64 hex>` tokens (constant `USUARIO_API_TOKEN_PREFIXO`), accepted in the same `Authorization: Bearer` as the [OAuth2](oauth2.md) tokens:
- `usuario_api_token_gerar($id_usuario, $name, $scopes = [], $expiration_days = null)` returns the plain token **only once**; the database (`usuarios_api_tokens`) keeps only its SHA-256 (`usuario_api_token_hash()`) and a displayable prefix (`usuario_api_token_prefixo()`);
- `usuario_api_token_validar($token)` checks hash, status and expiration (`usuario_api_token_situacao()` tells revoked from expired), records the use and returns the same format as `oauth2_validar_token()`;
- `usuario_api_token_formato()` only tells whether the string has the format, so the API picks the right validator;
- `usuario_api_token_revogar($id_token, $id_usuario)`, `usuario_api_tokens_listar($id_usuario)`;
- `usuario_api_tokens_disponivel()`: the table exists (migrated installation).

> [!NOTE]
> The `scopes` are stored but, as with OAuth2, no endpoint checks them.

## 2FA recovery codes

`usuario_recovery_codes_gerar($amount = 10)` draws the codes (without `0`, `O`, `1`, `I` and `L`, to be copied by hand); the database keeps only the hashes (`usuario_recovery_code_hash()` over the form normalized by `usuario_recovery_code_normalizar()`, which ignores hyphens, spaces and case). `usuario_recovery_code_consumir($code, $hashes)` returns whether the code is valid and the list **without it** (single use). `usuario_recovery_codes_disponivel()` tells whether the column exists.

## Legacy

`usuario_host_dados()` returns data of the host user (multi-host mode, see [host.php](host.md)) or of an anonymous one. It has no callers.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/usuario.php` by `c2f docs:extract` — 25 functions. Do not edit inside this block.

- `usuario_openssl_gerar_chaves(array|false $params = false): array|false` — [line 37](../../../../../gestor/bibliotecas/usuario.php#L37)
  Gera par de chaves pública/privada OpenSSL.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['tipo']`: Tipo de chave: 'RSA' (obrigatório).
  - `$params['senha']`: Senha para proteger chave privada (opcional).
  Returns: Array com 'publica' e 'privada' ou false.
- `usuario_gerar_jwt(array|false $params = false): string|false` — [line 94](../../../../../gestor/bibliotecas/usuario.php#L94)
  Gera token JWT assinado com RSA.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['host']`: Host emissor do token (obrigatório).
  - `$params['expiration']`: Unix timestamp de expiração (obrigatório).
  - `$params['pubID']`: ID público do token (obrigatório).
  - `$params['chavePublica']`: Chave pública RSA para assinar (obrigatório).
  Returns: Token JWT ou false se inválido.
- `usuario_gerar_token_autorizacao(array|false $params = false): bool` — [line 166](../../../../../gestor/bibliotecas/usuario.php#L166)
  Gera token de autorização para autenticação de usuários web.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id_usuarios']`: ID do usuário (obrigatório).
  - `$params['sessao']`: Se true, cria cookie de sessão que expira ao fechar navegador (opcional).
  Returns: True se token criado com sucesso, false caso contrário.
- `usuario_app_gerar_token_autorizacao(array|false $params = false): array|false` — [line 284](../../../../../gestor/bibliotecas/usuario.php#L284)
  Gera token de autorização para aplicativos mobile.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id_usuarios']`: ID do usuário (obrigatório).
  Returns: Array com 'token' e 'expiration', ou false se inválido.
- `usuario_autorizacao_provisoria(array|false $params = false): bool|void` — [line 373](../../../../../gestor/bibliotecas/usuario.php#L373)
  Gerencia autorizações provisórias de usuários via sessão.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['verificar']`: Retorna true/false se autorização existe e é válida (opcional).
  - `$params['validar']`: Cria nova autorização provisória (opcional).
  - `$params['invalidar']`: Remove autorização provisória (opcional).
  - `$params['verificarModal']`: ['autorizadoUrlQuerystring'] Query string adicional (opcional).
  Returns: Retorna bool se 'verificar' está definido, void caso contrário.
- `usuario_user_agent_analisar(string $userAgent): array{navegador:string,sistema:string,dispositivo:string}` — [line 499](../../../../../gestor/bibliotecas/usuario.php#L499)
  Identifica navegador, sistema operacional e tipo de dispositivo a partir do User-Agent.
  Parameters:
  - `$userAgent`: Cabeçalho `User-Agent` bruto (pode vir vazio).
  Returns: Nomes crus, sem tradução.
- `usuario_sessao_formatar(array $registro, string|null $tokenAtual = null): array` — [line 583](../../../../../gestor/bibliotecas/usuario.php#L583)
  Normaliza uma linha de `usuarios_tokens` para exibição no painel de sessões.
  Parameters:
  - `$registro`: Linha de `usuarios_tokens` (`pubID`, `ip`, `user_agent`, `expiration`,
  - `$tokenAtual`: `pubID` do token da requisição corrente.
  Returns: Sessão normalizada, com `atual`, `navegador`, `sistema`, `dispositivo` e `sessao`.
- `usuario_sessoes_listar(int $id_usuario, string|null $token_atual_pubID = null): array` — [line 619](../../../../../gestor/bibliotecas/usuario.php#L619)
  Lista as sessões ativas de um usuário, já normalizadas para exibição.
  Parameters:
  - `$id_usuario`: ID do usuário dono das sessões.
  - `$token_atual_pubID`: `pubID` do token da requisição corrente (opcional).
  Returns: Lista de sessões (mais recentes primeiro); vazia quando não há usuário válido.
- `usuario_sessao_revogar(string $pubID, int $id_usuario): bool` — [line 660](../../../../../gestor/bibliotecas/usuario.php#L660)
  Revoga uma sessão específica do usuário informado.
  Parameters:
  - `$pubID`: Identificador público do token a remover.
  - `$id_usuario`: ID do usuário dono do token.
  Returns: True quando o comando foi emitido; false quando os parâmetros são inválidos.
- `usuario_sessoes_revogar_outras(string $token_atual_pubID, int $id_usuario): bool` — [line 686](../../../../../gestor/bibliotecas/usuario.php#L686)
  Encerra todas as sessões do usuário, exceto a da requisição corrente.
  Parameters:
  - `$token_atual_pubID`: `pubID` do token que deve ser preservado.
  - `$id_usuario`: ID do usuário dono dos tokens.
  Returns: True quando o comando foi emitido; false quando os parâmetros são inválidos.
- `usuario_api_tokens_disponivel(): bool` — [line 715](../../../../../gestor/bibliotecas/usuario.php#L715)
  Diz se o schema já suporta Personal Access Tokens (req-119 / BATCH-122).
- `usuario_recovery_codes_disponivel(): bool` — [line 731](../../../../../gestor/bibliotecas/usuario.php#L731)
  Diz se o schema já suporta códigos de recuperação de 2FA (req-119 / BATCH-122).
- `usuario_api_token_formato(string $token): bool` — [line 750](../../../../../gestor/bibliotecas/usuario.php#L750)
  Diz se uma string TEM O FORMATO de um Personal Access Token.
  Parameters:
  - `$token`: Valor bruto recebido no cabeçalho.
- `usuario_api_token_hash(string $token): string` — [line 770](../../../../../gestor/bibliotecas/usuario.php#L770)
  Calcula o hash de armazenamento de um Personal Access Token.
  Parameters:
  - `$token`: Token em texto puro.
  Returns: Hash hexadecimal de 64 caracteres.
- `usuario_api_token_prefixo(string $token): string` — [line 784](../../../../../gestor/bibliotecas/usuario.php#L784)
  Extrai o prefixo exibível de um token.
  Parameters:
  - `$token`: Token em texto puro.
  Returns: Prefixo do sistema mais os 8 primeiros caracteres da parte aleatória.
- `usuario_api_token_situacao(array $registro, int|null $agora = null): string` — [line 804](../../../../../gestor/bibliotecas/usuario.php#L804)
  Decide se um token registrado está utilizável AGORA.
  Parameters:
  - `$registro`: Linha de `usuarios_api_tokens` (`status`, `expiracao`).
  - `$agora`: Timestamp de referência; omitido usa `time()`.
  Returns: `ativo`, `revogado` ou `expirado`.
- `usuario_api_token_gerar(int $id_usuario, string $nome, array $escopos = Array(), int|null $dias_expiracao = null): array|false` — [line 838](../../../../../gestor/bibliotecas/usuario.php#L838)
  Gera um Personal Access Token para o usuário.
  Parameters:
  - `$id_usuario`: Dono do token.
  - `$nome`: Identificador amigável.
  - `$escopos`: Permissões autorizadas.
  - `$dias_expiracao`: Validade em dias; `null` para token sem expiração.
  Returns: `['token' => …, 'prefixo' => …, 'expiracao' => …]` ou false se inválido.
- `usuario_api_token_validar(string $token_puro): array|false` — [line 892](../../../../../gestor/bibliotecas/usuario.php#L892)
  Valida um Personal Access Token e registra o uso.
  Parameters:
  - `$token_puro`: Token recebido no cabeçalho.
  Returns: Dados do usuário autenticado ou false.
- `usuario_api_token_revogar(int $id_token, int $id_usuario): bool` — [line 956](../../../../../gestor/bibliotecas/usuario.php#L956)
  Revoga um Personal Access Token do usuário informado.
  Parameters:
  - `$id_token`: Identificador do token.
  - `$id_usuario`: Dono do token.
  Returns: True quando o comando foi emitido.
- `usuario_api_tokens_listar(int $id_usuario): array` — [line 982](../../../../../gestor/bibliotecas/usuario.php#L982)
  Lista os tokens de um usuário, já anotados com a situação corrente.
  Parameters:
  - `$id_usuario`: Dono dos tokens.
  Returns: Lista de tokens (mais recentes primeiro).
- `usuario_recovery_codes_gerar(int $quantidade = 10): array` — [line 1034](../../../../../gestor/bibliotecas/usuario.php#L1034)
  Gera códigos de recuperação em texto puro.
  Parameters:
  - `$quantidade`: Quantos códigos gerar.
  Returns: Lista de códigos no formato `XXXX-XXXX`.
- `usuario_recovery_code_normalizar(string $codigo): string` — [line 1063](../../../../../gestor/bibliotecas/usuario.php#L1063)
  Normaliza um código de recuperação para comparação.
  Parameters:
  - `$codigo`: Código digitado.
  Returns: Código só com os caracteres significativos, em caixa alta.
- `usuario_recovery_code_hash(string $codigo): string` — [line 1077](../../../../../gestor/bibliotecas/usuario.php#L1077)
  Calcula o hash de armazenamento de um código de recuperação.
  Parameters:
  - `$codigo`: Código em texto puro.
  Returns: Hash hexadecimal.
- `usuario_recovery_code_consumir(string $codigo, array $hashes): array{valido:bool,restantes:array}` — [line 1092](../../../../../gestor/bibliotecas/usuario.php#L1092)
  Consome um código de recuperação de uma lista de hashes.
  Parameters:
  - `$codigo`: Código digitado pelo usuário.
  - `$hashes`: Lista de hashes ainda válidos.
- `usuario_host_dados(array|false $params = false): mixed|array` — [line 1131](../../../../../gestor/bibliotecas/usuario.php#L1131)
  Obtém dados do usuário do host atual.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['campo']`: Nome do campo específico a retornar (opcional, retorna todos se omitido).
  - `$params['id_hosts_usuarios']`: ID do usuário a buscar (opcional, usa usuário atual se omitido).
  Returns: Valor do campo ou array com todos os dados do usuário.

<!-- c2f:extract:end -->
