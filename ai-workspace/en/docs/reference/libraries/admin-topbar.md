---
title: "admin-topbar.php library"
description: "Administrative admin-topbar behavior, persistence and permissions."
section: reference
order: 100
sources:
  - gestor/bibliotecas/admin-topbar.php
verified_at: 914c7b10
---

# admin-topbar.php library


The header uses the server-authenticated user. Favorites live in usuarios_topbar_favoritos; width lives in usuarios_preferencias under admin_content_width. Missing columns cause reads to use defaults and writes to report unavailability.

- admin_topbar_escape escapes UTF-8 text/attributes, substituting invalid characters.
- admin_topbar_catalogo collects active pages in the current language, filtering module/action permissions and safe paths; it excludes mutation triggers and dynamic records.
- admin_topbar_caminho_seguro rejects empty paths, traversal, URLs, control characters and markers.
- admin_topbar_favoritos reads only the current user's favorites and retains authorized catalog items.
- admin_topbar_largura_conteudo accepts normal, expanded and full; default normal.
- admin_topbar_renderizar returns empty without a user, fills the component, injects escaped JSON state and queues admin-topbar.js.
- admin_topbar_ajax starts interface security, requires session and POST, and adds/removes/reorders favorites or saves width. Reordering must contain exactly the current favorites without duplicates. User identity never comes from the client; 400/401/403/405/503 and 500 distinguish input, session, permission, method, schema and write errors.

See [Administrative interface](../../concepts/admin-interface.md).

## Generated functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/admin-topbar.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `admin_topbar_escape($valor)` — [line 4](../../../../../gestor/bibliotecas/admin-topbar.php#L4)
  REQ-228: cabeçalho administrativo. Nenhum identificador de usuário vem do cliente.
- `admin_topbar_catalogo()` — [line 8](../../../../../gestor/bibliotecas/admin-topbar.php#L8)
- `admin_topbar_caminho_seguro($caminho)` — [line 38](../../../../../gestor/bibliotecas/admin-topbar.php#L38)
- `admin_topbar_favoritos($catalogo)` — [line 43](../../../../../gestor/bibliotecas/admin-topbar.php#L43)
- `admin_topbar_largura_conteudo($usuarioId)` — [line 55](../../../../../gestor/bibliotecas/admin-topbar.php#L55)
- `admin_topbar_renderizar()` — [line 64](../../../../../gestor/bibliotecas/admin-topbar.php#L64)
- `admin_topbar_ajax()` — [line 92](../../../../../gestor/bibliotecas/admin-topbar.php#L92)

<!-- c2f:extract:end -->
