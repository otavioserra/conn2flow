---
title: "deploy-lock.php library"
label: "Deploy lock"
description: "Atomic per-environment lock that prevents two deploys at the same time (req-197)."
section: reference
order: 350
sources:
  - gestor/bibliotecas/deploy-lock.php
verified_at: eb96c5c7
---

# `deploy-lock.php` library

Per-environment deploy lock (req-197). A lock file prevents two deploys at the same time in the same environment. Users:
- the system update (`temp/deploy.lock`, CLI and web);
- the API project deploy (same file; refuses with HTTP 409);
- the `c2f project:update-all` pipeline (per-target lock in `GIT/.c2f-deploy-locks/`, with waiting).

The library is **pure**: it does not depend on the Gestor, because it runs in the standalone updater, the API and the CLI. Load it with `require_once`.

- **Atomic creation** (`fopen` with `x`): only one process creates the file.
- **Lock content:** owner, detail, execution, host, PID, start and expiry (`expires_at`).
- **Expired lock** (a process that died without releasing): it is moved out of the way with an atomic `rename` and taken over. The new owner receives `stale` to log it.
- **Release and refresh:** only the `token` owner releases it; `deploy_lock_refresh()` extends the expiry for long runs.

## Usage

- `deploy_lock_acquire($arquivo, $dono, $ttl)` tries to take the lock. It returns `ok` and the `token`, or `busy` with whoever holds it (`holder`). Creates the folder when missing.
- `deploy_lock_release($arquivo, $token)` releases it, only with the right token; `deploy_lock_refresh($arquivo, $token, $ttl)` extends the expiry.
- `deploy_lock_read($arquivo)` reads the current lock; `deploy_lock_expired($trava)` tells whether it expired (an unreadable lock counts as expired).
- `deploy_lock_describe($trava)` builds the refusal text: owner, detail, execution, host, PID, start and expiry.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/deploy-lock.php` by `c2f docs:extract` — 6 functions. Do not edit inside this block.

- `deploy_lock_read(string $arquivo): ?array` — [line 24](../../../../../gestor/bibliotecas/deploy-lock.php#L24)
  Lê a trava atual.
  Returns: Conteúdo da trava ou null quando não existe ou está ilegível.
- `deploy_lock_expired(?array $trava, ?int $agora = null): bool` — [line 31](../../../../../gestor/bibliotecas/deploy-lock.php#L31)
  A trava está vencida? (sem validade legível conta como vencida)
- `deploy_lock_acquire(string $arquivo, array $dono, int $ttl = DEPLOY_LOCK_TTL): array` — [line 46](../../../../../gestor/bibliotecas/deploy-lock.php#L46)
  Tenta pegar a trava.
  Parameters:
  - `$arquivo`: Caminho do arquivo de trava (a pasta é criada se faltar).
  - `$dono`: ['owner' => quem (ex.: 'update-system', 'api-project-update', 'pipeline'),
  - `$ttl`: Validade em segundos.
  Returns: ['ok' => true, 'token' => string, 'lock' => array, 'stale' => array|null]
- `deploy_lock_release(string $arquivo, string $token): bool` — [line 83](../../../../../gestor/bibliotecas/deploy-lock.php#L83)
  Libera a trava, só se o token for o dela.
- `deploy_lock_refresh(string $arquivo, string $token, int $ttl = DEPLOY_LOCK_TTL): bool` — [line 90](../../../../../gestor/bibliotecas/deploy-lock.php#L90)
  Estende a validade (execuções longas).
- `deploy_lock_describe(?array $trava): string` — [line 98](../../../../../gestor/bibliotecas/deploy-lock.php#L98)
  Texto curto de quem está com a trava (mensagens de recusa).

<!-- c2f:extract:end -->
