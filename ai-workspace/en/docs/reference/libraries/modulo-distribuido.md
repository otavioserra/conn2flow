---
title: "modulo-distribuido.php library"
label: "Distributed modules"
description: "Central/distributed architecture: a module runs on the central Conn2Flow and writes its data to the client installation's database over an HMAC channel, with login and permission decided by the central."
section: reference
order: 340
sources:
  - gestor/bibliotecas/modulo-distribuido.php
  - gestor/controladores/api/api-module-distributed.php
  - gestor/controladores/api/api-module-central.php
  - gestor/config.php
verified_at: 900cbe77
---

# `modulo-distribuido.php` library

It lets a module have two halves:
- **central** (`"scope": "central-module"` in `<module>.json`): runs on the central installation (the vendor's), where the code, users and permissions live;
- **distributed** (`"scope": "distributed-module"`): runs on the client's site and keeps the **data** in the client's database.

The central module code keeps using `banco_select()`, `banco_insert()`… as usual; inside the distributed channel, `banco_query()` packs the SQL and runs it on the client installation. The client sees the module as an embedded screen (iframe) of the central, unlocked only after the central confirms login and permission.

## Configuration

In the `.env` ([global variables](../../concepts/global-variables.md)): `MODULO_DISTRIBUIDO_SECRET` (shared HMAC secret), `MODULO_DISTRIBUIDO_CENTRAL_URL` (on the distributed side: the central URL) and `MODULO_DISTRIBUIDO_ENDPOINT` (on the central side: the distributed API URL). Per module, `$_CONFIG['modulo-distribuido']['secrets'][<slug>]` and `['endpoints'][<slug>]` override the general values (`modulo_distribuido_config_get('modulo-distribuido.secrets.<slug>')`).

`modulo_distribuido_scope($manifest)`, `modulo_distribuido_scope_central()`, `modulo_distribuido_scope_distribuido()` and `modulo_distribuido_resolver_manifesto()` read the scope from the manifest (constants `C2F_SCOPE_CENTRAL` and `C2F_SCOPE_DISTRIBUIDO`).

## The data channel

```php
// In the central module, wrapping only the client's data operations:
// the central module manifest declares "distributed": {"target-slug": "my-module"}
$config = modulo_distribuido_canal_central($_GESTOR['modulo#'.$_GESTOR['modulo-id']]);
$orders = modulo_distribuido_com_canal(function () {
    return banco_select(['tabela' => 'pedidos', 'campos' => ['id', 'total']]);
}, $config);
// or by hand: banco_distribuido_iniciar($config); … banco_distribuido_finalizar();
```

1. `banco_distribuido_ativo()` makes `banco_query()` divert to `banco_distribuido_query()`.
2. `modulo_distribuido_montar_payload()` packs the SQL (`operacao`, `modulo`, `linguagem`, `timestamp`, `nonce`), and `modulo_distribuido_enviar()` sends it through `modulo_distribuido_http_post()` to `<endpoint>/modulo-distribuido/<slug>/db`, with `X-C2F-Signature` = HMAC-SHA256 of the body (`modulo_distribuido_assinar()`).
3. On the distributed side, the API checks the signature (`modulo_distribuido_verificar_assinatura()`, constant time) and `modulo_distribuido_executar_local()` runs the SQL through PDO: a `SELECT` returns columns and rows; a write returns `affected_rows` and `insert_id`.
4. `modulo_distribuido_resposta_para_resultado()` turns the response into a `BancoResultadoRemoto`, which mimics `mysqli_result` for the [banco.php](banco.md) functions.

Authentication, variables and the rest of the request stay in the central's local database: only what is inside the channel goes to the client. `modulo_distribuido_canal_central($manifest, $overrides)` builds the central-side configuration from the manifest's `distributed.target-slug` (and, optionally, `endpoint-config`/`secret-config`, key paths in `$_CONFIG`); `modulo_distribuido_canal_distribuido()` does the same on the distributed side, to talk to the central; `modulo_distribuido_detectar_operacao()` and `modulo_distribuido_operacao_leitura()` classify the SQL; `modulo_distribuido_parse_rota()` reads `{slug}/{action}` from the URL.

> [!WARNING]
> The distributed side accepts **any single-statement SQL** (`modulo_distribuido_sql_segura()` only rejects `;` outside strings), on any table, and does not check the `timestamp` or the `nonce`: a signed request can be replayed. Treat the secret as an administrator password to the client's database (req-181, item A11).

## Login, permission and screen

The distributed side does not decide access: it asks the central.
- `modulo_distribuido_signin()` sends username and password to the central and gets the tokens (the central's OAuth2); `modulo_distribuido_token_sessao()` and `modulo_distribuido_persistir_token()` keep the token in the session.
- `modulo_distribuido_guardiao()` (distributed side) asks the central, where `modulo_distribuido_middleware_central()` validates the token and the user's permission on the module and answers `nao-autenticado`, `sem-permissao` or authorized. Any failure resolves to the most restrictive state.
- `modulo_distribuido_estado_renderizacao()` translates that into `login`, `sem-permissao` or `iframe`; `modulo_distribuido_montar_url_iframe()` builds the URL of the central screen.
- `modulo_distribuido_app()` does it all in one call: token, guardian and the state screen, replacing `#modulo-distribuido-app#` on the page with the `modulo-distribuido-app` component (`modulo_distribuido_render_estado()`, `modulo_distribuido_render_componente()`, `modulo_distribuido_textos()`).

The variants `modulo_distribuido_estado_por_token_ativo()`, `modulo_distribuido_estado_por_permissao_central()` and `modulo_distribuido_middleware_permissao()` are the pure (testable) pieces of this flow.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/modulo-distribuido.php` by `c2f docs:extract` — 37 functions. Do not edit inside this block.

- `modulo_distribuido_scope(array|string $modulo, string|null $slug = null): string|null` — [line 140](../../../../../gestor/bibliotecas/modulo-distribuido.php#L140)
- `modulo_distribuido_scope_central($scope): bool` — [line 151](../../../../../gestor/bibliotecas/modulo-distribuido.php#L151)
- `modulo_distribuido_scope_distribuido($scope): bool` — [line 158](../../../../../gestor/bibliotecas/modulo-distribuido.php#L158)
- `modulo_distribuido_resolver_manifesto(array|string $modulo, string|null $slug = null): array|null` — [line 170](../../../../../gestor/bibliotecas/modulo-distribuido.php#L170)
- `modulo_distribuido_assinar(string $corpo, string $secret, string $algo = 'sha256'): string` — [line 209](../../../../../gestor/bibliotecas/modulo-distribuido.php#L209)
- `modulo_distribuido_verificar_assinatura(string $corpo, string $assinatura, string $secret, string $algo = 'sha256'): bool` — [line 223](../../../../../gestor/bibliotecas/modulo-distribuido.php#L223)
- `modulo_distribuido_detectar_operacao(string $sql): string` — [line 240](../../../../../gestor/bibliotecas/modulo-distribuido.php#L240)
- `modulo_distribuido_operacao_leitura($operacao): bool` — [line 254](../../../../../gestor/bibliotecas/modulo-distribuido.php#L254)
- `modulo_distribuido_montar_payload(string $sql, array $opcoes = []): array` — [line 266](../../../../../gestor/bibliotecas/modulo-distribuido.php#L266)
- `modulo_distribuido_enviar(array $payload, array $config): array` — [line 297](../../../../../gestor/bibliotecas/modulo-distribuido.php#L297)
- `modulo_distribuido_http_post($url, $corpo, array $headers, $timeout = 15): string|false` — [line 344](../../../../../gestor/bibliotecas/modulo-distribuido.php#L344)
- `modulo_distribuido_resposta_para_resultado(array $resposta): BancoResultadoRemoto|bool` — [line 375](../../../../../gestor/bibliotecas/modulo-distribuido.php#L375)
- `modulo_distribuido_executar_local(array $payload, PDO $pdo): array` — [line 413](../../../../../gestor/bibliotecas/modulo-distribuido.php#L413)
- `modulo_distribuido_sql_segura(string $sql): bool` — [line 475](../../../../../gestor/bibliotecas/modulo-distribuido.php#L475)
- `modulo_distribuido_parse_rota(array $caminho): array|null` — [line 497](../../../../../gestor/bibliotecas/modulo-distribuido.php#L497)
- `modulo_distribuido_estado_por_token_ativo(bool $token_ativo): string` — [line 548](../../../../../gestor/bibliotecas/modulo-distribuido.php#L548)
- `modulo_distribuido_estado_renderizacao(bool $token_ativo, bool $tem_permissao): string` — [line 567](../../../../../gestor/bibliotecas/modulo-distribuido.php#L567)
- `modulo_distribuido_estado_por_permissao_central(string $estado_central): string` — [line 585](../../../../../gestor/bibliotecas/modulo-distribuido.php#L585)
- `modulo_distribuido_middleware_permissao(array $config, string $token, string|null $slug = null): array` — [line 609](../../../../../gestor/bibliotecas/modulo-distribuido.php#L609)
- `modulo_distribuido_guardiao(array $config, array $opcoes = []): array` — [line 658](../../../../../gestor/bibliotecas/modulo-distribuido.php#L658)
- `modulo_distribuido_signin(array $config, string $usuario, string $senha): array|false` — [line 697](../../../../../gestor/bibliotecas/modulo-distribuido.php#L697)
- `modulo_distribuido_middleware_central(string $token, string $slug, array $opcoes = []): array` — [line 737](../../../../../gestor/bibliotecas/modulo-distribuido.php#L737)
- `modulo_distribuido_montar_url_iframe(string $endpoint_central, string $slug, array $opcoes = []): string` — [line 777](../../../../../gestor/bibliotecas/modulo-distribuido.php#L777)
- `banco_distribuido_iniciar(array $config): void` — [line 809](../../../../../gestor/bibliotecas/modulo-distribuido.php#L809)
- `banco_distribuido_finalizar(): void` — [line 819](../../../../../gestor/bibliotecas/modulo-distribuido.php#L819)
- `banco_distribuido_ativo(): bool` — [line 829](../../../../../gestor/bibliotecas/modulo-distribuido.php#L829)
- `banco_distribuido_query(string $sql): BancoResultadoRemoto|bool` — [line 845](../../../../../gestor/bibliotecas/modulo-distribuido.php#L845)
- `modulo_distribuido_config_get(string $path, mixed $default = null): mixed` — [line 875](../../../../../gestor/bibliotecas/modulo-distribuido.php#L875)
- `modulo_distribuido_canal_central(array $modulo_config, array $overrides = []): array` — [line 904](../../../../../gestor/bibliotecas/modulo-distribuido.php#L904)
- `modulo_distribuido_canal_distribuido(array $modulo_config, array $overrides = []): array` — [line 944](../../../../../gestor/bibliotecas/modulo-distribuido.php#L944)
- `modulo_distribuido_com_canal(callable $operacao, array $config): mixed` — [line 977](../../../../../gestor/bibliotecas/modulo-distribuido.php#L977)
- `modulo_distribuido_token_sessao(string $chave): string` — [line 993](../../../../../gestor/bibliotecas/modulo-distribuido.php#L993)
- `modulo_distribuido_persistir_token(string $chave, array $tokens, callable|null $persistir = null): void` — [line 1015](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1015)
- `modulo_distribuido_textos(string|null $lang = null, array $overrides = []): array` — [line 1041](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1041)
- `modulo_distribuido_render_componente(string $html, string $estado, string|null $iframe_url = null, array $textos = []): string` — [line 1096](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1096)
- `modulo_distribuido_render_estado(string $estado, string|null $iframe_url = null): string` — [line 1140](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1140)
- `modulo_distribuido_app(array $config, array $opcoes = []): array` — [line 1175](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1175)

<!-- c2f:extract:end -->
