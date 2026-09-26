---
title: "host.php library"
description: "Legacy of the multi-host mode: host_url(), host_pub_id() and host_loja_nome() query tables that current installations do not have."
section: reference
order: 330
sources:
  - gestor/bibliotecas/host.php
  - gestor/bibliotecas/interface.php
verified_at: 8768245a
---

# `host.php` library

A leftover of the time when one Gestor served several stores (hosts) in the same database. The three functions read the `hosts` and `hosts_variaveis` tables and use `$_GESTOR['host-id']` as the default host.

> [!WARNING]
> On current installations, **no migration creates `hosts`, `hosts_variaveis` or `hosts_arquivos`**, and nothing sets `$_GESTOR['host-id']`. Without an explicit `id_hosts`, the functions return `false`; with one, the query fails on a missing table. Do not use this library in new code.

| Function | Returns |
|---|---|
| `host_url(['opcao' => 'full', 'id_hosts' => …])` | `https://<domain>/` with `opcao=full`, otherwise just the domain |
| `host_pub_id(['id_hosts' => …])` | the host's `pub_id` column |
| `host_loja_nome(['id_hosts' => …])` | the `nome` variable of the `loja-configuracoes` module, or `Minha Loja <id>` |

Defects, for whoever maintains legacy code:

- The result is cached in the `$_HOST` global **regardless of `id_hosts`**: a second call with another host returns the first one's value.
- `host_pub_id()` ignores the `id_hosts` it receives in the query and always uses `$_GESTOR['host-id']`.
- `host_url()` without `opcao` raises an undefined-variable warning.
- `id_hosts` goes into the SQL unescaped.

The only caller in the core is `interface.php`, in the image fields that look for thumbnails in `hosts_arquivos`, a branch that also depends on the missing tables.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/host.php` by `c2f docs:extract` — 3 functions. Do not edit inside this block.

- `host_url(array|false $params = false): string|false` — [line 38](../../../../../gestor/bibliotecas/host.php#L38)
- `host_pub_id(array|false $params = false): string|false` — [line 98](../../../../../gestor/bibliotecas/host.php#L98)
- `host_loja_nome(array|false $params = false): string|false` — [line 148](../../../../../gestor/bibliotecas/host.php#L148)

<!-- c2f:extract:end -->
