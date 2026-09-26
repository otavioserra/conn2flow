---
title: "Biblioteca ip.php"
label: "IP do visitante"
description: "Descobrir o IP do visitante (ip_get) e validar um endereço (ip_check), com o comportamento atrás de proxy reverso e de CDN."
section: reference
order: 200
sources:
  - gestor/bibliotecas/ip.php
verified_at: c267f123
---

# Biblioteca `ip.php`

Duas funções: `ip_get()` descobre o IP do visitante e `ip_check()` valida um endereço. O IP devolvido alimenta os limites por IP do login, do cadastro, dos formulários e do OAuth2 (`acessos-*`, `formularios-*` em [variáveis globais](../../concepts/global-variables.md)).

Não vem carregada: peça `ip` no JSON do módulo ou use `gestor_incluir_biblioteca('ip')`. As bibliotecas `autenticacao`, `usuario`, `formulario` e `oauth2` já a usam.

## `ip_get($allow_private = false)`

Na ordem:

1. Com `SERVER_NAME` igual a `localhost`, devolve `REMOTE_ADDR` direto (ambiente local).
2. Se `REMOTE_ADDR` é um IP **público** válido, devolve-o. Cabeçalhos de proxy são ignorados.
3. Senão (a requisição veio de um proxy na rede privada, como um Nginx ou um contêiner), percorre `X-Forwarded-For` **da direita para a esquerda** e devolve o primeiro IP público válido.
4. Nada encontrado: `null`.

Ler a cadeia pela direita impede que o visitante forje o IP escrevendo um `X-Forwarded-For` próprio: o valor da direita é o que o seu proxy acrescentou.

> [!WARNING]
> **Atrás de uma CDN** (Cloudflare, por exemplo) o `REMOTE_ADDR` é o IP público da borda da CDN, então o passo 2 devolve **o IP da CDN**, e os limites por IP passam a valer para todos os visitantes que chegam pela mesma borda. O `CF-Connecting-IP` não é lido. Configure o servidor web para restaurar o IP real no `REMOTE_ADDR` (no Nginx, `real_ip_header CF-Connecting-IP` com `set_real_ip_from` para as faixas da Cloudflare; no Apache, `mod_remoteip`).

`127.0.0.1` está na lista de proxies confiáveis e nunca é devolvido pelos passos 2 e 3. A lista e o cabeçalho (`HTTP_X_FORWARDED_FOR`) são fixos no código.

## `ip_check($ip, $allow_private = false, $proxy_ip = [])`

`true` se `$ip` for um IPv4 ou IPv6 válido **fora** das faixas reservadas (inclusive loopback) e, sem `$allow_private`, fora das faixas privadas (`10/8`, `172.16/12`, `192.168/16`, `fc00::/7`). Um IP presente em `$proxy_ip` é sempre `false`.

> [!NOTE]
> O teste explícito de loopback no código (`/^127\.$/`) nunca casa com um IP real; quem bloqueia `127.x.x.x` é o `FILTER_FLAG_NO_RES_RANGE` do PHP. O efeito final é o documentado acima.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/ip.php` por `c2f docs:extract` — 2 funções. Não edite dentro deste bloco.

- `ip_check(string $ip, bool $allow_private = false, array $proxy_ip = []): bool` — [linha 36](../../../../../gestor/bibliotecas/ip.php#L36)
- `ip_get(bool $allow_private = false): string|null` — [linha 67](../../../../../gestor/bibliotecas/ip.php#L67)

<!-- c2f:extract:end -->
