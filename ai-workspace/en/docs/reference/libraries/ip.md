---
title: "ip.php library"
label: "Visitor IP"
description: "Finding the visitor's IP (ip_get) and validating an address (ip_check), including the behavior behind a reverse proxy and a CDN."
section: reference
order: 200
sources:
  - gestor/bibliotecas/ip.php
verified_at: c267f123
---

# `ip.php` library

Two functions: `ip_get()` finds the visitor's IP and `ip_check()` validates an address. The returned IP feeds the per-IP limits of login, sign-up, forms and OAuth2 (`acessos-*`, `formularios-*` in [global variables](../../concepts/global-variables.md)).

It is not loaded by default: request `ip` in the module JSON or use `gestor_incluir_biblioteca('ip')`. The `autenticacao`, `usuario`, `formulario` and `oauth2` libraries already use it.

## `ip_get($allow_private = false)`

In order:

1. With `SERVER_NAME` equal to `localhost`, it returns `REMOTE_ADDR` directly (local environment).
2. If `REMOTE_ADDR` is a valid **public** IP, it returns it. Proxy headers are ignored.
3. Otherwise (the request came from a proxy on the private network, such as Nginx or a container), it walks `X-Forwarded-For` **from right to left** and returns the first valid public IP.
4. Nothing found: `null`.

Reading the chain from the right prevents the visitor from forging the IP by sending their own `X-Forwarded-For`: the rightmost value is the one your proxy appended.

> [!WARNING]
> **Behind a CDN** (Cloudflare, for example) `REMOTE_ADDR` is the CDN edge's public IP, so step 2 returns **the CDN's IP**, and the per-IP limits then apply to every visitor coming through the same edge. `CF-Connecting-IP` is not read. Configure the web server to restore the real IP into `REMOTE_ADDR` (on Nginx, `real_ip_header CF-Connecting-IP` with `set_real_ip_from` for the Cloudflare ranges; on Apache, `mod_remoteip`).

`127.0.0.1` is in the trusted proxy list and is never returned by steps 2 and 3. The list and the header (`HTTP_X_FORWARDED_FOR`) are hard-coded.

## `ip_check($ip, $allow_private = false, $proxy_ip = [])`

`true` if `$ip` is a valid IPv4 or IPv6 address **outside** the reserved ranges (loopback included) and, without `$allow_private`, outside the private ranges (`10/8`, `172.16/12`, `192.168/16`, `fc00::/7`). An IP present in `$proxy_ip` is always `false`.

> [!NOTE]
> The explicit loopback test in the code (`/^127\.$/`) never matches a real IP; what blocks `127.x.x.x` is PHP's `FILTER_FLAG_NO_RES_RANGE`. The end result is the one documented above.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/ip.php` by `c2f docs:extract` — 2 functions. Do not edit inside this block.

- `ip_check(string $ip, bool $allow_private = false, array $proxy_ip = []): bool` — [line 36](../../../../../gestor/bibliotecas/ip.php#L36)
  Valida um endereço IP.
  Parameters:
  - `$ip`: O endereço IP a ser validado.
  - `$allow_private`: Se true, permite IPs de redes privadas como válidos. Padrão: false.
  - `$proxy_ip`: Array de IPs de proxy confiáveis que devem ser excluídos da validação.
  Returns: Retorna true se o IP for válido, false caso contrário.
- `ip_get(bool $allow_private = false): string|null` — [line 67](../../../../../gestor/bibliotecas/ip.php#L67)
  Obtém o endereço IP real do cliente.
  Parameters:
  - `$allow_private`: Se true, permite IPs privados como válidos. Padrão: false.
  Returns: Retorna o IP do cliente ou null se nenhum IP válido for encontrado.

<!-- c2f:extract:end -->
