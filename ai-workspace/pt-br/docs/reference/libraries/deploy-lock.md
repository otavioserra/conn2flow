---
title: "Biblioteca deploy-lock.php"
label: "Trava de deploy"
description: "Trava atômica por ambiente que impede dois deploys ao mesmo tempo (req-197)."
section: reference
order: 350
sources:
  - gestor/bibliotecas/deploy-lock.php
verified_at: eb96c5c7
---

# Biblioteca `deploy-lock.php`

Trava de deploy por ambiente (req-197). Um arquivo de trava impede dois deploys ao mesmo tempo no mesmo ambiente. Quem usa:
- a atualização do sistema (`temp/deploy.lock`, no CLI e no web);
- o deploy de projeto por API (mesmo arquivo; recusa com HTTP 409);
- o pipeline `c2f project:update-all` (trava por destino em `GIT/.c2f-deploy-locks/`, com espera).

A biblioteca é **pura**: não depende do Gestor, porque roda no atualizador independente, na API e no CLI. Para usar, carregue com `require_once`.

- **Criação atômica** (`fopen` com `x`): só um processo cria o arquivo.
- **Conteúdo da trava:** dono, detalhe, execução, máquina, PID, início e validade (`expires_at`).
- **Trava vencida** (processo que morreu sem liberar): é tirada do caminho por `rename` atômico e assumida. Quem assumiu recebe `stale` para registrar no log.
- **Liberação e renovação:** só o dono do `token` libera; `deploy_lock_refresh()` estende a validade em execuções longas.

## Uso

- `deploy_lock_acquire($arquivo, $dono, $ttl)` tenta pegar a trava. Devolve `ok` e o `token`, ou `busy` com quem está com ela (`holder`). Cria a pasta se faltar.
- `deploy_lock_release($arquivo, $token)` libera, só com o token certo; `deploy_lock_refresh($arquivo, $token, $ttl)` estende a validade.
- `deploy_lock_read($arquivo)` lê a trava atual; `deploy_lock_expired($trava)` diz se venceu (trava ilegível conta como vencida).
- `deploy_lock_describe($trava)` monta o texto de recusa: dono, detalhe, execução, máquina, PID, início e validade.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/deploy-lock.php` por `c2f docs:extract` — 6 funções. Não edite dentro deste bloco.

- `deploy_lock_read(string $arquivo): ?array` — [linha 24](../../../../../gestor/bibliotecas/deploy-lock.php#L24)
  Lê a trava atual.
  Retorno: Conteúdo da trava ou null quando não existe ou está ilegível.
- `deploy_lock_expired(?array $trava, ?int $agora = null): bool` — [linha 31](../../../../../gestor/bibliotecas/deploy-lock.php#L31)
  A trava está vencida? (sem validade legível conta como vencida)
- `deploy_lock_acquire(string $arquivo, array $dono, int $ttl = DEPLOY_LOCK_TTL): array` — [linha 46](../../../../../gestor/bibliotecas/deploy-lock.php#L46)
  Tenta pegar a trava.
  Parâmetros:
  - `$arquivo`: Caminho do arquivo de trava (a pasta é criada se faltar).
  - `$dono`: ['owner' => quem (ex.: 'update-system', 'api-project-update', 'pipeline'),
  - `$ttl`: Validade em segundos.
  Retorno: ['ok' => true, 'token' => string, 'lock' => array, 'stale' => array|null]
- `deploy_lock_release(string $arquivo, string $token): bool` — [linha 83](../../../../../gestor/bibliotecas/deploy-lock.php#L83)
  Libera a trava, só se o token for o dela.
- `deploy_lock_refresh(string $arquivo, string $token, int $ttl = DEPLOY_LOCK_TTL): bool` — [linha 90](../../../../../gestor/bibliotecas/deploy-lock.php#L90)
  Estende a validade (execuções longas).
- `deploy_lock_describe(?array $trava): string` — [linha 98](../../../../../gestor/bibliotecas/deploy-lock.php#L98)
  Texto curto de quem está com a trava (mensagens de recusa).

<!-- c2f:extract:end -->
